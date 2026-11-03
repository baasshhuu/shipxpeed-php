<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipxpeedapi\ServiceabilityController;
use App\Models\Order;
use App\Models\Warehouse;
use App\Support\ApiV1\Couriers;
use App\Support\ApiV1\Present;
use App\Support\ApiV1\Respond;
use App\Models\LogisticProvider;
use App\Support\CourierAccess;
use App\Support\CourierBrand;
use App\Support\RateCard;
use App\Support\ShipRate;
use App\Support\Xpeed\Kit;
use App\Http\Middleware\SellerApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * B2C forward shipments: live rates for the client's account, create + book, book an unassigned shipment.
 * Pricing, courier on/off switches and wallet debits run through the panel's own code.
 */
class B2cController extends Controller
{
    /** POST /api/v1/b2c/rates */
    public function rates(Request $request)
    {
        $v = Validator::make($request->all(), [
            'origin_pincode'      => 'required|digits:6',
            'destination_pincode' => 'required|digits:6',
            'payment_type'        => 'required|in:cod,prepaid',
            'order_amount'        => 'required|numeric|min:1',
            'cod_amount'          => 'nullable|numeric|min:0',
            'weight_g'            => 'required|numeric|min:1|max:100000',
            'length_cm'           => 'nullable|numeric|min:1|max:300',
            'breadth_cm'          => 'nullable|numeric|min:1|max:300',
            'height_cm'           => 'nullable|numeric|min:1|max:300',
            'origin_state'        => 'nullable|string|max:60',
            'destination_state'   => 'nullable|string|max:60',
            'mode'                => 'nullable|in:surface,air',
        ]);
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $d = $v->validated();

        $rates = $this->fetchRates($request, $d);
        if ($rates === null) {
            return Respond::error('Could not determine the states for these pincodes. Send origin_state and destination_state.', 422, 'pincode_unknown');
        }
        if (!empty($d['mode'])) {
            $rates = array_values(array_filter($rates, fn ($r) => $r['mode'] === $d['mode']));
        }

        return Respond::ok([
            'origin_pincode'      => (string) $d['origin_pincode'],
            'destination_pincode' => (string) $d['destination_pincode'],
            'payment_type'        => $d['payment_type'],
            'weight_g'            => (float) $d['weight_g'],
            'count'               => count($rates),
            'couriers'            => array_map([self::class, 'publicRate'], $rates),
        ], $rates ? 'Rates fetched' : 'No courier serves this lane for your account');
    }

