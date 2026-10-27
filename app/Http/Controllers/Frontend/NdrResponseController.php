<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerList;
use App\Services\WhatsApp\AiSensyClient;
use App\Services\WhatsApp\NdrActionDispatcher;
use App\Services\WhatsApp\OrderStatusNotifier;
use App\Support\CourierBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Public page the buyer opens from the "delivery failed" WhatsApp message.
 * No login: access is by a 40-char random token that expires.
 */
class NdrResponseController extends Controller
{
    public function show(string $token)
    {
        [$row, $order] = $this->load($token);
        return view('frontend.ndr.respond', $this->viewData($row, $order));
    }

    public function store(Request $request, string $token, NdrActionDispatcher $dispatcher)
    {
        [$row, $order] = $this->load($token);
        if (!$row || !$order || $row->status !== 'awaiting_buyer' || Carbon::parse($row->expires_at)->isPast()) {
            return redirect()->route('ndr.respond', $token);
        }

        $minDate = now()->addDay()->toDateString();
        $maxDate = now()->addDays(4)->toDateString();
        $data = $request->validate([
            'action'         => 'required|in:re-attempt,change-address,change-phone,rto',
            'reattempt_date' => "nullable|date|after_or_equal:{$minDate}|before_or_equal:{$maxDate}",
            'address'        => 'required_if:action,change-address|nullable|string|min:10|max:300',
            'landmark'       => 'nullable|string|max:120',
            'phone'          => 'required_if:action,change-phone|nullable|string|max:15',
            'remark'         => 'nullable|string|max:200',
        ], [
            'address.required_if' => 'Please enter the full new address.',
            'phone.required_if'   => 'Please enter the new phone number.',
        ]);

        if (($data['action'] === 'change-phone') && !AiSensyClient::normalizePhone($data['phone'])) {
            return back()->withErrors(['phone' => 'Enter a valid 10-digit mobile number.'])->withInput();
        }
        $data['reattempt_date'] = $data['reattempt_date'] ?? ($data['action'] === 'rto' ? null : $minDate);
        $data['remark'] = trim('Buyer via WhatsApp: ' . ($data['remark'] ?? ''));

        // Claim the row first so a double tap can't send twice
        $claimed = DB::table('ndr_responses')->where('id', $row->id)->where('status', 'awaiting_buyer')
            ->update(['status' => 'processing', 'updated_at' => now()]);
        if (!$claimed) {
            return redirect()->route('ndr.respond', $token);
        }

        $phone10 = isset($data['phone']) ? substr((string) AiSensyClient::normalizePhone($data['phone']), 3) : null;
        $result = $dispatcher->dispatch($order, $data['action'], array_merge($data, ['phone' => $phone10]));

        DB::table('ndr_responses')->where('id', $row->id)->update([
            'action'           => $data['action'],
            'reattempt_date'   => $data['reattempt_date'],
            'address'          => $data['address'] ?? null,
            'landmark'         => $data['landmark'] ?? null,
            'phone'            => $phone10,
            'remark'           => mb_substr($data['remark'], 0, 250),
            // a courier rejection still lands with ops, so the buyer's request isn't lost
            'status'           => $result['status'] === 'failed' ? 'needs_manual' : $result['status'],
            'courier_response' => mb_strimwidth(json_encode(['message' => $result['message'], 'response' => $result['response']]), 0, 4000, '…'),
            'responded_at'     => now(),
            'updated_at'       => now(),
        ]);

        return redirect()->route('ndr.respond', $token);
    }

    private function load(string $token): array
    {
        $row = DB::table('ndr_responses')->where('token', $token)->first();
        $order = $row ? Order::find($row->order_id) : null;
        return [$row, $order];
    }

    private function viewData($row, ?Order $order): array
    {
        if (!$row || !$order) {
            return ['state' => 'invalid'];
        }
        $seller = SellerList::find($order->seller_id);
        $consignee = is_array($order->consignee) ? $order->consignee : (json_decode((string) $order->consignee, true) ?: []);

        $state = match (true) {
            $row->status !== 'awaiting_buyer' && $row->action !== null => 'done',
            Carbon::parse($row->expires_at)->isPast() => 'expired',
            in_array(OrderStatusNotifier::eventFor($order->shipping_status), ['delivered', 'rto_initiated', 'rto_delivered', 'cancelled'], true) => 'closed',
            default => 'open',
        };

        return [
            'state'      => $state,
            'row'        => $row,
            'token'      => $row->token,
            'brand'      => $seller ? OrderStatusNotifier::brandName($seller, $order) : 'your store',
            'courier'    => CourierBrand::cleanName($order->courier_name ?: ($order->zone_courier_name ?: $order->all_courier_name)),
            'awb'        => $order->awb_number,
            'orderNo'    => $order->customer_order_id ?: $order->order_number,
            'name'       => $consignee['name'] ?? '',
            'address'    => trim(implode(', ', array_filter([$consignee['address'] ?? null, $consignee['city'] ?? null, $consignee['pincode'] ?? null]))),
            'phoneMask'  => AiSensyClient::mask(AiSensyClient::normalizePhone($consignee['phone'] ?? null)),
            'cod'        => strtolower((string) $order->payment_type) === 'cod' ? $order->collectable_amount : null,
            'minDate'    => now()->addDay()->toDateString(),
            'maxDate'    => now()->addDays(4)->toDateString(),
            'trackUrl'   => OrderStatusNotifier::trackingLink($order),
        ];
    }
}
