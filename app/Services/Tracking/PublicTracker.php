<?php

namespace App\Services\Tracking;

use App\Models\Order;
use App\Models\SellerList;
use App\Support\CourierBrand;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer-facing tracking for every courier in one shape.
 *
 * track($order) returns:
 *   awb, order_no, brand, courier, courier_logo, stage (booked|picked|transit|ofd|delivered|ndr|rto|cancelled|lost),
 *   status_label, status_note, edd (Carbon|null), destination, updated_at (Carbon|null), live (bool),
 *   events: [ ['time' => Carbon|null, 'status' => string, 'remark' => string, 'location' => string], ... ] newest first
 *
 * Aggregator names (Shipclues, Fship ...) never reach the buyer: only the real courier is shown.
 */
class PublicTracker
{
    public const STAGES = ['booked', 'picked', 'transit', 'ofd', 'delivered'];
    public const FINAL = ['delivered', 'rto_delivered', 'cancelled', 'lost'];

    private const TZ = 'Asia/Kolkata';

    /* =========================================================
     |  Order lookup (AWB or Order ID)
     * ========================================================= */

    /** @return array{0: ?Order, 1: ?string} order, error message */
    public static function find(string $q): array
    {
        $q = trim($q);
        if ($q === '' || mb_strlen($q) > 64) {
            return [null, 'Enter a valid AWB number or Order ID.'];
        }

        $order = Order::where('awb_number', $q)->orderByDesc('id')->first();
        if ($order) {
            return [$order, null];
        }

        $bare = ltrim($q, '# ');
        $variants = array_values(array_unique([$q, $bare, '#' . $bare]));
        $cols = array_values(array_filter(
            ['order_number', 'customer_order_id', 'shopify_order_number'],
            fn ($c) => self::hasColumn($c)
        ));
        if ($bare === '' || !$cols) {
            return [null, 'No shipment found for "' . $q . '". Check the AWB number and try again.'];
        }

        $matches = Order::where(function ($w) use ($cols, $variants) {
            foreach ($cols as $c) {
                $w->orWhereIn($c, $variants);
            }
        })->orderByDesc('id')->limit(3)->get();

        if ($matches->count() === 1) {
            return [$matches->first(), null];
        }
        if ($matches->count() > 1) {
            return [null, 'More than one shipment uses Order ID "' . $q . '". Please track with the AWB number from your shipping SMS or email.'];
        }
        return [null, 'No shipment found for "' . $q . '". Check the AWB number or Order ID and try again.'];
    }

    /* =========================================================
     |  Tracking
     * ========================================================= */

    public function track(Order $order, bool $fresh = false): array
    {
        $awb = trim((string) $order->awb_number);
        $base = $this->base($order);

        if ($awb === '') {
            return $base + ['stage' => 'booked', 'events' => [], 'live' => false]
                + $this->label('booked', 'Order received. It will be shipped soon.');
        }

        $key = 'pubtrack:v1:' . md5($awb . '|' . $order->courier_id);
        if ($fresh) {
            Cache::forget($key);
        }
        $live = Cache::remember($key, now()->addMinutes(10), function () use ($order, $awb) {
            try {
                return $this->fetch($order, $awb);
            } catch (\Throwable $e) {
                Log::warning('PublicTracker fetch failed', ['awb' => $awb, 'courier' => $order->courier_id, 'error' => $e->getMessage()]);
                return null;
            }
        });

        $events = $this->normalise($live['events'] ?? []);
        $stage = $this->stageFrom($live['status'] ?? null, $events, (string) $order->shipping_status, (string) $order->order_status);

        if (!empty($live['edd'])) {
            $base['edd'] = $this->time($live['edd']);
        }
        if (!empty($live['courier']) && $base['courier'] === 'Courier partner') {
            $base['courier'] = self::courierName($live['courier']);
            $base['courier_logo'] = CourierBrand::logo($live['courier']);
        }
        $base['updated_at'] = $events[0]['time'] ?? ($order->updated_at ? Carbon::parse($order->updated_at)->timezone(self::TZ) : null);

        // exact courier wording of the latest scan (remark, else the scan itself)
        $note = ($events[0]['remark'] ?? '') ?: ($events[0]['status'] ?? '');
        if ($note === '' && $stage === 'booked') {
            $note = 'Shipment booked. The courier will pick it up soon.';
        }

        return $base + [
            'stage' => $stage,
            'events' => $events,
            'live' => $live !== null && $events !== [],
        ] + $this->label($stage, $note);
    }