    /**
     * Normalised, cheapest-first rate rows for this client, or null when the states can't be found.
     * Each row: courier_id (to book), courier_code, courier_name, mode, zone, prices (GST included), edd.
     */
    public function fetchRates(Request $request, array $d): ?array
    {
        $seller = Respond::seller($request);
        $sellerId = (int) $seller->id;
        $originState = $d['origin_state'] ?? null ?: ShipRate::stateForPincode($d['origin_pincode']);
        $destState = $d['destination_state'] ?? null ?: ShipRate::stateForPincode($d['destination_pincode']);
        if (!$originState || !$destState) {
            return null;
        }
        $cod = $d['payment_type'] === 'cod';
        $amount = $cod ? (float) ($d['cod_amount'] ?? $d['order_amount']) : (float) $d['order_amount'];
        $grams = (float) $d['weight_g'];
        $params = [
            'seller_id'           => $sellerId,
            'origin_pincode'      => (string) $d['origin_pincode'],
            'destination_pincode' => (string) $d['destination_pincode'],
            'origin_state'        => $originState,
            'destination_state'   => $destState,
            'pickup_state'        => $originState,
            'payment_type'        => $d['payment_type'],
            'order_amount'        => $amount,
            'package_weight'      => $grams,
            'package_length'      => (float) ($d['length_cm'] ?? 10),
            'package_breadth'     => (float) ($d['breadth_cm'] ?? 10),
            'package_height'      => (float) ($d['height_cm'] ?? 10),
        ];
        $raw = [];

        // 1. Delhivery (direct): the client's rate card, same formula the panel charges (RateCard::price)
        $zone = RateCard::zoneForStates($originState, $destState);
        if ($zone && self::delhiveryDelivers((string) $d['destination_pincode'], $cod)) {
            foreach (CourierAccess::DIRECT as $key => $label) {
                if (!CourierAccess::isOn($sellerId, $key)) {
                    continue;
                }
                $p = RateCard::price(RateCard::find($sellerId, $zone, $key), $grams, $cod, $amount);
                if ($p) {
                    $raw[] = ['courier_id' => $key === 'Delhivery' ? 'delhivery' : 'delhivery_air', 'courierName' => $label,
                        'courierCharge' => $p['courierCharge'], 'freightCharges' => $p['freightCharges'], 'codCharge' => $p['codCharge'],
                        'zone' => $zone, 'chargeable_weight' => $p['slabs'] * RateCard::SLAB_GRAMS];
                }
            }
        }

        // 2. Aggregator couriers (live serviceability; priced from the client's rate card inside each adapter)
        foreach (['courier.shipclues', 'courier.fship'] as $svc) {
            try {
                if (app()->bound($svc)) {
                    $raw = array_merge($raw, (array) app($svc)->getRatesForParams($params));
                }
            } catch (\Throwable $e) {
                Log::warning("ApiV1 rates: $svc failed: " . $e->getMessage());
            }
        }

        // 3. Any older direct provider still switched on in the panel (priced by its own panel code)
        $legacyCodes = LogisticProvider::active()->pluck('code')->map(fn ($c) => strtolower((string) $c))
            ->reject(fn ($c) => in_array($c, self::NATIVE_CODES, true))->values();
        if ($legacyCodes->isNotEmpty()) {
            $res = Respond::legacy($request, ServiceabilityController::class, 'getAllServiceability', $params + [
                'origin_pincode' => (int) $d['origin_pincode'], 'destination_pincode' => (int) $d['destination_pincode'],
            ]);
            foreach ((array) ($res['data'] ?? []) as $r) {
                $id = (string) ($r['courier_id'] ?? '');
                if (is_array($r) && !empty($r['bookable']) && $id !== '' && !in_array($id, ['delhivery', 'delhivery_air'], true)
                    && !str_starts_with($id, 'shipclues_') && !str_starts_with($id, 'fship_')) {
                    $r['_legacy'] = true;
                    $raw[] = $r;
                }
            }
        }

        $rows = [];
        foreach ($raw as $r) {
            if (!is_array($r) || empty($r['courier_id']) || !isset($r['courierCharge'])) {
                continue;
            }
            $name = CourierBrand::cleanName((string) ($r['courierName'] ?? ''), 'Courier');
            $total = round((float) $r['courierCharge'], 2);
            if ($total <= 0) {
                continue;
            }
            $codCharge = round((float) ($r['codCharge'] ?? 0), 2);
            $rows[] = [
                'courier_id'        => (string) $r['courier_id'],
                'courier_code'      => Couriers::code($name),
                'courier_name'      => $name,
                'brand'             => Couriers::brand($name),
                'mode'              => Couriers::mode($name),
                'logo'              => CourierBrand::logoUrl($name),
                'zone'              => $r['zone'] ?? $zone,
                'freight_charge'    => round((float) ($r['freightCharges'] ?? ($total - $codCharge)), 2),
                'cod_charge'        => $cod ? $codCharge : 0.0,
                'total_charge'      => $total,
                'gst_included'      => true,
                'chargeable_weight_g' => isset($r['chargeable_weight']) ? (float) $r['chargeable_weight'] : null,
                'estimated_delivery_date' => $r['estimated_delivery_date'] ?? null,
                '_legacy'           => !empty($r['_legacy']),
            ];
        }

        // one row per courier_id, cheapest first
        $rows = array_values(collect($rows)->unique('courier_id')->sortBy('total_charge')->all());
        foreach ($rows as $i => &$r) {
            $r['rank'] = $i + 1;
        }
        unset($r);
        return $rows;
    }

    /** Provider codes v1 prices and books itself; anything else active goes through the legacy controller. */
    private const NATIVE_CODES = ['delhivery', 'delhivery_surface', 'delhivery_b2c', 'delhivery_air', 'delhivery_express',
        'delhivery_b2c_express', 'shipclues', 'fship'];

