<?php

namespace App\Http\Middleware;

use Closure;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public static function redirectTo($route)
    {
        return static::class . ':' . $route;
    }

    public function handle(Request $request, Closure $next, $redirectToRoute = null)
    {
        if (config('constant.email_varified')) {
            $guard  = Helper::getGuardFromURL($request);
            $user   = $request->user($guard);
            if (!$user ||  ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail())) {
                $guard  = $guard == 'web' ? 'admin' : $guard;
                return $request->expectsJson() ? abort(403, 'Your email address is not verified.')  : Redirect::guest(route('verification.notice', $guard));
            }
        }

        return $next($request);
    }
}
