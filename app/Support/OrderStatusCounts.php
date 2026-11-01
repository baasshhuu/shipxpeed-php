<?php

namespace App\Support;

use App\Models\Order;

/**
 * Live per-seller order counts for the "Filter by Status" tabs.
 *
 * Each condition mirrors the base filter of the page the tab opens
 * (Dashboard@order, index_Assigned, InTransit, OutForDelivery, Delivered,
 * Cancelled_order, NDR, RTO), so the number on a tab equals the rows
 * the seller sees after clicking it. Only the seller's own orders count.
 */
class OrderStatusCounts
{
    public const ASSIGNED = ['assigned', 'pending pickup', 'manifested', 'not picked', 'courier assigned'];
    public const RTO      = ['rto', 'rto delivered', 'rto-it', 'rto-dispatched', 'rto-pending', 'rto_ofd', 'rts'];

    public static function forSeller($sellerId): array
    {
        $zero = [
            'new' => 0, 'all' => 0, 'assigned' => 0, 'in_transit' => 0,
            'out_for_delivery' => 0, 'delivered' => 0, 'cancelled' => 0,
            'ndr' => 0, 'rto' => 0, 'other' => 0,
        ];

        if (!$sellerId) {
            return $zero;
        }

        $s   = 'LOWER(TRIM(shipping_status))';
        $os  = "LOWER(TRIM(COALESCE(order_status, '')))";
        $in  = fn (array $v) => "'" . implode("','", $v) . "'";
        $known = $in(array_merge(self::ASSIGNED, self::RTO, ['transit', 'out for delivery', 'delivered', 'ndr']));

        $row = Order::where('seller_id', $sellerId)->selectRaw("
            COUNT(*) AS total,
            SUM(awb_number IS NULL) AS new_orders,
            SUM(awb_number IS NOT NULL AND $os = ''
                AND (shipping_status IS NULL OR $s IN ({$in(self::ASSIGNED)}))) AS assigned,
            SUM($s = 'transit') AS in_transit,
            SUM($s = 'out for delivery') AS out_for_delivery,
            SUM($s = 'delivered') AS delivered,
            SUM($os = 'cancelled' OR $s = 'not picked') AS cancelled,
            SUM($s = 'ndr') AS ndr,
            SUM($s IN ({$in(self::RTO)})) AS rto,
            SUM(awb_number IS NOT NULL AND $os <> 'cancelled'
                AND shipping_status IS NOT NULL AND $s <> '' AND $s NOT IN ($known)) AS other
        ")->first();

        if (!$row) {
            return $zero;
        }

        return [
            'new'              => (int) $row->new_orders,
            'all'              => (int) $row->total,
            'assigned'         => (int) $row->assigned,
            'in_transit'       => (int) $row->in_transit,
            'out_for_delivery' => (int) $row->out_for_delivery,
            'delivered'        => (int) $row->delivered,
            'cancelled'        => (int) $row->cancelled,
            'ndr'              => (int) $row->ndr,
            'rto'              => (int) $row->rto,
            'other'            => (int) $row->other,
        ];
    }
}
