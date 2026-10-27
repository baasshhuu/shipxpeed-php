<?php

namespace App\Support\Xpeed;

use App\Models\Order;
use App\Models\Recharge;
use App\Support\WeightDisputeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared helpers for the Seller Control Suite: admin settings, wallet entries,
 * order lookups and status buckets. Everything here is defensive about columns
 * that may not exist on older databases.
 */
class Kit
{
    /** Admin-editable settings with their defaults. */
    public const DEFAULTS = [
        'protection_enabled'      => '0',   // off until admin opens it with final pricing
        'protection_fee_pct'      => '0.50',   // % of declared order value
        'protection_min_fee'      => '15',     // ₹ per shipment
        'protection_min_value'    => '2500',   // only orders at/above this value are protected
        'protection_max_cover'    => '50000',  // highest payout per shipment
        'early_cod_enabled'       => '0',
        'early_cod_d2_pct'        => '1.50',   // fee as % of the COD amount remitted early
        'early_cod_d3_pct'        => '1.00',
        'early_cod_d4_pct'        => '0.75',
        'claim_window_days'       => '30',
        'ndr_seller_actions'      => '1',
    ];

    public static function setting(string $key): string
    {
        $all = Cache::remember('xp_settings_all', now()->addMinutes(5), function () {
            try {
                return Schema::hasTable('xp_settings') ? DB::table('xp_settings')->pluck('value', 'key')->all() : [];
            } catch (\Throwable $e) {
                return [];
            }
        });
        return (string) ($all[$key] ?? self::DEFAULTS[$key] ?? '');
    }

    public static function num(string $key): float
    {
        return (float) self::setting($key);
    }

    public static function saveSettings(array $values): void
    {
        foreach ($values as $k => $v) {
            if (!array_key_exists($k, self::DEFAULTS)) {
                continue;
            }
            DB::table('xp_settings')->updateOrInsert(['key' => $k], ['value' => (string) $v, 'updated_at' => now(), 'created_at' => now()]);
        }
        Cache::forget('xp_settings_all');
    }

    public static function ready(string ...$tables): bool
    {
        try {
            foreach ($tables as $t) {
                if (!Schema::hasTable($t)) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ---------------------------------------------------------------- wallet

    public static function balance(int $sellerId): float
    {
        $credit = Recharge::where('seller_id', $sellerId)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerId)->where('type', 'Debit')->sum('amount');
        return round((float) $credit - (float) $debit, 2);
    }

    public static function debit(int $sellerId, float $amount, string $description): Recharge
    {
        return Recharge::create([
            'seller_id' => $sellerId, 'type' => 'Debit', 'amount' => round($amount, 2),
            'status' => 1, 'description' => mb_substr($description, 0, 250),
        ]);
    }

    public static function credit(int $sellerId, float $amount, string $description): Recharge
    {
        return Recharge::create([
            'seller_id' => $sellerId, 'type' => 'Credit', 'amount' => round($amount, 2),
            'status' => 1, 'description' => mb_substr($description, 0, 250),
        ]);
    }

    // ---------------------------------------------------------------- orders

    /** Shipment status bucket from whatever the courier sync wrote. */
    public static function bucket($order): string
    {
        $os = strtolower(trim((string) ($order->order_status ?? '')));
        if ($os === 'cancelled' || $os === 'canceled') {
            return 'cancelled';
        }
        $s = strtolower(str_replace(['-', ' '], '_', trim((string) ($order->shipping_status ?? ''))));
        return match (true) {
            $s === '' => empty($order->awb_number) ? 'new' : 'booked',
            str_contains($s, 'cancel') => 'cancelled',
            str_starts_with($s, 'rto') || str_contains($s, 'return') => 'rto',
            $s === 'ndr' || str_contains($s, 'undeliver') || str_contains($s, 'failed') => 'ndr',
            str_contains($s, 'out_for') => 'out_for_delivery',
            str_contains($s, 'deliver') => 'delivered',
            str_contains($s, 'transit') || (str_contains($s, 'picked') && !str_contains($s, 'not')) || str_contains($s, 'dispatch') => 'in_transit',
            default => empty($order->awb_number) ? 'new' : 'booked',
        };
    }

    public const BUCKET_LABELS = [
        'new' => 'New', 'booked' => 'Ready to ship', 'in_transit' => 'In transit',
        'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'ndr' => 'Delivery failed (NDR)',
        'rto' => 'RTO', 'cancelled' => 'Cancelled',
    ];

    public static function consignee($order): array
    {
        $c = $order->consignee ?? [];
        return is_array($c) ? $c : (json_decode((string) $c, true) ?: []);
    }

    public static function pickup($order): array
    {
        $c = $order->pickup ?? [];
        return is_array($c) ? $c : (json_decode((string) $c, true) ?: []);
    }

    public static function ref($order): string
    {
        return (string) ($order->customer_order_id ?: $order->order_number);
    }

    public static function courierLabel(Order $order): string
    {
        try {
            return WeightDisputeService::courierLabel($order);
        } catch (\Throwable $e) {
            return (string) ($order->courier_name ?: $order->courier_id ?: 'Courier');
        }
    }

    /** Parse "a, b\nc" into a clean unique list (upper-cased trimmed tokens). */
    public static function tokens(?string $raw, int $max = 500): array
    {
        $parts = preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_values(array_unique(array_map(fn ($p) => trim($p), $parts)));
        return array_slice($parts, 0, $max);
    }

    public static function pincodes(?string $raw, int $max = 5000): array
    {
        return array_values(array_filter(self::tokens($raw, $max), fn ($p) => preg_match('/^[1-9][0-9]{5}$/', $p)));
    }

    /** Resolve order IDs / order numbers / AWBs to this seller's orders (max 500). */
    public static function resolveOrders(int $sellerId, array $refs)
    {
        $refs = array_slice(array_values(array_filter(array_map('strval', $refs))), 0, 500);
        if (!$refs) {
            return collect();
        }
        $hasCustomer = self::hasCol('orders', 'customer_order_id');
        return Order::where('seller_id', $sellerId)
            ->where(function ($q) use ($refs, $hasCustomer) {
                $q->whereIn('awb_number', $refs)->orWhereIn('order_number', $refs);
                if ($hasCustomer) {
                    $q->orWhereIn('customer_order_id', $refs);
                }
                $ids = array_filter($refs, 'ctype_digit');
                if ($ids) {
                    $q->orWhereIn('id', $ids);
                }
            })->orderByDesc('id')->limit(500)->get();
    }

    public static function hasCol(string $table, string $col): bool
    {
        static $memo = [];
        $k = "$table.$col";
        if (!array_key_exists($k, $memo)) {
            try {
                $memo[$k] = Schema::hasColumn($table, $col);
            } catch (\Throwable $e) {
                $memo[$k] = false;
            }
        }
        return $memo[$k];
    }

    public static function money($v): string
    {
        return '₹' . number_format((float) $v, 2);
    }

    /** Stream rows as a CSV download. $rows: iterable of arrays. */
    public static function csv(string $filename, array $headings, iterable $rows)
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            foreach ($rows as $r) {
                fputcsv($out, array_map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $r));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
