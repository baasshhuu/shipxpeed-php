<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use App\Models\Recharge;
use App\Models\SellerList;
use App\Support\CourierBrand;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sends the buyer a WhatsApp message when a shipment changes status.
 *
 * Runs only when: admin has alerts on + API key set, the seller has turned on the
 * WhatsApp alert service, the event is enabled, the buyer number is valid, and the
 * seller wallet covers the charge. Each event goes out once per order (NDR once per day).
 * The seller is charged only after AiSensy accepts the message.
 */
class OrderStatusNotifier
{
    public function __construct(private AiSensyClient $client)
    {
    }

    /** Map any courier / panel status string to one of our alert events (or null). */
    public static function eventFor(?string $raw): ?string
    {
        $s = strtolower(trim((string) $raw));
        if ($s === '') {
            return null;
        }
        $s = str_replace(['_', '-'], ' ', $s);

        return match (true) {
            str_contains($s, 'rto') && (str_contains($s, 'deliver') || str_contains($s, 'received') || str_contains($s, 'returned')) => 'rto_delivered',
            str_contains($s, 'rto') || str_contains($s, 'return to origin') || $s === 'returned' => 'rto_initiated',
            str_contains($s, 'cancel') => 'cancelled',
            str_contains($s, 'lost') || str_contains($s, 'damage') || str_contains($s, 'destroy') => 'lost',
            str_contains($s, 'out for delivery') || $s === 'ofd' => 'out_for_delivery',
            str_contains($s, 'ndr') || str_contains($s, 'undeliver') || str_contains($s, 'not delivered')
                || str_contains($s, 'delivery failed') || str_contains($s, 'failed delivery') || str_contains($s, 'delivery attempt') => 'ndr',
            str_contains($s, 'deliver') => 'delivered',
            str_contains($s, 'picked') || $s === 'pickup done' || $s === 'pickup completed' => 'picked_up',
            str_contains($s, 'transit') || str_contains($s, 'dispatch') || str_contains($s, 'shipped') || str_contains($s, 'reached') => 'in_transit',
            str_contains($s, 'manifest') || str_contains($s, 'assigned') || str_contains($s, 'pickup awaited')
                || str_contains($s, 'pickup scheduled') || str_contains($s, 'booked') || $s === 'order created' => 'booked',
            default => null,
        };
    }

    /**
     * Called by the Order observer after an order is saved.
     */
    public function handleOrderChange(Order $order): void
    {
        $events = [];

        if ($order->wasChanged('order_status') && self::eventFor($order->order_status) === 'cancelled') {
            $events[] = 'cancelled';
        }
        if ($order->wasChanged('shipping_status')) {
            $e = self::eventFor($order->shipping_status);
            if ($e) {
                $events[] = $e;
            }
        }
        if ($order->wasChanged('awb_number') && filled($order->awb_number) && blank($order->getOriginal('awb_number'))) {
            $events[] = 'booked';
        }

        foreach (array_unique($events) as $event) {
            $this->notify($order, $event);
        }
    }

