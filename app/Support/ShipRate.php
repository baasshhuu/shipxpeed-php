<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Seller pricing from the zone rate card, shared by every place a price is shown or charged:
 * ship-order courier list, booking (wallet debit), seller rate calculator, seller rate card, API.
 *
 * Price formula: RateCard::price() (first 500 g + additional 500 g, COD, + 18% GST).
 * Rate used: the seller's own rate for courier + zone, else the default rate card.
 */
class ShipRate
{
    /** Context of the shipment being priced right now (set at the start of each lookup). */
    private static array $ctx = ['seller' => 0, 'zone' => null, 'cod' => false, 'amount' => 0.0, 'weight' => null];

    /** Remember seller / zone / payment type / order value from an order_id or from raw params. */
    public static function remember($params): void
    {
        $p = is_array($params) ? $params : (array) $params;
        $ctx = ['seller' => 0, 'zone' => null, 'cod' => false, 'amount' => 0.0, 'weight' => null];
        try {
            if (!empty($p['order_id']) && ($o = \App\Models\Order::find($p['order_id']))) {
                $pickup = is_string($o->pickup) ? json_decode($o->pickup, true) : (array) $o->pickup;
                $to = is_string($o->consignee) ? json_decode($o->consignee, true) : (array) $o->consignee;
                $ctx['seller'] = (int) $o->seller_id;
                $ctx['zone'] = RateCard::zoneForStates($pickup['state'] ?? null, $to['state'] ?? null);
                $ctx['cod'] = strtolower((string) $o->payment_type) === 'cod';
                $ctx['amount'] = (float) ($o->collectable_amount ?? 0);
                $ctx['weight'] = (float) ($o->package_weight ?? 0) ?: null;
            } else {
                $ctx['seller'] = (int) ($p['seller_id'] ?? 0);
                if (!$ctx['seller'] && Auth::guard('seller')->check()) {
                    $ctx['seller'] = (int) Auth::guard('seller')->id();
                }
                $ctx['cod'] = strtolower((string) ($p['payment_type'] ?? 'prepaid')) === 'cod';
                $ctx['amount'] = (float) ($p['order_amount'] ?? 0);
                $from = $p['origin_pincode'] ?? $p['origin'] ?? null;
                $dest = $p['destination_pincode'] ?? $p['destination'] ?? null;
                $ctx['zone'] = RateCard::zoneForStates($p['origin_state'] ?? null, $p['destination_state'] ?? null)
                    ?? (($from && $dest) ? self::zoneForPincodes($from, $dest) : null);
            }
        } catch (\Throwable $e) {
            Log::warning('ShipRate::remember failed: ' . $e->getMessage());
        }
        self::$ctx = $ctx;
    }

    public static function context(): array
    {
        return self::$ctx;
    }

    /** Zone between two pincodes via their states (India Post lookup, cached) and the panel's zone table. */
    public static function zoneForPincodes($from, $to): ?string
    {
        $a = self::stateForPincode($from);
        $b = self::stateForPincode($to);
        return ($a && $b) ? RateCard::zoneForStates($a, $b) : null;
    }

    public static function stateForPincode($pin): ?string
    {
        $pin = preg_replace('/\D/', '', (string) $pin);
        if (strlen($pin) !== 6) {
            return null;
        }
        $hit = Cache::get('pin_state_' . $pin);
        if ($hit !== null) {
            return $hit ?: null;
        }
        $state = self::stateFromDelhivery($pin) ?? '';
        if ($state === '') {
            try {
                $res = Http::timeout(5)->get('https://api.postalpincode.in/pincode/' . $pin);
                $state = (string) ($res->json()[0]['PostOffice'][0]['State'] ?? '');
            } catch (\Throwable $e) {
                $state = '';
            }
        }
        Cache::put('pin_state_' . $pin, $state, $state ? now()->addDays(60) : now()->addMinutes(10));
        return $state ?: null;
    }

