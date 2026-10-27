<?php

namespace App\Support\Xpeed;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Tracking data for the seller-branded public tracking page.
 * Live scans for couriers whose tracking API is in the panel (Delhivery direct, Fship);
 * every other courier shows the status our tracking sync saved on the order.
 * Results are cached for 10 minutes per AWB so buyers refreshing the page don't hit couriers.
 */
class Tracking
{
    public static function forOrder(Order $order): array
    {
        $awb = (string) $order->awb_number;
        $base = [
            'awb' => $awb,
            'courier' => Kit::courierLabel($order),
            'bucket' => Kit::bucket($order),
            'status' => Kit::BUCKET_LABELS[Kit::bucket($order)] ?? 'Booked',
            'edd' => null,
            'history' => [],
            'live' => false,
        ];
        if ($awb === '') {
            return $base;
        }

        $live = Cache::remember('xp_track_' . md5($awb), now()->addMinutes(10), function () use ($order, $awb) {
            $code = strtolower((string) $order->courier_id);
            try {
                if ($code === 'fship' && class_exists(\App\Services\FshipService::class)) {
                    $t = app(\App\Services\FshipService::class)->track($awb);
                    return !empty($t['status']) ? ['history' => $t['history'] ?? [], 'edd' => $t['edd'] ?? null] : null;
                }
                if (str_starts_with($code, 'delhivery')) {
                    $token = env('DELHIVERY_B2C_TOKEN') ?: env('DELHIVERY_API_TOKEN');
                    if (!$token) {
                        return null;
                    }
                    $r = Http::timeout(12)->withHeaders(['Authorization' => 'Token ' . $token])
                        ->get('https://track.delhivery.com/api/v1/packages/json/', ['waybill' => $awb]);
                    $s = $r->json('ShipmentData.0.Shipment');
                    if (!$s) {
                        return null;
                    }
                    $hist = array_map(fn ($x) => [
                        'event_time' => $x['ScanDetail']['ScanDateTime'] ?? '',
                        'message' => $x['ScanDetail']['Instructions'] ?? ($x['ScanDetail']['Scan'] ?? ''),
                        'location' => $x['ScanDetail']['ScannedLocation'] ?? '',
                    ], $s['Scans'] ?? []);
                    return ['history' => $hist, 'edd' => $s['ExpectedDeliveryDate'] ?? ($s['PromisedDeliveryDate'] ?? null)];
                }
            } catch (\Throwable $e) {
                \Log::info('Branded tracking fetch failed', ['awb' => $awb, 'error' => $e->getMessage()]);
            }
            return null;
        });

        if ($live) {
            $hist = array_values(array_filter($live['history'] ?? [], fn ($h) => trim((string) ($h['message'] ?? '')) !== ''));
            usort($hist, fn ($a, $b) => strtotime((string) $b['event_time']) <=> strtotime((string) $a['event_time']));
            $base['history'] = $hist;
            $base['edd'] = $live['edd'] ? date('d M Y', strtotime((string) $live['edd'])) : null;
            $base['live'] = true;
        }
        return $base;
    }

    public static function brand(int $sellerId): ?object
    {
        if (!Kit::ready('xp_tracking_brands')) {
            return null;
        }
        return DB::table('xp_tracking_brands')->where('seller_id', $sellerId)->first();
    }

    /** Public link a seller can share with buyers. */
    public static function url(string $awb): string
    {
        return route('xp.track.public', ['awb' => $awb]);
    }

    /** Progress steps for the page header. */
    public static function steps(string $bucket): array
    {
        $flow = ['booked' => 'Order placed', 'in_transit' => 'Shipped', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'];
        $pos = match ($bucket) {
            'in_transit', 'ndr' => 1, 'out_for_delivery' => 2, 'delivered' => 3, default => 0,
        };
        $out = [];
        $i = 0;
        foreach ($flow as $label) {
            $out[] = ['label' => $label, 'done' => $i <= $pos];
            $i++;
        }
        return $out;
    }
}