    private function base(Order $order): array
    {
        $consignee = self::arr($order->consignee);
        $city = trim((string) ($consignee['city'] ?? ''));
        $state = trim((string) ($consignee['state'] ?? ''));
        $pin = trim((string) ($consignee['pincode'] ?? $consignee['pin'] ?? ''));
        $dest = trim(implode(', ', array_filter([ucwords(strtolower($city)), ucwords(strtolower($state))])) . ($pin ? ' - ' . $pin : ''), ' -,');

        $courierRaw = (string) ($order->courier_name ?: ($order->all_courier_name ?? ''));
        $orderNo = (string) (($order->customer_order_id ?? null) ?: ($order->order_number ?: $order->id));

        return [
            'awb' => (string) $order->awb_number,
            'order_no' => $orderNo,
            'brand' => self::brand($order),
            'courier' => self::courierName($courierRaw),
            'courier_logo' => CourierBrand::logo($courierRaw),
            'payment' => strtolower((string) $order->payment_type) === 'cod' ? 'Cash on delivery' : 'Prepaid',
            'destination' => $dest,
            'edd' => null,
            'updated_at' => null,
            'booked_at' => $order->created_at ? Carbon::parse($order->created_at)->timezone(self::TZ) : null,
        ];
    }

    /** Raw events from the courier's API. Returns ['status' => ?, 'edd' => ?, 'courier' => ?, 'events' => [...]] */
    private function fetch(Order $order, string $awb): ?array
    {
        $cid = strtolower((string) $order->courier_id);
        $name = strtolower((string) $order->courier_name);

        return match (true) {
            str_starts_with($cid, 'fship') => $this->fship($awb),
            str_starts_with($cid, 'shipcorrect') => $this->shipcorrect($awb),
            str_starts_with($cid, 'shipclues') || str_starts_with($cid, 'sxc') => $this->shipclues($order, $awb),
            str_starts_with($cid, 'xpressbees') => $this->xpressbees($awb),
            str_starts_with($cid, 'delhivery') => $this->delhivery($awb),
            default => str_contains($name, 'delhivery') ? $this->delhivery($awb) : null,
        };
    }

    private function shipcorrect(string $awb): ?array
    {
        $t = app(\App\Services\ShipCorrectService::class)->track($awb);
        return empty($t['status']) ? null : [
            'status' => $t['raw_status'] ?? null, 'edd' => $t['edd'] ?? null, 'courier' => null,
            'events' => array_map(fn ($e) => ['time' => $e['event_time'] ?? null, 'status' => $e['message'] ?? '', 'location' => $e['location'] ?? ''], $t['history'] ?? []),
        ];
    }

    private function fship(string $awb): ?array
    {
        $svc = app(\App\Services\FshipService::class);
        if (!method_exists($svc, 'trackingHistory')) { // older server copy: use its normalised track()
            $t = $svc->track($awb);
            return empty($t['status']) ? null : [
                'status' => $t['raw_status'] ?? null, 'edd' => $t['edd'] ?? null, 'courier' => $t['courier'] ?? null,
                'events' => array_map(fn ($e) => ['time' => $e['event_time'] ?? null, 'status' => $e['message'] ?? '', 'location' => $e['location'] ?? ''], $t['history'] ?? []),
            ];
        }
        $res = $svc->trackingHistory($awb);
        if (empty($res['ok']) || empty($res['data'])) {
            return null;
        }
        $sum = $res['data']['summary'] ?? [];
        return [
            'status' => $sum['status'] ?? null,
            'edd' => $sum['expectedDeliveryDate'] ?? null,
            'courier' => $sum['fulfilledby'] ?? null,
            'events' => $res['data']['trackingdata'] ?? [],
        ];
    }

