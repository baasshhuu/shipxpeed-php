<?php

namespace App\Support;

use App\Models\ActicvSleb;
use App\Models\LogisticProvider;
use App\Models\ZonePriceSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * One place for everything the Zone Price page and the courier services share:
 *  - the list of courier rate keys (legacy direct couriers + aggregator couriers),
 *  - whether each courier's provider is integrated and switched on,
 *  - the zone list,
 *  - the rate-card maths (same formula every courier service in this panel uses).
 *
 * Rate logic (unchanged from the existing courier services):
 *   slabs = ceil(chargeable weight in grams / 500), minimum 1
 *   Prepaid:  fixed price > 0  -> fixed × slabs (final, no GST)
 *             otherwise        -> prepaid rate × slabs + 18% GST
 *   COD:      COD fixed > 0    -> COD fixed × slabs (final, no GST, no COD %)
 *             otherwise        -> (prepaid rate + COD rate) × slabs
 *                                 + COD % of order value when order value > ₹1,400
 *                                 + 18% GST on the total
 *   A seller without their own row for a courier + zone uses the default rate card.
 */
class RateCard
{
    public const GST_PERCENT = 18;
    public const SLAB_GRAMS = 500;
    public const COD_PERCENT_ABOVE = 1400;

    /** Direct (non-aggregator) rate keys the courier services read, with the provider code that runs them. */
    public const LEGACY = [
        'Delhivery'                 => ['Delhivery Surface', 'delhivery_b2c'],
        'Delhivery_Air'             => ['Delhivery Air', 'delhivery_b2c_express'],
        'Shadowfax'                 => ['Shadowfax', 'shadowfax'],
        'Ekart2KG_selloship'        => ['Ekart 2 kg (Selloship)', 'selloship'],
        'Ekart500gm'                => ['Ekart 500 g', 'ekart'],
        'DTDC_Surface_500gm'        => ['DTDC Surface 500 g', 'dtdc'],
        'DTDC_Surface_1kg'          => ['DTDC Surface 1 kg', 'dtdc'],
        'DTDC_Air'                  => ['DTDC Air', 'dtdc'],
        'tekipost_Delhivery_1_KG'   => ['Delhivery 1 kg (Tekipost)', 'tekipost'],
        'tekipost_Delhivery_5kg'    => ['Delhivery 5 kg (Tekipost)', 'tekipost'],
        'tekipost_Delhivery_10kg'   => ['Delhivery 10 kg (Tekipost)', 'tekipost'],
        'tekipost_Ekart_2_KG_Fixed' => ['Ekart 2 kg fixed (Tekipost)', 'tekipost'],
        'tekipost_Amazon_500_GM'    => ['Amazon 500 g (Tekipost)', 'tekipost'],
        'tekipost_Amazon_2_kg'      => ['Amazon 2 kg (Tekipost)', 'tekipost'],
        'Parcel_X_Delhivery'        => ['Delhivery (ParcelX)', 'parcelx'],
        'Parcel_X_Delhivery_250gm'  => ['Delhivery 250 g (ParcelX)', 'parcelx'],
        'Parcel_X_Amazon'           => ['Amazon (ParcelX)', 'parcelx'],
        'Parcel_X_Amazon_1KG'       => ['Amazon 1 kg (ParcelX)', 'parcelx'],
        'Parcel_X_Amazon_2KG'       => ['Amazon 2 kg (ParcelX)', 'parcelx'],
        'parcel_x_Xpressbee'        => ['Xpressbees (ParcelX)', 'parcelx'],
        'parcel_x_Shreemaruti'      => ['Shree Maruti (ParcelX)', 'parcelx'],
        'Shiprocket_Delhivery'      => ['Delhivery (Shiprocket)', 'shiprocket'],
        'Shiprocket_Xpressbee'      => ['Xpressbees (Shiprocket)', 'shiprocket'],
        'Shiprocket_Bluedart'       => ['Blue Dart (Shiprocket)', 'shiprocket'],
        'shiprocket_Bluedart_1kg'   => ['Blue Dart 1 kg (Shiprocket)', 'shiprocket'],
        'Ekart500gm_boxd'           => ['Ekart 500 g (Boxd)', 'boxd'],
        'boxd_bluedart_500gm'       => ['Blue Dart 500 g (Boxd)', 'boxd'],
    ];

    /** Provider codes the serviceability controller treats as the same provider. */
    private const PROVIDER_ALIASES = [
        'delhivery_b2c'         => ['delhivery_b2c', 'delhivery', 'delhivery_surface'],
        'delhivery_b2c_express' => ['delhivery_b2c_express', 'delhivery_air', 'delhivery_express'],
    ];

    /** Aggregators whose couriers are offered to every seller unless switched off for that seller. */
    public const AGGREGATORS = [
        'shipclues' => ['Shipclues', 'Shipclues_'],
        'fship'     => ['Fship', 'Fship_'],
    ];

