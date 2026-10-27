<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for AiSensy "API Campaign" sends.
 * POST {api_url}  { apiKey, campaignName, destination, userName, templateParams[], buttons[]? }
 */
class AiSensyClient
{
    /**
     * @return array{ok:bool, http:?int, body:mixed, error:?string}
     */
    public function send(string $campaign, string $phone, string $userName, array $params = [], array $extra = []): array
    {
        $key = WhatsAppSettings::apiKey();
        if (!$key) {
            return ['ok' => false, 'http' => null, 'body' => null, 'error' => 'AiSensy API key not set'];
        }

        $payload = array_merge([
            'apiKey'         => $key,
            'campaignName'   => $campaign,
            'destination'    => $phone,
            'userName'       => mb_substr($userName !== '' ? $userName : 'Customer', 0, 60),
            'source'         => 'shipxpeed-panel',
            'templateParams' => array_values(array_map(fn ($p) => self::param($p), $params)),
        ], $extra);

        try {
            $res = Http::timeout(config('whatsapp.timeout', 12))
                ->acceptJson()
                ->asJson()
                ->post(config('whatsapp.api_url'), $payload);

            $body = $res->json() ?? $res->body();
            $ok = $res->successful()
                && !(is_array($body) && (($body['success'] ?? null) === false || ($body['success'] ?? null) === 'false' || isset($body['errorMessage'])));

            $error = null;
            if (!$ok) {
                $error = is_array($body)
                    ? (string) ($body['errorMessage'] ?? $body['message'] ?? $body['error'] ?? json_encode($body))
                    : mb_strimwidth((string) $body, 0, 300, '…');
                Log::warning('AiSensy send failed', ['campaign' => $campaign, 'to' => self::mask($phone), 'http' => $res->status(), 'error' => $error]);
            }

            return ['ok' => $ok, 'http' => $res->status(), 'body' => $body, 'error' => $error];
        } catch (\Throwable $e) {
            Log::warning('AiSensy send exception', ['campaign' => $campaign, 'to' => self::mask($phone), 'error' => $e->getMessage()]);
            return ['ok' => false, 'http' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }

    /** Template params can't be empty or contain new lines / 4+ spaces (Meta rule). */
    private static function param($v): string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $v));
        return $v === '' ? '-' : mb_substr($v, 0, 200);
    }

    /**
     * Normalise an Indian mobile to +91XXXXXXXXXX. Returns null when it isn't a valid mobile.
     */
    public static function normalizePhone($raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (strlen($d) === 12 && str_starts_with($d, '91')) {
            $d = substr($d, 2);
        } elseif (strlen($d) === 11 && str_starts_with($d, '0')) {
            $d = substr($d, 1);
        }
        return preg_match('/^[6-9]\d{9}$/', $d) ? '+91' . $d : null;
    }

    public static function mask(?string $phone): string
    {
        $phone = (string) $phone;
        return strlen($phone) > 6 ? substr($phone, 0, 5) . str_repeat('•', max(0, strlen($phone) - 8)) . substr($phone, -3) : $phone;
    }
}