    private function delhivery(string $awb): ?array
    {
        $token = (string) (env('DELHIVERY_B2C_TOKEN') ?: config('services.delhivery.token'));
        if ($token === '') {
            return null; // DELHIVERY_B2C_TOKEN missing in .env: page shows the saved status instead
        }
        $r = Http::withHeaders(['Authorization' => 'Token ' . $token])->acceptJson()->timeout(20)
            ->get('https://track.delhivery.com/api/v1/packages/json/', ['waybill' => $awb]);
        $s = $r->successful() ? ($r->json('ShipmentData.0.Shipment') ?? null) : null;
        if (!$s) {
            return null;
        }
        $events = array_map(function ($scan) {
            $d = $scan['ScanDetail'] ?? $scan;
            return [
                'time' => $d['ScanDateTime'] ?? $d['StatusDateTime'] ?? null,
                'status' => $d['Scan'] ?? '',
                'remark' => $d['Instructions'] ?? '',
                'location' => $d['ScannedLocation'] ?? '',
            ];
        }, $s['Scans'] ?? []);

        return [
            'status' => (($s['Status']['StatusType'] ?? '') === 'RT' ? 'RTO ' : '') . ($s['Status']['Status'] ?? ''),
            'edd' => $s['ExpectedDeliveryDate'] ?? $s['PromisedDeliveryDate'] ?? null,
            'events' => $events,
        ];
    }

    private function shipclues(Order $order, string $awb): ?array
    {
        $key = env('SHIPCLUES_API_KEY') ?: (string) \App\Models\LogisticProvider::where('code', 'shipclues')->value('api_key');
        if (trim($key) === '') {
            return null;
        }
        $info = self::arr($order->additional_info);
        $row = null;
        foreach (array_unique(array_filter([$awb, $info['shipclues_awb'] ?? null])) as $a) {
            $r = Http::withHeaders(['Authorization' => 'Token ' . trim($key)])->acceptJson()->timeout(20)
                ->get('https://api.shipclues.in/seller/v1/order/tracking/', ['awb_number' => $a]);
            $j = $r->successful() ? $r->json() : null;
            $row = is_array($j) ? (array_is_list($j) ? ($j[0] ?? null) : ($j['data'][0] ?? $j['data'] ?? $j)) : null;
            if (is_array($row) && $row) {
                break;
            }
        }
        if (!is_array($row) || !$row) {
            return null;
        }
        $events = [];
        foreach (['scans', 'tracking_history', 'track_history', 'tracking_data', 'history', 'events', 'scan_details', 'shipment_track_activities', 'activities'] as $k) {
            if (!empty($row[$k]) && is_array($row[$k])) {
                $events = $row[$k];
                break;
            }
        }
        return [
            'status' => $row['status'] ?? $row['current_status'] ?? null,
            'edd' => $row['edd'] ?? $row['expected_delivery_date'] ?? null,
            'courier' => $row['courier_name'] ?? null,
            'events' => $events,
        ];
    }

    private function xpressbees(string $awb): ?array
    {
        $svc = app(\App\Services\XpressBeesService::class);
        $token = (fn () => $this->fetchAuthToken())->call($svc);
        $r = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->acceptJson()->timeout(20)
            ->get('https://shipment.xpressbees.com/api/shipments2/track/' . urlencode($awb));
        if (!$r->successful() || $r->json('status') !== true) {
            return null;
        }
        $d = (array) $r->json('data');
        return [
            'status' => $d['status'] ?? null,
            'edd' => $d['edd'] ?? $d['expected_delivery_date'] ?? null,
            'events' => $d['history'] ?? $d['scans'] ?? [],
        ];
    }

    /* =========================================================
     |  Normalising
     * ========================================================= */

