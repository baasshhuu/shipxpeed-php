<?php

namespace App\Services\Shopify;

use App\Http\Controllers\selleradmin\ShipmentController;
use App\Models\Order;
use App\Models\SellerAddress;
use App\Models\SellerList;
use App\Models\ShopifyConnection;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Pulls unfulfilled Shopify orders into the panel's orders table.
 * New orders go through the existing ShipmentController::createShipmentshopify()
 * so every courier payload (Delhivery, Shadowfax, ...) is built exactly as before.
 */
class ShopifyOrderSync
{
    private bool $withWeight = true;

    private const ORDER_FIELDS = <<<'GQL'
        id
        legacyResourceId
        name
        createdAt
        updatedAt
        cancelledAt
        displayFinancialStatus
        displayFulfillmentStatus
        email
        phone
        note
        tags
        paymentGatewayNames
        totalPriceSet { shopMoney { amount } }
        totalDiscountsSet { shopMoney { amount } }
        totalOutstandingSet { shopMoney { amount } }
        shippingLine { title originalPriceSet { shopMoney { amount } } }
        customAttributes { key value }
        customer { firstName lastName phone }
        shippingAddress { name firstName lastName company address1 address2 city province zip country phone }
        billingAddress { name address1 address2 city province zip country phone }
        lineItems(first: 100) {
          nodes {
            name
            sku
            quantity
            requiresShipping
            originalUnitPriceSet { shopMoney { amount } }
            discountedUnitPriceSet { shopMoney { amount } }
            product { legacyResourceId }
            variant { legacyResourceId __WEIGHT__ }
          }
        }
    GQL;

    private const WEIGHT_FIELDS = 'inventoryItem { measurement { weight { unit value } } }';

    public function __construct(private ShopifyConnection $conn)
    {
    }

    public static function for(ShopifyConnection $conn): self
    {
        return new self($conn);
    }

    private function fields(): string
    {
        return str_replace('__WEIGHT__', $this->withWeight ? self::WEIGHT_FIELDS : '', self::ORDER_FIELDS);
    }

    /** Runs a query, dropping the weight block once if the app lacks access to it. */
    private function query(string $gqlTemplate, array $vars): array
    {
        try {
            return ShopifyClient::for($this->conn)->graphql(str_replace('__FIELDS__', $this->fields(), $gqlTemplate), $vars);
        } catch (ShopifyGraphqlException $e) {
            if ($this->withWeight && preg_match('/inventoryItem|measurement|weight|read_products|read_inventory/i', $e->getMessage())) {
                $this->withWeight = false;
                return ShopifyClient::for($this->conn)->graphql(str_replace('__FIELDS__', $this->fields(), $gqlTemplate), $vars);
            }
            throw $e;
        }
    }

