<?php

namespace App\Support\ApiV1;

use App\Models\B2bOrder;
use App\Models\Order;
use App\Support\CourierBrand;
use App\Support\Xpeed\Kit;

/** Turns panel records into the public v1 JSON shapes. */
class Present
{
    /** Normalised status codes shared by B2C, reverse and B2B. */
    public const STATUSES = [
        'new'              => 'Created, courier not assigned',
        'booked'           => 'AWB generated, waiting for pickup',
        'in_transit'       => 'Picked up / in transit',
        'out_for_delivery' => 'Out for delivery',
        'delivered'        => 'Delivered',
        'ndr'              => 'Delivery attempt failed (NDR)',
        'rto'              => 'Returning / returned to origin',
        'cancelled'        => 'Cancelled',
    ];

    public static function status($order): string
    {
        try {
            return Kit::bucket($order);
        } catch (\Throwable $e) {
            return empty($order->awb_number) ? 'new' : 'booked';
        }
    }

    private static function json($v): array
    {
        if (is_array($v)) {
            return $v;
        }
        return is_string($v) ? (json_decode($v, true) ?: []) : (array) $v;
    }

    public static function courierName($order): ?string
    {
        $n = $order->courier_name ?? null ?: ($order->all_courier_name ?? null);
        return $n ? CourierBrand::cleanName($n, 'Courier') : null;
    }

    public static function shipment(Order $o): array
    {
        $to = self::json($o->consignee);
        $from = self::json($o->pickup);
        $status = self::status($o);
        $type = ($o->reverse ?? null) === 'reverse' ? 'reverse' : 'b2c';

        return [
            'shipment_id'      => (int) $o->id,
            'type'             => $type,
            'order_number'     => $o->order_number,
            'client_order_id'  => $o->customer_order_id ?? null,
            'awb_number'       => $o->awb_number ?: null,
            'courier_name'     => self::courierName($o),
            'status'           => $status,
            'status_text'      => self::STATUSES[$status] ?? $status,
            'courier_status'   => $o->shipping_status ?: null,
            'payment_type'     => strtolower((string) $o->payment_type) === 'cod' ? 'cod' : 'prepaid',
            'order_amount'     => (float) $o->order_amount,
            'cod_amount'       => strtolower((string) $o->payment_type) === 'cod' ? (float) $o->collectable_amount : 0.0,
            'shipping_charge'  => $o->seller_amount_walate !== null ? round((float) $o->seller_amount_walate, 2) : null,
            'weight_g'         => (float) $o->package_weight,
            'dimensions_cm'    => ['length' => (float) $o->package_length, 'breadth' => (float) $o->package_breadth, 'height' => (float) $o->package_height],
            'consignee'        => [
                'name' => $to['name'] ?? null, 'phone' => $to['phone'] ?? null, 'city' => $to['city'] ?? null,
                'state' => $to['state'] ?? null, 'pincode' => isset($to['pincode']) ? (string) $to['pincode'] : null,
            ],
            'pickup'           => [
                'warehouse_name' => $from['warehouse_name'] ?? null, 'city' => $from['city'] ?? null,
                'state' => $from['state'] ?? null, 'pincode' => isset($from['pincode']) ? (string) $from['pincode'] : null,
            ],
            'label_url'        => self::labelUrl($o),
            'tracking_url'     => $o->awb_number ? url('/track?awb=' . urlencode((string) $o->awb_number)) : null,
            'booking_error'    => $o->booking_error ?? null,
            'created_at'       => optional($o->created_at)->toIso8601String(),
            'shipped_at'       => $o->shipping_date ? \Illuminate\Support\Carbon::parse($o->shipping_date)->toIso8601String() : null,
        ];
    }

    private static function labelUrl(Order $o): ?string
    {
        foreach (['label_pdf', 'label'] as $col) {
            $v = (string) ($o->{$col} ?? '');
            if (preg_match('#^https?://#i', $v)) {
                return $v;
            }
        }
        return null;
    }

    public static function b2b(B2bOrder $o): array
    {
        $map = ['draft' => 'new', 'booked' => 'booked', 'in_transit' => 'in_transit', 'delivered' => 'delivered', 'rto' => 'rto', 'cancelled' => 'cancelled'];
        $status = $map[$o->status] ?? (string) $o->status;
        $to = (array) $o->consignee;
        $from = (array) $o->pickup;
        return [
            'shipment_id'     => (int) $o->id,
            'type'            => 'b2b',
            'order_ref'       => $o->order_ref,
            'awb_number'      => $o->awb ?: null,
            'lr_number'       => $o->lr_number ?: null,
            'courier_name'    => $o->courier_name ? CourierBrand::cleanName($o->courier_name, 'Courier') : null,
            'status'          => $status,
            'status_text'     => self::STATUSES[$status] ?? $status,
            'courier_status'  => $o->tracking_status ?: null,
            'invoice_no'      => $o->invoice_no,
            'invoice_amount'  => (float) $o->invoice_amount,
            'payment_type'    => $o->payment_mode,
            'cod_amount'      => (float) $o->cod_amount,
            'total_boxes'     => (int) $o->total_boxes,
            'total_weight_kg' => (float) $o->total_weight,
            'charged_weight_kg' => $o->charged_weight !== null ? (float) $o->charged_weight : null,
            'shipping_charge' => $o->seller_charge !== null ? round((float) $o->seller_charge, 2) : null,
            'tat_days'        => $o->tat !== null ? (int) $o->tat : null,
            'consignee'       => ['name' => $to['name'] ?? null, 'company' => $to['company'] ?? null, 'phone' => $to['phone'] ?? null,
                                  'city' => $to['city'] ?? null, 'state' => $to['state'] ?? null, 'pincode' => $to['pincode'] ?? null],
            'pickup'          => ['warehouse_id' => (int) $o->pickup_warehouse_id, 'city' => $from['city'] ?? null, 'pincode' => $from['pincode'] ?? null],
            'error'           => $o->error ?: null,
            'created_at'      => optional($o->created_at)->toIso8601String(),
            'booked_at'       => optional($o->booked_at)->toIso8601String(),
        ];
    }
}
