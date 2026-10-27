<?php

namespace App\Support;

use App\Models\ActicvSleb;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which couriers are integrated and working, and whether each is ON for a seller.
 *
 * Rule for every courier (Delhivery, Shipclues couriers, Fship couriers):
 *   1. The seller has their own switch row in zone_price (ActicvSleb)  -> that row decides.
 *   2. Otherwise the courier's DEFAULT decides (table courier_defaults, set on the
 *      Courier Status page with "All Sellers (default)" selected).
 *   3. A courier with no default saved yet is ON (new integrations go live for everyone).
 *
 * Only couriers listed here appear on the admin Courier Status page.
 */
class CourierAccess
{
    public const TABLE = 'courier_defaults';

    /** Direct integrations on our own accounts: rate key => label. */
    public const DIRECT = [
        'Delhivery'     => 'Delhivery Surface',
        'Delhivery_Air' => 'Delhivery Air',
    ];

    /** Groups shown on the page, in order: code => [title, rate-key prefix]. */
    public const GROUPS = [
        'delhivery' => ['Delhivery (direct)', null],
        'shipclues' => ['Shipclues couriers', 'Shipclues_'],
        'fship'     => ['Fship couriers', 'Fship_'],
    ];

    /**
     * Integrated couriers grouped for the page:
     * [group => ['title' => ..., 'configured' => bool, 'couriers' => [key => ['label','logo','default']]]]
     */
    public static function groups(): array
    {
        $defaults = self::defaults();
        $out = [];

        foreach (self::GROUPS as $code => [$title, $prefix]) {
            $keys = [];
            $configured = false;

            if ($code === 'delhivery') {
                $configured = true;
                $keys = self::DIRECT;
            } elseif ($code === 'shipclues') {
                $configured = class_exists(\App\Services\ShipcluesService::class)
                    && (new \App\Services\ShipcluesService())->enabled();
                $keys = $configured ? RateCard::aggregatorKeys('shipclues') : [];
            } elseif ($code === 'fship') {
                $configured = class_exists(\App\Services\FshipService::class)
                    && (new \App\Services\FshipService())->isConfigured();
                $keys = $configured ? RateCard::aggregatorKeys('fship') : [];
            }

            // Couriers already switched or defaulted stay listed if the API is briefly unreachable.
            if ($prefix && $configured) {
                foreach (self::knownKeys($prefix) as $k) {
                    $keys[$k] = $keys[$k] ?? trim(str_replace('_', ' ', substr($k, strlen($prefix))));
                }
            }

            if (!$keys && $code !== 'delhivery' && !$configured) {
                continue;
            }

            asort($keys, SORT_NATURAL | SORT_FLAG_CASE);
            $couriers = [];
            foreach ($keys as $key => $label) {
                $couriers[$key] = [
                    'label'   => $label,
                    'logo'    => CourierBrand::logo($label) ?? 'brands/image.png',
                    'default' => $defaults[$key] ?? true,
                ];
            }
            $out[$code] = ['title' => $title, 'configured' => $configured, 'couriers' => $couriers];
        }

        return $out;
    }

    /** Flat list of every integrated courier key. */
    public static function keys(): array
    {
        $all = [];
        foreach (self::groups() as $g) {
            $all = array_merge($all, array_keys($g['couriers']));
        }
        return $all;
    }

    /** key => bool for every courier that has a saved default. */
    public static function defaults(): array
    {
        return Cache::remember('courier_defaults_map', now()->addMinutes(10), function () {
            try {
                if (!Schema::hasTable(self::TABLE)) {
                    return [];
                }
                return DB::table(self::TABLE)->pluck('is_default', 'courier_key')
                    ->map(fn ($v) => (int) $v === 1)->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public static function isDefaultOn(string $key): bool
    {
        return self::defaults()[$key] ?? true;
    }

    public static function setDefault(string $key, bool $on): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['courier_key' => $key],
            ['is_default' => $on ? 1 : 0, 'updated_at' => now(), 'created_at' => now()]
        );
        Cache::forget('courier_defaults_map');
    }

    /** Seller's own switch for a courier: true / false, or null when it follows the default. */
    public static function sellerOverride(int $sellerId, string $key): ?bool
    {
        $row = ActicvSleb::where('seller_id', $sellerId)->where('LogisticProvider', $key)->latest('id')->first();
        return $row ? ((string) $row->status === '1') : null;
    }

    /** key => true/false for every courier this seller has their own switch for. */
    public static function sellerOverrides(int $sellerId): array
    {
        return ActicvSleb::where('seller_id', $sellerId)->orderBy('id')->get(['LogisticProvider', 'status'])
            ->mapWithKeys(fn ($r) => [$r->LogisticProvider => (string) $r->status === '1'])->all();
    }

    /** Is this courier ON for this seller? Used by the courier services when listing rates. */
    public static function isOn(int $sellerId, string $key, ?string $aggregatorName = null): bool
    {
        try {
            if ($sellerId > 0) {
                // Whole aggregator switched off for this seller (older switch, still honoured).
                if ($aggregatorName && ActicvSleb::where('seller_id', $sellerId)
                        ->whereIn('LogisticProvider', [$aggregatorName, strtolower($aggregatorName)])
                        ->whereIn('status', [0, '0'])->exists()) {
                    return false;
                }
                $own = self::sellerOverride($sellerId, $key);
                if ($own !== null) {
                    return $own;
                }
            }
            return self::isDefaultOn($key);
        } catch (\Throwable $e) {
            return true;
        }
    }

    private static function knownKeys(string $prefix): array
    {
        $keys = ActicvSleb::where('LogisticProvider', 'like', $prefix . '%')->distinct()->pluck('LogisticProvider')->all();
        try {
            if (Schema::hasTable(self::TABLE)) {
                $keys = array_merge($keys, DB::table(self::TABLE)->where('courier_key', 'like', $prefix . '%')->pluck('courier_key')->all());
            }
        } catch (\Throwable $e) {
        }
        return array_values(array_unique($keys));
    }
}
