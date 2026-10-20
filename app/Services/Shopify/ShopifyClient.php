<?php

namespace App\Services\Shopify;

use App\Models\ShopifyConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin Admin GraphQL client for one connected store.
 *
 * Supports the three ways a seller can connect:
 *  - oauth               : seller's own Dev Dashboard app, Client ID + Secret, approved on Shopify (permanent offline token) — the default
 *  - client_credentials  : seller's own Dev Dashboard app (Client ID + Secret, token lasts 24h, auto-refreshed)
 *  - legacy_token        : old "Develop apps" custom app token (shpat_...) created before 1 Jan 2026
 */
class ShopifyClient
{
    public function __construct(private ShopifyConnection $conn)
    {
    }

    public static function for(ShopifyConnection $conn): self
    {
        return new self($conn);
    }

    /* ----------------------------------------------------------------- */
    /*  Shop domain helpers                                              */
    /* ----------------------------------------------------------------- */

    /**
     * Turns anything the seller pastes into "store.myshopify.com".
     * Accepts: store, store.myshopify.com, https://store.myshopify.com/admin,
     * https://admin.shopify.com/store/store-handle
     */
    public static function normalizeShop(?string $input): ?string
    {
        $s = strtolower(trim((string) $input));
        if ($s === '') {
            return null;
        }
        if (preg_match('~admin\.shopify\.com/store/([a-z0-9][a-z0-9\-]*)~', $s, $m)) {
            return $m[1] . '.myshopify.com';
        }
        $s = preg_replace('~^https?://~', '', $s);
        $s = explode('/', $s)[0];
        if (preg_match('~^[a-z0-9][a-z0-9\-]*$~', $s)) {
            return $s . '.myshopify.com';
        }
        if (preg_match('~^[a-z0-9][a-z0-9\-]*\.myshopify\.com$~', $s)) {
            return $s;
        }
        return null; // custom domains (www.brand.in) can't be used for the API
    }

    /* ----------------------------------------------------------------- */
    /*  Token exchange                                                    */
    /* ----------------------------------------------------------------- */

