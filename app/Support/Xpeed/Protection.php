<?php

namespace App\Support\Xpeed;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Shipment protection: sellers who switch it on pay a small fee (set by admin) on every
 * shipment at or above the minimum order value; if such a shipment is lost or damaged the
 * claim is paid up to the declared value (capped) straight into the wallet once approved.
 * The fee is charged by the scheduled `xpeed:services` run, and refunded if the shipment is cancelled.
 */
class Protection
{
    public static function service(int $sellerId): object
    {
        $row = Kit::ready('xp_seller_services') ? DB::table('xp_seller_services')->where('seller_id', $sellerId)->first() : null;
        return $row ?: (object) ['seller_id' => $sellerId, 'protection_on' => 0, 'protection_since' => null,
            'early_cod_plan' => null, 'early_cod_status' => null, 'early_cod_requested_at' => null, 'early_cod_note' => null];
    }

    public static function saveService(int $sellerId, array $values): void
    {
        DB::table('xp_seller_services')->updateOrInsert(['seller_id' => $sellerId], $values + ['updated_at' => now(), 'created_at' => now()]);
    }

    public static function fee(float $value): float
    {
        return round(max(Kit::num('protection_min_fee'), $value * Kit::num('protection_fee_pct') / 100), 2);
    }

    public static function eligible(Order $order): bool
    {
        return (float) $order->order_amount >= Kit::num('protection_min_value');
    }

    /** Charge fees for new protected shipments and refund cancelled ones. Returns counts. */
    public static function run(): array
    {
        $out = ['charged' => 0, 'refunded' => 0];
        if (!Kit::ready('xp_seller_services', 'xp_protection_charges') || Kit::setting('protection_enabled') !== '1') {
            return $out;
        }
        foreach (DB::table('xp_seller_services')->where('protection_on', 1)->get() as $svc) {
            $orders = Order::where('seller_id', $svc->seller_id)
                ->whereNotNull('awb_number')->where('awb_number', '!=', '')
                ->where('created_at', '>=', $svc->protection_since ?: now()->subDay())
                ->where('order_amount', '>=', Kit::num('protection_min_value'))
                ->whereNotIn('id', DB::table('xp_protection_charges')->select('order_id'))
                ->limit(2000)->get();
            foreach ($orders as $o) {
                if (Kit::bucket($o) === 'cancelled') {
                    continue;
                }
                DB::transaction(function () use ($o, &$out) {
                    if (DB::table('xp_protection_charges')->where('order_id', $o->id)->lockForUpdate()->exists()) {
                        return;
                    }
                    $fee = self::fee((float) $o->order_amount);
                    $rc = Kit::debit((int) $o->seller_id, $fee, 'Shipment protection fee - AWB ' . $o->awb_number);
                    DB::table('xp_protection_charges')->insert([
                        'seller_id' => $o->seller_id, 'order_id' => $o->id, 'awb' => $o->awb_number,
                        'declared_value' => $o->order_amount, 'fee' => $fee, 'recharge_id' => $rc->id,
                        'status' => 'charged', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $out['charged']++;
                });
            }
        }

        // refunds for shipments cancelled after the fee was charged
        foreach (DB::table('xp_protection_charges')->where('status', 'charged')->where('created_at', '>=', now()->subDays(30))->get() as $ch) {
            $o = Order::find($ch->order_id);
            if ($o && Kit::bucket($o) === 'cancelled') {
                DB::transaction(function () use ($ch, &$out) {
                    $n = DB::table('xp_protection_charges')->where('id', $ch->id)->where('status', 'charged')->update(['status' => 'refunded', 'updated_at' => now()]);
                    if ($n) {
                        Kit::credit((int) $ch->seller_id, (float) $ch->fee, 'Shipment protection fee refund (cancelled) - AWB ' . $ch->awb);
                        $out['refunded']++;
                    }
                });
            }
        }
        return $out;
    }

    public static function isProtected(int $orderId): bool
    {
        return Kit::ready('xp_protection_charges')
            && DB::table('xp_protection_charges')->where('order_id', $orderId)->where('status', 'charged')->exists();
    }

    /** Early COD plans offered to sellers: code => [days, fee %]. */
    public static function earlyCodPlans(): array
    {
        return [
            'd2' => ['days' => 2, 'pct' => Kit::num('early_cod_d2_pct')],
            'd3' => ['days' => 3, 'pct' => Kit::num('early_cod_d3_pct')],
            'd4' => ['days' => 4, 'pct' => Kit::num('early_cod_d4_pct')],
        ];
    }

    /** Delivered, unremitted COD orders of a seller that are due under their early-COD plan. */
    public static function earlyCodDue(int $sellerId, string $plan)
    {
        $days = self::earlyCodPlans()[$plan]['days'] ?? 7;
        if (!Kit::hasCol('orders', 'delivered_date')) {
            return collect();
        }
        return Order::where('seller_id', $sellerId)->where('payment_type', 'cod')
            ->whereNotNull('delivered_date')
            ->whereDate('delivered_date', '<=', now()->subDays($days)->toDateString())
            ->where(fn ($q) => $q->whereNull('payment_status')->orWhere('payment_status', '!=', 'Paid'))
            ->orderBy('delivered_date')->limit(2000)->get()
            ->filter(fn ($o) => Kit::bucket($o) === 'delivered')->values();
    }
}
