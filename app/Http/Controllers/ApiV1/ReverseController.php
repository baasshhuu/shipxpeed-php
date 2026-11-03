<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipxpeedapi\ServiceabilityController;
use App\Http\Controllers\Xpeed\ReturnsController;
use App\Models\Order;
use App\Services\DelhiveryB2CService;
use App\Support\ApiV1\Present;
use App\Support\ApiV1\Respond;
use App\Support\CourierAccess;
use App\Support\RateCard;
use App\Support\ShipRate;
use App\Support\Xpeed\Kit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Reverse pickups (returns): buyer -> your warehouse, prepaid, booked with Delhivery reverse pickup (RVP).
 * Same booking + wallet debit as Seller panel > Reverse orders.
 */
class ReverseController extends Controller
{
    /** POST /api/v1/reverse/rates */
    public function rates(Request $request)
    {
        $seller = Respond::seller($request);
        $v = Validator::make($request->all(), [
            'pickup_pincode'   => 'required|digits:6',   // buyer
            'delivery_pincode' => 'required|digits:6',   // your warehouse
            'weight_g'         => 'required|numeric|min:1|max:50000',
            'pickup_state'     => 'nullable|string|max:60',
            'delivery_state'   => 'nullable|string|max:60',
        ]);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $d = $v->validated();
        $quote = $this->quote((int) $seller->id, $d);
        if (isset($quote['error'])) {
            return Respond::error($quote['error'], 422, $quote['code']);
        }
        return Respond::ok([
            'pickup_pincode' => (string) $d['pickup_pincode'], 'delivery_pincode' => (string) $d['delivery_pincode'],
            'weight_g' => (float) $d['weight_g'], 'count' => 1, 'couriers' => [$quote],
        ], 'Rates fetched');
    }

    private function quote(int $sellerId, array $d): array
    {
        if (!CourierAccess::isOn($sellerId, 'Delhivery')) {
            return ['error' => 'Reverse pickup is not enabled for your account.', 'code' => 'reverse_disabled'];
        }
        if (!DelhiveryB2CService::rvpPickupServiceable((string) $d['pickup_pincode'])) {
            return ['error' => 'Reverse pickup is not available from pincode ' . $d['pickup_pincode'] . '.', 'code' => 'not_serviceable'];
        }
        // the buyer is the origin of a return, your warehouse the destination
        $zone = RateCard::zoneForStates($d['pickup_state'] ?? null ?: ShipRate::stateForPincode($d['pickup_pincode']),
            $d['delivery_state'] ?? null ?: ShipRate::stateForPincode($d['delivery_pincode']));
        $p = $zone ? RateCard::price(RateCard::find($sellerId, $zone, 'Delhivery'), (float) $d['weight_g'], false, 0) : null;
        if (!$p) {
            return ['error' => 'No reverse rate is set for this lane on your account.', 'code' => 'no_rate'];
        }
        return [
            'courier_id' => 'delhivery_reverse', 'courier_code' => 'delhivery-reverse', 'courier_name' => 'Delhivery Reverse',
            'brand' => 'Delhivery', 'mode' => 'surface', 'zone' => $zone,
            'freight_charge' => round((float) $p['freightCharges'], 2), 'cod_charge' => 0.0,
            'total_charge' => round((float) $p['courierCharge'], 2), 'gst_included' => true,
            'chargeable_weight_g' => (float) ($p['slabs'] * RateCard::SLAB_GRAMS),
        ];
    }