    /** Delhivery pincode API: does Delhivery deliver (prepaid / COD) to this pincode? Cached 12 h; unknown = yes. */
    public static function delhiveryDelivers(string $pin, bool $cod): bool
    {
        $info = \Illuminate\Support\Facades\Cache::remember('dlv_fwd_pin_' . $pin, now()->addHours(12), function () use ($pin) {
            try {
                $token = env('DELHIVERY_B2C_TOKEN') ?: 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';
                $r = \Illuminate\Support\Facades\Http::timeout(8)->acceptJson()->withHeaders(['Authorization' => 'Token ' . $token])
                    ->get('https://track.delhivery.com/c/api/pin-codes/json/', ['filter_codes' => $pin]);
                if (!$r->successful()) {
                    return ['known' => false];
                }
                $pc = $r->json('delivery_codes.0.postal_code');
                if (!$pc) {
                    return ['known' => true, 'prepaid' => false, 'cod' => false];
                }
                return ['known' => true, 'prepaid' => strtoupper((string) ($pc['pre_paid'] ?? 'Y')) === 'Y', 'cod' => strtoupper((string) ($pc['cod'] ?? 'Y')) === 'Y'];
            } catch (\Throwable $e) {
                return ['known' => false];
            }
        });
        if (empty($info['known'])) {
            return true;
        }
        return $cod ? !empty($info['cod']) : !empty($info['prepaid']);
    }

    /** Public shape of a rate row. */
    public static function publicRate(array $r): array
    {
        unset($r['_legacy']);
        return $r;
    }

