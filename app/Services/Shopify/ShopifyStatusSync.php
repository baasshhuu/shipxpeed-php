<?php

namespace App\Services\Shopify;

use App\Models\Order;
use App\Models\ShopifyConnection;
use Illuminate\Support\Facades\Log;

/**
 * Pushes shipment progress from the panel back to Shopify:
 *   AWB generated        -> order marked Fulfilled with courier name, AWB and tracking link
 *   picked / in transit  -> fulfillment event (shows on Shopify order timeline + customer status page)
 *   out for delivery, NDR, delivered, RTO -> matching fulfillment events
 *   delivered COD order  -> order marked as Paid
 *   shipment cancelled   -> fulfillment cancelled (order becomes Unfulfilled again)
 *   AWB changed          -> tracking number updated
 */
class ShopifyStatusSync
{
    /** panel status -> Shopify FulfillmentEventStatus */
    private const EVENT = [
        'picked_up'        => 'CARRIER_PICKED_UP',
        'in_transit'       => 'IN_TRANSIT',
        'out_for_delivery' => 'OUT_FOR_DELIVERY',
        'ndr'              => 'ATTEMPTED_DELIVERY',
        'delivered'        => 'DELIVERED',
        'rto'              => 'FAILURE',
        'lost'             => 'FAILURE',
    ];

    private const FINAL = ['delivered', 'rto', 'cancelled', 'lost', 'blocked'];

    /**
     * Turns the many raw status strings different courier trackers write into one set.
     * Returns: manifested | picked_up | in_transit | out_for_delivery | ndr | delivered | rto | cancelled | lost | null
     */
    public static function normalize(Order $o): ?string
    {
        if (strtolower((string) $o->order_status) === 'cancelled') {
            return 'cancelled';
        }
        $s = strtolower(trim((string) $o->shipping_status));
        if ($s === '') {
            return $o->awb_number ? 'manifested' : null;
        }
        return match (true) {
            str_contains($s, 'rto') || str_contains($s, 'return')                                      => 'rto',
            str_contains($s, 'cancel')                                                                  => 'cancelled',
            str_contains($s, 'lost') || str_contains($s, 'damage')                                      => 'lost',
            str_contains($s, 'out for delivery') || str_contains($s, 'out_for_delivery') || $s === 'ofd' => 'out_for_delivery',
            str_contains($s, 'undeliver') || str_contains($s, 'ndr') || str_contains($s, 'attempt')
                || str_contains($s, 'not delivered')                                                    => 'ndr',
            str_contains($s, 'deliver')                                                                 => 'delivered',
            str_contains($s, 'picked') || $s === 'pickup done'                                          => 'picked_up',
            str_contains($s, 'transit') || str_contains($s, 'dispatch') || str_contains($s, 'shipped')
                || str_contains($s, 'reached') || str_contains($s, 'hub')                               => 'in_transit',
            default                                                                                     => 'manifested', // booked / manifested / pickup scheduled / pending pickup
        };
    }

    /** Pushes every Shopify order of this connection whose shipment changed. */
    public static function pushForConnection(ShopifyConnection $conn, int $limit = 300): array
    {
        $stats = ['pushed' => 0, 'unchanged' => 0, 'failed' => 0];
        if (!$conn->auto_fulfill) {
            return $stats;
        }

        $q = Order::query()
            ->where('seller_id', $conn->seller_id)
            ->whereNotNull('shopify_order_id')
            ->where('shopify_order_id', '!=', '')
            ->where(function ($q) use ($conn) {
                $q->where('shopify_connection_id', $conn->id)->orWhereNull('shopify_connection_id');
            })
            ->where(function ($q) {
                $q->whereNull('shopify_pushed_status')->orWhereNotIn('shopify_pushed_status', self::FINAL);
            })
            ->where(function ($q) {
                $q->whereNotNull('awb_number')->where('awb_number', '!=', '')
                  ->orWhereNotNull('shopify_fulfillment_id');
            })
            ->where('updated_at', '>=', now()->subDays(60))
            ->orderBy('updated_at')
            ->limit($limit);

        foreach ($q->get() as $order) {
            $r = (new self($conn))->push($order);
            $stats[$r]++;
        }

        $conn->last_status_push_at = now();
        $conn->save();

        return $stats;
    }

    public function __construct(private ShopifyConnection $conn)
    {
    }

