<?php

namespace App\Services;

use App\Models\ActicvSleb;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\ZonePriceSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fship aggregator adapter (API guide v1.2.3.3).
 *
 * One Fship account gives access to many couriers (Blue Dart, DTDC, Delhivery,
 * Amazon, Xpressbees ...). Every Fship courier is shown to sellers as its own
 * option; the Fship courierId travels as the serviceability id and the courier
 * name as provider_name, so assignOrder() knows which courier to book.
 *
 * Registered in config/couriers.php under the code "fship"; the matching
 * logistic_providers row (code = fship) is created by the 2026_10_25 migration.
 */
class FshipService implements CourierServiceInterface
{
    public const CODE = 'fship';

    private static ?bool $logTableExists = null;
    private static ?bool $warehouseTableExists = null;

    /* ------------------------------------------------------------------ */
    /*  HTTP client                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Client key: FSHIP_API_KEY in .env, otherwise the key saved in
     * Admin > Fship Integration (logistic_providers.api_key, code = fship).
     * Staging always uses the public sandbox key from config/fship.php.
     */
    public function apiKey(): string
    {
        $key = (string) config('fship.api_key');
        if ($key === '' && config('fship.env') !== 'staging') {
            try {
                $key = (string) Cache::remember('fship_api_key', now()->addMinutes(5), function () {
                    return (string) DB::table('logistic_providers')->where('code', self::CODE)->value('api_key');
                });
            } catch (\Throwable $e) {
                $key = '';
            }
        }
        return trim($key);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    /**
     * Call a Fship endpoint. Never throws: returns
     * ['ok' => bool, 'http' => int, 'data' => array|null, 'error' => string|null].
     */
    public function request(string $endpoint, ?array $payload = null, string $method = 'POST', array $ctx = []): array
    {
        $url = rtrim(config('fship.base_url'), '/') . '/api/' . ltrim($endpoint, '/');

        if (!$this->isConfigured()) {
            return ['ok' => false, 'http' => 0, 'data' => null, 'error' => 'Fship API key is not configured (FSHIP_API_KEY).'];
        }

        $started = microtime(true);
        $http = 0;
        $body = null;
        $data = null;
        $error = null;

        try {
            $client = Http::withHeaders([
                'signature'    => $this->apiKey(),
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(config('fship.timeout', 30));

            $response = strtoupper($method) === 'GET'
                ? $client->get($url)
                : $client->post($url, $payload ?? (object) []);

            $http = $response->status();
            $body = $response->body();
            $data = $response->json();

            if ($http === 401) {
                $error = 'Fship rejected the client key (401 Unauthorized). Check the key / API access on the Fship account.';
                // Stop hitting Fship on every courier-page load while the key is rejected
                Cache::put('fship_unauthorized_' . md5($this->apiKey()), true, now()->addMinutes(10));
            } elseif ($http >= 500) {
                $error = "Fship server error (HTTP {$http}).";
            } elseif (!is_array($data)) {
                $error = "Unexpected Fship response (HTTP {$http}).";
            } elseif (array_key_exists('status', $data) && $data['status'] === false) {
                $error = (string) ($data['response'] ?? $data['message'] ?? 'Fship returned status=false');
            } elseif ($http >= 400) {
                $error = (string) ($data['response'] ?? $data['title'] ?? "Fship HTTP {$http}");
            }
        } catch (\Throwable $e) {
            $error = 'Fship connection failed: ' . $e->getMessage();
        }

        $this->log($endpoint, $method, $payload, $http, $body, $error === null, (int) round((microtime(true) - $started) * 1000), $ctx);

        return ['ok' => $error === null, 'http' => $http, 'data' => is_array($data) ? $data : null, 'error' => $error];
    }

    private function log(string $endpoint, string $method, ?array $payload, int $http, ?string $body, bool $ok, int $ms, array $ctx): void
    {
        if (!$ok) {
            Log::warning('Fship API failure', ['endpoint' => $endpoint, 'http' => $http, 'body' => mb_substr((string) $body, 0, 2000), 'ctx' => $ctx]);
        }

        try {
            if (self::$logTableExists === null) {
                self::$logTableExists = Schema::hasTable('courier_api_logs');
            }
            if (!self::$logTableExists) {
                return;
            }
            DB::table('courier_api_logs')->insert([
                'courier'     => self::CODE,
                'endpoint'    => $endpoint,
                'method'      => strtoupper($method),
                'request'     => $payload === null ? null : mb_substr(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 60000),
                'response'    => $body === null ? null : mb_substr($body, 0, 60000),
                'http_status' => $http,
                'success'     => $ok,
                'duration_ms' => $ms,
                'order_id'    => $ctx['order_id'] ?? null,
                'awb'         => $ctx['awb'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // Logging must never break shipping.
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Raw API wrappers (one per endpoint in the Fship guide)             */
    /* ------------------------------------------------------------------ */

    /** Courier list: [['courierId'=>1,'courierName'=>'...','logoUrl'=>'...','isActive'=>true], ...] */
    public function getCouriers(bool $fresh = false): array
    {
        $key = 'fship_couriers_' . md5((string) config('fship.base_url') . $this->apiKey());
        if ($fresh) {
            Cache::forget($key);
        }
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }
        $res = $this->request('getallcourier', null, 'GET');
        $list = ($res['http'] === 200 && is_array($res['data']) && array_is_list($res['data'])) ? $res['data'] : [];
        if ($list) {
            Cache::put($key, $list, now()->addHours(6));
        }
        return $list;
    }

    public function rateCalculator(array $payload, array $ctx = []): array
    {
        return $this->request('ratecalculator', $payload, 'POST', $ctx);
    }

    public function pincodeServiceability(string $from, string $to): array
    {
        return $this->request('pincodeserviceability', ['source_Pincode' => $from, 'destination_Pincode' => $to]);
    }

    public function addWarehouse(array $payload): array
    {
        return $this->request('addwarehouse', $payload);
    }

    public function updateWarehouse(array $payload): array
    {
        return $this->request('updatewarehouse', $payload);
    }

    public function createForwardOrder(array $payload, array $ctx = []): array
    {
        return $this->request('createforwardorder', $payload, 'POST', $ctx);
    }

    public function createReverseOrder(array $payload, array $ctx = []): array
    {
        return $this->request('createreverseorder', $payload, 'POST', $ctx);
    }

    public function registerPickup(array $waybills, array $ctx = []): array
    {
        return $this->request('registerpickup', ['waybills' => array_values($waybills)], 'POST', $ctx);
    }

    public function shippingLabel(string $waybills, array $ctx = []): array
    {
        return $this->request('shippinglabel', ['waybill' => $waybills], 'POST', $ctx);
    }

    public function shippingLabelByPickupId(array $pickupOrderIds): array
    {
        return $this->request('shippinglabelbypickupid', ['pickupOrderId' => array_map('intval', $pickupOrderIds)]);
    }

    public function trackingHistory(string $awb): array
    {
        return $this->request('trackinghistory', ['waybill' => $awb], 'POST', ['awb' => $awb]);
    }

    public function cancelOrder(string $awb, string $reason = ''): array
    {
        return $this->request('cancelorder', ['waybill' => $awb, 'reason' => $reason], 'POST', ['awb' => $awb]);
    }

    /** action: re-attempt | change-address | change-phone | rto */
    public function reattemptOrder(array $payload, array $ctx = []): array
    {
        return $this->request('reattemptorder', $payload, 'POST', $ctx);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    public static function rateKey(string $courierName): string
    {
        return config('fship.rate_key_prefix', 'Fship_') . trim(preg_replace('/[^A-Za-z0-9]+/', '_', $courierName), '_');
    }

    /** Rate-card keys for the admin Zone Price Setting dropdown (cached courier list). */
    public static function rateCardKeys(): array
    {
        try {
            $svc = new self();
            if (!$svc->isConfigured()) {
                return [];
            }
            return collect($svc->getCouriers())
                ->filter(fn ($c) => ($c['isActive'] ?? true) && !empty($c['courierName']))
                ->map(fn ($c) => self::rateKey($c['courierName']))
                ->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Brand logo already used elsewhere in the panel, by courier family. */
    public static function logoFor(string $courierName, ?string $fallback = null): ?string
    {
        $n = strtolower($courierName);
        return match (true) {
            str_contains($n, 'delhivery')  => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
            str_contains($n, 'blue')       => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
            str_contains($n, 'amazon')     => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
            str_contains($n, 'dtdc')       => 'brands/1750337596_images-removebg-preview.png',
            str_contains($n, 'xpress')     => 'brands/Xpressbee.png',
            str_contains($n, 'ekart')      => 'brands/ecart.png',
            str_contains($n, 'maruti')     => 'brands/Shreemaruti.jpg',
            default                        => $fallback,
        };
    }

    private function decode($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?: [];
        }
        return json_decode((string) $value, true) ?: [];
    }

    private function phone10($phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    /** Order weight/dimensions in Fship units (kg, cm). Panel stores weight in grams. */
    private function dims(Order $order): array
    {
        $l = max(1.0, (float) ($order->package_length ?: 10));
        $w = max(1.0, (float) ($order->package_breadth ?: 10));
        $h = max(1.0, (float) ($order->package_height ?: 10));
        $kg = max(0.05, round(((float) ($order->package_weight ?: 500)) / 1000, 3));
        $vol = round(($l * $w * $h) / 5000, 3);
        return ['weight' => $kg, 'length' => $l, 'width' => $w, 'height' => $h, 'vol' => $vol];
    }

    private function isCod(Order $order): bool
    {
        return strtolower((string) $order->payment_type) === 'cod';
    }

    private function invoiceValue(Order $order): float
    {
        $items = $this->decode($order->order_items);
        $itemsTotal = 0.0;
        foreach ($items as $it) {
            $itemsTotal += (float) ($it['price'] ?? 0) * max(1, (int) ($it['qty'] ?? 1));
        }
        return round(max((float) $order->order_amount, (float) $order->collectable_amount, $itemsTotal, 1), 2);
    }

    /** Live Fship rates for an order (cached 5 minutes). */
    public function ratesForOrder(Order $order): array
    {
        $pickup = $this->decode($order->pickup);
        $consignee = $this->decode($order->consignee);
        $d = $this->dims($order);

        $payload = [
            'source_Pincode'      => (string) ($pickup['pincode'] ?? ''),
            'destination_Pincode' => (string) ($consignee['pincode'] ?? ''),
            'payment_Mode'        => $this->isCod($order) ? 'COD' : 'P',
            'amount'              => $this->invoiceValue($order),
            'express_Type'        => 'surface',
            'shipment_Weight'     => $d['weight'],
            'shipment_Length'     => $d['length'],
            'shipment_Width'      => $d['width'],
            'shipment_Height'     => $d['height'],
            'volumetric_Weight'   => $d['vol'],
        ];

        if ($payload['source_Pincode'] === '' || $payload['destination_Pincode'] === '') {
            return [];
        }

        $cacheKey = 'fship_rates_' . md5(json_encode($payload) . config('fship.base_url'));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
        if (Cache::has('fship_unauthorized_' . md5($this->apiKey()))) {
            return [];
        }
        $res = $this->rateCalculator($payload, ['order_id' => $order->id]);
        if (!$res['ok']) {
            return [];   // don't cache failures / "not serviceable"
        }
        $rates = array_values(array_filter($res['data']['shipment_rates'] ?? [], 'is_array'));
        Cache::put($cacheKey, $rates, now()->addMinutes(5));
        return $rates;
    }

    private function hiddenForSeller(int $sellerId, string $rateKey): bool
    {
        return !\App\Support\CourierAccess::isOn($sellerId, $rateKey, 'Fship');
    }

    private function zoneFor(array $pickup, array $consignee): ?string
    {
        if (empty($pickup['state']) || empty($consignee['state'])) {
            return null;
        }
        $row = DB::table('pincode_zones')
            ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickup['state'])])
            ->whereRaw('LOWER(deliver_state) = ?', [strtolower($consignee['state'])])
            ->first();
        return $row ? strtoupper($row->zone) : null;
    }

    /** Same zone rate-card maths the other aggregators in this panel use (weight in grams, 500 g slabs). */
    private function priceFromRateCard($zonePricing, float $weightGrams, bool $cod, float $orderAmount): ?array
    {
        if (!$zonePricing) {
            return null;
        }
        $slabs = max(1, ceil($weightGrams / 500));

        if ($cod) {
            if ($zonePricing->cod_fix_price > 0) {
                $total = $zonePricing->cod_fix_price * $slabs;
                return ['courierCharge' => round($total, 2), 'freightCharges' => round($total, 2), 'codCharge' => 0];
            }
            $base = $zonePricing->cod_price * $slabs;
            $prepaid = $zonePricing->prepaid_price * $slabs;
            $codCharge = $orderAmount > 1400 ? ($orderAmount * ($zonePricing->cod_charge_parsent ?? 0) / 100) : 0;
            $sub = $base + $prepaid + $codCharge;
            return ['courierCharge' => round($sub * 1.18, 2), 'freightCharges' => round($base + $prepaid, 2), 'codCharge' => round($codCharge, 2)];
        }

        if ($zonePricing->prepaid_fix_price > 0) {
            $total = $zonePricing->prepaid_fix_price * $slabs;
            return ['courierCharge' => round($total, 2), 'freightCharges' => round($total, 2), 'codCharge' => 0];
        }
        $base = $zonePricing->prepaid_price * $slabs;
        return ['courierCharge' => round($base * 1.18, 2), 'freightCharges' => round($base, 2), 'codCharge' => 0];
    }

    /** Fallback: Fship cost + markup + GST. */
    private function priceFromFship(array $rate, bool $cod): array
    {
        $m = 1 + ((float) config('fship.markup_percent', 20)) / 100;
        $gst = 1 + ((float) config('fship.gst_percent', 18)) / 100;
        $freight = round((float) ($rate['shipping_charge'] ?? 0) * $m, 2);
        $codCharge = $cod ? round((float) ($rate['cod_charge'] ?? 0) * $m, 2) : 0.0;
        return ['courierCharge' => round(($freight + $codCharge) * $gst, 2), 'freightCharges' => $freight, 'codCharge' => $codCharge];
    }

    /* ------------------------------------------------------------------ */
    /*  Panel contract: serviceability                                     */
    /* ------------------------------------------------------------------ */

    public function getServiceability(array $params): array
    {
        if (!$this->isConfigured() || empty($params['order_id'])) {
            return [];
        }
        $order = Order::find($params['order_id']);
        if (!$order) {
            return [];
        }

        $rates = $this->ratesForOrder($order);
        if (!$rates) {
            return [];
        }

        $results = [];
        foreach ($rates as $rate) {
            $name = trim((string) ($rate['courier_name'] ?? ''));
            if ($name === '' || empty($rate['courier_id'])) {
                continue;
            }
            if ($this->hiddenForSeller((int) $order->seller_id, self::rateKey($name))) {
                continue;
            }
            $p = $this->priceForRate($order, $rate);

            $results[] = [
                'order_id'          => $order->id,
                'serviceabilityId'  => (int) $rate['courier_id'],   // Fship courierId
                'courierName'       => $name,
                'courierCharge'     => $p['courierCharge'],
                'freightCharges'    => $p['freightCharges'],
                'codCharge'         => $p['codCharge'],
                'zone'              => $p['zone'],
                'zone_courier_name' => $name,
                'service_mode'      => $rate['service_mode'] ?? null,
                'edd'               => $rate['expectedDeliveryDate'] ?? null,
                'minWeight'         => $p['weightGrams'],
                'volWeight'         => $p['weightGrams'],
            ];
        }

        return $results;
    }

    /** Seller price for one Fship rate row: zone rate card if set, else Fship cost + markup + GST. */
    public function priceForRate(Order $order, array $rate): array
    {
        $pickup = $this->decode($order->pickup);
        $consignee = $this->decode($order->consignee);
        $cod = $this->isCod($order);
        $d = $this->dims($order);
        $weightGrams = max($d['weight'], $d['vol']) * 1000;
        $zone = $this->zoneFor($pickup, $consignee);
        $key = self::rateKey((string) ($rate['courier_name'] ?? ''));

        $charges = null;
        if ($zone) {
            $defaultSeller = (int) config('fship.default_rate_seller_id', 14);
            $card = ZonePriceSetting::where(['seller_id' => $order->seller_id, 'zone' => $zone, 'LogisticProvider' => $key, 'status' => 1])->first()
                ?? ZonePriceSetting::where(['seller_id' => $defaultSeller, 'zone' => $zone, 'LogisticProvider' => $key, 'status' => 1])->first();
            $charges = $this->priceFromRateCard($card, $weightGrams, $cod, (float) $order->collectable_amount);
        }
        $charges ??= $this->priceFromFship($rate, $cod);

        return $charges + ['zone' => $zone ?? ($rate['zone_name'] ?? null), 'weightGrams' => $weightGrams];
    }

    /**
     * Seller API (Shipxpeedapi) serviceability without a saved order.
     * Returns courier_id as "fship_<Fship courierId>" for the create-shipment API.
     */
    public function getRatesForParams(array $params): array
    {
        if (!$this->isConfigured()) {
            return [];
        }
        try {
            $cod = strtolower((string) ($params['payment_type'] ?? 'prepaid')) === 'cod';
            $order = new Order();
            $order->forceFill([
                'seller_id'          => $params['seller_id'] ?? null,
                'payment_type'       => $cod ? 'cod' : 'prepaid',
                'order_amount'       => (float) ($params['order_amount'] ?? 0),
                'collectable_amount' => $cod ? (float) ($params['order_amount'] ?? 0) : 0,
                'package_weight'     => (float) ($params['package_weight'] ?? 500),
                'package_length'     => (float) ($params['package_length'] ?? 10),
                'package_breadth'    => (float) ($params['package_breadth'] ?? 10),
                'package_height'     => (float) ($params['package_height'] ?? 10),
                'pickup'             => ['pincode' => (string) ($params['origin_pincode'] ?? ''), 'state' => is_string($params['pickup_state'] ?? null) ? $params['pickup_state'] : ''],
                'consignee'          => ['pincode' => (string) ($params['destination_pincode'] ?? ''), 'state' => is_string($params['destination_state'] ?? null) ? $params['destination_state'] : ''],
                'order_items'        => [],
            ]);
            $order->id = 0;

            $out = [];
            foreach ($this->ratesForOrder($order) as $rate) {
                $name = trim((string) ($rate['courier_name'] ?? ''));
                if ($name === '' || empty($rate['courier_id'])) {
                    continue;
                }
                if (!empty($params['seller_id']) && $this->hiddenForSeller((int) $params['seller_id'], self::rateKey($name))) {
                    continue;
                }
                $p = $this->priceForRate($order, $rate);
                $out[] = [
                    'courier_id'              => 'fship_' . (int) $rate['courier_id'],
                    'courierName'             => $name,
                    'courierCharge'           => $p['courierCharge'],
                    'freightCharges'          => $p['freightCharges'],
                    'codCharge'               => $p['codCharge'],
                    'zone'                    => $p['zone'],
                    'estimated_delivery_date' => $rate['expectedDeliveryDate'] ?? null,
                    'chargeable_weight'       => $p['weightGrams'],
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            Log::error('Fship getRatesForParams failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getServiceability_bulk(array $params): array
    {
        $ids = $params['order_ids'] ?? (isset($params['order_id']) ? [$params['order_id']] : []);
        $out = [];
        foreach ((array) $ids as $id) {
            try {
                $out = array_merge($out, $this->getServiceability(['order_id' => $id]));
            } catch (\Throwable $e) {
                Log::error("Fship bulk serviceability failed for order {$id}: " . $e->getMessage());
            }
        }
        return $out;
    }

    /**
     * Reverse pickups via Fship need brand/colour/size/product image per item
     * (createreverseorder). The panel's reverse form does not collect those yet,
     * so Fship is not offered for reverse until it does.
     */
    public function reversegetServiceability(array $params): array
    {
        return [];
    }

    /* ------------------------------------------------------------------ */
    /*  Warehouses                                                         */
    /* ------------------------------------------------------------------ */

    /** Returns Fship warehouse id for an address, creating it on Fship the first time. */
    public function warehouseIdFor(array $addr, int $sellerId, string $email = ''): array
    {
        $pincode = trim((string) ($addr['pincode'] ?? ''));
        $line1 = trim((string) ($addr['address'] ?? $addr['address_line1'] ?? ''));
        $phone = $this->phone10($addr['phone'] ?? '');

        if ($pincode === '' || $line1 === '' || strlen($phone) !== 10) {
            return ['ok' => false, 'error' => 'Pickup address is incomplete (need address, 6-digit pincode and 10-digit phone).'];
        }

        $hash = substr(md5(strtolower($pincode . '|' . $line1 . '|' . ($addr['address_2'] ?? '') . '|' . $phone)), 0, 10);

        if (self::$warehouseTableExists === null) {
            self::$warehouseTableExists = Schema::hasTable('fship_warehouses');
        }
        $cacheKey = "fship_wh_{$sellerId}_{$hash}_" . md5((string) config('fship.base_url'));

        if (self::$warehouseTableExists) {
            $row = DB::table('fship_warehouses')->where(['seller_id' => $sellerId, 'address_hash' => $hash, 'environment' => config('fship.env')])->first();
            if ($row) {
                return ['ok' => true, 'id' => (int) $row->fship_warehouse_id, 'rto_id' => (int) $row->fship_rto_warehouse_id];
            }
        } elseif ($c = Cache::get($cacheKey)) {
            return ['ok' => true] + $c;
        }

        $name = 'SXP' . $sellerId . '-' . strtoupper($hash);
        $payload = [
            'warehouseId'   => 0,
            'warehouseName' => $name,
            'contactName'   => mb_substr(trim((string) ($addr['name'] ?? $addr['warehouse_name'] ?? 'Shipxpeed Seller')), 0, 60),
            'addressLine1'  => mb_substr($line1, 0, 200),
            'addressLine2'  => mb_substr((string) ($addr['address_2'] ?? ''), 0, 200),
            'pincode'       => $pincode,
            'city'          => (string) ($addr['city'] ?? ''),
            'stateId'       => 0,   // Fship derives state/country from the pincode
            'countryId'     => 0,
            'phoneNumber'   => $phone,
            'email'         => $email ?: config('fship.fallback_email'),
        ];

        $res = $this->addWarehouse($payload);
        if (!$res['ok'] && str_contains(strtolower((string) $res['error']), 'exist')) {
            $payload['warehouseName'] = $name . '-' . substr((string) time(), -5);
            $res = $this->addWarehouse($payload);
        }
        if (!$res['ok'] || empty($res['data']['warehouseId'])) {
            return ['ok' => false, 'error' => 'Fship warehouse creation failed: ' . ($res['error'] ?? 'no warehouseId returned')];
        }

        $ids = ['id' => (int) $res['data']['warehouseId'], 'rto_id' => (int) ($res['data']['rtoWarehouseId'] ?? 0)];

        if (self::$warehouseTableExists) {
            DB::table('fship_warehouses')->insert([
                'seller_id'              => $sellerId,
                'address_hash'           => $hash,
                'environment'            => config('fship.env'),
                'warehouse_name'         => $payload['warehouseName'],
                'pincode'                => $pincode,
                'fship_warehouse_id'     => $ids['id'],
                'fship_rto_warehouse_id' => $ids['rto_id'] ?: null,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        } else {
            Cache::forever($cacheKey, $ids);
        }

        return ['ok' => true] + $ids;
    }

    /* ------------------------------------------------------------------ */
    /*  Panel contract: book shipment                                      */
    /* ------------------------------------------------------------------ */

    /**
     * $params: ['order_id' => int, 'provider_name' => Fship courier name,
     *           'serviceability_id' => Fship courierId (optional)]
     */
    public function assignOrder($params)
    {
        try {
            $res = $this->bookOrder($this->decode($params));
        } catch (\Throwable $e) {
            Log::error('Fship assignOrder exception: ' . $e->getMessage(), ['params' => $params]);
            $res = ['status' => false, 'message' => 'Fship booking failed: ' . $e->getMessage()];
        }
        return $res + ['couriername' => self::CODE];
    }

    private function bookOrder(array $params): array
    {
        $order = Order::find($params['order_id'] ?? 0);
        if (!$order) {
            return ['status' => false, 'message' => 'Order not found.'];
        }
        if (!empty($order->awb_number) && strtolower((string) $order->order_status) !== 'cancelled') {
            return ['status' => false, 'message' => 'Order already has AWB ' . $order->awb_number . '.'];
        }

        $seller = Auth::guard('seller')->user();
        if ($seller && (int) $seller->id !== (int) $order->seller_id) {
            return ['status' => false, 'message' => 'Order does not belong to this seller.'];
        }
        $sellerModel = $seller ?: \App\Models\SellerList::find($order->seller_id);
        if (!$sellerModel || (string) $sellerModel->status !== '1') {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
        }

        // Wallet check (same rule as the other couriers)
        $credit = Recharge::where('seller_id', $sellerModel->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerModel->id)->where('type', 'Debit')->sum('amount');
        $balance = $credit - $debit;
        if ($sellerModel->negative_balance != '1' && ($balance < $order->seller_amount_walate || $balance < 150)) {
            return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
        }

        // Resolve the Fship courier (id from serviceability, else by name) and its service mode
        $rates = $this->ratesForOrder($order);
        $wantedId = (int) ($params['serviceability_id'] ?? 0);
        $wantedName = strtolower(trim((string) ($params['provider_name'] ?? '')));
        $rate = collect($rates)->first(fn ($r) => $wantedId && (int) ($r['courier_id'] ?? 0) === $wantedId)
            ?? collect($rates)->first(fn ($r) => $wantedName !== '' && strtolower(trim((string) ($r['courier_name'] ?? ''))) === $wantedName);
        if (!$rate) {
            return ['status' => false, 'message' => 'Selected Fship courier is not serviceable for this order any more. Please pick another courier.'];
        }

        // Seller API bookings arrive without a picked price: charge the panel price for this courier
        if ((float) $order->seller_amount_walate <= 0) {
            $order->seller_amount_walate = $this->priceForRate($order, $rate)['courierCharge'];
            $order->save();
            if ($sellerModel->negative_balance != '1' && ($balance < $order->seller_amount_walate || $balance < 150)) {
                return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
            }
        }

        $pickup = $this->decode($order->pickup);
        $consignee = $this->decode($order->consignee);
        $rto = $this->decode($order->rto);
        $email = (!empty($consignee['email']) && filter_var($consignee['email'], FILTER_VALIDATE_EMAIL)) ? $consignee['email'] : config('fship.fallback_email');

        $wh = $this->warehouseIdFor($pickup, (int) $order->seller_id, (string) ($sellerModel->email ?? ''));
        if (!$wh['ok']) {
            return ['status' => false, 'message' => $wh['error']];
        }
        $returnId = 0;
        if (!empty($rto['pincode']) && ($rto['pincode'] != ($pickup['pincode'] ?? null) || ($rto['address'] ?? '') !== ($pickup['address'] ?? ''))) {
            $rwh = $this->warehouseIdFor($rto, (int) $order->seller_id, (string) ($sellerModel->email ?? ''));
            if ($rwh['ok']) {
                $returnId = $rwh['id'];
            }
        }

        // Products (COD orders store item price 0 in this panel, so spread the order value)
        $items = $this->decode($order->order_items) ?: [['name' => 'Product', 'qty' => 1, 'price' => 0]];
        $value = $this->invoiceValue($order);
        $qtyTotal = max(1, array_sum(array_map(fn ($i) => max(1, (int) ($i['qty'] ?? 1)), $items)));
        $itemsHavePrice = array_sum(array_map(fn ($i) => (float) ($i['price'] ?? 0), $items)) > 0;
        $products = [];
        foreach ($items as $it) {
            $qty = max(1, (int) ($it['qty'] ?? 1));
            $products[] = [
                'productId'       => (string) ($it['id'] ?? ''),
                'productName'     => mb_substr((string) ($it['name'] ?? 'Product'), 0, 150) ?: 'Product',
                'unitPrice'       => round($itemsHavePrice ? (float) ($it['price'] ?? 0) : $value / $qtyTotal, 2),
                'quantity'        => $qty,
                'productCategory' => (string) ($it['category'] ?? ''),
                'hsnCode'         => (string) ($it['hsn'] ?? $it['hsn_code'] ?? ''),
                'sku'             => (string) ($it['sku'] ?? ''),
                'taxRate'         => (float) ($it['tax_rate'] ?? 0),
                'productDiscount' => 0,
            ];
        }

        $cod = $this->isCod($order);
        $d = $this->dims($order);
        $refBase = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($order->order_number ?: ('SXP' . $order->id))) ?: ('SXP' . $order->id);

        $payload = [
            'customer_Name'         => mb_substr((string) ($consignee['name'] ?? 'Customer'), 0, 100),
            'customer_Mobile'       => $this->phone10($consignee['phone'] ?? ''),
            'customer_Emailid'      => $email,
            'customer_Address'      => mb_substr((string) ($consignee['address'] ?? ''), 0, 250),
            'landMark'              => mb_substr((string) ($consignee['address_2'] ?? ''), 0, 150),
            'customer_Address_Type' => 'Home',
            'customer_PinCode'      => (string) ($consignee['pincode'] ?? ''),
            'customer_City'         => (string) ($consignee['city'] ?? ''),
            'orderId'               => $refBase,
            'invoice_Number'        => $refBase,
            'payment_Mode'          => $cod ? 1 : 2,          // 1 = COD, 2 = Prepaid
            'express_Type'          => strtolower((string) ($rate['service_mode'] ?? 'surface')) === 'air' ? 'air' : 'surface',
            'is_Ndd'                => 0,
            'order_Amount'          => $value,
            'tax_Amount'            => 0,
            'extra_Charges'         => 0,
            'total_Amount'          => $value,
            'cod_Amount'            => $cod ? round((float) ($order->collectable_amount ?: $value), 2) : 0,
            'shipment_Weight'       => $d['weight'],
            'shipment_Length'       => $d['length'],
            'shipment_Width'        => $d['width'],
            'shipment_Height'       => $d['height'],
            'volumetric_Weight'     => $d['vol'],
            'latitude'              => 0,
            'longitude'             => 0,
            'pick_Address_ID'       => $wh['id'],
            'return_Address_ID'     => $returnId,
            'products'              => $products,
            'courierId'             => (int) $rate['courier_id'],
        ];

        if (strlen($payload['customer_Mobile']) !== 10) {
            return ['status' => false, 'message' => 'Customer mobile number must have 10 digits.'];
        }

        $res = $this->createForwardOrder($payload, ['order_id' => $order->id]);
        if (!$res['ok'] && str_contains(strtolower((string) $res['error']), 'exist')) {
            // Same order id was booked/cancelled on Fship before - retry with a fresh reference
            $payload['orderId'] = $refBase . '-' . substr((string) time(), -6);
            $res = $this->createForwardOrder($payload, ['order_id' => $order->id]);
        }

        $data = $res['data'] ?? [];
        if (!$res['ok'] || empty($data['waybill'])) {
            return ['status' => false, 'message' => 'Fship: ' . ($res['error'] ?? 'AWB not generated'), 'data' => $data];
        }

        $awb = (string) $data['waybill'];

        $order->courier_id = self::CODE;
        $order->all_courier_name = $rate['courier_name'];
        $order->courier_name = $rate['courier_name'];
        $order->zone_courier_name = $rate['courier_name'];
        $order->co_courier_id = (string) $rate['courier_id'];
        $order->awb_number = $awb;
        $order->courier_order_id = (string) ($data['apiorderid'] ?? '');
        $order->fwd_destination_code = (string) ($data['route_code'] ?? '');
        $order->label = (string) ($data['labelurl'] ?? '');
        $order->shipping_date = Carbon::now()->format('Y-m-d');
        $order->shipping_status = 'manifested';
        if (strtolower((string) $order->order_status) === 'cancelled') {
            $order->order_status = null;
        }
        $order->save();

        Recharge::create([
            'seller_id'   => $sellerModel->id,
            'type'        => 'Debit',
            'amount'      => $order->seller_amount_walate,
            'status'      => 1,
            'description' => 'Order created',
        ]);

        $pickupMsg = null;
        if (config('fship.auto_pickup', true)) {
            $p = $this->registerPickup([$awb], ['order_id' => $order->id, 'awb' => $awb]);
            $pickupId = $p['data']['apipickuporderids'][0]['pickupOrderId'] ?? null;
            if ($p['ok'] && $pickupId) {
                $order->shipment_id = (string) $pickupId;
                $order->save();
                $pickupMsg = 'Pickup registered for ' . ($p['data']['apipickuporderids'][0]['pickupDate'] ?? 'next slot');
            } else {
                $pickupMsg = 'Pickup not registered yet: ' . ($p['error'] ?? 'unknown');
            }
        }

        return [
            'status'         => true,
            'couriername'    => self::CODE,
            'message'        => 'Order booked with ' . $rate['courier_name'] . ' via Fship',
            'awb_number'     => $awb,
            'label'          => $order->label,
            'pickup_message' => $pickupMsg,
        ];
    }

    public function assignOrderbulk($params)
    {
        $params = $this->decode($params);
        if (!empty($params['order_ids']) && is_array($params['order_ids'])) {
            $out = [];
            foreach ($params['order_ids'] as $id) {
                $out[$id] = $this->assignOrder(['order_id' => $id] + $params);
            }
            return ['status' => true, 'couriername' => self::CODE, 'results' => $out];
        }
        return $this->assignOrder($params);
    }

    /* ------------------------------------------------------------------ */
    /*  Panel contract: cancel                                             */
    /* ------------------------------------------------------------------ */

    public function cancelShipment($awb): array
    {
        $order = Order::where('awb_number', $awb)->first();
        if (!$order) {
            return ['status' => false, 'message' => 'Order not found with AWB: ' . $awb];
        }
        if (strtolower((string) $order->order_status) === 'cancelled') {
            return ['status' => true, 'message' => 'Already cancelled', 'responseCode' => 200];
        }

        $res = $this->cancelOrder((string) $awb, 'Cancelled by seller');
        $msg = strtolower((string) ($res['error'] ?? ''));
        $alreadyCancelled = str_contains($msg, 'already cancel');

        if (!$res['ok'] && !$alreadyCancelled) {
            return ['status' => false, 'message' => $res['error'] ?? 'Fship cancellation failed', 'responseCode' => $res['http']];
        }

        DB::transaction(function () use ($order) {
            $fresh = Order::lockForUpdate()->find($order->id);
            if (strtolower((string) $fresh->order_status) === 'cancelled') {
                return;
            }
            $fresh->order_status = 'cancelled';
            $fresh->shipping_status = 'cancelled';
            $fresh->save();

            if ((float) $fresh->seller_amount_walate > 0) {
                Recharge::create([
                    'seller_id'   => $fresh->seller_id,
                    'type'        => 'Credit',
                    'amount'      => $fresh->seller_amount_walate,
                    'status'      => 1,
                    'description' => 'Order cancelled',
                ]);
            }
        });

        return ['status' => true, 'message' => 'Shipment cancelled via Fship', 'responseCode' => 200];
    }

    /* ------------------------------------------------------------------ */
    /*  Tracking & NDR                                                     */
    /* ------------------------------------------------------------------ */

    /** Fship status text -> panel shipping_status. */
    public static function mapStatus(?string $status): string
    {
        $s = strtolower(trim((string) $status));
        return match (true) {
            $s === ''                                                        => 'manifested',
            str_contains($s, 'cancel')                                       => 'cancelled',
            str_contains($s, 'rto') || str_contains($s, 'return')            => 'rto',
            str_contains($s, 'undeliver') || str_contains($s, 'ndr')
                || str_contains($s, 'exception') || str_contains($s, 'failed') => 'NDR',
            str_contains($s, 'delivered')                                    => 'delivered',
            str_contains($s, 'out for delivery') || $s === 'dispatched'      => 'out for delivery',
            str_contains($s, 'transit') || str_contains($s, 'picked')
                || str_contains($s, 'shipped') || str_contains($s, 'reached') => 'transit',
            str_contains($s, 'lost')                                         => 'lost',
            default                                                          => 'manifested', // booked, manifested, pickup initiated/scheduled
        };
    }

    /** Normalised tracking in the shape the public tracking page already renders. */
    public function track(string $awb): array
    {
        $res = $this->trackingHistory($awb);
        if (!$res['ok'] || empty($res['data']['summary'])) {
            return ['status' => false, 'message' => $res['error'] ?? 'No tracking data yet.'];
        }
        $sum = $res['data']['summary'];
        $history = array_map(function ($s) {
            $status = (string) ($s['status'] ?? $s['Status'] ?? '');
            $remark = (string) ($s['remark'] ?? $s['Remark'] ?? '');
            return [
                'event_time' => $s['dateandTime'] ?? $s['DateandTime'] ?? '',
                'message'    => ($remark !== '' && strcasecmp($remark, $status) !== 0) ? trim("{$status} - {$remark}", ' -') : $status,
                'location'   => $s['location'] ?? $s['Location'] ?? '',
            ];
        }, $res['data']['trackingdata'] ?? []);

        return [
            'status'        => true,
            'awb_number'    => $sum['waybill'] ?? $awb,
            'order_number'  => $sum['orderid'] ?? 'N/A',
            'order_id'      => $sum['orderid'] ?? 'N/A',
            'courier'       => $sum['fulfilledby'] ?? null,
            'raw_status'    => $sum['status'] ?? null,
            'mapped_status' => self::mapStatus($sum['status'] ?? null),
            'created'       => $sum['orderedon'] ?? 'N/A',
            'edd'           => $sum['expectedDeliveryDate'] ?? null,
            'history'       => $history,
        ];
    }

    /** Pull latest status for one order and save it. Returns new status or null. */
    public function syncOrderStatus(Order $order): ?string
    {
        if (empty($order->awb_number)) {
            return null;
        }
        $t = $this->track($order->awb_number);
        if (!$t['status']) {
            return null;
        }
        $new = $t['mapped_status'];
        if ($new === 'cancelled') {
            // Courier-side cancellation; wallet refund stays a manual/admin decision.
            $order->shipping_status = 'cancelled';
        } elseif ($order->shipping_status !== $new) {
            $order->shipping_status = $new;
            if ($new === 'delivered' && Schema::hasColumn('orders', 'delivered_date')) {
                $order->delivered_date = now()->format('Y-m-d');
            }
        }
        $order->save();
        return $new;
    }

    /**
     * NDR action. $action: re-attempt | change-address | change-phone | rto
     */
    public function ndrAction(Order $order, string $action, array $data = []): array
    {
        if (empty($order->courier_order_id)) {
            return ['status' => false, 'message' => 'Fship order id missing on this order.'];
        }
        $consignee = $this->decode($order->consignee);
        $payload = [
            'apiorderid'       => (int) $order->courier_order_id,
            'action'           => $action,
            'reattempt_date'   => Carbon::parse($data['reattempt_date'] ?? now()->addDay())->toIso8601String(),
            'contact_name'     => (string) ($data['name'] ?? $consignee['name'] ?? ''),
            'complete_address' => (string) ($data['address'] ?? $consignee['address'] ?? ''),
            'landmark'         => (string) ($data['landmark'] ?? ''),
            'mobilenumber'     => $this->phone10($data['phone'] ?? $consignee['phone'] ?? ''),
            'remarks'          => (string) ($data['remark'] ?? ''),
        ];
        $res = $this->reattemptOrder($payload, ['order_id' => $order->id, 'awb' => $order->awb_number]);
        return ['status' => $res['ok'], 'message' => $res['ok'] ? 'NDR action sent to Fship' : ($res['error'] ?? 'Failed'), 'api_response' => $res['data']];
    }
}