    /** Any courier's event list → [time, status, remark, location], newest first, duplicates removed. */
    private function normalise(array $raw): array
    {
        $out = [];
        foreach ($raw as $e) {
            if (!is_array($e)) {
                continue;
            }
            $e = array_change_key_case($e, CASE_LOWER);
            $status = self::pick($e, ['status', 'scan', 'activity', 'event', 'scan_status', 'status_description', 'current_status', 'message']);
            $remark = self::pick($e, ['remark', 'remarks', 'instructions', 'description', 'status_remark', 'comment', 'reason', 'sr-status-label', 'message']);
            $loc = self::pick($e, ['location', 'scannedlocation', 'scanned_location', 'city', 'hub', 'scan_location', 'current_location']);
            $time = self::pick($e, ['time', 'event_time', 'dateandtime', 'scandatetime', 'scan_datetime', 'date_time', 'datetime', 'timestamp', 'updated_at', 'created_at', 'date']);

            $status = self::tidy($status);
            $remark = self::tidy(CourierBrand::cleanName($remark, ''));
            if ($status === '' && $remark !== '') {
                [$status, $remark] = [$remark, ''];
            }
            if ($status === '') {
                continue;
            }
            if (strcasecmp($status, $remark) === 0) {
                $remark = '';
            }
            $out[] = [
                'time' => $this->time($time),
                'status' => $status,
                'remark' => $remark,
                'location' => self::place($loc),
            ];
        }

        usort($out, fn ($a, $b) => ($b['time']?->timestamp ?? 0) <=> ($a['time']?->timestamp ?? 0));

        $seen = [];
        return array_values(array_filter($out, function ($e) use (&$seen) {
            $k = strtolower($e['status'] . '|' . $e['remark'] . '|' . $e['location'] . '|' . ($e['time']?->format('YmdHi') ?? ''));
            if (isset($seen[$k])) {
                return false;
            }
            return $seen[$k] = true;
        }));
    }

    private function stageFrom(?string $apiStatus, array $events, string $dbShipping, string $dbOrder): string
    {
        $texts = array_filter([$apiStatus, $events[0]['status'] ?? null, $events[0]['remark'] ?? null]);
        $t = strtolower(str_replace(['_', '-'], ' ', implode(' | ', $texts)));
        $db = strtolower(str_replace(['_', '-'], ' ', trim($dbShipping . ' ' . $dbOrder)));

        $from = function (string $s): ?string {
            if ($s === '') {
                return null;
            }
            $rto = preg_match('/\brto\b|return to origin|returning to|\brts\b/', $s) === 1;
            return match (true) {
                $rto && str_contains($s, 'deliver'),
                str_contains($s, 'returned to') || str_contains($s, 'return delivered') => 'rto_delivered',
                $rto => 'rto',
                str_contains($s, 'cancel') => 'cancelled',
                str_contains($s, 'lost') || str_contains($s, 'damaged') => 'lost',
                str_contains($s, 'out for delivery') || str_contains($s, 'ofd') => 'ofd',
                (str_contains($s, 'undeliver') || preg_match('/\bndr\b/', $s) === 1 || str_contains($s, 'not delivered')
                    || str_contains($s, 'delivery attempt') || str_contains($s, 'failed')) => 'ndr',
                str_contains($s, 'deliver') && !str_contains($s, 'pending') => 'delivered',
                str_contains($s, 'transit') || str_contains($s, 'bag') || str_contains($s, 'hub') || str_contains($s, 'facility')
                    || str_contains($s, 'dispatch') || str_contains($s, 'reached') || str_contains($s, 'trip') || str_contains($s, 'origin center') => 'transit',
                str_contains($s, 'picked') || str_contains($s, 'pickup done') => 'picked',
                default => null,
            };
        };

        // the newest event wins, then the courier's summary status, then what the panel has saved
        foreach ([$events[0]['status'] ?? '', strtolower((string) $apiStatus), $t, $db] as $s) {
            $st = $from(strtolower(str_replace(['_', '-'], ' ', (string) $s)));
            if ($st) {
                return $st;
            }
        }
        return 'booked';
    }

    private function label(string $stage, string $note): array
    {
        [$label, $default] = match ($stage) {
            'picked' => ['Picked up', 'The courier has picked up your package.'],
            'transit' => ['In transit', 'Your package is on its way.'],
            'ofd' => ['Out for delivery', 'Your package will be delivered today. Please keep your phone reachable.'],
            'delivered' => ['Delivered', 'Your package has been delivered.'],
            'ndr' => ['Delivery attempt failed', 'The courier could not deliver today and will try again.'],
            'rto' => ['Returning to seller', 'This package is being returned to the seller.'],
            'rto_delivered' => ['Returned to seller', 'This package has been returned to the seller.'],
            'cancelled' => ['Cancelled', 'This shipment was cancelled.'],
            'lost' => ['Shipment issue', 'There is an issue with this shipment. Please contact the seller.'],
            default => ['Order booked', 'Shipment booked. The courier will pick it up soon.'],
        };
        return ['status_label' => $label, 'status_note' => $note !== '' ? $note : $default];
    }

    /* =========================================================
     |  Helpers
     * ========================================================= */

