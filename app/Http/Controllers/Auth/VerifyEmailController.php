<?php

namespace App\Http\Controllers\Auth;

use App\Helper\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $guard  = Helper::getGuardFromURL($request);
        $user   = $request->user($guard);

        if (!hash_equals((string) $user->getKey(), (string) $request->route('id'))) {
            return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
        }

        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
    }
}
