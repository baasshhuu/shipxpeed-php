<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Recharge;
use App\Models\SellerList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shipclues (api.shipclues.in) aggregator integration.
 *
 * One platform-level Shipclues account serves every seller. Each Shipclues courier
 * enabled on that account is offered to all sellers automatically.
 *
 * Docs: https://documenter.getpostman.com/view/32613966/2sBXcLewUc
 *
 * .env (optional, overrides the defaults below):
 *   SHIPCLUES_API_KEY=...
 *   SHIPCLUES_MARKUP_PERCENT=20      seller price = Shipclues cost + this %
 *   SHIPCLUES_MARKUP_FLAT=0          plus this flat amount per shipment
 *   SHIPCLUES_ENABLED=true
 */
class ShipcluesService implements CourierServiceInterface
{
    public const CODE = 'shipclues';
    private const BASE_URL = 'https://api.shipclues.in/seller/v1/';

    // Fallbacks used when .env has no value (or when config is cached and env() returns null).
    private const DEFAULT_MARKUP_PERCENT = 20;
    private const DEFAULT_MARKUP_FLAT = 0;

    /* =========================================================
     |  Config helpers
     * ========================================================= */

    /**
     * API key: SHIPCLUES_API_KEY in .env, otherwise the "API Key" saved on the
     * Shipclues row in Admin > Logistics (logistic_providers.api_key).
     */
    private function apiKey(): string
    {
        $key = env('SHIPCLUES_API_KEY');
        if (!$key) {
            $key = Cache::remember('shipclues_api_key', now()->addMinutes(5), function () {
                return (string) \App\Models\LogisticProvider::where('code', self::CODE)->value('api_key');
            });
        }
        return trim((string) $key);
    }

    /** Margin settings saved from Admin > Logistics > Shipclues (stored as JSON in api_secret). */
    public static function settingsFrom($provider): array
    {
        $saved = $provider ? json_decode((string) $provider->api_secret, true) : null;
        $saved = is_array($saved) ? $saved : [];

        $pct = env('SHIPCLUES_MARKUP_PERCENT');
        $flat = env('SHIPCLUES_MARKUP_FLAT');

        return [
            'markup_percent' => is_numeric($pct) ? (float) $pct : (float) ($saved['markup_percent'] ?? self::DEFAULT_MARKUP_PERCENT),
            'markup_flat' => is_numeric($flat) ? (float) $flat : (float) ($saved['markup_flat'] ?? self::DEFAULT_MARKUP_FLAT),
        ];
    }

    private function settings(): array
    {
        return Cache::remember('shipclues_settings', now()->addMinutes(5), function () {
            return self::settingsFrom(\App\Models\LogisticProvider::where('code', self::CODE)->first());
        });
    }

    public function enabled(): bool
    {
        $flag = env('SHIPCLUES_ENABLED');
        $on = $flag === null ? true : filter_var($flag, FILTER_VALIDATE_BOOLEAN);
        return $on && $this->apiKey() !== '';
    }

    public function sellerPrice(float $cost): float
    {
        $s = $this->settings();
        return round($cost + ($cost * $s['markup_percent'] / 100) + $s['markup_flat'], 2);
    }

    /* =========================================================
     |  HTTP
     * ========================================================= */