    /** @return string pushed|unchanged|failed */
    public function push(Order $order): string
    {
        $state = self::normalize($order);
        $awb = trim((string) $order->awb_number);

        if (!$state) {
            return 'unchanged';
        }
        if ($state === $order->shopify_pushed_status && $awb === (string) $order->shopify_pushed_awb) {
            return 'unchanged';
        }

        try {
            $client = ShopifyClient::for($this->conn);

            if ($state === 'cancelled') {
                if ($order->shopify_fulfillment_id) {
                    $d = $client->graphql('mutation($id: ID!) { fulfillmentCancel(id: $id) { fulfillment { id status } userErrors { field message } } }',
                        ['id' => $order->shopify_fulfillment_id]);
                    ShopifyClient::assertNoUserErrors($d['fulfillmentCancel'] ?? null, 'Cancel fulfillment');
                }
                $this->saved($order, 'cancelled', $awb, null);
                ShopifyClient::log($this->conn->id, $order->id, 'push', 'fulfillment_cancelled', true, $awb);
                return 'pushed';
            }

            if ($awb === '') {
                return 'unchanged';
            }

            // 1. Make sure the Shopify order is fulfilled with this AWB
            if (!$order->shopify_fulfillment_id) {
                $fid = $this->createFulfillment($client, $order, $awb);
                if (!$fid) {
                    return 'failed';
                }
                $order->shopify_fulfillment_id = $fid;
                $order->shopify_pushed_awb = $awb;
                $order->save();
            } elseif ($awb !== (string) $order->shopify_pushed_awb) {
                // 2. AWB was re-generated / courier switched
                $d = $client->graphql('mutation($id: ID!, $t: FulfillmentTrackingInput!, $n: Boolean) {
                        fulfillmentTrackingInfoUpdate(fulfillmentId: $id, trackingInfoInput: $t, notifyCustomer: $n) { fulfillment { id } userErrors { field message } } }',
                    ['id' => $order->shopify_fulfillment_id, 't' => $this->tracking($order, $awb), 'n' => (bool) $this->conn->notify_customer]);
                ShopifyClient::assertNoUserErrors($d['fulfillmentTrackingInfoUpdate'] ?? null, 'Update tracking');
                $order->shopify_pushed_awb = $awb;
                $order->save();
            }

            // 3. Progress event
            if (isset(self::EVENT[$state]) && $state !== $order->shopify_pushed_status) {
                $msg = match ($state) {
                    'rto'  => 'Returned to origin (RTO)',
                    'ndr'  => 'Delivery attempted — customer not available',
                    'lost' => 'Shipment lost/damaged in transit',
                    default => null,
                };
                $d = $client->graphql('mutation($e: FulfillmentEventInput!) { fulfillmentEventCreate(fulfillmentEvent: $e) { fulfillmentEvent { id status } userErrors { field message } } }',
                    ['e' => array_filter([
                        'fulfillmentId' => $order->shopify_fulfillment_id,
                        'status'        => self::EVENT[$state],
                        'happenedAt'    => now()->toIso8601String(),
                        'message'       => $msg,
                    ])]);
                ShopifyClient::assertNoUserErrors($d['fulfillmentEventCreate'] ?? null, 'Fulfillment event');

                if ($state === 'delivered' && strtolower((string) $order->payment_type) === 'cod') {
                    $this->markPaid($client, $order);
                }
            }

            $this->saved($order, $state, $awb, null);
            ShopifyClient::log($this->conn->id, $order->id, 'push', 'status_' . $state, true, $awb);
            return 'pushed';
        } catch (\Throwable $e) {
            $order->shopify_sync_error = mb_strimwidth($e->getMessage(), 0, 900, '…');
            $order->saveQuietly();
            Log::warning('Shopify status push failed', ['order' => $order->id, 'awb' => $awb, 'error' => $e->getMessage()]);
            ShopifyClient::log($this->conn->id, $order->id, 'push', 'status_' . $state, false, $e->getMessage());
            return 'failed';
        }
    }

    private function createFulfillment(ShopifyClient $client, Order $order, string $awb): ?string
    {
        $gid = 'gid://shopify/Order/' . $order->shopify_order_id;
        $d = $client->graphql('query($id: ID!) { order(id: $id) {
                displayFulfillmentStatus
                fulfillments(first: 20) { id status trackingInfo { number } }
                fulfillmentOrders(first: 20) { nodes { id status } }
            } }', ['id' => $gid]);

        $o = $d['order'] ?? null;
        if (!$o) {
            throw new \RuntimeException('Order not found on Shopify (deleted, or the app lost access).');
        }

        // Already fulfilled with this AWB (e.g. previous run timed out after Shopify saved it)
        foreach ($o['fulfillments'] ?? [] as $f) {
            foreach ($f['trackingInfo'] ?? [] as $t) {
                if (($t['number'] ?? '') === $awb && ($f['status'] ?? '') !== 'CANCELLED') {
                    return $f['id'];
                }
            }
        }

        $open = collect($o['fulfillmentOrders']['nodes'] ?? [])
            ->filter(fn ($fo) => in_array($fo['status'], ['OPEN', 'IN_PROGRESS'], true))
            ->map(fn ($fo) => ['fulfillmentOrderId' => $fo['id']])
            ->values()->all();

        if (!$open) {
            $statuses = collect($o['fulfillmentOrders']['nodes'] ?? [])->pluck('status')->implode(', ');
            $msg = str_contains($statuses, 'ON_HOLD')
                ? 'Order is On hold in Shopify — release the hold to let us fulfill it.'
                : 'Nothing left to fulfill on Shopify (already fulfilled elsewhere, or closed). Fulfillment orders: ' . ($statuses ?: 'none');
            // Stop retrying every 5 minutes; the error is shown on the order
            $order->shopify_sync_error = $msg;
            $order->shopify_pushed_awb = $awb;
            $order->shopify_pushed_status = str_contains($statuses, 'ON_HOLD') ? null : 'blocked';
            $order->saveQuietly();
            ShopifyClient::log($this->conn->id, $order->id, 'push', 'fulfillment_skipped', false, $msg);
            return null;
        }

        $d = $client->graphql('mutation($f: FulfillmentInput!) { fulfillmentCreate(fulfillment: $f) { fulfillment { id status } userErrors { field message } } }', [
            'f' => [
                'lineItemsByFulfillmentOrder' => $open,
                'notifyCustomer'              => (bool) $this->conn->notify_customer,
                'trackingInfo'                => $this->tracking($order, $awb),
            ],
        ]);
        ShopifyClient::assertNoUserErrors($d['fulfillmentCreate'] ?? null, 'Create fulfillment');

        ShopifyClient::log($this->conn->id, $order->id, 'push', 'fulfilled', true, $awb);
        return $d['fulfillmentCreate']['fulfillment']['id'] ?? null;
    }

    private function markPaid(ShopifyClient $client, Order $order): void
    {
        try {
            $d = $client->graphql('mutation($i: OrderMarkAsPaidInput!) { orderMarkAsPaid(input: $i) { order { id displayFinancialStatus } userErrors { field message } } }',
                ['i' => ['id' => 'gid://shopify/Order/' . $order->shopify_order_id]]);
            ShopifyClient::assertNoUserErrors($d['orderMarkAsPaid'] ?? null, 'Mark as paid');
            ShopifyClient::log($this->conn->id, $order->id, 'push', 'cod_marked_paid', true);
        } catch (\Throwable $e) {
            // Not fatal: already paid, or app lacks write_orders
            ShopifyClient::log($this->conn->id, $order->id, 'push', 'cod_marked_paid', false, $e->getMessage());
        }
    }

    private function tracking(Order $order, string $awb): array
    {
        return [
            'company' => self::carrierName($order),
            'number'  => $awb,
            'url'     => str_replace('{awb}', urlencode($awb), config('shopify.tracking_url')),
        ];
    }

    /** Uses the carrier names Shopify recognises where possible. */
    public static function carrierName(Order $o): string
    {
        $raw = strtolower(trim(($o->courier_name ?: '') . ' ' . ($o->courier_id ?: '') . ' ' . ($o->all_courier_name ?: '')));
        $known = [
            'delhivery'   => 'Delhivery',
            'xpressbees'  => 'Xpressbees',
            'xpress bees' => 'Xpressbees',
            'bluedart'    => 'Blue Dart',
            'blue dart'   => 'Blue Dart',
            'dtdc'        => 'DTDC',
            'ekart'       => 'Ekart',
            'shadowfax'   => 'Shadowfax',
            'ecom'        => 'Ecom Express',
            'amazon'      => 'Amazon Shipping',
            'india post'  => 'India Post',
            'speed post'  => 'India Post',
            'smartr'      => 'Smartr',
            'dhl'         => 'DHL Express',
            'aramex'      => 'Aramex',
            'fedex'       => 'FedEx',
        ];
        foreach ($known as $needle => $name) {
            if (str_contains($raw, $needle)) {
                return $name;
            }
        }
        return trim($o->courier_name ?: $o->courier_id ?: 'Shipxpeed');
    }

    private function saved(Order $order, string $state, string $awb, ?string $error): void
    {
        $order->shopify_pushed_status = $state;
        $order->shopify_pushed_awb = $awb ?: $order->shopify_pushed_awb;
        if ($state === 'cancelled') {
            $order->shopify_fulfillment_id = null;
        }
        $order->shopify_sync_error = $error;
        $order->shopify_synced_at = now();
        $order->saveQuietly();
    }
}
