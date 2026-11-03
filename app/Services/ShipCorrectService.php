<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Recharge;
use App\Support\RateCard;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ShipCorrect aggregator adapter (shipcorrect.com seller API).
 *
 * Endpoints used:
 *   rate_calculator_api_v2.php  live rates for every courier on the account
 *   book_shipment_b2c.php       create order + assign courier + AWB in one call (multipart)
 *   trackOrder.php              status + scan history by awb / order_no
 *   cancel_shipment_api.php     cancel shipment + label by AWB
 *   create_order_b2c.php        order without courier (kept as a raw wrapper only)
 *
 * Every ShipCorrect courier is offered to sellers as its own option (Ekart Surface,
 * Delhivery Air ...). The ShipCorrect booking name ("EKART SURFACE") travels as the
 * serviceability id so assignOrder() knows which courier to book.
 *
 * Registered in config/couriers.php under the code "shipcorrect"; the matching
 * logistic_providers row is created by the 2026_11_03 migration.
 * New ShipCorrect couriers start OFF until the admin approves them on Courier Status.
 */
class ShipCorrectService implements CourierServiceInterface
{
    public const CODE = 'shipcorrect';

    /** Prefix of courier ids in the seller API (internal); shown to clients as "sxr_". */
    public const API_PREFIX = 'shipcorrect_';

    private static ?bool $logTableExists = null;

    /** Set when ShipCorrect says our account wallet is empty; couriers are hidden while it is set. */
    public const LOW_BALANCE_FLAG = 'shipcorrect_account_low_balance';

    /** ShipCorrect account (not seller) out of balance, e.g. "auto-dispatch failed: Insufficient wallet balance". */
    public static function isAccountBalanceError(string $msg): bool
    {
        return (bool) preg_match('/insufficient|wallet balance|please recharge|low balance/i', $msg);
    }