    /** POST /api/v1/b2c/shipments - create the shipment and book it (one call). */
    public function create(Request $request)
    {
        $seller = Respond::seller($request);
        $v = Validator::make($request->all(), $this->rules($request->filled('rto')));
        if ($v->fails()) {
            return Respond::invalid($v);
        }
        $d = $v->validated();
        $cod = $d['payment_type'] === 'cod';

        if ($cod && (float) ($d['cod_amount'] ?? 0) <= 0) {
            return Respond::error('cod_amount is required for COD shipments.', 422, 'validation_failed', ['fields' => ['cod_amount' => ['cod_amount is required for COD shipments.']]]);
        }

        // Idempotency: the same client_order_id is never shipped twice.
        if (!empty($d['client_order_id'])) {
            $dup = Order::where('seller_id', $seller->id)->where('customer_order_id', $d['client_order_id'])
                ->where(fn ($q) => $q->whereNull('reverse')->orWhere('reverse', '!=', 'reverse'))
                ->orderByDesc('id')->get()
                ->first(fn ($o) => Present::status($o) !== 'cancelled');
            if ($dup) {
                $msg = $dup->awb_number
                    ? 'A shipment with this client_order_id already exists.'
                    : 'This client_order_id was created but not booked. Book it with POST /api/v1/b2c/shipments/' . $dup->id . '/book.';
                return Respond::error($msg, 409, 'duplicate_order', ['shipment' => Present::shipment($dup)]);
            }
        }

        // Pickup: saved warehouse or full address
        $pickup = $this->pickupFrom($seller->id, $d['pickup']);
        if (!$pickup) {
            return Respond::error('Pickup warehouse not found. Use GET /api/v1/warehouses or send the full pickup address.', 422, 'warehouse_not_found');
        }
        $rto = !empty($d['rto']) ? $this->pickupFrom($seller->id, $d['rto']) : null;

        $items = array_map(fn ($i) => [
            'name' => $i['name'], 'sku' => $i['sku'] ?? ('SKU-' . substr(md5($i['name']), 0, 8)),
            'qty' => (int) $i['qty'], 'price' => (float) $i['price'],
        ], $d['items']);
        $itemsTotal = array_sum(array_map(fn ($i) => $i['qty'] * $i['price'], $items));
        $orderAmount = (float) ($d['order_amount'] ?? $itemsTotal) ?: $itemsTotal;

        // Courier: explicit courier_id, a courier_code, or the cheapest one - always from a fresh quote,
        // so the price debited is the price of the client's rate card right now.
        $rates = $this->fetchRates($request, [
            'origin_pincode' => $pickup['pincode'], 'destination_pincode' => $d['consignee']['pincode'],
            'origin_state' => $pickup['state'], 'destination_state' => $d['consignee']['state'],
            'payment_type' => $d['payment_type'], 'order_amount' => $orderAmount, 'cod_amount' => $d['cod_amount'] ?? null,
            'weight_g' => $d['weight_g'], 'length_cm' => $d['length_cm'], 'breadth_cm' => $d['breadth_cm'], 'height_cm' => $d['height_cm'],
        ]) ?? [];
        $wanted = !empty($d['courier_id']) ? SellerApiToken::toInternalId((string) $d['courier_id']) : null;
        $pick = null;
        foreach ($rates as $r) {
            if (($wanted && SellerApiToken::toInternalId($r['courier_id']) === $wanted)
                || (!$wanted && !empty($d['courier_code']) && $r['courier_code'] === $d['courier_code'])) {
                $pick = $r;
                break;
            }
        }
        if (!$wanted && empty($d['courier_code'])) {
            $pick = $rates[0] ?? null;
        }
        if (!$pick) {
            return $rates
                ? Respond::error('That courier is not available on this lane for your account. Pick one from POST /api/v1/b2c/rates.', 422, 'courier_unavailable',
                    ['available' => array_map([self::class, 'publicRate'], $rates)])
                : Respond::error('No courier serves this lane for your account.', 422, 'not_serviceable');
        }
        $courierId = SellerApiToken::toInternalId($pick['courier_id']);

        $consignee = [
            'name' => $d['consignee']['name'], 'phone' => Respond::phone10($d['consignee']['phone']), 'email' => $d['consignee']['email'] ?? null,
            'address' => $d['consignee']['address'], 'address_2' => $d['consignee']['address_2'] ?? null,
            'pincode' => (string) $d['consignee']['pincode'], 'city' => $d['consignee']['city'], 'state' => $d['consignee']['state'],
        ];
        $collect = $cod ? (float) $d['cod_amount'] : $orderAmount;

        // Older providers: their own create + book code in the legacy controller.
        if (!empty($pick['_legacy'])) {
            return $this->createLegacy($request, $d, $items, $consignee, $pickup, $rto, $collect, $courierId);
        }

        try {
            $order = DB::transaction(function () use ($seller, $d, $items, $consignee, $pickup, $rto, $orderAmount, $collect) {
                $o = new Order();
                $o->seller_id = $seller->id;
                $o->order_number = 'SPX#' . ((int) Order::max('id') + 1);
                if (Kit::hasCol('orders', 'customer_order_id')) {
                    $o->customer_order_id = $d['client_order_id'] ?? null;
                }
                $o->courier_id = '';
                $o->shipping_charges = 0;
                $o->cod_charges = 0;
                $o->discount = 0;
                $o->payment_type = $d['payment_type'];
                $o->order_amount = $orderAmount;
                $o->collectable_amount = $collect;
                $o->package_weight = $d['weight_g'];
                $o->package_length = $d['length_cm'];
                $o->package_breadth = $d['breadth_cm'];
                $o->package_height = $d['height_cm'];
                $o->consignee = json_encode($consignee);
                $o->pickup = json_encode($pickup);
                $o->rto = json_encode($rto ?: $pickup);
                $o->order_items = json_encode($items);
                $o->save();
                // stored Delhivery payloads (used by booking, returns and the panel), same as orders created in the panel
                $o->delhivery_b2c = json_encode($this->delhiveryPayload($o, false));
                $o->delhivery_b2c_air = json_encode($this->delhiveryPayload($o, true));
                $o->save();
                return $o;
            });
        } catch (\Throwable $e) {
            Log::error('ApiV1 create order failed', ['seller' => $seller->id, 'e' => $e->getMessage()]);
            return Respond::error('Shipment could not be created. Try again.', 500, 'create_failed');
        }

        $result = $this->assignExisting($order, $courierId, $pick);
        $order->refresh();
        if (!$order->awb_number) {
            return Respond::error('Shipment created but not booked: ' . ($result['message'] ?? 'courier did not return an AWB'), 422, 'booking_failed', [
                'shipment' => Present::shipment($order),
                'retry'    => 'POST /api/v1/b2c/shipments/' . $order->id . '/book',
            ]);
        }
        return Respond::ok(Present::shipment($order) + ['charged' => round((float) $order->seller_amount_walate, 2)], 'Shipment booked', 201);
    }

