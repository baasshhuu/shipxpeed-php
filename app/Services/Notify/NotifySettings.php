<?php

namespace App\Services\Notify;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-seller buyer Email & SMS preferences (Seller › Notify › Email & SMS).
 * No saved row = nothing is sent until the seller picks the stages.
 */
class NotifySettings
{
    /** stage key => [label, what the buyer is told] — keys match OrderStatusNotifier::eventFor(). */
    public const STAGES = [
        'booked'           => ['Shipped',           'Your order has been shipped'],
        'picked_up'        => ['Picked Up',         'Your order has been picked up by the courier'],
        'in_transit'       => ['In Transit',        'Your order is on its way'],
        'out_for_delivery' => ['Out For Delivery',  'Your order is out for delivery today'],
        'ndr'              => ['Delivery Failed',   'We could not deliver your order'],
        'delivered'        => ['Delivered',         'Your order has been delivered'],
        'rto_initiated'    => ['Returning to Seller', 'Your order is being returned to the seller'],
        'cancelled'        => ['Cancelled',         'Your order has been cancelled'],
    ];

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('seller_notify_settings') && Schema::hasTable('buyer_notifications');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{hide_product:bool, hide_price_prepaid:bool, email:string[], sms:string[], saved:bool} */
    public static function for(int $sellerId): array
    {
        $out = ['hide_product' => false, 'hide_price_prepaid' => false, 'email' => [], 'sms' => [], 'saved' => false];
        if (!self::ready()) {
            return $out;
        }
        $row = DB::table('seller_notify_settings')->where('seller_id', $sellerId)->first();
        if (!$row) {
            return $out;
        }
        return [
            'hide_product'       => (bool) $row->hide_product,
            'hide_price_prepaid' => (bool) $row->hide_price_prepaid,
            'email'              => self::clean(json_decode((string) $row->email_events, true)),
            'sms'                => self::clean(json_decode((string) $row->sms_events, true)),
            'saved'              => true,
        ];
    }

    public static function save(int $sellerId, array $s): array
    {
        $data = [
            'hide_product'       => !empty($s['hide_product']) ? 1 : 0,
            'hide_price_prepaid' => !empty($s['hide_price_prepaid']) ? 1 : 0,
            'email_events'       => json_encode(self::clean($s['email'] ?? [])),
            'sms_events'         => json_encode(self::clean($s['sms'] ?? [])),
            'updated_at'         => now(),
        ];
        $exists = DB::table('seller_notify_settings')->where('seller_id', $sellerId)->exists();
        if ($exists) {
            DB::table('seller_notify_settings')->where('seller_id', $sellerId)->update($data);
        } else {
            DB::table('seller_notify_settings')->insert($data + ['seller_id' => $sellerId, 'created_at' => now()]);
        }
        return self::for($sellerId);
    }

    private static function clean($list): array
    {
        if (!is_array($list)) {
            return [];
        }
        return array_values(array_intersect(array_keys(self::STAGES), array_map('strval', $list)));
    }
}
