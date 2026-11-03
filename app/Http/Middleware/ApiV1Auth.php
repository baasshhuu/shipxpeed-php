<?php

namespace App\Http\Middleware;

use App\Models\SellerList;
use App\Support\ApiV1\Respond;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * Shipxpeed API v1 authentication.
 *
 * The client's secret key is the seller's API key (sxp_...), shown and regenerated in
 * Seller panel > Settings > API. It identifies the client account: every price, courier
 * switch, wallet debit and order belongs to that account.
 *
 * Accepted headers (any one):  X-API-Key: <key>  |  Authorization: Bearer <key>  |  Authorization: <key>
 *
 * Also:
 *  - signs the client into the "seller" guard for this request (the courier services read it)
 *  - 120 requests / minute per key
 *  - white-labels responses (no partner/aggregator names leave the API)
 *  - logs every call to api_v1_logs (best effort)
 */
class ApiV1Auth
{
    public const PER_MINUTE = 120;

    public function handle(Request $request, Closure $next)
    {
        $started = microtime(true);
        $key = self::keyFrom($request);

        if ($key === '') {
            return Respond::error('API key missing. Send it in the X-API-Key header.', 401, 'unauthenticated');
        }

        $seller = SellerList::where('api_token', $key)->first();
        if (!$seller) {
            return Respond::error('Invalid API key.', 401, 'unauthenticated');
        }
        // Keys from the old generator were base64("<seller id>|<timestamp>"): guessable, so not accepted here.
        if (self::isLegacyKey($key)) {
            return Respond::error('This API key is outdated. Generate a new key in Seller panel > API.', 401, 'key_outdated');
        }
        if ((string) $seller->status !== '1') {
            return Respond::error('This account is not active. Contact support@shipxpeed.com.', 403, 'account_inactive');
        }

        $bucket = 'apiv1:' . $seller->id;
        if (RateLimiter::tooManyAttempts($bucket, self::PER_MINUTE)) {
            return Respond::error('Too many requests. Limit is ' . self::PER_MINUTE . ' per minute.', 429, 'rate_limited')
                ->header('Retry-After', (string) RateLimiter::availableIn($bucket));
        }
        RateLimiter::hit($bucket, 60);

        // Legacy controllers read the raw key from Authorization and the seller from these places.
        $request->headers->set('Authorization', $key);
        $request->attributes->set('api_seller', $seller);
        Auth::guard('seller')->setUser($seller);

        // The legacy booking controller has a "continue" inside a switch that PHP reports with a compile
        // warning the first time the file is loaded; load it quietly so that warning can never fail a call.
        @class_exists(\App\Http\Controllers\Shipxpeedapi\ServiceabilityController::class);

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setData(SellerApiToken::whiteLabel($response->getData(true)));
        }

        self::log($request, $response, (int) $seller->id, $started);

        return $response;
    }

    public static function isLegacyKey(string $key): bool
    {
        if (str_starts_with($key, 'sxp_')) {
            return false;
        }
        $decoded = base64_decode($key, true);
        return $decoded !== false && (bool) preg_match('/^\d+\|/', $decoded);
    }

    public static function keyFrom(Request $request): string
    {
        $key = trim((string) $request->header('X-API-Key', ''));
        if ($key === '') {
            $key = trim((string) $request->header('Authorization', ''));
            if (stripos($key, 'Bearer ') === 0) {
                $key = trim(substr($key, 7));
            }
        }
        return $key;
    }

    private static function log(Request $request, $response, int $sellerId, float $started): void
    {
        try {
            if (!Schema::hasTable('api_v1_logs')) {
                return;
            }
            $body = $request->except(['password', 'invoice_file', 'ewaybill_file', 'invoice_file_base64', 'ewaybill_file_base64']);
            DB::table('api_v1_logs')->insert([
                'seller_id'   => $sellerId,
                'method'      => $request->method(),
                'path'        => mb_substr($request->path(), 0, 190),
                'status'      => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'ip'          => $request->ip(),
                'request'     => mb_substr(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 8000),
                'response'    => method_exists($response, 'getContent') ? mb_substr((string) $response->getContent(), 0, 8000) : null,
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('api_v1 log failed: ' . $e->getMessage());
        }
    }
}