    private function request(string $method, string $path, array $payload = [])
    {
        $http = Http::withHeaders(['Authorization' => 'Token ' . $this->apiKey()])
            ->acceptJson()
            ->timeout(40);

        $url = self::BASE_URL . ltrim($path, '/');
        $response = $method === 'GET' ? $http->get($url, $payload) : $http->asJson()->post($url, $payload);

        if ($response->failed()) {
            Log::warning('Shipclues API error', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 2000),
                'payload' => $method === 'GET' ? $payload : array_diff_key($payload, ['products' => 1]),
            ]);
        }

        return $response;
    }

    private function errorText($response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            foreach (['detail', 'message', 'error', 'errors'] as $k) {
                if (!empty($json[$k])) {
                    return is_string($json[$k]) ? $json[$k] : json_encode($json[$k]);
                }
            }
            return mb_substr(json_encode($json), 0, 300);
        }
        return mb_substr((string) $response->body(), 0, 300) ?: ('HTTP ' . $response->status());
    }

    /* =========================================================
     |  Serviceability (seller panel)
     * ========================================================= */

    public function getServiceability(array $params): array
    {
        if (!$this->enabled()) {
            return [];
        }

        try {
            $order = Order::findOrFail($params['order_id']);
            $pickup = $this->arr($order->pickup);
            $consignee = $this->arr($order->consignee);
            $isCod = strtolower((string) $order->payment_type) === 'cod';

            $rates = $this->rateCalculator(
                $pickup['pincode'] ?? null,
                $consignee['pincode'] ?? null,
                (float) $order->package_weight,
                (float) $order->package_length,
                (float) $order->package_breadth,
                (float) $order->package_height,
                $isCod
            );

            return array_map(fn ($r) => $this->toServiceabilityEntry($r), $rates);
        } catch (\Throwable $e) {
            Log::error('Shipclues getServiceability failed: ' . $e->getMessage(), ['order_id' => $params['order_id'] ?? null]);
            return [];
        }
    }

    public function getServiceability_bulk(array $params): array
    {
        $results = [];
        $ids = $params['order_ids'] ?? (isset($params['order_id']) ? [$params['order_id']] : []);
        foreach ($ids as $orderId) {
            foreach ($this->getServiceability(['order_id' => $orderId]) as $entry) {
                $results[] = $entry + ['order_id' => $orderId];
            }
        }
        return $results;
    }

    public function reversegetServiceability(array $params): array
    {
        return []; // Reverse pickups via Shipclues are not enabled yet.
    }

    /**
     * Rates for the public Shipxpeed API (no order yet).
     * $params: origin_pincode, destination_pincode, payment_type, package_weight, package_length/breadth/height
     */
    public function getRatesForParams(array $params): array
    {
        if (!$this->enabled()) {
            return [];
        }

        try {
            $rates = $this->rateCalculator(
                $params['origin_pincode'] ?? null,
                $params['destination_pincode'] ?? null,
                (float) ($params['package_weight'] ?? 500),
                (float) ($params['package_length'] ?? 10),
                (float) ($params['package_breadth'] ?? 10),
                (float) ($params['package_height'] ?? 10),
                strtolower((string) ($params['payment_type'] ?? 'prepaid')) === 'cod'
            );

            return array_map(function ($r) {
                $e = $this->toServiceabilityEntry($r);
                return [
                    'courier_id' => 'sxc_' . $e['serviceabilityId'],
                    'courierName' => $e['courierName'],
                    'courierCharge' => $e['courierCharge'],
                    'freightCharges' => $e['freightCharges'],
                    'codCharge' => $e['codCharge'],
                    'zone' => $e['zone'],
                    'estimated_delivery_date' => $e['edd'],
                    'chargeable_weight' => $e['minWeight'],
                ];
            }, $rates);
        } catch (\Throwable $e) {
            Log::error('Shipclues getRatesForParams failed: ' . $e->getMessage());
            return [];
        }
    }

    private function rateCalculator($from, $to, float $weight, float $l, float $b, float $h, bool $cod): array
    {
        if (!$from || !$to) {
            return [];
        }

        $query = [
            'payment_mode' => $cod ? 'cod' : 'prepaid',
            'pickup_pincode' => (string) $from,
            'shipping_pincode' => (string) $to,
            'package_length_in_cm' => $l > 0 ? $l : 10,
            'package_breadth_in_cm' => $b > 0 ? $b : 10,
            'package_height_in_cm' => $h > 0 ? $h : 10,
            'package_weight_in_gm' => $weight > 0 ? $weight : 500,
        ];

        $cacheKey = 'shipclues_rates_' . md5(json_encode($query));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($query) {
            $res = $this->request('GET', 'rate-calculator/', $query);
            $rows = $res->successful() ? $res->json() : [];
            return is_array($rows) ? array_values(array_filter($rows, fn ($r) => isset($r['courier_id']))) : [];
        });
    }

    private function toServiceabilityEntry(array $r): array
    {
        $cost = (float) ($r['total_charges'] ?? 0);
        $price = $this->sellerPrice($cost);
        $codCost = (float) ($r['cod_charges'] ?? 0);
        $weight = (float) ($r['charged_weight_in_gm'] ?? $r['weight_in_gm'] ?? 0);
        // Split the seller price into freight + COD in the same ratio as Shipclues' cost.
        $codSeller = ($cost > 0 && $codCost > 0) ? round($price * $codCost / $cost, 2) : 0;

        return [
            'serviceabilityId' => (int) $r['courier_id'],          // Shipclues courier id
            'courierName' => \App\Support\CourierBrand::cleanName($r['courier_name'] ?? null),
            'courierCharge' => $price,
            'freightCharges' => round($price - $codSeller, 2),
            'codCharge' => $codSeller,
            'zone' => $r['zone'] ?? null,
            'zone_courier_name' => \App\Support\CourierBrand::cleanName($r['courier_name'] ?? null),
            'minWeight' => $weight,
            'volWeight' => (float) ($r['vol_weight_in_gm'] ?? $weight),
            'edd' => $r['estimated_delivery_date'] ?? null,
            'logoHint' => $this->logoFor((string) ($r['courier_name'] ?? ''), $r['courier_logo'] ?? null, (string) ($r['courier_keyword'] ?? $r['courier_id'] ?? '')),
        ];
    }

    /**
     * Courier logo for sellers. Uses our own brand images first; otherwise copies the
     * courier's logo from the API into public/brands/couriers once, so the seller's
     * browser never loads an aggregator-hosted URL.
     */
    private function logoFor(string $name, ?string $remoteLogo = null, string $keyword = ''): ?string
    {
        if ($local = \App\Support\CourierBrand::logo($name)) {
            return $local;
        }
        if (!$remoteLogo || !str_starts_with($remoteLogo, 'http')) {
            return null;
        }

        $slug = \Illuminate\Support\Str::slug($keyword !== '' ? $keyword : $name) ?: md5($name);
        $dir = public_path('brands/couriers');
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
            if (is_file("$dir/$slug.$ext")) {
                return "brands/couriers/$slug.$ext";
            }
        }

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(6)->get($remoteLogo);
            $type = strtolower((string) $res->header('Content-Type'));
            $ext = match (true) {
                str_contains($type, 'png') => 'png',
                str_contains($type, 'webp') => 'webp',
                str_contains($type, 'svg') => 'svg',
                str_contains($type, 'jpeg'), str_contains($type, 'jpg') => 'jpg',
                default => null,
            };
            if ($res->successful() && $ext && strlen($res->body()) > 100) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                file_put_contents("$dir/$slug.$ext", $res->body());
                return "brands/couriers/$slug.$ext";
            }
        } catch (\Throwable $e) {
            Log::warning('Courier logo download failed: ' . $e->getMessage());
        }
        return null;
    }

    /* =========================================================
     |  Assign (book) an order
     * ========================================================= */

    /**
     * $params: order_id, serviceability_id (Shipclues courier id), provider_name, seller_id (optional, for API calls)
     */
    public function assignOrder($params)
    {
        $params = (array) $params;
        $orderId = $params['order_id'] ?? null;
        $courierId = (int) ($params['serviceability_id'] ?? 0);

        $row = \App\Models\LogisticProvider::where('code', self::CODE)->first();
        if (!$this->enabled() || ($row && (string) $row->status !== '1')) {
            return $this->fail('This courier is currently unavailable.');
        }

        $order = Order::find($orderId);
        if (!$order) {
            return $this->fail('Order not found.');
        }

        if (!empty($order->awb_number) && strtolower((string) $order->order_status) !== 'cancelled') {
            return $this->fail('This order already has AWB ' . $order->awb_number . '.');
        }

        $seller = Auth::guard('seller')->user();
        if (!$seller || (int) $seller->id !== (int) $order->seller_id) {
            $seller = SellerList::find($params['seller_id'] ?? $order->seller_id);
        }
        if (!$seller || (int) $seller->status !== 1) {
            return $this->fail('Unauthorized or inactive seller.');
        }

        $pickup = $this->arr($order->pickup);
        $consignee = $this->arr($order->consignee);

        try {
            // 1. Pickup address -> Shipclues warehouse
            $warehouseId = $this->ensureWarehouse((int) $seller->id, $pickup);

            // 2. Create the order on Shipclues
            $scOrder = $this->createRemoteOrder($order, $warehouseId, $pickup, $consignee);
            $scOrderId = (int) $scOrder['id'];

            // 3. Exact rate for this order -> seller price (never trust the browser-posted amount)
            $ratesRes = $this->request('GET', 'order/shipping-rates/', ['order_id' => $scOrderId]);
            $rates = $ratesRes->successful() && is_array($ratesRes->json()) ? $ratesRes->json() : [];
            $rate = null;
            foreach ($rates as $r) {
                if ((int) ($r['courier_id'] ?? 0) === $courierId) {
                    $rate = $r;
                    break;
                }
            }
            if (!$rate && !$courierId && $rates) {
                usort($rates, fn ($a, $b) => ($a['total_charges'] ?? 0) <=> ($b['total_charges'] ?? 0));
                $rate = $rates[0];
                $courierId = (int) $rate['courier_id'];
            }
            if (!$rate) {
                return $this->fail('The selected courier is not serviceable for this order.');
            }

            $sellerCharge = $this->sellerPrice((float) ($rate['total_charges'] ?? 0));

            // 4. Wallet check (same rule as the other couriers)
            $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
            $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
            $walletBalance = $credit - $debit;
            if ($seller->negative_balance != '1' && ($walletBalance < $sellerCharge || $walletBalance < 150)) {
                return $this->fail('Insufficient wallet balance. Please recharge your wallet.');
            }

            // 5. Ship -> AWB
            $shipRes = $this->request('POST', 'order/ship/', ['order_id' => $scOrderId, 'courier_id' => $courierId]);
            $ship = $shipRes->json() ?? [];
            if (!$shipRes->successful() || (empty($ship['courier_awb_number']) && empty($ship['awb_number']))) {
                return $this->fail('The courier could not assign an AWB: ' . \App\Support\CourierBrand::cleanName($this->errorText($shipRes), 'please try another courier'));
            }

            $awb = $ship['courier_awb_number'] ?: $ship['awb_number'];
            $actualCost = (float) ($ship['total_charges'] ?? $rate['total_charges'] ?? 0);
            $sellerCharge = $this->sellerPrice($actualCost);
            $courierName = \App\Support\CourierBrand::cleanName($ship['courier_name'] ?? $rate['courier_name'] ?? ($params['provider_name'] ?? null));

            // 6. Save on our order
            $order->courier_id = self::CODE;
            $order->all_courier_name = $courierName;
            $order->courier_name = $courierName;
            $order->awb_number = $awb;
            $order->courier_order_id = $scOrderId;
            $order->shipment_id = $ship['shipment_id'] ?? null;
            $order->co_courier_id = $courierId;
            $order->fwd_destination_code = $ship['route_code'] ?? null;
            $order->co_payment_type = $order->payment_type;
            $order->zone = $rate['zone'] ?? $order->zone;
            $order->zone_courier_name = $courierName;
            $order->seller_amount_walate = $sellerCharge;
            $order->shipping_status = 'assigned';
            $order->shipping_date = Carbon::now()->format('Y-m-d');
            $order->additional_info = [
                'provider' => 'shipclues',
                'shipclues_order_id' => $scOrderId,
                'shipclues_awb' => $ship['awb_number'] ?? null,
                'courier_awb' => $ship['courier_awb_number'] ?? null,
                'shipclues_cost' => $actualCost,
                'route_code' => $ship['route_code'] ?? null,
            ];
            $order->save();

            // 7. Debit wallet
            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $sellerCharge,
                'status' => 1,
                'description' => 'Order created',
            ]);

            return [
                'status' => true,
                'couriername' => self::CODE,
                'message' => 'Courier assigned successfully via ' . $courierName,
                'awb_number' => $awb,
                'courier_name' => $courierName,
                'charge' => $sellerCharge,
            ];
        } catch (\Throwable $e) {
            Log::error('Shipclues assignOrder failed: ' . $e->getMessage(), ['order_id' => $orderId]);
            return $this->fail('Courier booking failed. Please try again or choose another courier.');
        }
    }

    public function assignOrderbulk($params)
    {
        return $this->assignOrder($params);
    }

    public function assignOrder_bulk($params)
    {
        return $this->assignOrder($params);
    }

    private function createRemoteOrder(Order $order, int $warehouseId, array $pickup, array $consignee): array
    {
        $items = $this->arr($order->order_items);
        $isCod = strtolower((string) $order->payment_type) === 'cod';

        $products = [];
        $subTotal = 0;
        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? $item['quantity'] ?? 1));
            $price = (float) ($item['price'] ?? $item['unit_price'] ?? 0);
            $subTotal += $qty * $price;
            $products[] = [
                'sku' => (string) ($item['sku'] ?? 'SKU'),
                'name' => (string) ($item['name'] ?? 'Product'),
                'description' => (string) ($item['name'] ?? 'Product'),
                'quantity' => $qty,
                'unit_price' => number_format($price, 2, '.', ''),
                'discount_amount' => '0.00',
                'is_tax_inclusive' => true,
                'tax_rate' => '0.00',
                'hsn_code' => (string) ($item['hsn'] ?? $item['hsn_code'] ?? ''),
            ];
        }

        $total = (float) ($order->order_amount ?: $subTotal);
        if (!$products) {
            $products[] = [
                'sku' => 'ITEM', 'name' => 'Item', 'description' => 'Item', 'quantity' => 1,
                'unit_price' => number_format($total, 2, '.', ''), 'discount_amount' => '0.00',
                'is_tax_inclusive' => true, 'tax_rate' => '0.00', 'hsn_code' => '',
            ];
        }

        $email = $consignee['email'] ?? null;
        if (!$email || strtolower($email) === 'na' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        $addr = [
            'name' => (string) ($consignee['name'] ?? 'Customer'),
            'email' => $email,
            'phone_number' => $this->phone($consignee['phone'] ?? ''),
            'isd_code' => '+91',
            'address_line1' => (string) ($consignee['address'] ?? ''),
            'address_line2' => (string) ($consignee['address_2'] ?? ''),
            'landmark' => null,
            'pincode' => (string) ($consignee['pincode'] ?? ''),
            'city' => (string) ($consignee['city'] ?? ''),
            'state' => (string) ($consignee['state'] ?? ''),
            'country' => 'India',
        ];

        $weight = (float) ($order->package_weight ?: 500);
        $cod = $isCod ? (float) ($order->collectable_amount ?: $total) : 0;

        $payload = [
            'order_number' => (string) $order->order_number,
            'customer_name' => $addr['name'],
            'customer_email' => $email,
            'customer_phone_number' => $addr['phone_number'],
            'customer_isd_code' => '+91',
            'warehouse_id' => $warehouseId,
        ];
        foreach ($addr as $k => $v) {
            $payload['shipping_' . $k] = $v;
            $payload['billing_' . $k] = $v;
        }
        $payload += [
            'package_length_in_cm' => number_format((float) ($order->package_length ?: 10), 2, '.', ''),
            'package_breadth_in_cm' => number_format((float) ($order->package_breadth ?: 10), 2, '.', ''),
            'package_height_in_cm' => number_format((float) ($order->package_height ?: 10), 2, '.', ''),
            'package_weight_in_gm' => number_format($weight, 2, '.', ''),
            'package_weight_in_kg' => round($weight / 1000, 3),
            'is_tax_inclusive' => true,
            'subtotal_amount' => number_format($total, 2, '.', ''),
            'total_amount' => number_format($total, 2, '.', ''),
            'payment_mode' => $isCod ? 'cod' : 'prepaid',
            'is_cod' => $isCod,
            'cod_collectable_amount' => number_format($cod, 2, '.', ''),
            'is_mps' => false,
            'number_of_packets' => 1,
            'is_reverse' => false,
            'is_reverse_qc' => false,
            'order_date' => ($order->created_at ?: now())->format('Y-m-d\TH:i:s'),
            'products' => $products,
        ];

        $res = $this->request('POST', 'order/create/', $payload);

        // Order number already used on Shipclues (e.g. a retry) -> retry once with a unique suffix.
        if (!$res->successful() && str_contains(strtolower($this->errorText($res)), 'exist')) {
            $payload['order_number'] = $order->order_number . '-R' . substr((string) time(), -5);
            $res = $this->request('POST', 'order/create/', $payload);
        }

        $data = $res->json();
        if (!$res->successful() || empty($data['id'])) {
            throw new \RuntimeException('order create failed: ' . $this->errorText($res));
        }

        return $data;
    }

    /** Find or create the Shipclues warehouse for this pickup address; returns Shipclues warehouse id. */
    private function ensureWarehouse(int $sellerId, array $p): int
    {
        $sig = md5(strtolower(implode('|', [
            $sellerId, $p['address'] ?? '', $p['pincode'] ?? '', $this->phone($p['phone'] ?? ''), $p['name'] ?? '',
        ])));
        $code = 'SX' . $sellerId . strtoupper(substr($sig, 0, 6));
        $cacheKey = 'shipclues_wh_' . $code;

        if ($id = Cache::get($cacheKey)) {
            return (int) $id;
        }

        // Already created earlier? (Shipclues appends its own suffix to the code.)
        for ($page = 1; $page <= 30; $page++) {
            $res = $this->request('GET', 'warehouses/', ['page' => $page]);
            $rows = $res->json('data') ?? [];
            foreach ($rows as $w) {
                if (str_starts_with((string) ($w['warehouse_code'] ?? ''), $code)) {
                    Cache::forever($cacheKey, (int) $w['id']);
                    return (int) $w['id'];
                }
            }
            $next = $res->json('pagination.next') ?? $res->json('pagination.next_page') ?? null;
            $totalPages = (int) ($res->json('pagination.total_pages') ?? $res->json('pagination.num_pages') ?? 0);
            if (!$rows || (!$next && (!$totalPages || $page >= $totalPages))) {
                break;
            }
        }

        $phone = $this->phone($p['phone'] ?? '');
        $name = trim((string) ($p['name'] ?? $p['warehouse_name'] ?? 'Seller'));
        $whName = mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', ($p['warehouse_name'] ?? $name)) ?: 'Warehouse', 0, 20) . ' ' . $sellerId;
        $email = filter_var($p['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $p['email'] : 'support@shipxpeed.com';

        $addr = [
            'full_name' => $name,
            'email' => $email,
            'phone_number' => $phone,
            'isd_code' => '+91',
            'alt_phone_number' => $phone,
            'alt_isd_code' => '+91',
            'support_phone_number' => $phone,
            'support_isd_code' => '+91',
            'address_line1' => (string) ($p['address'] ?? ''),
            'address_line2' => (string) ($p['address_2'] ?? ''),
            'landmark' => '',
            'pincode' => (string) ($p['pincode'] ?? ''),
            'city' => (string) ($p['city'] ?? ''),
            'state' => (string) ($p['state'] ?? ''),
            'country' => 'India',
            'gst_number' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        $payload = ['warehouse_code' => $code, 'warehouse_name' => mb_substr($whName, 0, 30), 'tags' => null] + $addr;
        foreach ($addr as $k => $v) {
            $payload['return_' . $k] = $v;
        }
        $payload['return_warehouse_name'] = $payload['warehouse_name'];
        $payload['same_as_rto_address'] = true;
        $payload['is_default'] = false;

        $res = $this->request('POST', 'warehouse/create/', $payload);
        $id = $res->json('id');
        if (!$res->successful() || !$id) {
            throw new \RuntimeException('pickup address could not be registered: ' . $this->errorText($res));
        }

        Cache::forever($cacheKey, (int) $id);
        return (int) $id;
    }

    /* =========================================================
     |  Cancel
     * ========================================================= */

    public function cancelShipment($awb)
    {
        $order = Order::where('awb_number', $awb)->first();
        if (!$order) {
            return ['status' => false, 'success' => false, 'message' => 'Order not found with AWB: ' . $awb];
        }
        if (strtolower((string) $order->order_status) === 'cancelled') {
            return ['status' => true, 'success' => true, 'message' => 'Already cancelled', 'responseCode' => 200];
        }

        $info = $this->arr($order->additional_info);
        $scOrderId = (int) ($info['shipclues_order_id'] ?? $order->courier_order_id);
        if (!$scOrderId) {
            return ['status' => false, 'success' => false, 'message' => 'Courier order reference missing for AWB ' . $awb];
        }

        $res = $this->request('POST', 'order/cancel-shipment/', ['order_id' => $scOrderId, 'cancel_order' => true]);
        if (!$res->successful()) {
            return [
                'status' => false,
                'success' => false,
                'message' => 'Cancellation failed: ' . \App\Support\CourierBrand::cleanName($this->errorText($res), 'please try again'),
                'responseCode' => $res->status(),
            ];
        }

        $order->order_status = 'cancelled';
        $order->shipping_status = 'cancelled';
        $order->save();

        // Refund once
        $alreadyRefunded = Recharge::where('seller_id', $order->seller_id)
            ->where('type', 'Credit')
            ->where('description', 'Order cancelled - AWB ' . $awb)
            ->exists();
        if (!$alreadyRefunded && (float) $order->seller_amount_walate > 0) {
            Recharge::create([
                'seller_id' => $order->seller_id,
                'type' => 'Credit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order cancelled - AWB ' . $awb,
            ]);
        }

        return [
            'status' => true,
            'success' => true,
            'message' => 'Shipment cancelled successfully',
            'data' => $res->json(),
            'responseCode' => 200,
        ];
    }

    /* =========================================================
     |  Tracking (used by "php artisan shipclues:track")
     * ========================================================= */

    public function trackOrder(Order $order): ?string
    {
        $info = $this->arr($order->additional_info);
        $awb = $order->awb_number ?: ($info['shipclues_awb'] ?? null);
        if (!$awb) {
            return null;
        }

        $res = $this->request('GET', 'order/tracking/', ['awb_number' => $awb]);
        $row = $res->successful() ? ($res->json()[0] ?? null) : null;
        if (!$row && !empty($info['shipclues_awb']) && $info['shipclues_awb'] !== $awb) {
            $res = $this->request('GET', 'order/tracking/', ['awb_number' => $info['shipclues_awb']]);
            $row = $res->successful() ? ($res->json()[0] ?? null) : null;
        }
        if (!$row) {
            return null;
        }

        $raw = strtolower(str_replace(['_', '-'], ' ', (string) ($row['status'] ?? '')));
        $mapped = match (true) {
            str_contains($raw, 'rto') && str_contains($raw, 'deliver') => 'rto delivered',
            str_contains($raw, 'rto') || !empty($row['is_rto']) => 'rto',
            str_contains($raw, 'out for delivery') => 'out for delivery',
            str_contains($raw, 'deliver') && !str_contains($raw, 'undeliver') => 'delivered',
            str_contains($raw, 'ndr') || str_contains($raw, 'undeliver') => 'NDR',
            str_contains($raw, 'cancel') => 'cancelled',
            str_contains($raw, 'lost') => 'lost',
            str_contains($raw, 'picked') => 'picked up',
            str_contains($raw, 'transit') || str_contains($raw, 'dispatch') || str_contains($raw, 'reached') => 'transit',
            str_contains($raw, 'manifest') => 'manifested',
            default => 'assigned', // shipped / pickup scheduled / awb assigned
        };

        $order->shipping_status = $mapped;
        if ($mapped === 'delivered') {
            $order->delivered_date = !empty($row['delivered_date'])
                ? date('Y-m-d', strtotime($row['delivered_date']))
                : now()->format('Y-m-d');
        }
        $order->save();

        return $mapped;
    }

    /* =========================================================
     |  Helpers
     * ========================================================= */

    private function arr($v): array
    {
        if (is_array($v)) {
            return $v;
        }
        if (is_object($v)) {
            return json_decode(json_encode($v), true) ?: [];
        }
        $d = json_decode((string) $v, true);
        return is_array($d) ? $d : [];
    }

    private function phone($p): string
    {
        $d = preg_replace('/\D/', '', (string) $p);
        return strlen($d) > 10 ? substr($d, -10) : $d;
    }

    private function fail(string $msg): array
    {
        return ['status' => false, 'success' => false, 'couriername' => self::CODE, 'message' => $msg];
    }
}
