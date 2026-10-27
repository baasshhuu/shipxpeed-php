<?php

namespace App\Support\Xpeed;

use App\Models\Order;
use App\Support\CourierBrand;
use App\Support\RateCard;
use Illuminate\Support\Facades\DB;

/**
 * Seller courier preferences, applied to the courier list the seller sees when shipping:
 *   1. Pincode block list (all couriers) and per-courier pincode blocks -> those couriers/orders hidden
 *   2. Courier rules (first matching rule by priority) -> that courier moves to the top as "Your rule: …"
 *   3. Otherwise the priority mode orders the list: cheapest | fastest | custom brand order
 * Couriers are matched by brand (Delhivery, Xpressbees, …) found in the seller-facing courier name,
 * so the rules keep working when an aggregator's service names change.
 */
class CourierPreferences
{
    public const BRANDS = ['Delhivery', 'Xpressbees', 'Ekart', 'Bluedart', 'Amazon', 'DTDC', 'Shadowfax', 'Ecom Express', 'India Post', 'Shree Maruti', 'Smartr', 'Gati', 'Trackon', 'Professional'];

    private const ALIASES = [
        'xpressbee' => 'Xpressbees', 'xpress bees' => 'Xpressbees', 'blue dart' => 'Bluedart', 'bluedart' => 'Bluedart',
        'flipkart' => 'Ekart', 'ekart' => 'Ekart', 'amazon' => 'Amazon', 'atsl' => 'Amazon', 'dtdc' => 'DTDC',
        'delhivery' => 'Delhivery', 'deliveri' => 'Delhivery', 'shadowfax' => 'Shadowfax', 'ecom' => 'Ecom Express',
        'india post' => 'India Post', 'speed post' => 'India Post', 'shree maruti' => 'Shree Maruti', 'shreemaruti' => 'Shree Maruti',
        'smartr' => 'Smartr', 'gati' => 'Gati', 'trackon' => 'Trackon', 'professional' => 'Professional',
    ];

    public static function brandOf(string $label): ?string
    {
        $l = strtolower($label);
        foreach (self::ALIASES as $needle => $brand) {
            if (str_contains($l, $needle)) {
                return $brand;
            }
        }
        return null;
    }

    public static function isAir(string $label): bool
    {
        return (bool) preg_match('/\b(air|express|prime|priority)\b/i', $label);
    }

    public static function prefs(int $sellerId): object
    {
        $row = Kit::ready('xp_courier_prefs') ? DB::table('xp_courier_prefs')->where('seller_id', $sellerId)->first() : null;
        return (object) [
            'mode'          => $row->mode ?? 'cheapest',
            'brand_order'   => $row ? (json_decode((string) $row->brand_order, true) ?: []) : [],
            'blocked'       => $row ? Kit::pincodes($row->blocked_pincodes) : [],
            'blocked_raw'   => $row->blocked_pincodes ?? '',
            'courier_blocks'=> $row ? (json_decode((string) $row->courier_blocks, true) ?: []) : [],
        ];
    }

    public static function rules(int $sellerId)
    {
        return Kit::ready('xp_courier_rules')
            ? DB::table('xp_courier_rules')->where('seller_id', $sellerId)->orderBy('priority')->orderBy('id')->get()
            : collect();
    }

    /** Does a rule match this order? */
    public static function ruleMatches(object $rule, Order $order): bool
    {
        if (!$rule->active) {
            return false;
        }
        $cod = strtolower((string) $order->payment_type) === 'cod';
        if ($rule->payment === 'cod' && !$cod) {
            return false;
        }
        if ($rule->payment === 'prepaid' && $cod) {
            return false;
        }
        $value = (float) $order->order_amount;
        if ($rule->min_value !== null && $value < (float) $rule->min_value) {
            return false;
        }
        if ($rule->max_value !== null && $value > (float) $rule->max_value) {
            return false;
        }
        $w = self::weightGrams($order);
        if ($rule->min_weight_g !== null && $w < (int) $rule->min_weight_g) {
            return false;
        }
        if ($rule->max_weight_g !== null && $w > (int) $rule->max_weight_g) {
            return false;
        }
        if ($rule->pincodes) {
            $pin = (string) (Kit::consignee($order)['pincode'] ?? '');
            if (!in_array($pin, Kit::pincodes($rule->pincodes), true)) {
                return false;
            }
        }
        if ($rule->zones) {
            $zones = array_map('strtoupper', Kit::tokens($rule->zones));
            try {
                $z = RateCard::zoneForOrder($order)['zone'] ?? null;
            } catch (\Throwable $e) {
                $z = $order->zone ?? null;
            }
            if (!$z || !in_array(strtoupper((string) $z), $zones, true)) {
                return false;
            }
        }
        return true;
    }