    /**
     * @return array{status:string, reason?:string}
     */
    public function notify(Order $order, string $event, bool $force = false): array
    {
        try {
            if (!Schema::hasTable('whatsapp_messages')) {
                return ['status' => 'skipped_not_migrated'];
            }
            if (!WhatsAppSettings::alertsEnabled()) {
                return ['status' => 'skipped_disabled'];
            }

            $events = WhatsAppSettings::events();
            if (!isset($events[$event])) {
                return ['status' => 'skipped_unknown_event'];
            }

            // Couriers that jump straight to "in transit" never report "picked up":
            // the first transit scan is sent as the (free) picked-up message.
            if ($event === 'in_transit' && ($events['picked_up']['usable'] ?? false) && !$this->alreadySent($order, 'picked_up')) {
                $event = 'picked_up';
            }

            $seller = SellerList::find($order->seller_id);
            if (!$seller || !(bool) ($seller->whatsapp_alerts_active ?? false)) {
                return ['status' => 'skipped_seller_off']; // not logged: seller hasn't bought the service
            }

            $dedupe = $order->id . ':' . $event . ($event === 'ndr' ? ':' . now()->format('Ymd') : '');
            if (!$force && DB::table('whatsapp_messages')->where('dedupe_key', $dedupe)->exists()) {
                return ['status' => 'skipped_duplicate'];
            }

            $cfg = $events[$event];
            if (!$cfg['usable']) {
                // not set up in AiSensy yet / switched off: nothing sent, nothing charged, no log noise
                return ['status' => 'skipped_event_off'];
            }

            $consignee = is_array($order->consignee) ? $order->consignee : (json_decode((string) $order->consignee, true) ?: []);
            $phone = AiSensyClient::normalizePhone($consignee['phone'] ?? null)
                ?? AiSensyClient::normalizePhone($consignee['alternate_phone'] ?? null);
            if (!$phone) {
                return $this->log($order, $event, $dedupe, 'skipped_no_phone', 'Buyer mobile missing or invalid');
            }

            $rate = WhatsAppSettings::rateFor($seller, $event);
            if ($rate > 0 && (string) $seller->negative_balance !== '1' && self::walletBalance($seller->id) < $rate) {
                return $this->log($order, $event, $dedupe, 'skipped_low_balance', "Wallet below ₹{$rate}", $phone);
            }

            $orderNo = $order->customer_order_id ?: ($order->order_number ?: $order->id);
            if ($cfg['format'] === 'basic') {
                // older approved templates: {{1}} name {{2}} order no. {{3}} AWB
                $params = [$consignee['name'] ?? 'Customer', $orderNo, $order->awb_number ?: '-'];
            } else {
                $link = $event === 'ndr' ? $this->ndrLink($order) : self::trackingLink($order);
                $params = [
                    $consignee['name'] ?? 'Customer',
                    self::brandName($seller, $order),
                    $orderNo,
                    CourierBrand::cleanName($order->courier_name ?: ($order->zone_courier_name ?: $order->all_courier_name)),
                    $order->awb_number ?: '-',
                    $link,
                ];
            }

            $res = $this->client->send($cfg['campaign'], $phone, (string) ($consignee['name'] ?? 'Customer'), $params);

            if (!$res['ok']) {
                return $this->log($order, $event, $dedupe, 'failed', $res['error'], $phone, $cfg['campaign'], 0, null, $res);
            }

            $rechargeId = null;
            if ($rate > 0) {
                $rechargeId = Recharge::create([
                    'seller_id'   => $seller->id,
                    'type'        => 'Debit',
                    'amount'      => $rate,
                    'status'      => 1,
                    'description' => "WhatsApp alert ({$cfg['label']}) AWB " . ($order->awb_number ?: $order->order_number),
                ])->id;
            }

            return $this->log($order, $event, $dedupe, 'sent', null, $phone, $cfg['campaign'], $rate, $rechargeId, $res);
        } catch (\Throwable $e) {
            // Never break the status update that triggered this
            Log::error('WhatsApp order alert error', ['order' => $order->id ?? null, 'event' => $event, 'error' => $e->getMessage()]);
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    private function alreadySent(Order $order, string $event): bool
    {
        return DB::table('whatsapp_messages')->where('order_id', $order->id)->where('event', $event)->where('status', 'sent')->exists();
    }

    private function log(Order $order, string $event, ?string $dedupe, string $status, ?string $reason = null, ?string $phone = null,
                         ?string $campaign = null, float $charge = 0, ?int $rechargeId = null, ?array $res = null): array
    {
        $row = [
            'seller_id'   => $order->seller_id,
            'order_id'    => $order->id,
            'awb_number'  => $order->awb_number,
            'event'       => $event,
            // Failed / skipped rows must not block a later retry, so only "sent" claims the dedupe key
            'dedupe_key'  => $status === 'sent' ? $dedupe : null,
            'phone'       => $phone,
            'campaign'    => $campaign,
            'status'      => $status,
            'reason'      => $reason ? mb_strimwidth($reason, 0, 250, '…') : null,
            'charge'      => $charge,
            'recharge_id' => $rechargeId,
            'http_status' => $res['http'] ?? null,
            'response'    => $res ? mb_strimwidth(is_string($res['body']) ? $res['body'] : json_encode($res['body']), 0, 2000, '…') : null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        try {
            DB::table('whatsapp_messages')->insert($row);
        } catch (QueryException $e) {
            // two workers raced on the same status: keep the first
            $row['dedupe_key'] = null;
            $row['status'] = 'duplicate_race';
            DB::table('whatsapp_messages')->insert($row);
        }
        return ['status' => $status, 'reason' => $reason];
    }

    public static function walletBalance(int $sellerId): float
    {
        $credit = Recharge::where('seller_id', $sellerId)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerId)->where('type', 'Debit')->sum('amount');
        return (float) $credit - (float) $debit;
    }

    public static function brandName($seller, Order $order): string
    {
        if (filled($seller->whatsapp_brand_name ?? null)) {
            return $seller->whatsapp_brand_name;
        }
        $pickup = is_array($order->pickup) ? $order->pickup : (json_decode((string) $order->pickup, true) ?: []);
        // default: the seller's own name; warehouse name only if the account has none
        return filled($seller->name ?? null) ? trim($seller->name) : ($pickup['warehouse_name'] ?? $pickup['name'] ?? 'our store');
    }

    public static function trackingLink(Order $order): string
    {
        return config('whatsapp.public_url') . '/order-details?awb=' . urlencode((string) $order->awb_number);
    }

    /** One response link per NDR (reused for the day). */
    private function ndrLink(Order $order): string
    {
        $row = DB::table('ndr_responses')->where('order_id', $order->id)->where('status', 'awaiting_buyer')
            ->where('expires_at', '>', now())->orderByDesc('id')->first();
        $token = $row->token ?? null;
        if (!$token) {
            $token = Str::random(40);
            DB::table('ndr_responses')->insert([
                'order_id'   => $order->id,
                'seller_id'  => $order->seller_id,
                'awb_number' => $order->awb_number,
                'token'      => $token,
                'expires_at' => now()->addDays(config('whatsapp.ndr_link_days', 5)),
                'status'     => 'awaiting_buyer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return config('whatsapp.public_url') . '/ndr/' . $token;
    }
}
