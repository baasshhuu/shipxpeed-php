<?php

namespace App\Http\Controllers\Xpeed;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Xpeed\Kit;
use App\Support\Xpeed\Tracking;

/** Buyer-facing tracking page at /t/{awb}, branded with the seller's logo and colour. */
class PublicTrackingController extends Controller
{
    public function show(string $awb)
    {
        $order = Order::where('awb_number', $awb)->first();
        if (!$order) {
            return response()->view('frontend.xpeed.track', ['notFound' => true, 'awb' => $awb, 'brand' => null], 404);
        }
        $brand = Tracking::brand((int) $order->seller_id);
        if ($brand && !$brand->active) {
            $brand = null;
        }
        $c = Kit::consignee($order);
        return view('frontend.xpeed.track', [
            'notFound' => false,
            'awb' => $awb,
            'brand' => $brand,
            'track' => Tracking::forOrder($order),
            'ref' => Kit::ref($order),
            'city' => trim(($c['city'] ?? '') . ', ' . ($c['state'] ?? ''), ', '),
            'cod' => strtolower((string) $order->payment_type) === 'cod' ? (float) $order->collectable_amount : null,
            'items' => collect(is_array($order->order_items) ? $order->order_items : [])->take(3)->map(fn ($i) => is_array($i) ? ($i['name'] ?? $i['product_name'] ?? null) : null)->filter()->values(),
        ]);
    }
}