    public static function brand(Order $order): string
    {
        $seller = SellerList::find($order->seller_id);
        if (class_exists(\App\Services\Notify\BuyerNotifier::class) && $seller) {
            return \App\Services\Notify\BuyerNotifier::brand($seller);
        }
        $b = trim((string) ($seller->whatsapp_brand_name ?? ''));
        return $b !== '' ? $b : (trim((string) ($seller->name ?? '')) ?: 'the seller');
    }

    /** "Delhivery Premium 0.25" → "Delhivery". Unknown couriers keep their cleaned name. */
    public static function courierName(?string $raw): string
    {
        $n = strtolower((string) $raw);
        $family = match (true) {
            str_contains($n, 'delhivery') => 'Delhivery',
            str_contains($n, 'blue') && str_contains($n, 'dart') => 'Blue Dart',
            str_contains($n, 'xpressbee') || str_contains($n, 'xpress bee') => 'Xpressbees',
            str_contains($n, 'ekart') || str_contains($n, 'e-kart') => 'Ekart',
            str_contains($n, 'dtdc') => 'DTDC',
            str_contains($n, 'amazon') => 'Amazon Shipping',
            str_contains($n, 'shadowfax') => 'Shadowfax',
            str_contains($n, 'ecom') => 'Ecom Express',
            str_contains($n, 'maruti') => 'Shree Maruti',
            str_contains($n, 'india post') || str_contains($n, 'speed post') => 'India Post',
            str_contains($n, 'dhl') => 'DHL',
            str_contains($n, 'aramex') => 'Aramex',
            default => null,
        };
        if ($family) {
            return $family;
        }
        $clean = CourierBrand::cleanName(ucwords(str_replace('_', ' ', (string) $raw)), '');
        $clean = trim(preg_replace('/\b(b2c|surface|air|prime|premium|express|\d+(\.\d+)?\s*(kg|g|gm)?)\b/i', '', $clean));
        return $clean !== '' ? $clean : 'Courier partner';
    }

    private function time($v): ?Carbon
    {
        if ($v === null || $v === '' || is_array($v)) {
            return null;
        }
        try {
            if (is_numeric($v)) {
                $n = (int) $v;
                return Carbon::createFromTimestamp($n > 9999999999 ? intdiv($n, 1000) : $n)->timezone(self::TZ);
            }
            $s = trim((string) $v);
            if (preg_match('#^(\d{1,2})[-/](\d{1,2})[-/](\d{4})(.*)$#', $s, $m)) {
                $s = "{$m[3]}-{$m[2]}-{$m[1]}{$m[4]}"; // dd-mm-yyyy
            }
            return Carbon::parse($s, self::TZ)->timezone(self::TZ);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function pick(array $e, array $keys): string
    {
        foreach ($keys as $k) {
            if (isset($e[$k]) && is_scalar($e[$k]) && trim((string) $e[$k]) !== '') {
                return (string) $e[$k];
            }
        }
        return '';
    }

    private static function tidy(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)));
        // "Delivered to consignee Delivered to consignee" → once
        if (preg_match('/^(.{3,}?)\s+\1$/iu', $s, $m)) {
            $s = $m[1];
        }
        return $s;
    }

    /** "Delhi_Kalkaji_D (Delhi)" → "Kalkaji, Delhi" */
    private static function place(string $loc): string
    {
        $loc = trim($loc);
        if ($loc === '') {
            return '';
        }
        if (preg_match('/^([A-Za-z]+)_([A-Za-z0-9]+)(?:_[A-Za-z0-9]+)?\s*(?:\(([^)]+)\))?$/', $loc, $m)) {
            $area = preg_replace('/([a-z])([A-Z0-9])/', '$1 $2', $m[2]);
            $area = trim(preg_replace('/\b(Sector)\s*(\d+)\s*[A-Z]?\d*\b/i', 'Sector $2', $area));
            $state = $m[3] ?? '';
            $city = $m[1];
            return trim($area . ', ' . $city . ($state && strcasecmp($state, $city) !== 0 ? ', ' . $state : ''), ', ');
        }
        return ucwords(strtolower(str_replace('_', ' ', $loc)));
    }

    private static function hasColumn(string $c): bool
    {
        static $cache = [];
        return $cache[$c] ??= Schema::hasColumn('orders', $c);
    }

    public static function arr($v): array
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
}