    /** State name for a pincode from the Delhivery pincode API (our own token). */
    private static function stateFromDelhivery(string $pin): ?string
    {
        $token = env('DELHIVERY_B2C_TOKEN');
        if (!$token) {
            return null;
        }
        try {
            $res = Http::timeout(6)->withHeaders(['Authorization' => 'Token ' . $token, 'Accept' => 'application/json'])
                ->get('https://track.delhivery.com/c/api/pin-codes/json/', ['filter_codes' => $pin]);
            $pc = $res->json()['delivery_codes'][0]['postal_code'] ?? null;
            if (!is_array($pc)) {
                return null;
            }
            $code = strtoupper(trim((string) ($pc['state_code'] ?? '')));
            return self::STATE_CODES[$code] ?? (strlen($code) > 3 ? ucwords(strtolower($code)) : null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Delhivery state codes -> state names as used in the panel's zone table. */
    private const STATE_CODES = [
        'AN' => 'Andaman & Nicobar Islands', 'AP' => 'Andhra Pradesh', 'AR' => 'Arunachal Pradesh', 'AS' => 'Assam',
        'BR' => 'Bihar', 'BH' => 'Bihar', 'CH' => 'Chandigarh', 'CG' => 'Chhattisgarh', 'CT' => 'Chhattisgarh',
        'DN' => 'Dadra and Nagar Haveli and Daman and Diu', 'DD' => 'Dadra and Nagar Haveli and Daman and Diu',
        'DL' => 'Delhi', 'GA' => 'Goa', 'GJ' => 'Gujarat', 'HR' => 'Haryana', 'HP' => 'Himachal Pradesh',
        'JK' => 'Jammu & Kashmir', 'JH' => 'Jharkhand', 'KA' => 'Karnataka', 'KL' => 'Kerala', 'LA' => 'Ladakh',
        'LD' => 'Lakshadweep', 'MP' => 'Madhya Pradesh', 'MH' => 'Maharashtra', 'MN' => 'Manipur', 'ML' => 'Meghalaya',
        'MZ' => 'Mizoram', 'NL' => 'Nagaland', 'OR' => 'Odisha', 'OD' => 'Odisha', 'PY' => 'Puducherry', 'PB' => 'Punjab',
        'RJ' => 'Rajasthan', 'SK' => 'Sikkim', 'TN' => 'Tamil Nadu', 'TG' => 'Telangana', 'TS' => 'Telangana',
        'TR' => 'Tripura', 'UP' => 'Uttar Pradesh', 'UK' => 'Uttarakhand', 'UT' => 'Uttarakhand', 'WB' => 'West Bengal',
    ];

    /** Rate-card key for a Shipclues courier name. */
    public static function shipcluesKey(?string $courierName): string
    {
        $name = CourierBrand::cleanName($courierName);
        return 'Shipclues_' . trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_');
    }

    /**
     * Seller price for one Shipclues rate row from the rate card, or null when no rate is set
     * for that courier (the Shipclues margin then applies).
     */
    public static function shipcluesPrice(array $r): ?array
    {
        try {
            $c = self::$ctx;
            $zone = $c['zone'] ?: RateCard::normalizeZone($r['zone'] ?? null);
            $card = RateCard::find((int) $c['seller'], $zone, self::shipcluesKey($r['courier_name'] ?? null));
            if (!$card) {
                return null;
            }
            $weight = (float) ($r['charged_weight_in_gm'] ?? $r['weight_in_gm'] ?? $c['weight'] ?? 0) ?: 500;
            $p = RateCard::price($card, $weight, (bool) $c['cod'], (float) $c['amount']);
            return $p ? $p + ['zone' => $card->zone] : null;
        } catch (\Throwable $e) {
            Log::warning('ShipRate::shipcluesPrice failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Recompute, on the server, the price of the courier the seller picked for an order.
     * Returns ['courierCharge','freightCharges','codCharge'] to charge,
     *         false when that courier is not offered for this order (block the booking),
     *         null for couriers this panel does not price (old adapters keep their own logic).
     */
    public static function verifiedCharge(int $orderId, $provider, $providerName = null, $serviceabilityId = null)
    {
        $code = is_object($provider) ? (string) ($provider->code ?? '') : (string) $provider;
        $lc = strtolower($code);
        if (!in_array($lc, ['delhivery_b2c', 'delhivery_b2c_express', 'shipclues', 'fship'], true)) {
            return null;
        }
        try {
            $svc = null;
            foreach (array_unique([$code, $lc, $lc === 'delhivery_b2c_express' ? 'delhivery_b2c_Express' : $lc]) as $k) {
                if (app()->bound('courier.' . $k)) {
                    $svc = app('courier.' . $k);
                    break;
                }
            }
            if (!$svc) {
                return null;
            }
            $entries = array_values(array_filter((array) $svc->getServiceability(['order_id' => $orderId]), 'is_array'));
            if (!$entries) {
                return false;
            }
            $pick = null;
            if (str_starts_with($lc, 'delhivery')) {
                $pick = $entries[0];
            } else {
                $want = strtolower(CourierBrand::cleanName((string) $providerName, ''));
                foreach ($entries as $e) {
                    if ($serviceabilityId !== null && $serviceabilityId !== '' && (string) ($e['serviceabilityId'] ?? '') === (string) $serviceabilityId) {
                        $pick = $e;
                        break;
                    }
                    if ($want !== '' && strtolower(CourierBrand::cleanName((string) ($e['courierName'] ?? ''), '')) === $want) {
                        $pick = $e;
                    }
                }
            }
            if (!$pick || !isset($pick['courierCharge'])) {
                return false;
            }
            return [
                'courierCharge'  => round((float) $pick['courierCharge'], 2),
                'freightCharges' => round((float) ($pick['freightCharges'] ?? $pick['courierCharge']), 2),
                'codCharge'      => round((float) ($pick['codCharge'] ?? 0), 2),
            ];
        } catch (\Throwable $e) {
            Log::error('ShipRate::verifiedCharge failed', ['order' => $orderId, 'code' => $code, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Rates for the seller rate calculator (no order yet).
     * $in: origin, destination, payment_type, order_amount, weight_grams
     * Returns rows: name, zone, freight_charges, cod_charges, gst, total_charges, chargeable_weight, min_weight
     */
    public static function quote(int $sellerId, array $in): array
    {
        $grams = max(1, (float) ($in['weight_grams'] ?? 500));
        $cod = strtolower((string) ($in['payment_type'] ?? 'prepaid')) === 'cod';
        $amount = (float) ($in['order_amount'] ?? 0);
        // States filled in by the calculator page (India Post lookup in the browser), else server lookup
        $zone = RateCard::zoneForStates($in['origin_state'] ?? null, $in['destination_state'] ?? null)
            ?? self::zoneForPincodes($in['origin'] ?? '', $in['destination'] ?? '');
        $out = [];

        // Delhivery (direct): zone rate card
        if ($zone) {
            foreach (CourierAccess::DIRECT as $key => $label) {
                if (!CourierAccess::isOn($sellerId, $key)) {
                    continue;
                }
                $p = RateCard::price(RateCard::find($sellerId, $zone, $key), $grams, $cod, $amount);
                if ($p) {
                    $out[] = self::row($label, $zone, $p, $grams);
                }
            }
        }

        // Shipclues couriers (live serviceability; priced from the rate card inside the adapter)
        try {
            if (class_exists(\App\Services\ShipcluesService::class)) {
                $rates = (new \App\Services\ShipcluesService())->getRatesForParams([
                    'seller_id' => $sellerId,
                    'origin_pincode' => $in['origin'] ?? '',
                    'destination_pincode' => $in['destination'] ?? '',
                    'origin_state' => $in['origin_state'] ?? null,
                    'destination_state' => $in['destination_state'] ?? null,
                    'payment_type' => $cod ? 'cod' : 'prepaid',
                    'order_amount' => $amount,
                    'package_weight' => $grams,
                    'package_length' => $in['length'] ?? 10,
                    'package_breadth' => $in['breadth'] ?? 10,
                    'package_height' => $in['height'] ?? 10,
                ]);
                foreach ((array) $rates as $r) {
                    $total = (float) ($r['courierCharge'] ?? 0);
                    $codC = (float) ($r['codCharge'] ?? 0);
                    $out[] = [
                        'name' => (string) ($r['courierName'] ?? 'Courier'),
                        'zone' => $r['zone'] ?? $zone,
                        'freight_charges' => round((float) ($r['freightCharges'] ?? $total - $codC), 2),
                        'cod_charges' => round($codC, 2),
                        'gst' => null,
                        'total_charges' => round($total, 2),
                        'chargeable_weight' => (float) ($r['chargeable_weight'] ?? $grams),
                        'min_weight' => (float) ($r['chargeable_weight'] ?? $grams),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ShipRate::quote Shipclues failed: ' . $e->getMessage());
        }

        usort($out, fn ($a, $b) => $a['total_charges'] <=> $b['total_charges']);
        return $out;
    }

    private static function row(string $name, ?string $zone, array $p, float $grams): array
    {
        return [
            'name' => $name,
            'zone' => $zone,
            'freight_charges' => $p['freightCharges'],
            'cod_charges' => $p['codCharge'],
            'gst' => $p['gst'] ?? null,
            'total_charges' => $p['courierCharge'],
            'chargeable_weight' => $p['slabs'] * RateCard::SLAB_GRAMS,
            'min_weight' => $grams,
        ];
    }

    /**
     * Zone-wise rate card a seller sees: every courier that is ON for them,
     * with their own rate or the default rate per zone.
     */
    public static function sellerRateCard(int $sellerId): array
    {
        $zones = RateCard::zones();
        $cards = [];
        foreach (CourierAccess::groups() as $g) {
            foreach ($g['couriers'] as $key => $c) {
                if (!CourierAccess::isOn($sellerId, $key, str_starts_with($key, 'Shipclues_') ? 'Shipclues' : null)) {
                    continue;
                }
                $rows = [];
                foreach ($zones as $z) {
                    $r = RateCard::find($sellerId, $z, $key);
                    if ($r && (float) $r->prepaid_price > 0) {
                        $rows[$z] = [
                            'first' => (float) $r->prepaid_price,
                            'additional' => (float) ($r->additional_price ?: $r->prepaid_price),
                            'cod' => (float) $r->cod_price,
                            'cod_percent' => (float) $r->cod_charge_parsent,
                        ];
                    }
                }
                if ($rows) {
                    $cards[] = ['name' => $c['label'], 'logo' => $c['logo'], 'zones' => $rows];
                }
            }
        }
        return ['zones' => $zones, 'couriers' => $cards];
    }
}