    public static function weightGrams(Order $order): float
    {
        $w = (float) $order->package_weight;
        return $w > 0 && $w < 10 ? $w * 1000 : $w; // same convention as weight disputes: < 10 means kg
    }

    /** First rule matching every order (bulk) or the single order. */
    public static function matchingRule(int $sellerId, iterable $orders): ?object
    {
        $orders = collect($orders)->filter();
        if ($orders->isEmpty()) {
            return null;
        }
        foreach (self::rules($sellerId) as $rule) {
            if ($orders->every(fn ($o) => self::ruleMatches($rule, $o))) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Re-order / filter the courier list (array of provider arrays, as built by CourierController).
     * Never throws: on any problem the list comes back untouched.
     */
    public static function apply(int $sellerId, iterable $orders, array $providers): array
    {
        try {
            if (!$providers || !Kit::ready('xp_courier_prefs')) {
                return $providers;
            }
            $orders = collect($orders)->filter()->values();
            $prefs = self::prefs($sellerId);
            $pins = $orders->map(fn ($o) => (string) (Kit::consignee($o)['pincode'] ?? ''))->filter()->unique()->all();

            // seller-wide block list: nothing should be offered for a blocked pincode
            if ($prefs->blocked && ($hit = array_intersect($pins, $prefs->blocked))) {
                try {
                    session()->now('error', 'Pincode ' . implode(', ', $hit) . ' is on your block list (Control Center › Courier preferences), so no courier is offered.');
                } catch (\Throwable $e) {
                }
                return [];
            }

            $rows = [];
            foreach (array_values($providers) as $i => $p) {
                [$label] = CourierBrand::forSeller($p);
                $brand = self::brandOf($label);
                if ($brand && !empty($prefs->courier_blocks[$brand]) && array_intersect($pins, (array) $prefs->courier_blocks[$brand])) {
                    continue; // this courier is blocked for one of these pincodes
                }
                $days = $p['estimatedDays'] ?? $p['etd'] ?? null;
                $rows[] = ['p' => $p, 'i' => $i, 'label' => $label, 'brand' => $brand,
                    'price' => (float) ($p['courierCharge'] ?? 0), 'air' => self::isAir($label),
                    'days' => is_numeric($days) ? (float) $days : null];
            }

            $order = array_flip(array_values($prefs->brand_order));
            usort($rows, function ($a, $b) use ($prefs, $order) {
                if ($prefs->mode === 'fastest') {
                    $da = $a['days'] ?? ($a['air'] ? 2 : 5);
                    $db = $b['days'] ?? ($b['air'] ? 2 : 5);
                    return [$da, $a['price'], $a['i']] <=> [$db, $b['price'], $b['i']];
                }
                if ($prefs->mode === 'custom' && $order) {
                    $ra = $order[$a['brand']] ?? 999;
                    $rb = $order[$b['brand']] ?? 999;
                    return [$ra, $a['price'], $a['i']] <=> [$rb, $b['price'], $b['i']];
                }
                return [$a['price'], $a['i']] <=> [$b['price'], $b['i']];
            });

            $badge = ['cheapest' => 'Cheapest', 'fastest' => 'Fastest', 'custom' => 'Your priority #1'][$prefs->mode] ?? 'Recommended';

            if ($rule = self::matchingRule($sellerId, $orders)) {
                $hit = null;
                foreach ($rows as $k => $r) {
                    if ($r['brand'] === $rule->courier_brand
                        && ($rule->courier_mode === 'any' || ($rule->courier_mode === 'air') === $r['air'])) {
                        $hit = $k;
                        break;
                    }
                }
                if ($hit !== null) {
                    $top = $rows[$hit];
                    unset($rows[$hit]);
                    array_unshift($rows, $top);
                    $badge = 'Your rule: ' . mb_strimwidth($rule->name, 0, 28, '…');
                }
            }

            $out = [];
            foreach (array_values($rows) as $k => $r) {
                $p = $r['p'];
                if ($k === 0) {
                    $p['xp_badge'] = $badge;
                }
                $out[] = $p;
            }
            return $out;
        } catch (\Throwable $e) {
            \Log::warning('CourierPreferences apply failed: ' . $e->getMessage());
            return $providers;
        }
    }

    /** Brands that make sense for this seller (integrated couriers they can use) + the standard list. */
    public static function brandsFor(int $sellerId): array
    {
        $brands = [];
        try {
            foreach (\App\Support\CourierAccess::groups() as $g) {
                foreach ($g['couriers'] as $key => $c) {
                    if (\App\Support\CourierAccess::isOn($sellerId, $key) && ($b = self::brandOf($c['label']))) {
                        $brands[$b] = true;
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        $live = array_keys($brands);
        return array_values(array_unique(array_merge($live, self::BRANDS)));
    }
}