    /**
     * Pulls orders changed since the last pull (or since sync_start_date on the first run).
     * @return array{created:int, skipped:int, failed:int, fetched:int, errors:array}
     */
    public function pull(): array
    {
        $c = $this->conn;
        $since = $c->last_order_pull_at
            ? $c->last_order_pull_at->copy()->subMinutes(15)       // overlap so nothing slips between runs
            : ($c->sync_start_date ? Carbon::parse($c->sync_start_date)->startOfDay() : now()->subDays(config('shopify.default_lookback_days')));

        $startedAt = now();
        $q = sprintf("updated_at:>='%s'", $since->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z'));
        if ($c->sync_start_date) {
            $q .= sprintf(" AND created_at:>='%s'", Carbon::parse($c->sync_start_date)->startOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z'));
        }

        $gql = 'query($q: String!, $after: String) {
            orders(first: 50, after: $after, query: $q, sortKey: UPDATED_AT) {
              pageInfo { hasNextPage endCursor }
              nodes { __FIELDS__ }
            }
        }';

        $stats = ['created' => 0, 'skipped' => 0, 'failed' => 0, 'fetched' => 0, 'errors' => []];
        $after = null;
        $pages = 0;

        do {
            $data = $this->query($gql, ['q' => $q, 'after' => $after]);
            $conn = $data['orders'] ?? ['nodes' => [], 'pageInfo' => ['hasNextPage' => false]];

            foreach ($conn['nodes'] as $node) {
                $stats['fetched']++;
                $r = $this->importNode($node);
                $stats[$r['result']]++;
                if ($r['result'] === 'failed') {
                    $stats['errors'][] = $node['name'] . ': ' . $r['message'];
                }
            }

            $after = $conn['pageInfo']['endCursor'] ?? null;
            $pages++;
        } while (($conn['pageInfo']['hasNextPage'] ?? false) && $pages < 40);

        $c->last_order_pull_at = $startedAt;
        $c->last_sync_at = now();
        $c->orders_synced_count = (int) $c->orders_synced_count + $stats['created'];
        $c->last_error = $stats['errors'] ? implode(' | ', array_slice($stats['errors'], 0, 3)) : null;
        $c->save();

        ShopifyClient::log($c->id, null, 'pull', 'orders', $stats['failed'] === 0,
            "fetched {$stats['fetched']}, new {$stats['created']}, skipped {$stats['skipped']}, failed {$stats['failed']}");

        return $stats;
    }

    /** Imports one order by its numeric Shopify id (used by the orders/create webhook). */
    public function pullOne(string $legacyId): array
    {
        $gql = 'query($id: ID!) { order(id: $id) { __FIELDS__ } }';
        $data = $this->query($gql, ['id' => 'gid://shopify/Order/' . $legacyId]);
        if (empty($data['order'])) {
            return ['result' => 'skipped', 'message' => 'Order not visible to the app'];
        }
        return $this->importNode($data['order']);
    }

    /* ----------------------------------------------------------------- */

    private function importNode(array $o): array
    {
        $c = $this->conn;
        $legacyId = (string) $o['legacyResourceId'];
        $number = ltrim((string) $o['name'], '#');

        // Already in the panel?
        $existing = Order::where('seller_id', $c->seller_id)
            ->where(function ($q) use ($legacyId, $number) {
                $q->where('shopify_order_id', $legacyId)->orWhere('shopify_order_number', $number);
            })->first();

        if ($existing) {
            if (!empty($o['cancelledAt']) && empty($existing->awb_number)) {
                // Cancelled on Shopify before we shipped it -> cancel here too.
                $existing->order_status = 'cancelled';
                $existing->shopify_sync_error = 'Cancelled on Shopify';
                $existing->save();
                ShopifyClient::log($c->id, $existing->id, 'pull', 'cancelled_on_shopify', true, $o['name']);
            } elseif (!empty($o['cancelledAt'])) {
                $existing->shopify_sync_error = 'Cancelled on Shopify after AWB ' . $existing->awb_number . ' was generated — cancel the shipment manually';
                $existing->save();
            }
            if (!$existing->shopify_connection_id) {
                $existing->shopify_connection_id = $c->id;
                $existing->shopify_order_id = $legacyId;
                $existing->save();
            }
            return ['result' => 'skipped', 'message' => 'exists'];
        }

        if (!empty($o['cancelledAt'])) {
            return ['result' => 'skipped', 'message' => 'cancelled'];
        }
        if (in_array($o['displayFulfillmentStatus'] ?? '', ['FULFILLED', 'RESTOCKED'], true)) {
            return ['result' => 'skipped', 'message' => 'already fulfilled'];
        }
        if (in_array($o['displayFinancialStatus'] ?? '', ['REFUNDED', 'VOIDED'], true)) {
            return ['result' => 'skipped', 'message' => 'refunded/voided'];
        }

        $items = array_values(array_filter($o['lineItems']['nodes'] ?? [], fn ($li) => ($li['requiresShipping'] ?? true) && (int) $li['quantity'] > 0));
        if (!$items) {
            return ['result' => 'skipped', 'message' => 'nothing to ship'];
        }

        try {
            $payload = $this->buildPayload($o, $items, $legacyId, $number);

            $seller = SellerList::find($c->seller_id);
            if (!$seller) {
                throw new \RuntimeException('Seller not found');
            }
            Auth::guard('seller')->setUser($seller);

            $req = new Request();
            $req->merge($payload);
            $res = (new ShipmentController())->createShipmentshopify($req);
            $json = json_decode($res->getContent(), true) ?: [];

            if (empty($json['success'])) {
                throw new \RuntimeException($json['message'] ?? 'Order creation failed');
            }

            Order::where('id', $json['order_id'])->update([
                'shopify_connection_id' => $c->id,
                'shopify_order_id'      => $legacyId,
                'shopify_order_number'  => $number,
                'additional_info'       => json_encode([
                    'source'             => 'shopify',
                    'shop'               => $c->shopify_shop_domain,
                    'shopify_order_name' => $o['name'],
                    'financial_status'   => $o['displayFinancialStatus'] ?? null,
                    'fulfillment_status' => $o['displayFulfillmentStatus'] ?? null,
                    'payment_gateways'   => $o['paymentGatewayNames'] ?? [],
                    'tags'               => $o['tags'] ?? [],
                    'note'               => $o['note'] ?? null,
                    'shopify_created_at' => $o['createdAt'] ?? null,
                ]),
            ]);

            ShopifyClient::log($c->id, (int) $json['order_id'], 'pull', 'order_created', true, $o['name']);
            return ['result' => 'created', 'message' => 'ok', 'order_id' => $json['order_id']];
        } catch (\Throwable $e) {
            Log::error('Shopify order import failed', ['shop' => $c->shopify_shop_domain, 'order' => $o['name'] ?? $legacyId, 'error' => $e->getMessage()]);
            ShopifyClient::log($c->id, null, 'pull', 'order_failed', false, ($o['name'] ?? $legacyId) . ': ' . $e->getMessage());
            return ['result' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /** Same request shape the old Shopify sync sent to createShipmentshopify(). */
    private function buildPayload(array $o, array $items, string $legacyId, string $number): array
    {
        $ship = ($o['shippingAddress'] ?? null) ?: (($o['billingAddress'] ?? null) ?: []);
        $cust = $o['customer'] ?? [];

        // Some Indian COD-form apps put the address in order "note attributes"
        $attr = [];
        foreach ($o['customAttributes'] ?? [] as $a) {
            $attr[strtolower(trim($a['key']))] = $a['value'];
        }
        $pick = fn (array $keys) => collect($keys)->map(fn ($k) => $attr[$k] ?? null)->first(fn ($v) => filled($v));

        $name = $pick(['full name', 'name']) ?: ($ship['name'] ?? trim(($cust['firstName'] ?? '') . ' ' . ($cust['lastName'] ?? '')));
        $phone = $pick(['phone number', 'phone', 'mobile']) ?: ($ship['phone'] ?? null) ?: ($o['phone'] ?? null) ?: ($cust['phone'] ?? '');

        $consignee = [
            'name'      => trim((string) $name) ?: 'Customer',
            'email'     => $o['email'] ?? '',
            'phone'     => self::cleanPhone($phone),
            'address'   => $pick(['address', 'address1']) ?: trim(($ship['address1'] ?? '') . ' ' . ($ship['company'] ?? '')),
            'address_2' => $pick(['landmark', 'address2']) ?: ($ship['address2'] ?? ''),
            'city'      => $pick(['city']) ?: ($ship['city'] ?? ''),
            'state'     => $pick(['state']) ?: ($ship['province'] ?? ''),
            'country'   => $ship['country'] ?? 'India',
            'pincode'   => preg_replace('/\D/', '', (string) ($pick(['zip_code', 'pincode', 'zip']) ?: ($ship['zip'] ?? ''))),
        ];

        if (strlen($consignee['pincode']) !== 6 || strlen($consignee['phone']) !== 10 || $consignee['address'] === '') {
            throw new \RuntimeException(sprintf(
                'Incomplete shipping address (pincode "%s", phone "%s"). If name/address are blank, enable Protected customer data (name, address, phone, email) for the app in Shopify Dev Dashboard.',
                $consignee['pincode'], $consignee['phone']
            ));
        }

        [$pickup, $warehouseName] = $this->pickupAddress();

        $orderItems = [];
        $grams = 0;
        foreach ($items as $li) {
            $qty = (int) $li['quantity'];
            $price = (float) ($li['discountedUnitPriceSet']['shopMoney']['amount'] ?? $li['originalUnitPriceSet']['shopMoney']['amount'] ?? 0);
            $w = self::toGrams($li['variant']['inventoryItem']['measurement']['weight'] ?? null);
            $grams += $w * $qty;
            $orderItems[] = [
                'product_id' => $li['product']['legacyResourceId'] ?? null,
                'variant_id' => $li['variant']['legacyResourceId'] ?? null,
                'name'       => $li['name'],
                'sku'        => $li['sku'] ?? '',
                'qty'        => $qty,
                'price'      => $price,
                'total'      => round($qty * $price, 2),
                'weight'     => $w,
            ];
        }

        $total = (float) ($o['totalPriceSet']['shopMoney']['amount'] ?? 0);
        $outstanding = (float) ($o['totalOutstandingSet']['shopMoney']['amount'] ?? 0);
        $isCod = self::isCod($o);
        $collectable = $isCod ? ($outstanding > 0 ? $outstanding : $total) : 0;

        return [
            'order_number'       => $number,
            'courier_id'         => '',
            'payment_type'       => $isCod ? 'cod' : 'prepaid',
            'order_amount'       => $total,
            'shipping_charges'   => (float) ($o['shippingLine']['originalPriceSet']['shopMoney']['amount'] ?? 0),
            'cod_charges'        => 0,
            'discount'           => (float) ($o['totalDiscountsSet']['shopMoney']['amount'] ?? 0),
            'collectable_amount' => round($collectable, 2),
            'package_type'       => 'box',
            'package_weight'     => $grams > 0 ? $grams : 500,
            'package_length'     => 20,
            'package_breadth'    => 15,
            'package_height'     => 10,
            'consignee'          => $consignee,
            'pickup'             => $pickup,
            'rto'                => array_merge($pickup, ['warehouse_name' => $warehouseName]),
            'order_items'        => $orderItems,
            'shopify_order_id'   => $legacyId,
            'shopify_order_name' => $o['name'],
            'financial_status'   => $o['displayFinancialStatus'] ?? null,
            'fulfillment_status' => $o['displayFulfillmentStatus'] ?? null,
            'shopify_created_at' => $o['createdAt'] ?? null,
        ];
    }

    /** Seller's default warehouse (falls back to first warehouse, then KYC address). */
    private function pickupAddress(): array
    {
        $seller = SellerList::find($this->conn->seller_id);
        $wh = Warehouse::where('seller_id', $this->conn->seller_id)->orderByDesc('is_default')->orderBy('id')->first();

        if ($wh) {
            return [[
                'name'     => $wh->name,
                'email'    => $wh->email,
                'phone'    => self::cleanPhone($wh->phone),
                'address'  => $wh->address_line1,
                'address2' => $wh->address_line2,
                'city'     => $wh->city,
                'state'    => $wh->state,
                'country'  => $wh->country ?: 'India',
                'pincode'  => $wh->pincode,
            ], $wh->address_title];
        }

        $sa = SellerAddress::where('seller_id', $this->conn->seller_id)->first();
        if (!$sa) {
            throw new \RuntimeException('No pickup warehouse. Add a warehouse in the panel first.');
        }
        return [[
            'name'     => $seller->company_name ?? $seller->name ?? '',
            'email'    => $seller->email ?? '',
            'phone'    => self::cleanPhone($seller->mobile ?? ''),
            'address'  => $sa->address_line ?? '',
            'address2' => '',
            'city'     => $sa->city_id ?? '',
            'state'    => $sa->state ?? '',
            'country'  => 'India',
            'pincode'  => $sa->pincode ?? '',
        ], 'Primary'];
    }

    public static function isCod(array $o): bool
    {
        foreach ($o['paymentGatewayNames'] ?? [] as $g) {
            if (preg_match('/\bcod\b|cash on delivery|cash_on_delivery|pay on delivery/i', $g)) {
                return true;
            }
        }
        // "manual" payment that is still unpaid is how most Indian stores run COD
        return ($o['displayFinancialStatus'] ?? '') === 'PENDING'
            && collect($o['paymentGatewayNames'] ?? [])->contains(fn ($g) => stripos($g, 'manual') !== false);
    }

    public static function cleanPhone($p): string
    {
        $d = preg_replace('/\D/', '', (string) $p);
        return strlen($d) > 10 ? substr($d, -10) : $d;
    }

    private static function toGrams(?array $w): float
    {
        if (!$w || !isset($w['value'])) {
            return 0;
        }
        $v = (float) $w['value'];
        return match (strtoupper($w['unit'] ?? 'GRAMS')) {
            'KILOGRAMS' => $v * 1000,
            'POUNDS'    => $v * 453.592,
            'OUNCES'    => $v * 28.3495,
            default     => $v,
        };
    }
}