    /** Client credentials grant (seller-owned Dev Dashboard app). Returns [token, expiresIn, scope]. */
    public static function exchangeClientCredentials(string $shop, string $clientId, string $clientSecret): array
    {
        $res = Http::asForm()->timeout(20)->post("https://{$shop}/admin/oauth/access_token", [
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (!$res->successful() || !$res->json('access_token')) {
            $err = $res->json('error_description') ?: $res->json('error') ?: $res->body();
            throw new RuntimeException('Shopify refused the Client ID / Secret: ' . mb_strimwidth((string) $err, 0, 300, '…'));
        }

        return [$res->json('access_token'), (int) ($res->json('expires_in') ?: 86399), (string) $res->json('scope')];
    }

    /** Authorization-code grant using the seller's own app credentials. Returns [token, scope]. */
    public static function exchangeOAuthCode(string $shop, string $code, string $clientId, string $clientSecret): array
    {
        $res = Http::asForm()->timeout(20)->post("https://{$shop}/admin/oauth/access_token", [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
        ]);

        if (!$res->successful() || !$res->json('access_token')) {
            throw new RuntimeException('Shopify OAuth token exchange failed: ' . mb_strimwidth($res->body(), 0, 300, '…'));
        }

        return [$res->json('access_token'), (string) $res->json('scope')];
    }

    /** Returns a usable token, refreshing client-credentials tokens shortly before they expire. */
    public function token(bool $forceRefresh = false): string
    {
        $c = $this->conn;

        if ($c->auth_type === 'client_credentials') {
            $expiring = !$c->token_expires_at || $c->token_expires_at->lt(now()->addMinutes(10));
            if ($forceRefresh || $expiring || !$c->getToken()) {
                [$token, $ttl, $scope] = self::exchangeClientCredentials(
                    $c->shopify_shop_domain, $c->client_id, $c->getClientSecret()
                );
                $c->setToken($token);
                $c->token_expires_at = now()->addSeconds($ttl);
                if ($scope) {
                    $c->scopes = $scope;
                }
                $c->save();
            }
        }

        $token = $c->getToken();
        if (!$token) {
            throw new RuntimeException('Store is not authorised. Please reconnect Shopify.');
        }
        return $token;
    }

    /* ----------------------------------------------------------------- */
    /*  GraphQL                                                           */
    /* ----------------------------------------------------------------- */

    /**
     * Runs a query/mutation and returns the "data" array.
     * Retries on throttling and on an expired client-credentials token.
     */
    public function graphql(string $query, array $variables = []): array
    {
        $url = sprintf('https://%s/admin/api/%s/graphql.json', $this->conn->shopify_shop_domain, config('shopify.api_version'));
        $refreshed = false;

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $res = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->token(),
                'Content-Type'           => 'application/json',
            ])->timeout(30)->post($url, ['query' => $query, 'variables' => (object) $variables]);

            if ($res->status() === 401 && $this->conn->auth_type === 'client_credentials' && !$refreshed) {
                $this->token(true);
                $refreshed = true;
                continue;
            }
            if ($res->status() === 401 || $res->status() === 403) {
                throw new RuntimeException('Shopify rejected the access token (HTTP ' . $res->status() . '). Reconnect the store or check the app scopes.');
            }
            if ($res->status() === 402) {
                throw new RuntimeException('Shopify store is frozen / unpaid (HTTP 402).');
            }
            if ($res->status() === 404) {
                throw new RuntimeException('Shop not found. Check the myshopify.com domain.');
            }
            if ($res->status() === 429 || $res->serverError()) {
                sleep(min(2 * $attempt, 6));
                continue;
            }

            $body = $res->json() ?? [];
            $errors = $body['errors'] ?? [];

            if ($errors) {
                $throttled = collect($errors)->contains(fn ($e) => ($e['extensions']['code'] ?? '') === 'THROTTLED');
                if ($throttled && $attempt < 4) {
                    sleep(2 * $attempt);
                    continue;
                }
                $msg = is_array($errors) ? collect($errors)->pluck('message')->filter()->implode('; ') : (string) $errors;
                throw new ShopifyGraphqlException($msg ?: 'Unknown Shopify GraphQL error', $errors, $body['data'] ?? null);
            }

            return $body['data'] ?? [];
        }

        throw new RuntimeException('Shopify API is busy, try again in a minute.');
    }

    /** Throws on userErrors returned by a mutation payload. */
    public static function assertNoUserErrors(?array $payload, string $what): void
    {
        $errs = $payload['userErrors'] ?? [];
        if ($errs) {
            $msg = collect($errs)->map(fn ($e) => trim(implode('.', (array) ($e['field'] ?? [])) . ' ' . ($e['message'] ?? '')))->implode('; ');
            throw new RuntimeException("{$what}: {$msg}");
        }
    }

    /** Shopify's OAuth / webhook HMAC check, signed with the seller app's Client Secret. */
    public static function validQueryHmac(array $query, string $secret): bool
    {
        $hmac = $query['hmac'] ?? '';
        unset($query['hmac'], $query['signature']);
        ksort($query);
        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = $k . '=' . (is_array($v) ? '["' . implode('", "', $v) . '"]' : $v);
        }
        $msg = implode('&', $pairs);
        return $hmac !== '' && hash_equals(hash_hmac('sha256', $msg, $secret), $hmac);
    }

    public static function validWebhookHmac(string $rawBody, ?string $header, string $secret): bool
    {
        if (!$header) {
            return false;
        }
        return hash_equals(base64_encode(hash_hmac('sha256', $rawBody, $secret, true)), $header);
    }

    public function shopInfo(): array
    {
        $d = $this->graphql('{ shop { name myshopifyDomain email currencyCode } }');
        return $d['shop'] ?? [];
    }

    public static function log(?int $connId, ?int $orderId, string $direction, string $action, bool $ok, ?string $message = null): void
    {
        try {
            \DB::table('shopify_sync_logs')->insert([
                'connection_id' => $connId,
                'order_id'      => $orderId,
                'direction'     => $direction,
                'action'        => $action,
                'success'       => $ok,
                'message'       => $message ? mb_strimwidth($message, 0, 2000, '…') : null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('shopify_sync_logs insert failed: ' . $e->getMessage());
        }
    }
}
