<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Admin dashboard: B2C vs B2B split — order counts, tonnage, most used couriers,
 * and the accounts (sellers) shipping the most B2B.
 *
 * Every block is defensive: the live DB has hand-made tables, so a missing table or
 * column returns zeros instead of breaking the dashboard.
 */
class AdminShipmentStats
{
    public static function build(): array
    {
        $out = [
            'b2c' => ['orders' => 0, 'shipped' => 0, 'kg' => 0.0, 'top_couriers' => []],
            'b2b' => ['orders' => 0, 'shipped' => 0, 'boxes' => 0, 'kg' => 0.0, 'top_couriers' => [], 'by_status' => [], 'revenue' => 0.0],
            'top_couriers' => [],
            'top_b2b_accounts' => [],
        ];

        try {
            $out['b2c'] = self::b2c();
        } catch (\Throwable $e) {
            Log::warning('Dashboard B2C stats failed', ['error' => $e->getMessage()]);
        }

        try {
            $out['b2b'] = self::b2b();
            $out['top_b2b_accounts'] = self::topB2bAccounts();
        } catch (\Throwable $e) {
            Log::warning('Dashboard B2B stats failed', ['error' => $e->getMessage()]);
        }

        // Combined courier ranking (B2C + B2B) by shipments
        $merged = [];
        foreach ([$out['b2c']['top_couriers'], $out['b2b']['top_couriers']] as $list) {
            foreach ($list as $c) {
                $merged[$c['name']] = ($merged[$c['name']] ?? 0) + $c['count'];
            }
        }
        arsort($merged);
        foreach (array_slice($merged, 0, 5, true) as $name => $count) {
            $out['top_couriers'][] = ['name' => $name, 'count' => $count];
        }

        $out['total_orders']  = $out['b2c']['orders'] + $out['b2b']['orders'];
        $out['total_shipped'] = $out['b2c']['shipped'] + $out['b2b']['shipped'];
        $out['total_kg']      = $out['b2c']['kg'] + $out['b2b']['kg'];

        return $out;
    }

    /** Tonnes with 2 decimals, but show kg when under one tonne so small numbers aren't 0.00. */
    public static function tonnage(float $kg): string
    {
        if ($kg < 1000) {
            return number_format($kg, 1) . ' kg';
        }
        return number_format($kg / 1000, 2) . ' t';
    }

    private static function b2c(): array
    {
        $res = ['orders' => 0, 'shipped' => 0, 'kg' => 0.0, 'top_couriers' => []];
        if (!Schema::hasTable('orders')) {
            return $res;
        }
        $cols = Schema::getColumnListing('orders');
        $has = fn ($c) => in_array($c, $cols, true);

        $res['orders'] = (int) DB::table('orders')->count();

        // A shipment = has an AWB and is not cancelled
        $shipped = DB::table('orders')->whereNotNull('awb_number')->where('awb_number', '!=', '');
        if ($has('status')) {
            $shipped->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'cancelled'));
        }
        $res['shipped'] = (int) (clone $shipped)->count();

        // package_weight is stored in kg by some flows and grams by others (>= 50 treated as grams,
        // same rule the shipment report uses)
        if ($has('package_weight')) {
            $res['kg'] = round((float) (clone $shipped)
                ->selectRaw('SUM(CASE WHEN CAST(package_weight AS DECIMAL(12,3)) >= 50 THEN CAST(package_weight AS DECIMAL(12,3)) / 1000 ELSE CAST(package_weight AS DECIMAL(12,3)) END) AS kg')
                ->value('kg'), 2);
        }

        $nameCols = array_values(array_filter(['courier_name', 'zone_courier_name', 'all_courier_name', 'courier_id'], $has));
        if ($nameCols) {
            $expr = count($nameCols) > 1
                ? 'COALESCE(' . implode(', ', array_map(fn ($c) => "NULLIF($c, '')", $nameCols)) . ')'
                : "NULLIF({$nameCols[0]}, '')";
            $rows = (clone $shipped)
                ->selectRaw("$expr AS cname, COUNT(*) AS cnt")
                ->groupBy('cname')
                ->havingRaw('cname IS NOT NULL')
                ->orderByDesc('cnt')
                ->limit(5)
                ->get();
            foreach ($rows as $r) {
                $res['top_couriers'][] = ['name' => (string) $r->cname, 'count' => (int) $r->cnt];
            }
        }

        return $res;
    }

    private static function b2b(): array
    {
        $res = ['orders' => 0, 'shipped' => 0, 'boxes' => 0, 'kg' => 0.0, 'top_couriers' => [], 'by_status' => [], 'revenue' => 0.0];
        if (!Schema::hasTable('b2b_orders')) {
            return $res;
        }

        $res['orders'] = (int) DB::table('b2b_orders')->where('status', '!=', 'draft')->count();

        $shipped = DB::table('b2b_orders')->whereNotIn('status', ['draft', 'cancelled']);
        $agg = (clone $shipped)->selectRaw(
            'COUNT(*) AS cnt, COALESCE(SUM(total_boxes),0) AS boxes, ' .
            'COALESCE(SUM(COALESCE(charged_weight, total_weight)),0) AS kg, COALESCE(SUM(seller_charge),0) AS revenue'
        )->first();
        $res['shipped'] = (int) $agg->cnt;
        $res['boxes']   = (int) $agg->boxes;
        $res['kg']      = round((float) $agg->kg, 2);
        $res['revenue'] = round((float) $agg->revenue, 2);

        $res['by_status'] = DB::table('b2b_orders')
            ->selectRaw('status, COUNT(*) AS cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();

        $rows = (clone $shipped)
            ->selectRaw("COALESCE(NULLIF(courier_name, ''), 'Unassigned') AS cname, COUNT(*) AS cnt")
            ->groupBy('cname')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();
        foreach ($rows as $r) {
            $res['top_couriers'][] = ['name' => (string) $r->cname, 'count' => (int) $r->cnt];
        }

        return $res;
    }

    private static function topB2bAccounts(): array
    {
        if (!Schema::hasTable('b2b_orders')) {
            return [];
        }

        $rows = DB::table('b2b_orders')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw('seller_id, COUNT(*) AS cnt, COALESCE(SUM(COALESCE(charged_weight, total_weight)),0) AS kg, ' .
                'COALESCE(SUM(total_boxes),0) AS boxes, COALESCE(SUM(seller_charge),0) AS spend, MAX(booked_at) AS last_at')
            ->groupBy('seller_id')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $sellers = Schema::hasTable('seller_lists')
            ? DB::table('seller_lists')->whereIn('id', $rows->pluck('seller_id'))->get(['id', 'name', 'email'])->keyBy('id')
            : collect();

        return $rows->map(function ($r) use ($sellers) {
            $s = $sellers->get($r->seller_id);
            return [
                'seller_id' => (int) $r->seller_id,
                'name'      => $s->name ?? ('Seller #' . $r->seller_id),
                'email'     => $s->email ?? '',
                'orders'    => (int) $r->cnt,
                'kg'        => round((float) $r->kg, 2),
                'boxes'     => (int) $r->boxes,
                'spend'     => round((float) $r->spend, 2),
                'last_at'   => $r->last_at,
            ];
        })->all();
    }
}
