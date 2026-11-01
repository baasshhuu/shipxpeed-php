<?php

namespace App\Http\Controllers\Xpeed;

use App\Http\Controllers\Controller;
use App\Support\Xpeed\EmailLoginOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Seller login: "Email OTP" tab on the login page. */
class EmailOtpLoginController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email|max:190'], ['email.*' => 'Enter a valid e-mail address.']);
        $res = EmailLoginOtp::send($request->email);
        return response()->json(['message' => $res['message'], 'retry_after' => $res['retry_after'] ?? null], $res['ok'] ? 200 : 429);
    }

    public function verify(Request $request)
    {
        $request->validate(['email' => 'required|email|max:190', 'otp' => 'required|string|max:8']);
        $res = EmailLoginOtp::verify($request->email, trim((string) $request->otp));
        if (!$res['ok']) {
            return response()->json(['errors' => ['otp' => [$res['message']]]], 422);
        }
        Auth::guard('seller')->login($res['seller']);
        $request->session()->regenerate();
        return response()->json(['redirect' => route('seller.dashboard')]);
    }
}
