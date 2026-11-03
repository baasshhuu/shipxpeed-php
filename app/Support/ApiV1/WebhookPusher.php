<?php

namespace App\Support\ApiV1;

use App\Models\B2bOrder;
use App\Models\Order;
use App\Models\SellerList;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Sends "shipment.status_updated" to the client's webhook URL whenever a shipment's status changes,
 * whichever courier sync, courier webhook, cron or admin action saved it (called from the model events).
 *
 * Body is JSON; header X-Shipxpeed-Signature = hex HMAC-SHA256 of the raw body, keyed with the
 * client's API key, so the client can verify the call came from Shipxpeed.
 */
class WebhookPusher
{
    public static function orderChanged(Order $order): void
    {
        try {
            if (!$order->wasChanged(['shipping_status', 'order_status', 'awb_number'])) {
                return;
            }
            $before = clone $order;
            foreach (['shipping_status', 'order_status', 'awb_number'] as $c) {
                $before->{$c} = $order->getOriginal($c);
            }
            $old = Present::status($before);
            $new = Present::status($order);
            if ($old === $new && !$order->wasChanged('shipping_status')) {
                return;
            }
            $s = Present::shipment($order);
            self::send((int) $order->seller_id, [
                'awb_number' => $s['awb_number'], 'shipment_id' => $s['shipment_id'], 'type' => $s['type'],
                'client_order_id' => $s['client_order_id'], 'order_number' => $s['order_number'],
                'courier_name' => $s['courier_name'], 'status' => $new, 'previous_status' => $old,
                'courier_status' => $s['courier_status'], 'time' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ApiV1 webhook (order) skipped: ' . $e->getMessage());
        }
    }

    public static function b2bChanged(B2bOrder $order): void
    {
        try {
            if (!$order->wasChanged(['status', 'tracking_status', 'awb'])) {
                return;
            }
            $s = Present::b2b($order);
            self::send((int) $order->seller_id, [
                'awb_number' => $s['awb_number'], 'shipment_id' => $s['shipment_id'], 'type' => 'b2b',
                'order_ref' => $s['order_ref'], 'invoice_no' => $s['invoice_no'], 'courier_name' => $s['courier_name'],
                'status' => $s['status'], 'courier_status' => $s['courier_status'], 'time' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ApiV1 webhook (b2b) skipped: ' . $e->getMessage());
        }
    }

    private static function send(int $sellerId, array $data): void
    {
        if (!$sellerId || empty($data['awb_number']) || !Schema::hasTable('webhooks')) {
            return;
        }
        $hook = Webhook::where('seller_id', $sellerId)->where('status', 1)->orderByDesc('id')->first();
        if (!$hook || !preg_match('#^https://#i', (string) $hook->webhook_url)) {
            return;
        }
        $secret = (string) SellerList::where('id', $sellerId)->value('api_token');
        $body = json_encode(['event' => 'shipment.status_updated', 'data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $url = (string) $hook->webhook_url;

        $deliver = function () use ($url, $body, $secret, $sellerId, $data) {
            try {
                Http::timeout(5)->withHeaders([
                    'Content-Type'          => 'application/json',
                    'X-Shipxpeed-Event'     => 'shipment.status_updated',
                    'X-Shipxpeed-Signature' => hash_hmac('sha256', $body, $secret),
                ])->withBody($body, 'application/json')->post($url);
            } catch (\Throwable $e) {
                Log::info('ApiV1 webhook delivery failed', ['seller' => $sellerId, 'awb' => $data['awb_number'], 'e' => $e->getMessage()]);
            }
        };

        // after the current request / command, so courier syncs are never slowed down
        app()->runningUnitTests() ? $deliver() : app()->terminating($deliver);
    }
}