    public static function lowBalanceSince(): ?string
    {
        return Cache::get(self::LOW_BALANCE_FLAG);
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP client                                                        */
    /* ------------------------------------------------------------------ */

    /** SHIPCORRECT_API_KEY in .env, otherwise the key saved in Admin > ShipCorrect. */
    public function apiKey(): string
    {
        $key = (string) config('shipcorrect.api_key');
        if ($key === '') {
            try {
                $key = (string) Cache::remember('shipcorrect_api_key', now()->addMinutes(5), function () {
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
     * Call a ShipCorrect endpoint. Never throws: returns
     * ['ok' => bool, 'http' => int, 'data' => array|null, 'error' => string|null].
     * $asForm = true sends multipart/form-data (book_shipment_b2c.php needs it).
     */
    public function request(string $endpoint, array $payload, bool $asForm = false, array $ctx = []): array
    {
        $url = config('shipcorrect.base_url', 'https://shipcorrect.com') . '/api/' . ltrim($endpoint, '/');

        if (!$this->isConfigured()) {
            return ['ok' => false, 'http' => 0, 'data' => null, 'error' => 'ShipCorrect API key is not configured (SHIPCORRECT_API_KEY).'];
        }

        $started = microtime(true);
        $http = 0;
        $body = null;
        $data = null;
        $error = null;

        try {
            $client = Http::withHeaders(['X-API-KEY' => $this->apiKey(), 'Accept' => 'application/json'])
                ->timeout(config('shipcorrect.timeout', 40));

            $response = $asForm
                ? $client->asMultipart()->post($url, collect($payload)->map(fn ($v, $k) => ['name' => $k, 'contents' => (string) $v])->values()->all())
                : $client->asJson()->post($url, $payload);

            $http = $response->status();
            $body = $response->body();
            $data = $response->json();

            $msg = is_array($data) ? (string) ($data['message'] ?? $data['error_message'] ?? $data['error'] ?? '') : '';
            $failedFlag = is_array($data) && (
                (isset($data['status']) && is_string($data['status']) && in_array(strtolower($data['status']), ['error', 'failed', 'fail', 'false', 'partial_success'], true))
                || (array_key_exists('status', $data) && $data['status'] === false)
                || (array_key_exists('success', $data) && $data['success'] === false)
            );

            if ($http === 401) {
                $error = 'ShipCorrect rejected the API key (401). Check the key on ShipCorrect > API Documentation.';
                Cache::put('shipcorrect_unauthorized_' . md5($this->apiKey()), true, now()->addMinutes(10));
            } elseif ($http >= 500) {
                $error = "ShipCorrect server error (HTTP {$http})." . ($msg !== '' ? ' ' . $msg : '');
            } elseif (!is_array($data)) {
                $error = "Unexpected ShipCorrect response (HTTP {$http}): " . mb_substr(trim(strip_tags((string) $body)), 0, 200);
            } elseif ($failedFlag || $http >= 400) {
                $error = $msg !== '' ? $msg : "ShipCorrect HTTP {$http}";
            }
        } catch (\Throwable $e) {
            $error = 'ShipCorrect connection failed: ' . $e->getMessage();
        }

        $this->log($endpoint, $payload, $http, $body, $error === null, (int) round((microtime(true) - $started) * 1000), $ctx);

        return ['ok' => $error === null, 'http' => $http, 'data' => is_array($data) ? $data : null, 'error' => $error];
    }

    private function log(string $endpoint, array $payload, int $http, ?string $body, bool $ok, int $ms, array $ctx): void
    {
        unset($payload['api_key']); // never store the key in logs
        if (!$ok) {
            Log::warning('ShipCorrect API failure', ['endpoint' => $endpoint, 'http' => $http, 'body' => mb_substr((string) $body, 0, 2000), 'ctx' => $ctx]);
        }
        try {
            if (self::$logTableExists === null) {
                self::$logTableExists = Schema::hasTable('courier_api_logs');
            }
            if (!self::$logTableExists) {
                return;
            }
            static $cols = null;
            if ($cols === null) {
                $cols = array_flip(Schema::getColumnListing('courier_api_logs'));
            }
            $req  = mb_substr(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 60000);
            $resp = $body === null ? null : mb_substr($body, 0, 60000);
            $row = [
                'courier' => self::CODE, 'endpoint' => $endpoint, 'method' => 'POST',
                'request' => $req, 'request_body' => $req,
                'response' => $resp, 'response_body' => $resp,
                'http_status' => $http, 'status_code' => $http,
                'success' => $ok, 'duration_ms' => $ms,
                'order_id' => $ctx['order_id'] ?? null, 'awb' => $ctx['awb'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            DB::table('courier_api_logs')->insert(array_intersect_key($row, $cols));
        } catch (\Throwable $e) {
            // Logging must never break shipping.
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Raw API wrappers                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Live rates. Response: {status, applied_weight, volumetric_weight,
     *   rates: [{courier:"Ekart Surface 0.5kg", rate, mode, alias, slab, weight, COD_CHARGE, Freight_charge, Gst}]}
     */
    public function rateCalculator(array $payload, array $ctx = []): array
    {
        return $this->request('rate_calculator_api_v2.php', ['shipment_type' => 'forward'] + $payload, false, $ctx);
    }

    public function bookShipment(array $form, array $ctx = []): array
    {
        return $this->request('book_shipment_b2c.php', ['api_key' => $this->apiKey()] + $form, true, $ctx);
    }

    public function createOrder(array $payload, array $ctx = []): array
    {
        return $this->request('create_order_b2c.php', ['api_key' => $this->apiKey()] + $payload, false, $ctx);
    }

    public function trackOrder(?string $awb = null, ?string $orderNo = null): array
    {
        $p = ['api_key' => $this->apiKey()];
        if ($awb) {
            $p['awb'] = $awb;
        }
        if ($orderNo) {
            $p['order_no'] = $orderNo;
        }
        return $this->request('trackOrder.php', $p, false, ['awb' => $awb]);
    }

    public function cancelRequest(string $awb, string $reason): array
    {
        return $this->request('cancel_shipment_api.php', ['shipment_awb' => $awb, 'cancel_reason' => $reason ?: 'Cancelled by seller'], false, ['awb' => $awb]);
    }

    /* ------------------------------------------------------------------ */
    /*  Courier names & keys                                               */
    /* ------------------------------------------------------------------ */

    /** "Ekart Surface 0.5kg" / "Bluedart-Air" -> "EKART SURFACE" / "BLUEDART AIR" (ShipCorrect booking name). */
    public static function bookingName(string $name): string
    {
        $n = strtoupper(trim($name));
        $n = preg_replace('/[\-_]+/', ' ', $n);
        $n = preg_replace('/\b\d+(\.\d+)?\s*(KG|KGS|G|GM|GMS|GRAMS?)\b/', '', $n);
        $n = preg_replace('/\bBLUE\s+DART\b/', 'BLUEDART', $n);
        $n = preg_replace('/\bXPRESS\s*BEES?\b/', 'XPRESSBEES', $n);
        $n = trim(preg_replace('/\s+/', ' ', $n));
        if ($n !== '' && !str_contains($n, ' ')) {
            $n .= ' SURFACE'; // e.g. "SHADOWFAX"
        }
        return $n;
    }

    /** "EKART SURFACE" -> "Ekart Surface" (what sellers see). */
    public static function displayName(string $name): string
    {
        $b = self::bookingName($name);
        $pretty = ucwords(strtolower($b));
        return str_replace(['Dtdc', 'Bluedart', 'Xpressbees'], ['DTDC', 'Blue Dart', 'Xpressbees'], $pretty);
    }

    public static function rateKey(string $name): string
    {
        return config('shipcorrect.rate_key_prefix', 'ShipCorrect_')
            . str_replace(' ', '_', ucwords(strtolower(self::bookingName($name))));
    }

    /** Seller API courier id: "shipcorrect_ekart_surface". */
    public static function apiCourierId(string $name): string
    {
        return self::API_PREFIX . strtolower(str_replace(' ', '_', self::bookingName($name)));
    }

    /** Rate-card keys for every courier ShipCorrect can book (fixed list from its docs). */
    public static function rateCardKeys(): array
    {
        $out = [];
        foreach ((array) config('shipcorrect.couriers', []) as $c) {
            $out[self::rateKey($c)] = self::displayName($c);
        }
        return $out;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

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

    /** kg / cm. Panel stores weight in grams. */
    private function dims(Order $order): array
    {
        $l = max(1.0, (float) ($order->package_length ?: 10));
        $b = max(1.0, (float) ($order->package_breadth ?: 10));
        $h = max(1.0, (float) ($order->package_height ?: 10));
        $kg = max(0.05, round(((float) ($order->package_weight ?: 500)) / 1000, 3));
        return ['weight' => $kg, 'length' => $l, 'breadth' => $b, 'height' => $h, 'vol' => round(($l * $b * $h) / 5000, 3)];
    }

    private function isCod(Order $order): bool
    {
        return strtolower((string) $order->payment_type) === 'cod';
    }

    private function invoiceValue(Order $order): float
    {
        $itemsTotal = 0.0;
        foreach ($this->decode($order->order_items) as $it) {
            $itemsTotal += (float) ($it['price'] ?? 0) * max(1, (int) ($it['qty'] ?? 1));
        }
        return round(max((float) $order->order_amount, (float) $order->collectable_amount, $itemsTotal, 1), 2);
    }

    /** Live ShipCorrect rates for an order (cached 5 minutes). Each row gets 'applied_weight'. */
    public function ratesForOrder(Order $order): array
    {
        $pickup = $this->decode($order->pickup);
        $consignee = $this->decode($order->consignee);
        $d = $this->dims($order);

        $payload = [
            'pickup_pincode'   => (string) ($pickup['pincode'] ?? ''),
            'delivery_pincode' => (string) ($consignee['pincode'] ?? ''),
            'actual_weight'    => $d['weight'],
            'length'           => $d['length'],
            'breadth'          => $d['breadth'],
            'height'           => $d['height'],
            'payment_type'     => $this->isCod($order) ? 'cod' : 'prepaid',
            'shipment_value'   => $this->invoiceValue($order),
        ];
        if (!preg_match('/^\d{6}$/', $payload['pickup_pincode']) || !preg_match('/^\d{6}$/', $payload['delivery_pincode'])) {
            return [];
        }

        $cacheKey = 'shipcorrect_rates_' . md5(json_encode($payload));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
        if (Cache::has('shipcorrect_unauthorized_' . md5($this->apiKey()))) {
            return [];
        }
        $res = $this->rateCalculator($payload, ['order_id' => $order->id ?: null]);
        if (!$res['ok']) {
            return [];
        }
        $applied = (float) ($res['data']['applied_weight'] ?? 0);
        $rates = [];
        foreach ((array) ($res['data']['rates'] ?? []) as $r) {
            $name = (string) ($r['courier'] ?? $r['courier_name'] ?? '');
            if (!is_array($r) || trim($name) === '') {
                continue;
            }
            $r['booking_name'] = self::bookingName($name);
            $r['applied_weight'] = $applied ?: (float) ($r['weight'] ?? 0);
            $rates[] = $r;
        }
        Cache::put($cacheKey, $rates, now()->addMinutes(5));
        return $rates;
    }

    private function hiddenForSeller(int $sellerId, string $rateKey): bool
    {
        return !\App\Support\CourierAccess::isOn($sellerId, $rateKey, 'ShipCorrect');
    }

    /** Seller price: zone rate card (first 500 g + additional 500 g, + COD, + 18% GST); else ShipCorrect cost + markup + GST. */
    public function priceForRate(Order $order, array $rate): array
    {
        $cod = $this->isCod($order);
        $d = $this->dims($order);
        $weightGrams = max($d['weight'], $d['vol'], (float) ($rate['applied_weight'] ?? 0)) * 1000;
        $key = self::rateKey((string) ($rate['courier'] ?? $rate['booking_name'] ?? ''));

        $zone = null;
        try {
            $zone = RateCard::zoneForOrder($order)['zone'] ?? null;
        } catch (\Throwable $e) {
        }

        $charges = null;
        if ($zone) {
            $card = RateCard::find((int) $order->seller_id, $zone, $key);
            $charges = $card ? RateCard::price($card, $weightGrams, $cod, (float) ($order->collectable_amount ?: $order->order_amount)) : null;
        }

        if (!$charges) {
            $m = 1 + ((float) config('shipcorrect.markup_percent', 20)) / 100;
            $gst = 1 + ((float) config('shipcorrect.gst_percent', 18)) / 100;
            $freightCost = (float) ($rate['Freight_charge'] ?? 0);
            $codCost = $cod ? (float) ($rate['COD_CHARGE'] ?? $rate['cod_amount'] ?? 0) : 0.0;
            if ($freightCost <= 0) {
                // Only the GST-inclusive total came back: back GST out of it
                $freightCost = max(0, (float) ($rate['rate'] ?? 0) / $gst - $codCost);
            }
            $freight = round($freightCost * $m, 2);
            $codCharge = round($codCost * $m, 2);
            $charges = ['courierCharge' => round(($freight + $codCharge) * $gst, 2), 'freightCharges' => $freight, 'codCharge' => $codCharge];
        }

        return $charges + ['zone' => $zone, 'weightGrams' => $weightGrams];
    }

    /* ------------------------------------------------------------------ */
    /*  Panel contract: serviceability                                     */
    /* ------------------------------------------------------------------ */

    public function getServiceability(array $params): array
    {
        if (!$this->isConfigured() || empty($params['order_id']) || self::lowBalanceSince()) {
            return [];
        }
        $order = Order::find($params['order_id']);
        if (!$order) {
            return [];
        }

        $results = [];
        $seen = [];
        foreach ($this->ratesForOrder($order) as $rate) {
            $booking = $rate['booking_name'];
            if (isset($seen[$booking])) {
                continue;
            }
            $seen[$booking] = true;
            if ($this->hiddenForSeller((int) $order->seller_id, self::rateKey($booking))) {
                continue;
            }
            $p = $this->priceForRate($order, $rate);
            $name = self::displayName($booking);
            $results[] = [
                'order_id'          => $order->id,
                'serviceabilityId'  => $booking,           // ShipCorrect booking courier_name
                'courierName'       => $name,
                'courierCharge'     => $p['courierCharge'],
                'freightCharges'    => $p['freightCharges'],
                'codCharge'         => $p['codCharge'],
                'zone'              => $p['zone'],
                'zone_courier_name' => $name,
                'service_mode'      => $rate['mode'] ?? null,
                'minWeight'         => $p['weightGrams'],
                'volWeight'         => $p['weightGrams'],
            ];
        }
        return $results;
    }

    /** Seller API serviceability without a saved order. courier_id = "shipcorrect_<booking name>". */
    public function getRatesForParams(array $params): array
    {
        if (!$this->isConfigured() || self::lowBalanceSince()) {
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
            $seen = [];
            foreach ($this->ratesForOrder($order) as $rate) {
                $booking = $rate['booking_name'];
                if (isset($seen[$booking])) {
                    continue;
                }
                $seen[$booking] = true;
                if (!empty($params['seller_id']) && $this->hiddenForSeller((int) $params['seller_id'], self::rateKey($booking))) {
                    continue;
                }
                $p = $this->priceForRate($order, $rate);
                $out[] = [
                    'courier_id'        => self::apiCourierId($booking),
                    'courierName'       => self::displayName($booking),
                    'courierCharge'     => $p['courierCharge'],
                    'freightCharges'    => $p['freightCharges'],
                    'codCharge'         => $p['codCharge'],
                    'zone'              => $p['zone'],
                    'chargeable_weight' => $p['weightGrams'],
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            Log::error('ShipCorrect getRatesForParams failed: ' . $e->getMessage());
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
                Log::error("ShipCorrect bulk serviceability failed for order {$id}: " . $e->getMessage());
            }
        }
        return $out;
    }

    /** ShipCorrect's API has forward shipments only. */
    public function reversegetServiceability(array $params): array
    {
        return [];
    }

    /* ------------------------------------------------------------------ */
    /*  Panel contract: book shipment                                      */
    /* ------------------------------------------------------------------ */

    /**
     * $params: ['order_id' => int, 'serviceability_id' => "EKART SURFACE" (or api slug),
     *           'provider_name' => "Ekart Surface" (fallback)]
     */
    public function assignOrder($params)
    {
        try {
            $res = $this->bookOrder($this->decode($params));
        } catch (\Throwable $e) {
            Log::error('ShipCorrect assignOrder exception: ' . $e->getMessage(), ['params' => $params]);
            $res = ['status' => false, 'message' => 'Courier booking failed: ' . $e->getMessage()];
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

        $credit = Recharge::where('seller_id', $sellerModel->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerModel->id)->where('type', 'Debit')->sum('amount');
        $balance = $credit - $debit;
        $lowBalance = fn () => $sellerModel->negative_balance != '1' && ($balance < $order->seller_amount_walate || $balance < 150);
        if ($lowBalance()) {
            return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
        }

        // Which ShipCorrect courier: serviceability id (booking name or api slug), else provider name
        $wanted = (string) ($params['serviceability_id'] ?? '');
        if (str_starts_with(strtolower($wanted), self::API_PREFIX)) {
            $wanted = str_replace('_', ' ', substr($wanted, strlen(self::API_PREFIX)));
        }
        $wanted = self::bookingName($wanted !== '' ? $wanted : (string) ($params['provider_name'] ?? ''));
        $rate = collect($this->ratesForOrder($order))->first(fn ($r) => $r['booking_name'] === $wanted);
        if (!$rate) {
            return ['status' => false, 'message' => 'Selected courier is not available for this order any more. Please pick another courier.'];
        }
        if ($this->hiddenForSeller((int) $order->seller_id, self::rateKey($wanted))) {
            return ['status' => false, 'message' => 'This courier is switched off for your account.'];
        }

        // Seller API bookings arrive without a picked price: charge the panel price for this courier
        if ((float) $order->seller_amount_walate <= 0) {
            $order->seller_amount_walate = $this->priceForRate($order, $rate)['courierCharge'];
            $order->save();
            if ($lowBalance()) {
                return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
            }
        }

        $pickup = $this->decode($order->pickup);
        $consignee = $this->decode($order->consignee);
        $cod = $this->isCod($order);
        $d = $this->dims($order);
        $value = $this->invoiceValue($order);

        $items = $this->decode($order->order_items) ?: [['name' => 'Product', 'qty' => 1]];
        $productName = mb_substr(implode(', ', array_map(function ($i) {
            $q = max(1, (int) ($i['qty'] ?? 1));
            return trim((string) ($i['name'] ?? 'Product')) . ($q > 1 ? " x{$q}" : '');
        }, $items)), 0, 200) ?: 'Product';
        $first = $items[0] ?? [];

        $customerPhone = $this->phone10($consignee['phone'] ?? '');
        $pickupPhone = $this->phone10($pickup['phone'] ?? '');
        if (strlen($customerPhone) !== 10) {
            return ['status' => false, 'message' => 'Customer mobile number must have 10 digits.'];
        }
        if (strlen($pickupPhone) !== 10 || empty($pickup['pincode']) || empty($pickup['address'] ?? $pickup['address_line1'] ?? null)) {
            return ['status' => false, 'message' => 'Pickup address is incomplete (need address, 6-digit pincode and 10-digit phone).'];
        }

        $eway = (string) ($order->eway_bill ?? $order->ewaybill ?? '');
        if ($value > 49999 && $eway === '') {
            return ['status' => false, 'message' => 'Orders above ₹49,999 need an E-Way Bill number for this courier.'];
        }

        $ref = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($order->order_number ?: ('SXP' . $order->id))) ?: ('SXP' . $order->id);
        $customerEmail = (!empty($consignee['email']) && filter_var($consignee['email'], FILTER_VALIDATE_EMAIL)) ? $consignee['email'] : '';

        $form = [
            'courier_name'              => $wanted,
            'customer_name'             => mb_substr((string) ($consignee['name'] ?? 'Customer'), 0, 100),
            'customer_email'            => $customerEmail,
            'customer_contact_number1'  => $customerPhone,
            'customer_contact_number2'  => $this->phone10($consignee['alternate_phone'] ?? $consignee['phone2'] ?? ''),
            'product_name'              => $productName,
            'client_order_no'           => $ref,
            'customer_address1'         => mb_substr((string) ($consignee['address'] ?? ''), 0, 250),
            'customer_address2'         => mb_substr((string) ($consignee['address_2'] ?? ''), 0, 200),
            'customer_address_pincode'  => (string) ($consignee['pincode'] ?? ''),
            'customer_address_landmark' => mb_substr((string) ($consignee['landmark'] ?? ''), 0, 150),
            'quantity'                  => 1,   // number of boxes; ShipCorrect accepts only 1
            'mrp'                       => $value,
            'total_amount'              => $cod ? round((float) ($order->collectable_amount ?: $value), 2) : $value,
            'pay_mode'                  => $cod ? 'COD' : 'prepaid',
            'comment_1'                 => mb_substr((string) ($first['sku'] ?? ''), 0, 100),
            'product_weight'            => $d['weight'],
            'length'                    => $d['length'],
            'breadth'                   => $d['breadth'],
            'height'                    => $d['height'],
            'gstNo'                     => $value > 49999 ? (string) ($sellerModel->gst_number ?: $sellerModel->gst_no) : '',
            'ewaybillNo'                => $value > 49999 ? $eway : '',
            'pickup_name'               => mb_substr(trim((string) ($pickup['name'] ?? $pickup['warehouse_name'] ?? 'Warehouse')), 0, 60),
            'pickup_contact'            => $pickupPhone,
            'pickup_pincode'            => (string) $pickup['pincode'],
            'pickup_address1'           => mb_substr(trim((string) ($pickup['address'] ?? $pickup['address_line1']) . ' ' . ($pickup['address_2'] ?? '')), 0, 250),
        ];

        $res = $this->bookShipment($form, ['order_id' => $order->id]);
        if (!$res['ok'] && preg_match('/exist|duplicate/i', (string) $res['error'])) {
            // Same reference booked/cancelled before - retry once with a fresh reference
            $form['client_order_no'] = $ref . '-' . substr((string) time(), -6);
            $res = $this->bookShipment($form, ['order_id' => $order->id]);
        }

        $data = $res['data'] ?? [];
        if (!$res['ok'] || empty($data['awb'])) {
            $err = (string) ($res['error'] ?? ($data['message'] ?? 'AWB not generated'));
            if (self::isAccountBalanceError($err)) {
                // Our own ShipCorrect account is out of balance: not the seller's fault.
                // Hide ShipCorrect couriers for a while so other sellers are not sent into the same failure.
                Cache::put(self::LOW_BALANCE_FLAG, now()->toDateTimeString(), now()->addMinutes(30));
                Log::error('ShipCorrect account balance is too low - couriers hidden for 30 minutes', ['order_id' => $order->id]);
                return ['status' => false, 'message' => 'This courier is temporarily unavailable on our side. Our team has been notified - please choose another courier.'];
            }
            return ['status' => false, 'message' => 'Courier: ' . $err];
        }

        $awb = (string) $data['awb'];
        $name = self::displayName($wanted);

        $order->courier_id = self::CODE;
        $order->all_courier_name = $name;
        $order->courier_name = $name;
        $order->zone_courier_name = $name;
        $order->co_courier_id = $wanted;
        $order->awb_number = $awb;
        $order->courier_order_id = (string) ($data['order_no'] ?? '');
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

        return [
            'status'      => true,
            'couriername' => self::CODE,
            'message'     => 'Order booked with ' . $name,
            'awb_number'  => $awb,
            'sub_courier' => $data['sub_courier'] ?? null,
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

        $res = $this->cancelRequest((string) $awb, 'Cancelled by seller');
        $alreadyCancelled = str_contains(strtolower((string) ($res['error'] ?? '')), 'already cancel');
        if (!$res['ok'] && !$alreadyCancelled) {
            return ['status' => false, 'message' => $res['error'] ?? 'Courier cancellation failed', 'responseCode' => $res['http']];
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

        return ['status' => true, 'message' => 'Shipment cancelled', 'responseCode' => 200];
    }

    /* ------------------------------------------------------------------ */
    /*  Tracking                                                           */
    /* ------------------------------------------------------------------ */

    /** ShipCorrect status text -> panel shipping_status. */
    public static function mapStatus(?string $status): string
    {
        $s = strtolower(trim((string) $status));
        return match (true) {
            $s === ''                                                          => 'manifested',
            str_contains($s, 'cancel')                                         => 'cancelled',
            str_contains($s, 'rto') || str_contains($s, 'return')              => 'rto',
            str_contains($s, 'undeliver') || str_contains($s, 'ndr')
                || str_contains($s, 'exception') || str_contains($s, 'failed') => 'NDR',
            str_contains($s, 'delivered')                                      => 'delivered',
            str_contains($s, 'out for delivery')                               => 'out for delivery',
            str_contains($s, 'transit') || str_contains($s, 'picked')
                || str_contains($s, 'shipped') || str_contains($s, 'reached')
                || str_contains($s, 'dispatch')                                => 'transit',
            str_contains($s, 'lost')                                           => 'lost',
            default                                                            => 'manifested', // booked, manifested, pickup pending
        };
    }

    /** Normalised tracking (same shape as FshipService::track). */
    public function track(string $awb): array
    {
        $res = $this->trackOrder($awb);
        $data = $res['data'] ?? [];
        if (!$res['ok'] || empty($data)) {
            return ['status' => false, 'message' => $res['error'] ?? 'No tracking data yet.'];
        }
        $raw = (string) ($data['tracking_status'] ?? $data['tacking_status'] ?? $data['current_status'] ?? '');
        $history = array_map(fn ($s) => [
            'event_time' => (string) ($s['date'] ?? $s['datetime'] ?? ''),
            'message'    => (string) ($s['activity'] ?? $s['status'] ?? ''),
            'location'   => (string) ($s['location'] ?? ''),
        ], array_values(array_filter((array) ($data['scan_stages'] ?? []), 'is_array')));
        usort($history, fn ($a, $b) => strcmp($b['event_time'], $a['event_time'])); // newest first

        return [
            'status'        => true,
            'awb_number'    => (string) ($data['awb'] ?? $awb),
            'order_number'  => (string) ($data['order_no'] ?? 'N/A'),
            'order_id'      => (string) ($data['order_no'] ?? 'N/A'),
            'courier'       => $data['courier_name'] ?? null,
            'raw_status'    => $raw !== '' ? $raw : null,
            'mapped_status' => self::mapStatus($raw !== '' ? $raw : ($history[0]['message'] ?? '')),
            'created'       => $history ? end($history)['event_time'] : 'N/A',
            'edd'           => $data['edd'] ?? null,
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
        if ($order->shipping_status !== $new) {
            // Courier-side cancellation: wallet refund stays an admin decision.
            $order->shipping_status = $new;
            if ($new === 'delivered' && Schema::hasColumn('orders', 'delivered_date')) {
                $order->delivered_date = now()->format('Y-m-d');
            }
            $order->save();
        }
        return $new;
    }

    /** ShipCorrect has no NDR action API yet. */
    public function ndrAction(Order $order, string $action, array $data = []): array
    {
        return ['status' => false, 'message' => 'NDR actions for this courier must be raised from the ShipCorrect panel (no API yet).'];
    }
}
