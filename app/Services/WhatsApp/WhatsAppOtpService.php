<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * WhatsApp OTP: one-time codes for signup (verify mobile) and OTP login.
 * Codes are stored hashed, expire, are rate-limited per number and burn after too many wrong tries.
 */
class WhatsAppOtpService
{
    public function __construct(private AiSensyClient $client)
    {
    }

    /**
     * @return array{ok:bool, message:string, retry_after?:int}
     */
    public function send(string $purpose, string $phone, string $name = 'Customer', ?string $ip = null): array
    {
        if (!WhatsAppSettings::otpEnabled()) {
            return ['ok' => false, 'message' => 'WhatsApp OTP is not available right now.'];
        }
        $cfg = config('whatsapp.otp');

        $last = DB::table('whatsapp_otps')->where('phone', $phone)->orderByDesc('id')->first();
        if ($last) {
            $wait = (int) ceil($cfg['resend_seconds'] - abs(now()->diffInSeconds(Carbon::parse($last->created_at))));
            if ($wait > 0) {
                return ['ok' => false, 'message' => "Please wait {$wait}s before requesting another code.", 'retry_after' => (int) $wait];
            }
        }
        $hourCount = DB::table('whatsapp_otps')->where('phone', $phone)->where('created_at', '>=', now()->subHour())->count();
        if ($hourCount >= $cfg['max_per_hour']) {
            return ['ok' => false, 'message' => 'Too many codes requested for this number. Try again in an hour.', 'retry_after' => 3600];
        }

        $code = str_pad((string) random_int(0, 10 ** $cfg['length'] - 1), $cfg['length'], '0', STR_PAD_LEFT);

        // Only the newest code for this number+purpose stays usable
        DB::table('whatsapp_otps')->where('phone', $phone)->where('purpose', $purpose)->whereNull('consumed_at')
            ->update(['consumed_at' => now(), 'updated_at' => now()]);

        $id = DB::table('whatsapp_otps')->insertGetId([
            'purpose'    => $purpose,
            'phone'      => $phone,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes($cfg['ttl_minutes']),
            'ip'         => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $extra = [];
        if ((bool) (WhatsAppSettings::saved()['otp_copy_button'] ?? true)) {
            // Authentication templates carry a "Copy code" button that needs the code as its URL parameter
            $extra['buttons'] = [[
                'type' => 'button', 'sub_type' => 'url', 'index' => 0,
                'parameters' => [['type' => 'text', 'text' => $code]],
            ]];
        }

        $res = $this->client->send(WhatsAppSettings::otpCampaign(), $phone, $name, [$code], $extra);
        DB::table('whatsapp_otps')->where('id', $id)->update(['send_status' => $res['ok'] ? 'sent' : 'failed', 'updated_at' => now()]);

        if (!$res['ok']) {
            DB::table('whatsapp_otps')->where('id', $id)->update(['consumed_at' => now()]);
            return ['ok' => false, 'message' => 'Could not send the WhatsApp code. Check the number is on WhatsApp and try again.'];
        }

        return ['ok' => true, 'message' => 'Code sent on WhatsApp to ' . AiSensyClient::mask($phone) . '.', 'retry_after' => $cfg['resend_seconds']];
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function verify(string $purpose, string $phone, string $code): array
    {
        $row = DB::table('whatsapp_otps')
            ->where('phone', $phone)->where('purpose', $purpose)
            ->whereNull('consumed_at')->where('send_status', 'sent')
            ->orderByDesc('id')->first();

        if (!$row) {
            return ['ok' => false, 'message' => 'No active code for this number. Request a new one.'];
        }
        if (now()->greaterThan(Carbon::parse($row->expires_at))) {
            DB::table('whatsapp_otps')->where('id', $row->id)->update(['consumed_at' => now(), 'updated_at' => now()]);
            return ['ok' => false, 'message' => 'This code has expired. Request a new one.'];
        }
        if (!preg_match('/^\d{4,8}$/', $code) || !Hash::check($code, $row->code_hash)) {
            $attempts = $row->attempts + 1;
            $burn = $attempts >= config('whatsapp.otp.max_attempts');
            DB::table('whatsapp_otps')->where('id', $row->id)->update([
                'attempts' => $attempts, 'consumed_at' => $burn ? now() : null, 'updated_at' => now(),
            ]);
            return ['ok' => false, 'message' => $burn ? 'Too many wrong tries. Request a new code.' : 'Wrong WhatsApp code. Please check and try again.'];
        }

        DB::table('whatsapp_otps')->where('id', $row->id)->update(['consumed_at' => now(), 'updated_at' => now()]);
        return ['ok' => true, 'message' => 'Verified'];
    }
}
