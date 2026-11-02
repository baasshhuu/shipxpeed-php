<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\B2bOrder;
use App\Models\Recharge;
use App\Models\SellerList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Bigship Unified Outbound API - domestic B2B (segment_type = domestic_b2b).
 *
 * Flow used by the panel:
 *   rate calculator (quote, no Bigship order yet)
 *   -> create-order (draft at Bigship) -> courier-wise-shipment-cost (exact price)
 *   -> place-order (multipart: invoice + e-way bill files) -> AWB
 * Sellers only ever see the real courier names returned by Bigship.
 */
class BigshipService
{
    public const CODE = 'bigship';
    public const SEGMENT = 'domestic_b2b';
    private const SETTINGS_KEY = 'bigship';
    private const SETTINGS_CACHE = 'bigship_settings_v1';

    /* ------------------------------------------------------------------ */
    /*  Settings (admin page, encrypted) with .env override                */
    /* ------------------------------------------------------------------ */

    public static function saved(): array
    {
        return Cache::remember(self::SETTINGS_CACHE, 300, function () {
            try {
                if (!Schema::hasTable('app_settings')) {
                    return [];
                }
                $data = json_decode((string) AppSetting::getValue(self::SETTINGS_KEY), true);
                return is_array($data) ? $data : [];
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public static function saveSettings(array $data): void
    {
        foreach (['password', 'access_key'] as $secret) {
            if (array_key_exists($secret, $data) && filled($data[$secret])) {
                $data[$secret] = Crypt::encryptString(trim((string) $data[$secret]));
            }
        }
        $merged = array_replace(self::saved(), $data);
        AppSetting::setValue(self::SETTINGS_KEY, json_encode($merged));
        Cache::forget(self::SETTINGS_CACHE);
        Cache::forget('bigship_token');
    }

    private static function secret(string $key): string
    {
        $v = self::saved()[$key] ?? '';
        if ($v === '') {
            return '';
        }
        try {
            return Crypt::decryptString($v);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function credentials(): array
    {
        return [
            'username'   => trim((string) (config('bigship.username') ?: (self::saved()['username'] ?? ''))),
            'password'   => (string) (config('bigship.password') ?: self::secret('password')),
            'access_key' => trim((string) (config('bigship.access_key') ?: self::secret('access_key'))),
        ];
    }

    public function credentialSource(): string
    {
        return config('bigship.username') ? 'env' : 'admin';
    }

    public function isConfigured(): bool
    {
        $c = $this->credentials();
        return $c['username'] !== '' && $c['password'] !== '' && $c['access_key'] !== '';
    }

    /** Admin master switch: B2B shipping visible to sellers. Off until admin turns it on. */
    public function enabledForSellers(): bool
    {
        return (bool) (self::saved()['enabled'] ?? false) && $this->isConfigured();
    }

    public function markupPercent(): float
    {
        $m = self::saved()['markup_percent'] ?? null;
        return is_numeric($m) ? (float) $m : (float) config('bigship.markup_percent', 20);
    }

    public function gstPercent(): float
    {
        return (float) config('bigship.gst_percent', 18);
    }

    /** Seller price (incl. GST) for a Bigship cost. */
    public function sellerPrice(float $cost): float
    {
        return round($cost * (1 + $this->markupPercent() / 100) * (1 + $this->gstPercent() / 100), 2);
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP                                                               */
    /* ------------------------------------------------------------------ */

    public function login(bool $fresh = false): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Bigship login details are not saved yet (Admin > Bigship B2B).', 'data' => null];
        }
        if (!$fresh && ($t = Cache::get('bigship_token'))) {
            return ['ok' => true, 'error' => null, 'data' => ['token' => $t]];
        }
        $c = $this->credentials();
        $res = $this->raw('api/outbound/login', 'POST', [
            'username' => $c['username'], 'password' => $c['password'], 'access_key' => $c['access_key'],
        ], [], null);
        $token = $res['data']['data']['token'] ?? null;
        if (!$res['ok'] || !$token) {
            return ['ok' => false, 'error' => $res['error'] ?: 'Bigship login failed.', 'data' => $res['data']];
        }
        $ttl = now()->addMinutes(50);
        if (!empty($res['data']['data']['tokenExpiringAt'])) {
            try {
                $exp = Carbon::parse($res['data']['data']['tokenExpiringAt'], 'UTC')->subMinutes(5);
                if ($exp->isFuture()) {
                    $ttl = $exp;
                }
            } catch (\Throwable $e) {
            }
        }
        Cache::put('bigship_token', $token, $ttl);
        return ['ok' => true, 'error' => null, 'data' => $res['data']['data']];
    }

    /**
     * Authenticated call. Never throws. Returns ['ok','http','data' (full JSON),'error'].
     * $files = [field => ['path' => ..., 'name' => ...]] sends multipart/form-data.
     */
    public function request(string $endpoint, string $method = 'POST', array $payload = [], array $ctx = [], ?array $files = null): array
    {
        $login = $this->login();
        if (!$login['ok']) {
            return ['ok' => false, 'http' => 0, 'data' => null, 'error' => $login['error']];
        }
        $res = $this->raw($endpoint, $method, $payload, $ctx, $login['data']['token'], $files);
        if ($res['http'] === 401) {
            Cache::forget('bigship_token');
            $login = $this->login(true);
            if ($login['ok']) {
                $res = $this->raw($endpoint, $method, $payload, $ctx, $login['data']['token'], $files);
            }
        }
        return $res;
    }

    private function raw(string $endpoint, string $method, array $payload, array $ctx, ?string $token, ?array $files = null): array
    {
        $url = rtrim((string) config('bigship.base_url'), '/') . '/' . ltrim($endpoint, '/');
        $started = microtime(true);
        $http = 0;
        $body = null;
        $data = null;
        $error = null;

        try {
            $client = Http::acceptJson()->timeout((int) config('bigship.timeout', 45));
            if ($token) {
                $client = $client->withToken($token);
            }
            if ($files) {
                foreach ($files as $field => $f) {
                    $client = $client->attach($field, file_get_contents($f['path']), $f['name']);
                }
                $response = $client->post($url, array_map(fn ($v) => (string) $v, $payload));
            } elseif (strtoupper($method) === 'GET') {
                // Bigship documents a JSON body on its GET endpoints; send it as query too.
                $response = $payload
                    ? $client->send('GET', $url, ['json' => $payload, 'query' => $payload])
                    : $client->get($url);
            } else {
                $response = $client->asJson()->post($url, $payload);
            }

            $http = $response->status();
            $body = $response->body();
            $data = $response->json();

            if (!is_array($data)) {
                $error = "Unexpected Bigship response (HTTP {$http}).";
            } elseif ($http === 401) {
                $error = 'Bigship: ' . ($data['error'] ?? $data['message'] ?? 'Unauthenticated');
            } elseif ($http === 429) {
                $error = 'Bigship rate limit reached, please try again in a minute.';
            } elseif (($data['status'] ?? null) === false || $http >= 400) {
                $error = $this->errorText($data, $http);
            }
        } catch (\Throwable $e) {
            $error = 'Bigship connection failed: ' . $e->getMessage();
        }

        $logPayload = $payload;
        foreach (['password', 'access_key'] as $s) {
            if (isset($logPayload[$s])) {
                $logPayload[$s] = '***';
            }
        }
        if ($files) {
            $logPayload['_files'] = array_map(fn ($f) => $f['name'], $files);
        }
        $logBody = $body;
        if ($endpoint === 'api/outbound/login' && is_array($data) && isset($data['data']['token'])) {
            $copy = $data;
            $copy['data']['token'] = '***';
            $logBody = json_encode($copy);
        }
        $this->log($endpoint, $method, $logPayload, $http, $logBody, $error === null, (int) round((microtime(true) - $started) * 1000), $ctx);

        return ['ok' => $error === null, 'http' => $http, 'data' => is_array($data) ? $data : null, 'error' => $error];
    }

    private function errorText(array $data, int $http): string
    {
        $msg = (string) ($data['message'] ?? $data['error'] ?? "Bigship HTTP {$http}");
        if (!empty($data['errors']) && is_array($data['errors'])) {
            $parts = [];
            foreach ($data['errors'] as $field => $errs) {
                $parts[] = is_array($errs) ? implode(' ', array_map('strval', $errs)) : (string) $errs;
            }
            $msg .= ': ' . implode(' | ', array_filter($parts));
        }
        return $msg;
    }

    private function log(string $endpoint, string $method, array $payload, int $http, ?string $body, bool $ok, int $ms, array $ctx): void
    {
        if (!$ok) {
            Log::warning('Bigship API failure', ['endpoint' => $endpoint, 'http' => $http, 'body' => mb_substr((string) $body, 0, 2000)]);
        }
        try {
            static $cols = null;
            if ($cols === null) {
                $cols = Schema::hasTable('courier_api_logs') ? array_flip(Schema::getColumnListing('courier_api_logs')) : [];
            }
            if (!$cols) {
                return;
            }
            $req = mb_substr(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 60000);
            $resp = $body === null ? null : mb_substr($body, 0, 60000);
            $row = [
                'courier' => self::CODE, 'endpoint' => mb_substr($endpoint, 0, 80), 'method' => strtoupper($method),
                'request' => $req, 'request_body' => $req, 'response' => $resp, 'response_body' => $resp,
                'http_status' => $http, 'status_code' => $http, 'success' => $ok, 'duration_ms' => $ms,
                'order_id' => $ctx['order_id'] ?? null, 'awb' => $ctx['awb'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            DB::table('courier_api_logs')->insert(array_intersect_key($row, $cols));
        } catch (\Throwable $e) {
            // logging must never break shipping
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Warehouses                                                         */
    /* ------------------------------------------------------------------ */

    private function clip(?string $s, int $min, int $max, string $fallback): string
    {
        $s = trim(preg_replace('/\s+/', ' ', (string) $s));
        if (mb_strlen($s) < $min) {
            $s = trim($s . ' ' . $fallback);
        }
        return mb_substr($s, 0, $max);
    }

    public static function phone10($phone): string
    {
        $d = preg_replace('/\D/', '', (string) $phone);
        return strlen($d) > 10 ? substr($d, -10) : $d;
    }

    /**
     * Bigship warehouse id for a pickup/return address
     * (['name','phone','address_line1','address_line2','city','state','pincode']).
     */
    public function warehouseIdFor(int $sellerId, array $a): array
    {
        $hash = substr(sha1(json_encode([$a['name'] ?? '', self::phone10($a['phone'] ?? ''), $a['address_line1'] ?? '', $a['address_line2'] ?? '', $a['pincode'] ?? ''])), 0, 40);
        $hasTable = Schema::hasTable('bigship_warehouses');
        if ($hasTable && ($id = DB::table('bigship_warehouses')->where('seller_id', $sellerId)->where('address_hash', $hash)->value('bigship_warehouse_id'))) {
            return ['ok' => true, 'id' => (int) $id, 'error' => null];
        }

        $city = (string) ($a['city'] ?? '');
        $whName = trim(preg_replace('/[^A-Za-z0-9 \-]/', ' ', (string) ($a['title'] ?? '') ?: (string) ($a['name'] ?? '')));
        $whName = mb_substr(trim(preg_replace('/\s+/', ' ', ($whName ?: 'Warehouse') . ' ' . ($a['pincode'] ?? '') . ' ' . substr($hash, 0, 4))), 0, 50);
        $payload = [
            'warehouseName'            => $whName,
            'warehouseContactPerson'   => mb_substr(trim((string) ($a['name'] ?? 'Warehouse')), 0, 50) ?: 'Warehouse',
            'warehouseAddressPhone'    => self::phone10($a['phone'] ?? ''),
            'warehouseCountry'         => 'India',
            'warehouseState'           => (string) ($a['state'] ?? ''),
            'warehouseCity'            => $city,
            'warehousePinCode'         => (string) ($a['pincode'] ?? ''),
            'warehouseAddressLine1'    => $this->clip($a['address_line1'] ?? '', 3, 75, $city),
            'warehouseAddressLine2'    => $this->clip($a['address_line2'] ?? '', 3, 75, $city),
            'warehouseAddressLandMark' => $this->clip($a['landmark'] ?? ($a['address_line2'] ?? ''), 3, 50, $city ?: 'Near main road'),
        ];
        if (strlen($payload['warehouseAddressPhone']) !== 10) {
            return ['ok' => false, 'id' => null, 'error' => 'Pickup/return address phone must have 10 digits.'];
        }

        $segments = array_values(array_unique([(string) config('bigship.warehouse_segment', self::SEGMENT), self::SEGMENT, 'local']));
        $res = null;
        foreach ($segments as $seg) {
            $res = $this->request('api/outbound/save-warehouse-data', 'POST', ['segment_type' => $seg] + $payload);
            if ($res['ok'] || !str_contains(strtolower((string) $res['error']), 'segment')) {
                break;
            }
        }
        $id = $res['data']['data']['warehouseId'] ?? null;
        if (!$res['ok'] || !$id) {
            return ['ok' => false, 'id' => null, 'error' => 'Could not register pickup address with the courier: ' . ($res['error'] ?? 'no warehouse id')];
        }
        if ($hasTable) {
            DB::table('bigship_warehouses')->insert([
                'seller_id' => $sellerId, 'address_hash' => $hash, 'pincode' => $payload['warehousePinCode'],
                'bigship_warehouse_id' => (int) $id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return ['ok' => true, 'id' => (int) $id, 'error' => null];
    }

    /* ------------------------------------------------------------------ */
    /*  Rates                                                              */
    /* ------------------------------------------------------------------ */

    /** Normalised boxes: [['count'=>2,'length'=>40,'breadth'=>30,'height'=>30,'weight'=>12], ...] (cm / kg per box) */
    public static function normaliseBoxes(array $boxes): array
    {
        $out = [];
        foreach ($boxes as $b) {
            $count = max(0, (int) ($b['count'] ?? 0));
            if (!$count) {
                continue;
            }
            $out[] = [
                'count'   => $count,
                'length'  => max(1, (int) ceil((float) ($b['length'] ?? 0))),
                'breadth' => max(1, (int) ceil((float) ($b['breadth'] ?? 0))),
                'height'  => max(1, (int) ceil((float) ($b['height'] ?? 0))),
                'weight'  => max(1, (int) ceil((float) ($b['weight'] ?? 0))),
            ];
        }
        return $out;
    }

    /**
     * Quote for a B2B shipment (no Bigship order created).
     * $p: from, to, invoice_amount, payment ('prepaid'|'cod'), cod_amount, risk_type_id, boxes (normalised)
     * Returns ['ok', 'error', 'rates' => [[courier_id, courier_name, tat, charged_weight, zone, cost, price, approved], ...]]
     */
    public function quote(array $p, bool $onlyApproved = true): array
    {
        $cod = ($p['payment'] ?? 'prepaid') === 'cod';
        $payload = [
            'segment_type'  => self::SEGMENT,
            'sourcePincode' => (string) $p['from'],
            'destPincode'   => (string) $p['to'],
            'invoiceValue'  => (int) round((float) $p['invoice_amount']),
            'invoicevalue'  => (int) round((float) $p['invoice_amount']),
            'paymentModeId' => $cod ? 2 : 1,
            'riskTypeId'    => (int) ($p['risk_type_id'] ?? 2),
            'boxes'         => array_map(fn ($b) => [
                'no_of_box'       => $b['count'],
                'box_length'      => $b['length'],
                'box_width'       => $b['breadth'],
                'box_height'      => $b['height'],
                'box_dead_weight' => $b['weight'],
            ], $p['boxes']),
        ];
        if ($cod) {
            $payload['codAmount'] = (int) round((float) ($p['cod_amount'] ?? 0));
        }

        $res = $this->request('api/outbound/user-rate-calculator', 'POST', $payload);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'], 'rates' => []];
        }
        $rows = $res['data']['data'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $rates = [];
        foreach ($rows as $r) {
            $id = (string) ($r['courier_partner_id'] ?? $r['courierId'] ?? '');
            $name = trim((string) ($r['courierName'] ?? ''));
            $cost = (float) ($r['totalCharge'] ?? $r['total'] ?? 0);
            if ($id === '' || $name === '' || $cost <= 0) {
                continue;
            }
            $rates[] = [
                'courier_id'     => $id,
                'courier_name'   => $name,
                'plan_name'      => (string) ($r['planName'] ?? ''),
                'tat'            => (int) ($r['tat'] ?? 0),
                'charged_weight' => (float) ($r['weight'] ?? 0),
                'zone'           => (string) ($r['zone'] ?? ''),
                'note'           => (string) ($r['restricted_pincode_message'] ?? ''),
                'cost'           => round($cost, 2),
                'price'          => $this->sellerPrice($cost),
            ];
        }
        $this->syncCouriers($rates);
        $approved = $this->approvedIds();
        foreach ($rates as &$r) {
            $r['approved'] = isset($approved[$r['courier_id']]);
        }
        unset($r);
        if ($onlyApproved) {
            $rates = array_values(array_filter($rates, fn ($r) => $r['approved']));
        }
        usort($rates, fn ($a, $b) => $a['price'] <=> $b['price']);

        return ['ok' => true, 'error' => null, 'rates' => $rates];
    }

    /** Remember every courier Bigship offers; new ones start OFF until admin approves. */
    public function syncCouriers(array $rates): void
    {
        if (!Schema::hasTable('bigship_couriers')) {
            return;
        }
        foreach ($rates as $r) {
            try {
                $row = DB::table('bigship_couriers')->where('courier_id', $r['courier_id'])->first();
                if ($row) {
                    DB::table('bigship_couriers')->where('id', $row->id)->update([
                        'name' => $r['courier_name'], 'plan_name' => $r['plan_name'] ?? null, 'last_seen_at' => now(), 'updated_at' => now(),
                    ]);
                } else {
                    DB::table('bigship_couriers')->insert([
                        'courier_id' => $r['courier_id'], 'name' => $r['courier_name'], 'plan_name' => $r['plan_name'] ?? null,
                        'approved' => false, 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
            }
        }
    }

    public function approvedIds(): array
    {
        if (!Schema::hasTable('bigship_couriers')) {
            return [];
        }
        return array_flip(DB::table('bigship_couriers')->where('approved', true)->pluck('courier_id')->map(fn ($v) => (string) $v)->all());
    }

    /* ------------------------------------------------------------------ */
    /*  Wallet                                                             */
    /* ------------------------------------------------------------------ */

    public static function walletBalance(int $sellerId): float
    {
        $credit = Recharge::where('seller_id', $sellerId)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerId)->where('type', 'Debit')->sum('amount');
        return round((float) $credit - (float) $debit, 2);
    }

    private function canAfford(SellerList $seller, float $amount): bool
    {
        if ((string) $seller->negative_balance === '1') {
            return true;
        }
        $bal = self::walletBalance((int) $seller->id);
        return $bal >= $amount && $bal - $amount >= 0 && $bal >= (float) config('bigship.min_wallet', 150);
    }

    /* ------------------------------------------------------------------ */
    /*  Booking                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Book a draft B2B order with the chosen courier.
     * $quotedPrice is what the seller saw; if the exact price is more than 3% higher we stop and show it.
     * Returns ['ok', 'message', 'price_changed' => bool].
     */
    public function book(B2bOrder $order, string $courierId, float $quotedPrice): array
    {
        if ($order->status !== 'draft' || $order->awb) {
            return ['ok' => false, 'message' => 'This order is already booked.'];
        }
        $seller = SellerList::find($order->seller_id);
        if (!$seller || (string) $seller->status !== '1') {
            return ['ok' => false, 'message' => 'Your account is not active.'];
        }
        if (!isset($this->approvedIds()[$courierId])) {
            return ['ok' => false, 'message' => 'This courier is not available any more. Please choose another.'];
        }
        if (!$this->canAfford($seller, $quotedPrice)) {
            return ['ok' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
        }
        $ctx = ['order_id' => $order->id];

        // 1. Draft order at Bigship (reused if an earlier attempt created one)
        if (!$order->provider_order_id) {
            $pick = $this->warehouseIdFor((int) $order->seller_id, $order->pickup ?? []);
            if (!$pick['ok']) {
                return $this->fail($order, $pick['error']);
            }
            $ret = $pick;
            if (!empty($order->return_address) && $order->return_warehouse_id && $order->return_warehouse_id != $order->pickup_warehouse_id) {
                $ret = $this->warehouseIdFor((int) $order->seller_id, $order->return_address);
                if (!$ret['ok']) {
                    return $this->fail($order, $ret['error']);
                }
            }

            $c = $order->consignee ?? [];
            $boxes = $order->boxes ?? [];
            $payload = [
                'segment_type'                 => self::SEGMENT,
                'MasterOrderPickUpLocation'    => $pick['id'],
                'MasterOrderReturnLocation'    => $ret['id'],
                'MasterOrderDate'              => now('UTC')->format('Y-m-d H:i:s'),
                'MasterOrderPaymentMode'       => $order->isCod() ? 2 : 1,
                'OrderInvoiceNo'               => (string) $order->invoice_no,
                'MasterOrderInvoiceAmount'     => (int) round((float) $order->invoice_amount),
                'MasterOrderShippingName'      => mb_substr((string) ($c['name'] ?? ''), 0, 100),
                'MasterOrderShippingMobileNo'  => self::phone10($c['phone'] ?? ''),
                'MasterOrderShippingEmail'     => (string) ($c['email'] ?? ''),
                'MasterOrderShippingAddress'   => mb_substr((string) ($c['address_line1'] ?? ''), 0, 200),
                'MasterOrderShippingAddress2'  => mb_substr((string) ($c['address_line2'] ?? ''), 0, 200),
                'MasterOrderShippingLandmark'  => mb_substr((string) ($c['landmark'] ?? ''), 0, 100),
                'MasterOrderShippingZipCode'   => (string) ($c['pincode'] ?? ''),
                'MasterOrderShippingCity'      => (string) ($c['city'] ?? ''),
                'MasterOrderShippingState'     => (string) ($c['state'] ?? ''),
                'MasterOrderShippingCountry'   => 'India',
                'ProductName'                  => mb_substr((string) $order->product_name, 0, 150),
                'totalNumOfBoxes'              => array_sum(array_column($boxes, 'count')),
                'boxes'                        => array_map(fn ($b) => [
                    'weight_unit'    => 'kg',
                    'dimension_unit' => 'cm',
                    'noOfBoxes'      => (int) $b['count'],
                    'dimensions'     => [[
                        'length' => (int) $b['length'], 'breadth' => (int) $b['breadth'],
                        'height' => (int) $b['height'], 'weight' => (int) $b['weight'],
                    ]],
                ], $boxes),
            ];
            if ($order->isCod()) {
                $payload['MasterOrderCollectableAmount'] = (int) round((float) $order->cod_amount);
            }
            $res = $this->request('api/outbound/create-order', 'POST', $payload, $ctx);
            $gid = $res['data']['data']['CustomGlobalOrderId'] ?? null;
            if (!$res['ok'] || !$gid) {
                return $this->fail($order, 'Courier rejected the order: ' . ($res['error'] ?? 'no order id'));
            }
            $order->provider_order_id = (string) $gid;
            $order->save();
        }

        // 2. Exact price for the chosen courier
        $res = $this->request('api/outbound/courier-wise-shipment-cost', 'POST', ['MasterCustomOrderId' => $order->provider_order_id], $ctx);
        if (!$res['ok']) {
            return $this->fail($order, 'Could not fetch the final price: ' . $res['error']);
        }
        $rate = collect($res['data']['data']['calculatedRates'] ?? [])
            ->first(fn ($r) => (string) ($r['courierId'] ?? '') === $courierId);
        if (!$rate) {
            return $this->fail($order, 'The selected courier cannot carry this shipment any more. Please choose another courier.');
        }
        $cost = (float) ($rate['total'] ?? 0);
        // "total" includes the risk option Bigship marked as selected; swap in the seller's choice
        $risks = collect($rate['riskCharges'] ?? []);
        $selected = $risks->first(fn ($rc) => !empty($rc['isRisk']));
        $chosen = $risks->first(fn ($rc) => (int) ($rc['typeId'] ?? 0) === (int) $order->risk_type_id);
        if ($selected && $chosen && $selected !== $chosen) {
            $cost += (float) ($chosen['chargeValue'] ?? 0) - (float) ($selected['chargeValue'] ?? 0);
        }
        if ($cost <= 0) {
            return $this->fail($order, 'The courier did not return a price for this shipment.');
        }
        $price = $this->sellerPrice($cost);
        if ($price > $quotedPrice * 1.03 + 1) {
            $order->quote = array_merge((array) $order->quote, ['final_price' => $price, 'final_cost' => $cost, 'courier_id' => $courierId]);
            $order->save();
            return ['ok' => false, 'price_changed' => true, 'message' => 'Final price for this courier is ₹' . number_format($price, 2) . ' (quoted ₹' . number_format($quotedPrice, 2) . '). Please confirm again.'];
        }
        if (!$this->canAfford($seller, $price)) {
            return ['ok' => false, 'message' => 'Insufficient wallet balance for ₹' . number_format($price, 2) . '. Please recharge your wallet.'];
        }

        // 3. Place order with invoice / e-way bill files
        $disk = Storage::disk('local');
        $files = [];
        if ($order->invoice_file && $disk->exists($order->invoice_file)) {
            $files['InvoiceData'] = ['path' => $disk->path($order->invoice_file), 'name' => 'invoice.' . pathinfo($order->invoice_file, PATHINFO_EXTENSION)];
        }
        if ($order->ewaybill_file && $disk->exists($order->ewaybill_file)) {
            $files['EwayBillData'] = ['path' => $disk->path($order->ewaybill_file), 'name' => 'ewaybill.' . pathinfo($order->ewaybill_file, PATHINFO_EXTENSION)];
        }
        if (!isset($files['InvoiceData'])) {
            return $this->fail($order, 'Invoice file is missing. Please upload the invoice again.');
        }
        $res = $this->request('api/outbound/place-order', 'POST', [
            'MasterCustomOrderId' => $order->provider_order_id,
            'courierId'           => $courierId,
            'invoiceType'         => 'uploaded',
            'EwaybillNo'          => (string) ($order->ewaybill_no ?? ''),
            'riskTypeId'          => (string) $order->risk_type_id,
        ], $ctx, $files);
        if (!$res['ok']) {
            return $this->fail($order, 'Booking failed: ' . $res['error']);
        }
        $d = $res['data']['data'] ?? [];
        $awb = $d['awb_assigned'] ?? null;
        $awb = (is_string($awb) || is_int($awb)) && (string) $awb !== '' && $awb !== true ? (string) $awb : null;

        $order->courier_id = $courierId;
        $order->courier_name = (string) ($rate['courierName'] ?? $order->courier_name);
        $order->tat = (int) ($rate['tat'] ?? 0) ?: null;
        $order->charged_weight = (float) ($rate['charged_weight'] ?? 0) ?: null;
        $order->courier_cost = round($cost, 2);
        $order->seller_charge = $price;
        $order->awb = $awb ?: (string) ($d['reference_number'] ?? '');
        $order->status = 'booked';
        $order->error = null;
        $order->booked_at = now();
        $order->save();

        // AWB / LR from order details when place-order didn't return a usable one
        $this->refreshDetails($order);

        Recharge::create([
            'seller_id'   => $seller->id,
            'type'        => 'Debit',
            'amount'      => $price,
            'status'      => 1,
            'description' => 'B2B shipment ' . $order->order_ref . ($order->awb ? ' AWB ' . $order->awb : ''),
        ]);

        return ['ok' => true, 'message' => 'Booked with ' . $order->courier_name . ($order->awb ? ' - AWB ' . $order->awb : '') . '. ₹' . number_format($price, 2) . ' debited from wallet.'];
    }

    private function fail(B2bOrder $order, string $msg): array
    {
        $order->error = mb_substr($msg, 0, 1000);
        $order->save();
        return ['ok' => false, 'message' => $msg];
    }

    public function refreshDetails(B2bOrder $order): void
    {
        if (!$order->provider_order_id) {
            return;
        }
        $res = $this->request('api/outbound/order-shipment-details', 'GET', ['MasterCustomOrderId' => $order->provider_order_id], ['order_id' => $order->id]);
        $d = $res['data']['data']['getOrderDetails'] ?? null;
        if (!$res['ok'] || !is_array($d)) {
            return;
        }
        if (!empty($d['AwbNumber'])) {
            $order->awb = (string) $d['AwbNumber'];
        }
        if (!empty($d['lr_number'])) {
            $order->lr_number = (string) $d['lr_number'];
        }
        $order->save();
    }

    /* ------------------------------------------------------------------ */
    /*  After booking                                                      */
    /* ------------------------------------------------------------------ */

    public function cancel(B2bOrder $order): array
    {
        if (!$order->canCancel()) {
            return ['ok' => false, 'message' => 'This shipment can no longer be cancelled.'];
        }
        $res = $this->request('api/outbound/cancel-order', 'POST', [
            'CustomGlobalOrderId' => $order->provider_order_id,
            'MasterCustomOrderId' => $order->provider_order_id,
        ], ['order_id' => $order->id, 'awb' => $order->awb]);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => 'Courier did not accept the cancellation: ' . $res['error']];
        }
        $order->status = 'cancelled';
        $order->cancelled_at = now();
        $order->save();

        if ((float) $order->seller_charge > 0) {
            Recharge::create([
                'seller_id'   => $order->seller_id,
                'type'        => 'Credit',
                'amount'      => $order->seller_charge,
                'status'      => 1,
                'description' => 'Refund: B2B shipment ' . $order->order_ref . ' cancelled',
            ]);
        }
        return ['ok' => true, 'message' => 'Shipment cancelled and ₹' . number_format((float) $order->seller_charge, 2) . ' refunded to wallet.'];
    }

    public function track(B2bOrder $order): array
    {
        if (!$order->provider_order_id && !$order->awb) {
            return ['ok' => false, 'message' => 'Not booked yet.'];
        }
        $body = $order->provider_order_id ? ['CustomGlobalOrderId' => $order->provider_order_id] : ['TrackingNumber' => $order->awb];
        $res = $this->request('api/outbound/track-order', 'GET', $body, ['order_id' => $order->id, 'awb' => $order->awb]);
        $order->last_tracked_at = now();
        if (!$res['ok'] || !is_array($res['data']['data'] ?? null)) {
            $order->save();
            return ['ok' => false, 'message' => $res['error'] ?? 'No tracking yet.'];
        }
        $d = $res['data']['data'];
        $order->tracking = $d;
        if (!empty($d['tracking_number'])) {
            $order->awb = (string) $d['tracking_number'];
        }
        $raw = (string) ($d['order_status'] ?? '');
        $order->tracking_status = $raw ?: $order->tracking_status;
        $mapped = self::mapStatus($raw);
        if ($mapped && $order->status !== 'cancelled') {
            $order->status = $mapped;
        }
        $order->save();
        return ['ok' => true, 'message' => $raw ?: 'Updated'];
    }

    public static function mapStatus(string $raw): ?string
    {
        $s = strtolower($raw);
        if ($s === '') {
            return null;
        }
        if (str_contains($s, 'rto') || str_contains($s, 'return')) {
            return 'rto';
        }
        if (str_contains($s, 'cancel')) {
            return 'cancelled';
        }
        if (str_contains($s, 'delivered')) {
            return 'delivered';
        }
        if (preg_match('/transit|picked|out for|dispatch|reached|arrived|in-transit|connected/', $s)) {
            return 'in_transit';
        }
        return null;
    }

    /** Document URL from Bigship: invoice | label | ewaybill | manifest */
    public function documentUrl(B2bOrder $order, string $type): array
    {
        $res = $this->request('api/outbound/download-shipment-documents', 'GET', [
            'CustomGlobalOrderId' => $order->provider_order_id,
            'document_type'       => $type,
        ], ['order_id' => $order->id, 'awb' => $order->awb]);
        $url = $res['data']['data']['AttachmentData'] ?? null;
        if (!$res['ok'] || !$url) {
            return ['ok' => false, 'error' => $res['error'] ?? 'Document not available yet.', 'url' => null];
        }
        return ['ok' => true, 'error' => null, 'url' => (string) $url, 'mime' => (string) ($res['data']['data']['File_extention'] ?? 'application/pdf')];
    }
}