    /** Delhivery payload for a stored order, built by the panel's own builder. */
    private function delhiveryPayload(Order $o, bool $air): array
    {
        $req = new Request();
        $req->replace([
            'consignee' => json_decode((string) $o->consignee, true) ?: (array) $o->consignee,
            'pickup' => json_decode((string) $o->pickup, true) ?: (array) $o->pickup,
            'order_items' => json_decode((string) $o->order_items, true) ?: (array) $o->order_items,
            'payment_type' => $o->payment_type, 'collectable_amount' => $o->collectable_amount,
            'package_weight' => $o->package_weight, 'package_length' => $o->package_length,
            'package_breadth' => $o->package_breadth, 'package_height' => $o->package_height,
            'unique_order_number' => $o->order_number,
        ]);
        $svc = app(ServiceabilityController::class);
        return $air
            ? (fn ($r, $s) => $this->delhivery_b2c_air($r, $s))->call($svc, $req, $o->seller_id)
            : (fn ($r, $s) => $this->delhivery_b2c($r, $s))->call($svc, $req, $o->seller_id);
    }

    private function createLegacy(Request $request, array $d, array $items, array $consignee, array $pickup, ?array $rto, float $collect, string $courierId)
    {
        $seller = Respond::seller($request);
        $before = (int) Order::max('id');
        $res = Respond::legacy($request, ServiceabilityController::class, 'createShipment', [
            'order_number' => $d['client_order_id'] ?? null, 'payment_type' => $d['payment_type'], 'collectable_amount' => $collect,
            'order_items' => $items, 'consignee' => $consignee, 'pickup' => $pickup, 'rto' => $rto,
            'package_weight' => (float) $d['weight_g'], 'package_length' => (float) $d['length_cm'],
            'package_breadth' => (float) $d['breadth_cm'], 'package_height' => (float) $d['height_cm'], 'courier_id' => $courierId,
        ]);
        $orderId = (int) ($res['data']['order_id'] ?? $res['order_id'] ?? 0);
        $order = $orderId ? Order::where('seller_id', $seller->id)->find($orderId)
            : Order::where('seller_id', $seller->id)->where('id', '>', $before)->orderByDesc('id')->first();
        if (!$order) {
            return Respond::error($res['message'] ?? 'Shipment could not be created', Respond::legacyStatus($res, 422), 'create_failed', isset($res['errors']) ? ['fields' => $res['errors']] : []);
        }
        $order->refresh();
        if (!$order->awb_number) {
            $why = $res['data']['assignment']['message'] ?? $res['assignment']['message'] ?? $res['message'] ?? 'Courier booking failed';
            return Respond::error('Shipment created but not booked: ' . $why, 422, 'booking_failed', ['shipment' => Present::shipment($order)]);
        }
        return Respond::ok(Present::shipment($order) + ['charged' => round((float) $order->seller_amount_walate, 2)], 'Shipment booked', 201);
    }