    public static function defaultSellerId(): int
    {
        $id = config('fship.default_rate_seller_id') ?: env('RATE_CARD_DEFAULT_SELLER_ID', 14);
        return (int) $id ?: 14;
    }

    public static function normalizeZone($zone): ?string
    {
        $z = strtoupper(trim((string) $zone));
        if ($z === '') {
            return null;
        }
        // "ZONE A", "Z_A", "zone-a" -> "A"
        $z = preg_replace('/^(ZONE|Z)[\s_\-:]*/', '', $z);
        return $z !== '' ? $z : null;
    }

    /** Zones used by routes (pincode_zones) plus any zone that already has rates. */
    public static function zones(): array
    {
        $routeZones = [];
        try {
            $routeZones = DB::table('pincode_zones')->distinct()->pluck('zone')->all();
        } catch (\Throwable $e) {
        }
        $rateZones = ZonePriceSetting::query()->distinct()->pluck('zone')->all();

        $all = collect(array_merge($routeZones, $rateZones))
            ->map(fn ($z) => self::normalizeZone($z))
            ->filter()->unique()->sort()->values()->all();

        return $all ?: ['A', 'B', 'C', 'D', 'E'];
    }

    /** Zones that at least one pickup→delivery state pair maps to. */
    public static function routeZones(): array
    {
        try {
            return DB::table('pincode_zones')->distinct()->pluck('zone')
                ->map(fn ($z) => self::normalizeZone($z))->filter()->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** code => ['name' => ..., 'active' => bool] for every row in Admin > Logistics. */
    public static function providers(): array
    {
        $out = [];
        foreach (LogisticProvider::query()->get(['id', 'name', 'code', 'status']) as $p) {
            $code = strtolower(trim((string) $p->code));
            if ($code === '') {
                continue;
            }
            $status = strtolower((string) $p->status);
            $out[$code] = ['name' => $p->name, 'active' => in_array($status, ['active', '1'], true), 'id' => $p->id];
        }
        return $out;
    }

    private static function providerFor(string $code, array $providers): ?array
    {
        foreach (self::PROVIDER_ALIASES[$code] ?? [$code] as $alias) {
            if (isset($providers[$alias])) {
                return $providers[$alias] + ['code' => $alias];
            }
        }
        return null;
    }

    /**
     * Every courier the Zone Price page can price.
     * Each: key, label, provider_code, provider_name, integrated (provider row exists),
     *       live (provider switched on), mode ('optin' | 'optout'), fallback ('none' | 'markup').
     */
    public static function couriers(bool $withRemote = true): array
    {
        $providers = self::providers();
        $list = [];

        foreach (self::LEGACY as $key => [$label, $code]) {
            $p = self::providerFor($code, $providers);
            $list[$key] = [
                'key' => $key,
                'label' => $label,
                'provider_code' => $code,
                'provider_name' => $p['name'] ?? ucfirst(str_replace('_', ' ', $code)),
                'integrated' => (bool) $p,
                'live' => (bool) ($p['active'] ?? false),
                'mode' => 'optin',
                'fallback' => 'none',
            ];
        }

        foreach (self::AGGREGATORS as $code => [$name, $prefix]) {
            $p = $providers[$code] ?? null;
            $keys = $withRemote ? self::aggregatorKeys($code) : [];
            // Keys that already have rates or switches stay listed even if the API is unreachable.
            $known = ZonePriceSetting::where('LogisticProvider', 'like', $prefix . '%')->distinct()->pluck('LogisticProvider')
                ->merge(ActicvSleb::where('LogisticProvider', 'like', $prefix . '%')->distinct()->pluck('LogisticProvider'))
                ->all();
            foreach ($known as $k) {
                $keys[$k] = $keys[$k] ?? trim(str_replace('_', ' ', substr($k, strlen($prefix))));
            }
            foreach ($keys as $key => $label) {
                $list[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'provider_code' => $code,
                    'provider_name' => $p['name'] ?? $name,
                    'integrated' => (bool) $p,
                    'live' => (bool) ($p['active'] ?? false),
                    'mode' => 'optout',
                    'fallback' => 'markup',
                ];
            }
        }

        // Any other key that already has rates (old data) so nothing is hidden from the admin.
        foreach (ZonePriceSetting::distinct()->pluck('LogisticProvider') as $key) {
            if ($key && !isset($list[$key])) {
                $list[$key] = [
                    'key' => $key, 'label' => str_replace('_', ' ', $key), 'provider_code' => 'other',
                    'provider_name' => 'Other', 'integrated' => false, 'live' => false,
                    'mode' => 'optin', 'fallback' => 'none',
                ];
            }
        }

        // Live couriers first, then integrated-but-off, then the rest; alphabetical inside each group.
        uasort($list, function ($a, $b) {
            $rank = fn ($c) => $c['live'] ? 0 : ($c['integrated'] ? 1 : 2);
            return [$rank($a), $a['provider_name'], $a['label']] <=> [$rank($b), $b['provider_name'], $b['label']];
        });

        return $list;
    }

    /** key => label for the couriers an aggregator account currently offers (cached). */
    public static function aggregatorKeys(string $code): array
    {
        $cacheKey = 'ratecard_keys_' . $code;
        if (is_array($hit = Cache::get($cacheKey))) {
            return $hit;
        }
        $keys = [];
        try {
            if ($code === 'shipclues' && class_exists(\App\Services\ShipcluesService::class)
                && method_exists(\App\Services\ShipcluesService::class, 'rateCardCouriers')) {
                $keys = \App\Services\ShipcluesService::rateCardCouriers();
            }
            if ($code === 'fship' && class_exists(\App\Services\FshipService::class)
                && method_exists(\App\Services\FshipService::class, 'rateCardKeys')) {
                foreach (\App\Services\FshipService::rateCardKeys() as $k) {
                    $keys[$k] = trim(str_replace('_', ' ', substr($k, strlen('Fship_'))));
                }
            }
        } catch (\Throwable $e) {
            $keys = [];
        }
        // Keep a good list for 6 hours; retry an empty one after 10 minutes.
        Cache::put($cacheKey, $keys, $keys ? now()->addHours(6) : now()->addMinutes(10));
        return $keys;
    }

    /** Seller's own row, else the default rate card row. */
    public static function find(int $sellerId, ?string $zone, string $key): ?ZonePriceSetting
    {
        $zone = self::normalizeZone($zone);
        if (!$zone) {
            return null;
        }
        return ZonePriceSetting::where(['seller_id' => $sellerId, 'zone' => $zone, 'LogisticProvider' => $key, 'status' => 1])->first()
            ?? ZonePriceSetting::where(['seller_id' => self::defaultSellerId(), 'zone' => $zone, 'LogisticProvider' => $key, 'status' => 1])->first();
    }

    /** Price for one shipment. $card can be a model or an array with the rate fields. */
    public static function price($card, float $weightGrams, bool $cod, float $orderAmount): ?array
    {
        if (!$card) {
            return null;
        }
        $g = fn ($f) => (float) (is_array($card) ? ($card[$f] ?? 0) : ($card->{$f} ?? 0));
        $slabs = max(1, (int) ceil($weightGrams / self::SLAB_GRAMS));
        $gst = 1 + self::GST_PERCENT / 100;

        if ($cod) {
            if ($g('cod_fix_price') > 0) {
                $t = round($g('cod_fix_price') * $slabs, 2);
                return ['courierCharge' => $t, 'freightCharges' => $t, 'codCharge' => 0.0, 'slabs' => $slabs];
            }
            $freight = ($g('cod_price') + $g('prepaid_price')) * $slabs;
            $codCharge = $orderAmount > self::COD_PERCENT_ABOVE ? $orderAmount * $g('cod_charge_parsent') / 100 : 0;
            return [
                'courierCharge' => round(($freight + $codCharge) * $gst, 2),
                'freightCharges' => round($freight, 2),
                'codCharge' => round($codCharge, 2),
                'slabs' => $slabs,
            ];
        }

        if ($g('prepaid_fix_price') > 0) {
            $t = round($g('prepaid_fix_price') * $slabs, 2);
            return ['courierCharge' => $t, 'freightCharges' => $t, 'codCharge' => 0.0, 'slabs' => $slabs];
        }
        $freight = $g('prepaid_price') * $slabs;
        return ['courierCharge' => round($freight * $gst, 2), 'freightCharges' => round($freight, 2), 'codCharge' => 0.0, 'slabs' => $slabs];
    }

    /** Aggregator couriers: hidden only when switched off for this seller. */
    public static function hiddenFor(int $sellerId, string $key, ?string $aggregatorName = null): bool
    {
        try {
            $keys = array_filter([$key, $aggregatorName, $aggregatorName ? strtolower($aggregatorName) : null]);
            return ActicvSleb::where('seller_id', $sellerId)
                ->whereIn('LogisticProvider', $keys)
                ->whereIn('status', [0, '0'])
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function zoneForStates(?string $pickupState, ?string $deliveryState): ?string
    {
        if (!$pickupState || !$deliveryState) {
            return null;
        }
        try {
            $row = DB::table('pincode_zones')
                ->whereRaw('LOWER(pickup_state) = ?', [strtolower(trim($pickupState))])
                ->whereRaw('LOWER(deliver_state) = ?', [strtolower(trim($deliveryState))])
                ->first();
            return $row ? self::normalizeZone($row->zone) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
