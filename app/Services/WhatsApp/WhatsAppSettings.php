<?php

namespace App\Services\WhatsApp;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp settings = config/whatsapp.php defaults, overridden by what the
 * admin saves on the WhatsApp settings page (app_settings, key "whatsapp").
 */
class WhatsAppSettings
{
    private const KEY = 'whatsapp';
    private const CACHE = 'whatsapp_settings_v1';

    /** Raw saved overrides from the admin page. */
    public static function saved(): array
    {
        return Cache::remember(self::CACHE, 300, function () {
            try {
                if (!Schema::hasTable('app_settings')) {
                    return [];
                }
                $raw = AppSetting::getValue(self::KEY);
                $data = $raw ? json_decode($raw, true) : [];
                return is_array($data) ? $data : [];
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public static function save(array $data): void
    {
        $merged = array_replace_recursive(self::saved(), $data);
        AppSetting::setValue(self::KEY, json_encode($merged));
        Cache::forget(self::CACHE);
    }

    public static function apiKey(): ?string
    {
        $key = config('whatsapp.api_key') ?: (self::saved()['api_key'] ?? null);
        return filled($key) ? trim($key) : null;
    }

    public static function apiKeySource(): ?string
    {
        if (config('whatsapp.api_key')) {
            return 'env';
        }
        return filled(self::saved()['api_key'] ?? null) ? 'admin' : null;
    }

    /** Master switch for buyer alerts (admin). Sellers still need to turn their own service on. */
    public static function alertsEnabled(): bool
    {
        return (bool) (self::saved()['alerts_enabled'] ?? true) && self::apiKey() !== null;
    }

    /** WhatsApp OTP on signup / OTP login. */
    public static function otpEnabled(): bool
    {
        return (bool) (self::saved()['otp_enabled'] ?? true) && self::apiKey() !== null && filled(self::otpCampaign());
    }

    public static function otpCampaign(): ?string
    {
        return self::saved()['otp_campaign'] ?? config('whatsapp.otp.campaign');
    }

    /** event => ['label','campaign','rate','enabled'] with admin overrides applied. */
    public static function events(): array
    {
        $saved = self::saved()['events'] ?? [];
        $out = [];
        foreach (config('whatsapp.events') as $key => $e) {
            $s = $saved[$key] ?? [];
            $out[$key] = [
                'label'    => $e['label'],
                'campaign' => filled($s['campaign'] ?? null) ? $s['campaign'] : $e['campaign'],
                'rate'     => isset($s['rate']) && is_numeric($s['rate']) ? (float) $s['rate'] : (float) $e['rate'],
                'enabled'  => (bool) ($s['enabled'] ?? true),
            ];
        }
        return $out;
    }

    /** Rate (₹) a seller pays for one message of this event. Per-seller rate (admin) wins over default. */
    public static function rateFor($seller, string $event): float
    {
        $events = self::events();
        $default = $events[$event]['rate'] ?? 0.0;
        $custom = self::sellerRates($seller);
        if (array_key_exists($event, $custom) && is_numeric($custom[$event])) {
            return max(0, (float) $custom[$event]);
        }
        return max(0, (float) $default);
    }

    public static function sellerRates($seller): array
    {
        $raw = $seller->whatsapp_rates ?? null;
        if (!$raw) {
            return [];
        }
        $data = is_array($raw) ? $raw : json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