    /** POST /api/v1/b2c/shipments/{id}/book - book a created-but-unbooked shipment. */
    public function book(Request $request, int $id)
    {
        $seller = Respond::seller($request);
        $order = Order::where('seller_id', $seller->id)->find($id);
        if (!$order) {
            return Respond::error('Shipment not found.', 404, 'not_found');
        }
        if ($order->awb_number) {
            return Respond::error('This shipment is already booked.', 409, 'already_booked', ['shipment' => Present::shipment($order)]);
        }
        if (Present::status($order) === 'cancelled') {
            return Respond::error('This shipment is cancelled.', 409, 'cancelled');
        }
        $v = Validator::make($request->all(), ['courier_id' => 'nullable|string|max:60', 'courier_code' => 'nullable|string|max:80']);
        if ($v->fails()) {
            return Respond::invalid($v);
        }

        $to = is_array($order->consignee) ? $order->consignee : (json_decode((string) $order->consignee, true) ?: []);
        $from = is_array($order->pickup) ? $order->pickup : (json_decode((string) $order->pickup, true) ?: []);
        $cod = strtolower((string) $order->payment_type) === 'cod';
        $rates = $this->fetchRates($request, [
            'origin_pincode' => $from['pincode'] ?? '', 'destination_pincode' => $to['pincode'] ?? '',
            'origin_state' => $from['state'] ?? null, 'destination_state' => $to['state'] ?? null,
            'payment_type' => $cod ? 'cod' : 'prepaid', 'order_amount' => (float) $order->collectable_amount ?: (float) $order->order_amount,
            'weight_g' => (float) $order->package_weight, 'length_cm' => (float) $order->package_length,
            'breadth_cm' => (float) $order->package_breadth, 'height_cm' => (float) $order->package_height,
        ]) ?? [];

        $wanted = SellerApiToken::toInternalId((string) $request->input('courier_id', ''));
        $pick = null;
        foreach ($rates as $r) {
            $internal = SellerApiToken::toInternalId($r['courier_id']);
            if (($wanted !== '' && $internal === $wanted) || ($request->filled('courier_code') && $r['courier_code'] === $request->input('courier_code'))) {
                $pick = $r;
                break;
            }
        }
        if ($wanted === '' && !$request->filled('courier_code')) {
            $pick = $rates[0] ?? null;
        }
        if (!$pick) {
            return Respond::error('That courier is not available for this shipment.', 422, 'courier_unavailable', ['available' => array_map([self::class, 'publicRate'], $rates)]);
        }

        $result = $this->assignExisting($order, SellerApiToken::toInternalId($pick['courier_id']), $pick);
        $order->refresh();
        if (!$order->awb_number) {
            return Respond::error('Booking failed: ' . ($result['message'] ?? 'courier did not return an AWB'), 422, 'booking_failed', ['shipment' => Present::shipment($order)]);
        }
        return Respond::ok(Present::shipment($order), 'Shipment booked');
    }

