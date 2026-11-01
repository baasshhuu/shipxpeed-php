<?php

namespace App\Support\Xpeed;

use App\Models\SellerList;
use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Seller login with a 6-digit code sent to the registered e-mail.
 * Codes are stored hashed in the cache (no table needed), valid 10 minutes,
 * 5 wrong tries per code, one new code every 45 s and at most 6 codes an hour per e-mail.
 */
class EmailLoginOtp
{
    public const TTL_MIN = 10;
    public const COOLDOWN_S = 45;
    public const MAX_TRIES = 5;
    public const MAX_PER_HOUR = 6;

    /** Always the file store, so codes survive between requests even if the app cache is "array". */
    private static function cache()
    {
        return CacheFacade::store('file');
    }

    private static function key(string $email, string $what): string
    {
        return 'xp_email_otp_' . $what . '_' . sha1(strtolower(trim($email)));
    }

    public static function seller(string $email): ?SellerList
    {
        return SellerList::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->orderBy('id')->first();
    }

    /** @return array{ok:bool, message:string, retry_after?:int} */
    public static function send(string $email): array
    {
        $email = strtolower(trim($email));
        $generic = 'If this e-mail is registered with Shipxpeed, a 6-digit code is on its way. Check your inbox and spam folder.';

        $wait = (int) self::cache()->get(self::key($email, 'cool'), 0) - time();
        if ($wait > 0) {
            return ['ok' => false, 'message' => "Please wait {$wait} seconds before asking for a new code.", 'retry_after' => $wait];
        }
        $sent = (int) self::cache()->get(self::key($email, 'hour'), 0);
        if ($sent >= self::MAX_PER_HOUR) {
            return ['ok' => false, 'message' => 'Too many codes requested for this e-mail. Please try again in an hour or log in with your password.'];
        }

        self::cache()->put(self::key($email, 'cool'), time() + self::COOLDOWN_S, self::COOLDOWN_S);
        $seller = self::seller($email);
        if (!$seller) {
            // same answer as for a real account, so the form can't be used to discover who is registered
            return ['ok' => true, 'message' => $generic, 'retry_after' => self::COOLDOWN_S];
        }

        $code = (string) random_int(100000, 999999);
        self::cache()->put(self::key($email, 'code'), ['hash' => Hash::make($code), 'tries' => 0, 'seller_id' => $seller->id], now()->addMinutes(self::TTL_MIN));
        self::cache()->put(self::key($email, 'hour'), $sent + 1, now()->addHour());

        try {
            $name = trim((string) $seller->name) ?: 'there';
            Mail::send('email.xp-login-otp', ['code' => $code, 'name' => $name, 'minutes' => self::TTL_MIN],
                fn ($m) => $m->to($seller->email)->subject($code . ' is your Shipxpeed login code'));
        } catch (\Throwable $e) {
            Log::error('Email login OTP could not be sent', ['seller' => $seller->id, 'error' => $e->getMessage()]);
            self::cache()->forget(self::key($email, 'code'));
            self::cache()->forget(self::key($email, 'cool'));
            return ['ok' => false, 'message' => 'We could not send the e-mail right now. Please try again in a minute or log in with your password.'];
        }

        return ['ok' => true, 'message' => $generic, 'retry_after' => self::COOLDOWN_S];
    }

    /** @return array{ok:bool, message:string, seller?:SellerList} */
    public static function verify(string $email, string $code): array
    {
        $email = strtolower(trim($email));
        $row = self::cache()->get(self::key($email, 'code'));
        if (!$row) {
            return ['ok' => false, 'message' => 'This code has expired. Ask for a new one.'];
        }
        if ($row['tries'] >= self::MAX_TRIES) {
            self::cache()->forget(self::key($email, 'code'));
            return ['ok' => false, 'message' => 'Too many wrong tries. Ask for a new code.'];
        }
        if (!preg_match('/^\d{6}$/', $code) || !Hash::check($code, $row['hash'])) {
            $row['tries']++;
            self::cache()->put(self::key($email, 'code'), $row, now()->addMinutes(self::TTL_MIN));
            $left = self::MAX_TRIES - $row['tries'];
            return ['ok' => false, 'message' => $left > 0 ? "Wrong code. {$left} tries left." : 'Too many wrong tries. Ask for a new code.'];
        }
        self::cache()->forget(self::key($email, 'code'));
        $seller = SellerList::find($row['seller_id']);
        if (!$seller) {
            return ['ok' => false, 'message' => 'Account not found.'];
        }
        return ['ok' => true, 'message' => 'Verified', 'seller' => $seller];
    }
}
