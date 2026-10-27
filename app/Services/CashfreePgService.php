<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cashfree Payment Gateway (PG) client — used for seller wallet recharge.
 *
 * Separate from the Cashfree Secure ID (KYC) keys: PG has its own App ID / Secret,
 * found in Cashfree Merchant Dashboard → Payment Gateway → Developers → API Keys.
 *
 * .env:
 *   CASHFREE_PG_APP_ID=
 *   CASHFREE_PG_SECRET_KEY=
 *   CASHFREE_PG_ENV=sandbox        # or production
 */
class CashfreePgService
{
    public const API_VERSION = '2023-08-01';

    public function isProduction(): bool
    {
        return config('cashfree.pg.environment') === 'production';
    }

    public function mode(): string
    {
        return $this->isProduction() ? 'production' : 'sandbox';
    }

    public function isConfigured(): bool
    {
        return filled(config('cashfree.pg.app_id')) && filled(config('cashfree.pg.secret_key'));
    }

    protected function baseUrl(): string
    {
        return $this->isProduction()
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    protected function client()
    {
        return Http::withHeaders([
            'x-client-id'     => config('cashfree.pg.app_id'),
            'x-client-secret' => config('cashfree.pg.secret_key'),
            'x-api-version'   => self::API_VERSION,
            'Content-Type'    => 'application/json',
            'Accept'          => 'application/json',
        ])->timeout(20);
    }

    /**
     * Create an order and return Cashfree's response (contains payment_session_id).
     *
     * @throws \RuntimeException
     */
    public function createOrder(array $payload): array
    {
        $res = $this->client()->post($this->baseUrl() . '/orders', $payload);

        if (!$res->successful() || empty($res->json('payment_session_id'))) {
            Log::error('Cashfree PG create order failed', [
                'order_id' => $payload['order_id'] ?? null,
                'status'   => $res->status(),
                'body'     => $res->json() ?? $res->body(),
            ]);
            throw new \RuntimeException($res->json('message') ?: 'Could not start payment with Cashfree.');
        }

        return $res->json();
    }

    /**
     * Create a Payment Link (paid on Cashfree's own page — no domain whitelisting needed).
     *
     * @throws \RuntimeException
     */
    public function createLink(array $payload): array
    {
        $res = $this->client()->post($this->baseUrl() . '/links', $payload);

        if (!$res->successful() || empty($res->json('link_url'))) {
            Log::error('Cashfree PG create link failed', [
                'link_id' => $payload['link_id'] ?? null,
                'status'  => $res->status(),
                'body'    => $res->json() ?? $res->body(),
            ]);
            throw new \RuntimeException($res->json('message') ?: 'Could not start payment with Cashfree.');
        }

        return $res->json();
    }

    /** Fetch a payment link (link_status: ACTIVE | PAID | EXPIRED | CANCELLED, link_amount_paid). */
    public function getLink(string $linkId): ?array
    {
        $res = $this->client()->get($this->baseUrl() . '/links/' . rawurlencode($linkId));

        if (!$res->successful()) {
            Log::warning('Cashfree PG get link failed', ['link_id' => $linkId, 'status' => $res->status(), 'body' => $res->body()]);
            return null;
        }

        return $res->json();
    }

    /** Orders created under a payment link (to record the paid order / payment id). */
    public function getLinkOrders(string $linkId): array
    {
        $res = $this->client()->get($this->baseUrl() . '/links/' . rawurlencode($linkId) . '/orders');

        return $res->successful() && is_array($res->json()) ? $res->json() : [];
    }

    /** Fetch an order from Cashfree (source of truth for order_status). */
    public function getOrder(string $orderId): ?array
    {
        $res = $this->client()->get($this->baseUrl() . '/orders/' . rawurlencode($orderId));

        if (!$res->successful()) {
            Log::warning('Cashfree PG get order failed', ['order_id' => $orderId, 'status' => $res->status(), 'body' => $res->body()]);
            return null;
        }

        return $res->json();
    }

    /** Payments made against an order (to record the cf_payment_id). */
    public function getPayments(string $orderId): array
    {
        $res = $this->client()->get($this->baseUrl() . '/orders/' . rawurlencode($orderId) . '/payments');

        return $res->successful() && is_array($res->json()) ? $res->json() : [];
    }

    /**
     * Verify a webhook: signature = base64(HMAC-SHA256(timestamp . rawBody, secret_key)).
     */
    public function verifyWebhook(?string $signature, ?string $timestamp, string $rawBody): bool
    {
        if (!$signature || !$timestamp || !$this->isConfigured()) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, config('cashfree.pg.secret_key'), true));

        return hash_equals($expected, $signature);
    }
}
