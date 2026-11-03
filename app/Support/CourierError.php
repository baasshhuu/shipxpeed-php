<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Turns any courier API response (every adapter returns a different shape) into one
 * clear, seller-friendly failure reason, and records it on the order + a failure log.
 */
class CourierError
{
    /** Pull the most specific raw message out of a courier response. */
    public static function raw($response): string
    {
        if ($response instanceof \Throwable) {
            return $response->getMessage();
        }
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        if (is_string($response)) {
            return trim($response);
        }
        if (!is_array($response) || !$response) {
            return 'The courier did not send any response.';
        }

        $found = [];
        // Delhivery: packages[].remarks / status
        foreach (($response['packages'] ?? []) as $pkg) {
            foreach ((array) ($pkg['remarks'] ?? []) as $r) {
                if (is_string($r) && trim($r) !== '') $found[] = $r;
            }
        }
        $paths = [
            'message', 'error', 'errors', 'rmk', 'remark', 'remarks', 'reason', 'error_message', 'msg', 'detail',
            'data.message', 'data.error', 'data.errors', 'data.remarks', 'response.message', 'result.message',
            'data.0.message', 'data.0.error', 'error.message',
        ];
        foreach ($paths as $p) {
            $v = data_get($response, $p);
            foreach (self::flatten($v) as $s) {
                $found[] = $s;
            }
        }
        $found = array_values(array_unique(array_filter(array_map('trim', $found))));
        // drop useless generic words when something better exists
        $generic = ['success', 'failed', 'error', 'false', 'unknown error', 'order creation failed: unknown error', 'api returned error.', 'api returned error'];
        $better = array_values(array_filter($found, fn ($s) => !in_array(strtolower($s), $generic, true)));
        return implode(' | ', array_slice($better ?: $found, 0, 3)) ?: 'The courier rejected the order without giving a reason.';
    }

    private static function flatten($v): array
    {
        if (is_string($v) || is_numeric($v)) return [(string) $v];
        if (!is_array($v)) return [];
        $out = [];
        array_walk_recursive($v, function ($x, $k) use (&$out) {
            if (is_string($x) && strlen($x) < 400) $out[] = (is_string($k) && !is_numeric($k) && strlen($k) < 30 ? $k . ': ' : '') . $x;
        });
        return $out;
    }

    /** Map raw courier text to a clear reason + what to do next. */
    public static function friendly(string $raw): string
    {
        $r = strtolower($raw);
        $map = [
            ['/insufficient|low balance|wallet/', 'Insufficient wallet balance. Recharge your wallet and try again.'],
            ['/non.?serviceable|not serviceable|pincode.*(not|invalid)|pin code.*(not|invalid)|no serviceab|serviceability/', 'Delivery or pickup PIN code is not serviceable by this courier. Choose another courier.'],
            ['/warehouse|pickup location|client.?store|pickup address/', 'Pickup address (warehouse) was rejected by the courier. Check the pickup address and PIN code.'],
            ['/duplicate|already exists.*order|order id.*exist|same order/', 'This order number was already sent to the courier. Use a new order number or clone the order.'],
            ['/phone|mobile/', 'Customer or pickup phone number is invalid. Use a 10-digit Indian mobile number.'],
            ['/weight/', 'Package weight is missing or outside this courier\'s limit. Check the weight and dimensions.'],
            ['/(dimension|length|breadth|height)/', 'Package dimensions are invalid. Enter length, breadth and height in cm.'],
            ['/address|consignee/', 'Delivery address is incomplete or too short. Add house number, street and landmark.'],
            ['/cod.*(limit|amount|not)|collectable/', 'COD amount is not accepted by this courier (limit exceeded or COD unavailable). Choose another courier or prepaid.'],
            ['/(token|unauthori|authenticat|forbidden|invalid key|api key|credentials|401|403)/', 'Courier account authentication failed on our side. Our team has been notified; please try another courier meanwhile.'],
            ['/(timed out|timeout|curl error|could not resolve|connection|503|502|504|gateway)/', 'Courier server is not responding right now. Please try again in a few minutes or choose another courier.'],
            ['/invoice|ewaybill|e-way/', 'Invoice / e-way bill details required for this shipment value. Add them and try again.'],
        ];
        foreach ($map as [$re, $msg]) {
            if (preg_match($re, $r)) {
                return $msg;
            }
        }
        return 'Courier rejected the order.';
    }

    /** Full message shown to the seller: friendly reason + courier's own words. */
    public static function message($response): string
    {
        $raw = self::raw($response);
        $raw = mb_substr(preg_replace('/\s+/', ' ', strip_tags($raw)), 0, 300);
        $friendly = self::friendly($raw);
        return $friendly . ' (Courier said: ' . $raw . ')';
    }

    /** Save the failure on the order and in the failure log. Never throws. */
    public static function record($order, ?string $courierCode, ?string $provider, $response): string
    {
        $msg = self::message($response);
        try {
            if ($order && Schema::hasColumn('orders', 'booking_error')) {
                $order->forceFill(['booking_error' => $msg, 'booking_error_at' => now()])->saveQuietly();
            }
            if (Schema::hasTable('courier_booking_failures')) {
                $raw = is_string($response) ? $response : json_encode($response instanceof \Throwable ? ['exception' => $response->getMessage(), 'line' => $response->getLine(), 'file' => basename($response->getFile())] : $response);
                DB::table('courier_booking_failures')->insert([
                    'seller_id'     => $order->seller_id ?? null,
                    'order_id'      => $order->id ?? null,
                    'courier_code'  => mb_substr((string) $courierCode, 0, 60),
                    'provider_name' => mb_substr((string) $provider, 0, 120),
                    'reason'        => mb_substr($msg, 0, 500),
                    'raw'           => mb_substr((string) $raw, 0, 8000),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('CourierError::record failed: ' . $e->getMessage());
        }
        Log::warning('Courier booking failed', ['order' => $order->id ?? null, 'courier' => $courierCode, 'provider' => $provider, 'reason' => $msg]);
        return $msg;
    }

    /**
     * One-click fixes for a saved booking failure: [[label, url], ...].
     * "Try another courier" is always offered; the rest depend on the reason.
     */
    public static function fixActions(?string $msg, $order): array
    {
        $r = strtolower((string) $msg);
        $id = $order->id ?? null;
        $out = [];
        $link = function (string $label, string $route, $param = null) use (&$out) {
            try {
                $out[] = [$label, $param === null ? route($route) : route($route, $param)];
            } catch (\Throwable $e) {
            }
        };
        if (preg_match('/insufficient|low balance|wallet|recharge/', $r)) {
            $link('Recharge wallet', 'seller.recharge');
        }
        if (preg_match('/warehouse|pickup/', $r)) {
            $link('Fix pickup address', 'seller.warehouse');
        }
        if ($id && preg_match('/address|consignee|phone|mobile|weight|dimension|length|breadth|height|invoice|e-way|ewaybill|order number|duplicate|already|pin ?code|pincode|cod/', $r)) {
            $link('Edit order', 'seller.orderedit', $id);
        }
        if ($id) {
            $link('Try another courier', 'seller.courier', $id);
        }
        return $out;
    }

    public static function clear($order): void
    {
        try {
            if ($order && Schema::hasColumn('orders', 'booking_error') && $order->booking_error) {
                $order->forceFill(['booking_error' => null, 'booking_error_at' => null])->saveQuietly();
            }
        } catch (\Throwable $e) {
        }
    }
}