    /**
     * POST /api/v1/reverse/shipments
     *  a) {"awb_number": "<delivered forward AWB>", "reason": "..."}  -> return of a delivered order
     *  b) full details: pickup (buyer), warehouse_id (where it comes back), items, weight  -> standalone reverse pickup
     */
    public function create(Request $request)
    {
        $seller = Respond::seller($request);
        $returns = app(ReturnsController::class);

        if ($request->filled('awb_number')) {
            $v = Validator::make($request->all(), ['awb_number' => 'required|string|max:60', 'reason' => 'nullable|string|max:120']);
            if ($v->fails()) {
                return Respond::invalid($v);
            }
            $orig = Order::where('seller_id', $seller->id)->where('awb_number', $request->awb_number)->first();
            if (!$orig) {
                return Respond::error('AWB not found in your account.', 404, 'not_found');
            }
            if (($orig->reverse ?? null) === 'reverse') {
                return Respond::error('This AWB is already a reverse shipment.', 422, 'already_reverse');
            }
            if (Present::status($orig) !== 'delivered') {
                return Respond::error('A return can only be created for a delivered shipment (current status: ' . Present::status($orig) . ').', 422, 'not_delivered');
            }
            if (Schema::hasTable('xp_returns')) {
                $existing = DB::table('xp_returns')->where('original_order_id', $orig->id)->pluck('return_order_id');
                if ($existing->isNotEmpty() && ($r = Order::whereIn('id', $existing)->orderByDesc('id')->first())) {
                    return Respond::error('A return already exists for this AWB.', 409, 'duplicate_return', ['shipment' => Present::shipment($r)]);
                }
            }
            if (!Schema::hasTable('xp_returns')) {
                return Respond::error('Returns are being set up. Try again shortly.', 503, 'not_ready');
            }
            $reason = in_array($request->reason, ReturnsController::REASONS, true) ? $request->reason : 'Customer return';
            try {
                $ret = DB::transaction(fn () => (fn ($o, $r) => $this->cloneAsReturn($o, $r))->call($returns, $orig, $reason));
            } catch (\Throwable $e) {
                Log::error('ApiV1 return create failed', ['order' => $orig->id, 'e' => $e->getMessage()]);
                return Respond::error('Return could not be created.', 500, 'create_failed');
            }
            return $this->assignAndRespond($returns, $ret);
        }

        // standalone reverse pickup
        $v = Validator::make($request->all(), [
            'client_order_id'   => 'nullable|string|max:100',
            'warehouse_id'      => 'required|integer',
            'pickup'            => 'required|array',
            'pickup.name'       => 'required|string|max:100',
            'pickup.phone'      => ['required', 'regex:/^(\+?91)?[6-9]\d{9}$/'],
            'pickup.address'    => 'required|string|min:5|max:300',
            'pickup.address_2'  => 'nullable|string|max:300',
            'pickup.pincode'    => 'required|digits:6',
            'pickup.city'       => 'required|string|max:60',
            'pickup.state'      => 'required|string|max:60',
            'items'             => 'required|array|min:1|max:50',
            'items.*.name'      => 'required|string|max:200',
            'items.*.sku'       => 'nullable|string|max:100',
            'items.*.qty'       => 'required|integer|min:1',
            'items.*.price'     => 'required|numeric|min:0',
            'weight_g'          => 'required|numeric|min:1|max:50000',
            'length_cm'         => 'required|numeric|min:1|max:300',
            'breadth_cm'        => 'required|numeric|min:1|max:300',
            'height_cm'         => 'required|numeric|min:1|max:300',
            'reason'            => 'nullable|string|max:120',
        ]);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $d = $v->validated();
        $wh = app(B2cController::class)->pickupFrom((int) $seller->id, ['warehouse_id' => $d['warehouse_id']]);
        if (!$wh) {
            return Respond::error('Warehouse not found. Use GET /api/v1/warehouses.', 422, 'warehouse_not_found');
        }
        $quote = $this->quote((int) $seller->id, [
            'pickup_pincode' => $d['pickup']['pincode'], 'delivery_pincode' => $wh['pincode'], 'weight_g' => $d['weight_g'],
            'pickup_state' => $d['pickup']['state'], 'delivery_state' => $wh['state'],
        ]);
        if (isset($quote['error'])) {
            return Respond::error($quote['error'], 422, $quote['code']);
        }
        if (!empty($d['client_order_id']) && ($dup = Order::where('seller_id', $seller->id)->where('reverse', 'reverse')
                ->where('customer_order_id', $d['client_order_id'])->orderByDesc('id')->first()) && Present::status($dup) !== 'cancelled') {
            return Respond::error('A reverse shipment with this client_order_id already exists.', 409, 'duplicate_order', ['shipment' => Present::shipment($dup)]);
        }

        $items = array_map(fn ($i) => ['name' => $i['name'], 'sku' => $i['sku'] ?? ('SKU-' . substr(md5($i['name']), 0, 8)), 'qty' => (int) $i['qty'], 'price' => (float) $i['price']], $d['items']);
        $total = array_sum(array_map(fn ($i) => $i['qty'] * $i['price'], $items));
        $consignee = [
            'name' => $d['pickup']['name'], 'phone' => $d['pickup']['phone'], 'address' => $d['pickup']['address'],
            'address_2' => $d['pickup']['address_2'] ?? null, 'pincode' => (string) $d['pickup']['pincode'],
            'city' => $d['pickup']['city'], 'state' => $d['pickup']['state'],
        ];

        try {
            $order = DB::transaction(function () use ($seller, $d, $items, $total, $consignee, $wh) {
                $o = new Order();
                $o->seller_id = $seller->id;
                $o->order_number = 'SPX#' . ((int) Order::max('id') + 1);
                if (Kit::hasCol('orders', 'customer_order_id')) {
                    $o->customer_order_id = $d['client_order_id'] ?? null;
                }
                $o->reverse = 'reverse';
                $o->courier_id = '';
                $o->shipping_charges = 0;
                $o->cod_charges = 0;
                $o->discount = 0;
                $o->payment_type = 'prepaid';
                $o->order_amount = $total;
                $o->collectable_amount = 0;
                $o->package_weight = $d['weight_g'];
                $o->package_length = $d['length_cm'];
                $o->package_breadth = $d['breadth_cm'];
                $o->package_height = $d['height_cm'];
                $o->consignee = json_encode($consignee);
                $o->pickup = json_encode($wh);
                $o->rto = json_encode($wh);
                $o->order_items = json_encode($items);
                $o->save();

                $req = new Request();
                $req->replace([
                    'consignee' => $consignee, 'pickup' => $wh, 'order_items' => $items, 'payment_type' => 'Prepaid',
                    'collectable_amount' => $total, 'package_weight' => $d['weight_g'], 'package_length' => $d['length_cm'],
                    'package_breadth' => $d['breadth_cm'], 'package_height' => $d['height_cm'], 'unique_order_number' => $o->order_number,
                ]);
                $o->delhivery_b2c = json_encode((fn ($r, $s) => $this->delhivery_b2c($r, $s))->call(app(ServiceabilityController::class), $req, $seller->id));
                $o->save();
                return $o;
            });
        } catch (\Throwable $e) {
            Log::error('ApiV1 reverse create failed', ['seller' => $seller->id, 'e' => $e->getMessage()]);
            return Respond::error('Reverse shipment could not be created.', 500, 'create_failed');
        }

        return $this->assignAndRespond($returns, $order);
    }

    private function assignAndRespond(ReturnsController $returns, Order $ret)
    {
        $r = (fn ($o) => $this->autoAssign($o))->call($returns, $ret);
        $ret->refresh();
        if (empty($r['ok']) || !$ret->awb_number) {
            return Respond::error('Reverse shipment created but not booked: ' . ($r['message'] ?? 'courier did not return an AWB'), 422, 'booking_failed', [
                'shipment' => Present::shipment($ret),
                'next'     => 'Choose a courier for it in Seller panel > Reverse orders, or cancel it.',
            ]);
        }
        return Respond::ok(Present::shipment($ret), 'Reverse pickup booked', 201);
    }
}
