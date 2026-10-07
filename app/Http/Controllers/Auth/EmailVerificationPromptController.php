<?php

namespace App\Http\Controllers\Auth;

use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        if (config('constant.email_varified')) {
            $guard  = Helper::getGuardFromURL($request);
            $user   = $request->user($guard);
            $guard  = $guard == 'web' ? 'admin' : $guard;
            return $user->hasVerifiedEmail()  ? redirect()->intended(RouteServiceProvider::HOME) : view('auth.verify-email', compact('guard'));
        } else {
            return redirect()->intended(RouteServiceProvider::HOME);
        }
    }
}
