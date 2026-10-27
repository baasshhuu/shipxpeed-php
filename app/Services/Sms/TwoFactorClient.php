<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 2Factor.in SMS OTP delivery. We send our own code
 * (GET /API/V1/{key}/SMS/{phone}/{otp}[/{template}]) and verify it ourselves.
 */
class TwoFactorClient
{
    public static function configured(): bool
    {
        return filled(config('sms.twofactor.api_key'));
    }

    /**
     * @param string $phone +91XXXXXXXXXX
     * @return array{ok:bool, session?:string, error?:string}
     */
    public function sendOtp(string $phone, string $code): array
    {
        $cfg = config('sms.twofactor');
        if (empty($cfg['api_key'])) {
            return ['ok' => false, 'error' => 'SMS key not set'];
        }

        $to = preg_replace('/\D/', '', $phone); // 91XXXXXXXXXX
        $url = $cfg['api_url'] . '/' . rawurlencode(trim($cfg['api_key'])) . '/SMS/' . $to . '/' . rawurlencode($code);
        if (filled($cfg['template'])) {
            $url .= '/' . rawurlencode(trim($cfg['template']));
        }

        try {
            $res = Http::timeout($cfg['timeout'] ?? 12)->acceptJson()->get($url);
            $body = $res->json() ?? [];
            if ($res->successful() && ($body['Status'] ?? '') === 'Success') {
                return ['ok' => true, 'session' => (string) ($body['Details'] ?? '')];
            }
            $err = (string) ($body['Details'] ?? ('HTTP ' . $res->status()));
            Log::warning('2Factor SMS OTP failed', ['to' => substr($to, 0, 4) . '••••' . substr($to, -3), 'error' => $err]);
            return ['ok' => false, 'error' => $err];
        } catch (\Throwable $e) {
            Log::warning('2Factor SMS OTP error', ['error' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