    /**
     * Book an existing order with one of the panel's live couriers (Delhivery, Delhivery Air, aggregator couriers).
     * Each path checks the wallet and debits it exactly like a booking made in the panel.
     */
    private function assignExisting(Order $order, string $courierId, array $rate): array
    {
        $sellerId = (int) $order->seller_id;
        try {
            if (str_starts_with($courierId, 'shipclues_')) {
                return (array) app('courier.shipclues')->assignOrder(['order_id' => $order->id, 'serviceability_id' => (int) substr($courierId, 10), 'seller_id' => $sellerId]);
            }
            if (str_starts_with($courierId, 'fship_')) {
                return (array) app('courier.fship')->assignOrder(['order_id' => $order->id, 'serviceability_id' => (int) substr($courierId, 6), 'seller_id' => $sellerId]);
            }
            if (in_array($courierId, ['delhivery', 'delhivery_air'], true)) {
                // Same Delhivery services the seller panel books with: wallet check + debit inside,
                // courier_id 'delhivery_b2c' so tracking sync, NDR and cancel all pick the shipment up.
                $air = $courierId === 'delhivery_air';
                $order->seller_amount_walate = $rate['total_charge'];   // the quoted price is what the wallet is debited
                $order->save();
                $svc = app($air ? 'courier.delhivery_b2c_Express' : 'courier.delhivery_b2c');
                $r = (array) $svc->assignOrder($this->delhiveryPayload($order, $air));
                $awb = $r['packages'][0]['waybill'] ?? null;
                $order->refresh();
                if ($awb) {
                    $order->awb_number = $awb;
                    $order->courier_id = 'delhivery_b2c';
                    $order->all_courier_name = $air ? 'Delhivery Air' : 'Delhivery Surface';
                    if (Kit::hasCol('orders', 'courier_name')) {
                        $order->courier_name = $air ? 'Delhivery Air' : 'Delhivery Surface';
                    }
                    $order->shipping_status = $order->shipping_status ?: 'assigned';
                    $order->shipping_date = $order->shipping_date ?: now();
                    $order->save();
                    return ['status' => true, 'awb_number' => $awb];
                }
                if (!$order->awb_number) {
                    $order->seller_amount_walate = null;
                    $order->save();
                }
                return ['status' => false, 'message' => $r['message'] ?? 'Delhivery did not return an AWB'];
            }
        } catch (\Throwable $e) {
            Log::error('ApiV1 booking failed', ['order' => $order->id, 'courier' => $courierId, 'e' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Booking error. Try again or book from the panel.'];
        }
        return ['status' => false, 'message' => 'This courier can only be booked from the panel.'];
    }

    /** Pickup array the panel stores, from {warehouse_id} or a full address. */
    public function pickupFrom(int $sellerId, array $p): ?array
    {
        if (!empty($p['warehouse_id'])) {
            $w = Warehouse::where('seller_id', $sellerId)->find($p['warehouse_id']);
            if (!$w) {
                return null;
            }
            return [
                'warehouse_name' => $w->address_title ?: $w->name,
                'name'           => $w->name,
                'phone'          => (string) $w->phone,
                'address'        => $w->address_line1,
                'address_2'      => $w->address_line2,
                'pincode'        => (string) $w->pincode,
                'city'           => $w->city,
                'state'          => $w->state,
            ];
        }
        if (empty($p['address']) || empty($p['pincode'])) {
            return null;
        }
        return [
            'warehouse_name' => $p['warehouse_name'] ?? $p['name'],
            'name'           => $p['name'],
            'phone'          => (string) $p['phone'],
            'address'        => $p['address'],
            'address_2'      => $p['address_2'] ?? null,
            'pincode'        => (string) $p['pincode'],
            'city'           => $p['city'],
            'state'          => $p['state'],
        ];
    }

    private function rules(bool $withRto = false): array
    {
        $addr = fn ($prefix, $required) => [
            "$prefix.warehouse_id"   => 'nullable|integer',
            "$prefix.warehouse_name" => 'nullable|string|max:100',
            "$prefix.name"           => "required_without:$prefix.warehouse_id|nullable|string|max:100",
            "$prefix.phone"          => ["required_without:$prefix.warehouse_id", 'nullable', 'regex:/^(\+?91)?[6-9]\d{9}$/'],
            "$prefix.address"        => "required_without:$prefix.warehouse_id|nullable|string|max:300",
            "$prefix.address_2"      => 'nullable|string|max:300',
            "$prefix.pincode"        => "required_without:$prefix.warehouse_id|nullable|digits:6",
            "$prefix.city"           => "required_without:$prefix.warehouse_id|nullable|string|max:60",
            "$prefix.state"          => "required_without:$prefix.warehouse_id|nullable|string|max:60",
        ] + ($required ? [$prefix => 'required|array'] : [$prefix => 'nullable|array']);

        return [
            'client_order_id'     => 'nullable|string|max:100',
            'payment_type'        => 'required|in:cod,prepaid',
            'order_amount'        => 'nullable|numeric|min:1',
            'cod_amount'          => 'nullable|numeric|min:0',
            'weight_g'            => 'required|numeric|min:1|max:100000',
            'length_cm'           => 'required|numeric|min:1|max:300',
            'breadth_cm'          => 'required|numeric|min:1|max:300',
            'height_cm'           => 'required|numeric|min:1|max:300',
            'courier_id'          => 'nullable|string|max:60',
            'courier_code'        => 'nullable|string|max:80',
            'consignee'           => 'required|array',
            'consignee.name'      => 'required|string|max:100',
            'consignee.phone'     => ['required', 'regex:/^(\+?91)?[6-9]\d{9}$/'],
            'consignee.email'     => 'nullable|email|max:120',
            'consignee.address'   => 'required|string|min:5|max:300',
            'consignee.address_2' => 'nullable|string|max:300',
            'consignee.pincode'   => 'required|digits:6',
            'consignee.city'      => 'required|string|max:60',
            'consignee.state'     => 'required|string|max:60',
            'items'               => 'required|array|min:1|max:50',
            'items.*.name'        => 'required|string|max:200',
            'items.*.sku'         => 'nullable|string|max:100',
            'items.*.qty'         => 'required|integer|min:1|max:10000',
            'items.*.price'       => 'required|numeric|min:0',
        ] + $addr('pickup', true) + ($withRto ? $addr('rto', false) : []);
    }
}
