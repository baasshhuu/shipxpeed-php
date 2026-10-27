<?php

namespace App\Services\Notify;

use App\Models\Order;
use App\Models\Recharge;
use App\Models\SellerList;
use App\Services\WhatsApp\OrderStatusNotifier;
use App\Support\CourierBrand;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Buyer Email (and, once a gateway is connected, SMS) on shipment status changes.
 * Same status detection as the WhatsApp alerts. One message per stage per order
 * (delivery-failed once per day). The seller is charged only after the email is sent.
 */
class BuyerNotifier
{
    /** Called by the Order observer after an order is saved. */
    public function handleOrderChange(Order $order): void
    {
        $events = [];
        if ($order->wasChanged('order_status') && OrderStatusNotifier::eventFor($order->order_status) === 'cancelled') {
            $events[] = 'cancelled';
        }
        if ($order->wasChanged('shipping_status') && ($e = OrderStatusNotifier::eventFor($order->shipping_status))) {
            $events[] = $e;
        }
        if ($order->wasChanged('awb_number') && filled($order->awb_number) && blank($order->getOriginal('awb_number'))) {
            $events[] = 'booked';
        }
        foreach (array_unique($events) as $event) {
            $this->notify($order, $event);
        }
    }

    public function notify(Order $order, string $event): void
    {
        try {
            if (!NotifySettings::ready() || !isset(NotifySettings::STAGES[$event])) {
                return;
            }
            $s = NotifySettings::for((int) $order->seller_id);
            if (in_array($event, $s['email'], true)) {
                $this->sendEmail($order, $event, $s);
            }
            // SMS: choices are saved; nothing is sent until an SMS gateway is connected.
        } catch (\Throwable $e) {
            Log::error('BuyerNotifier failed', ['order' => $order->id, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }

    private function sendEmail(Order $order, string $event, array $s): void
    {
        $seller = SellerList::find($order->seller_id);
        if (!$seller) {
            return;
        }
        $dedupe = 'email:' . $order->id . ':' . $event . ($event === 'ndr' ? ':' . now()->format('Ymd') : '');
        if (DB::table('buyer_notifications')->where('dedupe_key', $dedupe)->exists()) {
            return;
        }

        $consignee = self::arr($order->consignee);
        $to = trim((string) ($consignee['email'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->log($order, $event, $dedupe, 'skipped_no_email', 'Buyer email missing or invalid', $to ?: null);
            return;
        }

        $rate = max(0, (float) config('notify.email_rate', 0.5));
        if ($rate > 0 && (string) ($seller->negative_balance ?? '') !== '1' && OrderStatusNotifier::walletBalance($seller->id) < $rate) {
            $this->log($order, $event, $dedupe, 'skipped_low_balance', "Wallet below ₹{$rate}", $to);
            return;
        }

        // reserve the slot first so two status syncs can't both send
        try {
            $id = DB::table('buyer_notifications')->insertGetId([
                'seller_id' => $order->seller_id, 'order_id' => $order->id, 'awb_number' => $order->awb_number,
                'channel' => 'email', 'event' => $event, 'recipient' => $to, 'dedupe_key' => $dedupe,
                'status' => 'sending', 'charge' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            return; // already being sent
        }

        $mail = self::email($seller, $order, $event, $s);
        try {
            Mail::html($mail['html'], function ($m) use ($to, $consignee, $mail) {
                $m->to($to, $consignee['name'] ?? null)->subject($mail['subject']);
                if ($from = config('mail.from.address')) {
                    $m->from($from, $mail['from_name']);
                }
            });
        } catch (\Throwable $e) {
            DB::table('buyer_notifications')->where('id', $id)->update(['status' => 'failed', 'reason' => mb_substr($e->getMessage(), 0, 250), 'updated_at' => now()]);
            return;
        }

        if ($rate > 0) {
            Recharge::create([
                'seller_id' => $order->seller_id, 'type' => 'Debit', 'amount' => $rate, 'status' => 1,
                'description' => 'Buyer email (' . NotifySettings::STAGES[$event][0] . ') for order ' . ($order->customer_order_id ?: $order->order_number) . ' / AWB ' . $order->awb_number,
            ]);
        }
        DB::table('buyer_notifications')->where('id', $id)->update(['status' => 'sent', 'charge' => $rate, 'updated_at' => now()]);
    }

    /** Subject + HTML for a stage. Used for real sends, previews and test emails. */
    public static function email($seller, Order $order, string $event, array $s): array
    {
        [$label, $headline] = NotifySettings::STAGES[$event] ?? ['Update', 'Your order has an update'];
        $brand = OrderStatusNotifier::brandName($seller, $order);
        $consignee = self::arr($order->consignee);
        $orderNo = $order->customer_order_id ?: ($order->order_number ?: $order->id);
        $prepaid = strtolower((string) $order->payment_type) !== 'cod';

        $items = [];
        if (!$s['hide_product']) {
            foreach (self::arr($order->order_items) as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $items[] = [
                    'name' => (string) ($it['name'] ?? 'Item'),
                    'qty' => (int) ($it['quantity'] ?? $it['qty'] ?? 1),
                    'price' => isset($it['price']) ? (float) $it['price'] : null,
                    'image' => filter_var($it['image'] ?? '', FILTER_VALIDATE_URL) ?: null,
                ];
            }
        }
        $showPrice = !($s['hide_price_prepaid'] && $prepaid);

        $html = view('emails.buyer-status', [
            'event' => $event, 'label' => $label, 'headline' => $headline, 'brand' => $brand,
            'name' => $consignee['name'] ?? 'there', 'orderNo' => $orderNo, 'awb' => $order->awb_number,
            'courier' => self::courier($order, ''),
            'trackUrl' => OrderStatusNotifier::trackingLink($order),
            'items' => $items, 'hideProduct' => $s['hide_product'], 'showPrice' => $showPrice,
            'amount' => $showPrice ? (float) $order->order_amount : null, 'cod' => !$prepaid,
            'collect' => !$prepaid ? (float) ($order->collectable_amount ?: $order->order_amount) : null,
        ])->render();

        return [
            'subject' => "{$label}: your {$brand} order #{$orderNo}",
            'from_name' => mb_substr($brand . ' via Shipxpeed', 0, 70),
            'html' => $html,
        ];
    }

    /** SMS text for a stage (preview now; sent once an SMS gateway is connected). */
    public static function sms($seller, Order $order, string $event): string
    {
        $brand = OrderStatusNotifier::brandName($seller, $order);
        $orderNo = $order->customer_order_id ?: ($order->order_number ?: $order->id);
        $courier = self::courier($order, 'our courier');
        $link = OrderStatusNotifier::trackingLink($order);
        $line = match ($event) {
            'booked' => "is shipped with {$courier}, AWB {$order->awb_number}",
            'picked_up' => "has been picked up by {$courier}",
            'in_transit' => 'is in transit',
            'out_for_delivery' => 'is out for delivery today. Please keep your phone reachable',
            'ndr' => 'could not be delivered today. The courier will try again',
            'delivered' => 'has been delivered. Thank you for shopping with us',
            'rto_initiated' => 'is being returned to the seller',
            'cancelled' => 'has been cancelled',
            default => 'has an update',
        };
        return "{$brand}: Your order #{$orderNo} {$line}. Track: {$link} - Shipxpeed";
    }

    /** Latest real shipment of the seller, else a sample one (for previews). */
    public static function sampleOrder($seller): Order
    {
        $o = Order::where('seller_id', $seller->id)->whereNotNull('awb_number')->where('awb_number', '!=', '')->orderByDesc('id')->first();
        if ($o) {
            return $o;
        }
        $o = new Order();
        $o->forceFill([
            'seller_id' => $seller->id, 'order_number' => 'SPX1001', 'awb_number' => '1234567890123',
            'courier_name' => 'Delhivery Surface', 'payment_type' => 'prepaid', 'order_amount' => 1299, 'collectable_amount' => 0,
            'consignee' => ['name' => 'Ramesh', 'email' => 'ramesh@example.com', 'phone' => '9876543210'],
            'order_items' => [['name' => 'Cotton T-shirt (Blue, M)', 'quantity' => 1, 'price' => 1299]],
            'pickup' => ['name' => $seller->name ?? 'Your store'],
        ]);
        return $o;
    }

    private function log(Order $order, string $event, string $dedupe, string $status, string $reason, ?string $to): void
    {
        try {
            DB::table('buyer_notifications')->insert([
                'seller_id' => $order->seller_id, 'order_id' => $order->id, 'awb_number' => $order->awb_number,
                'channel' => 'email', 'event' => $event, 'recipient' => $to, 'dedupe_key' => $dedupe,
                'status' => $status, 'reason' => $reason, 'charge' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
        }
    }

    private static function courier(Order $order, string $fallback): string
    {
        $n = str_replace('_', ' ', (string) $order->courier_name);
        $n = trim(preg_replace(['/\bb2c\b/i', '/\s{2,}/'], ['', ' '], $n));
        return CourierBrand::cleanName($n !== '' ? ucwords($n) : null, $fallback);
    }

    private static function arr($v): array
    {
        return is_array($v) ? $v : (json_decode((string) $v, true) ?: []);
    }
}
