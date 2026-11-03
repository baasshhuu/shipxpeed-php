<?php

namespace App\Support\ApiV1;

use App\Services\BigshipService;
use App\Support\CourierAccess;
use App\Support\CourierBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Courier catalog for the API and the public API docs.
 *
 * Built from the panel's own courier switches every time it is read, so a courier the admin
 * approves on the Courier Status page (or a B2B courier approved on the Bigship page) shows up in
 * GET /api/v1/couriers and on /developers automatically - no code or docs change needed.
 *
 *   B2C      : CourierAccess groups (Delhivery direct + every aggregator courier), ON for the client
 *   Reverse  : Delhivery reverse pickup (when Delhivery is ON for the client)
 *   B2B      : Bigship couriers the admin approved (when B2B is enabled)
 *
 * Partner/aggregator names never appear; couriers are shown by their real brand.
 */
class Couriers
{
    /** Stable public code for a courier name, e.g. "Bluedart Surface Prime" -> "bluedart-surface-prime". */
    public static function code(string $name): string
    {
        return Str::slug(CourierBrand::cleanName($name, $name)) ?: 'courier';
    }

    public static function mode(string $name): string
    {
        return preg_match('/\b(air|express|priority|premium)\b/i', $name) ? 'air' : 'surface';
    }

    public static function brand(string $name): string
    {
        $n = strtolower($name);
        foreach (['delhivery' => 'Delhivery', 'bluedart' => 'Blue Dart', 'blue dart' => 'Blue Dart', 'xpressbee' => 'Xpressbees',
                  'ekart' => 'Ekart', 'amazon' => 'Amazon Shipping', 'dtdc' => 'DTDC', 'shadowfax' => 'Shadowfax',
                  'ecom' => 'Ecom Express', 'gati' => 'Gati', 'safexpress' => 'Safexpress', 'tci' => 'TCI Express',
                  'v-xpress' => 'V-Xpress', 'rivigo' => 'Rivigo', 'smartr' => 'Smartr', 'dhl' => 'DHL', 'aramex' => 'Aramex',
                  'professional' => 'The Professional Couriers', 'shree maruti' => 'Shree Maruti', 'ondot' => 'Ondot'] as $needle => $brand) {
            if (str_contains($n, $needle)) {
                return $brand;
            }
        }
        return trim(explode(' ', CourierBrand::cleanName($name, $name))[0]) ?: 'Courier';
    }

    private static function row(string $type, string $name, ?string $key, bool $live, array $extra = []): array
    {
        $clean = CourierBrand::cleanName($name, $name);
        return array_merge([
            'courier_code' => self::code($clean),
            'name'         => $clean,
            'brand'        => self::brand($clean),
            'type'         => $type,
            'mode'         => self::mode($clean),
            'logo'         => CourierBrand::logoUrl($clean),
            'live'         => $live,
        ], $extra, ['_key' => $key]);
    }

    /**
     * Couriers for one client (sellerId > 0: only couriers ON for them; 0: couriers live by default, for docs).
     * $type: b2c | reverse | b2b | null (all)
     */
    public static function forSeller(int $sellerId, ?string $type = null): array
    {
        $out = [];

        if ($type === null || $type === 'b2c' || $type === 'reverse') {
            foreach (self::b2cRaw() as $key => $label) {
                $on = $sellerId > 0 ? CourierAccess::isOn($sellerId, $key, self::aggregatorName($key)) : CourierAccess::isDefaultOn($key);
                if (!$on) {
                    continue;
                }
                if ($type !== 'reverse') {
                    $out[] = self::row('b2c', $label, $key, true, ['cod' => true, 'prepaid' => true]);
                }
                if ($key === 'Delhivery' && ($type === null || $type === 'reverse')) {
                    $out[] = self::row('reverse', 'Delhivery Reverse', 'delhivery_reverse', true, ['cod' => false, 'prepaid' => true]);
                }
            }
        }

        if ($type === null || $type === 'b2b') {
            foreach (self::b2bRaw() as $c) {
                $out[] = self::row('b2b', $c->name, 'b2b_' . $c->courier_id, true, [
                    'cod' => true, 'prepaid' => true, 'b2b_courier_id' => (string) $c->courier_id,
                ]);
            }
        }

        // one entry per code + type
        $seen = [];
        $out = array_values(array_filter($out, function ($r) use (&$seen) {
            $k = $r['type'] . '|' . $r['courier_code'];
            if (isset($seen[$k])) {
                return false;
            }
            return $seen[$k] = true;
        }));

        usort($out, fn ($a, $b) => [$a['type'], $a['name']] <=> [$b['type'], $b['name']]);
        return $out;
    }

    /** Aggregator name for a rate key ("Shipclues_Bluedart..." -> "Shipclues"), from the panel's aggregator registry. */
    private static function aggregatorName(string $key): ?string
    {
        foreach (\App\Support\RateCard::AGGREGATORS as [$name, $prefix]) {
            if (str_starts_with($key, $prefix)) {
                return $name;
            }
        }
        return null;
    }

    /** Public shape (internal key removed). */
    public static function present(array $rows): array
    {
        return array_map(function ($r) {
            unset($r['_key']);
            return $r;
        }, $rows);
    }

    /** key => label of every integrated B2C courier that is not removed. */
    private static function b2cRaw(): array
    {
        $all = [];
        try {
            foreach (CourierAccess::groups() as $g) {
                foreach ($g['couriers'] as $key => $c) {
                    $all[$key] = $c['label'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ApiV1 couriers: groups failed: ' . $e->getMessage());
            $all = CourierAccess::DIRECT;
        }
        return $all;
    }

    private static function b2bRaw(): array
    {
        try {
            if (!Schema::hasTable('bigship_couriers') || !app(BigshipService::class)->enabledForSellers()) {
                return [];
            }
            return DB::table('bigship_couriers')->where('approved', true)->orderBy('name')->get(['courier_id', 'name'])->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
