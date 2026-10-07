<?php

namespace App\Http\Middleware;

use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            $guard = Helper::getGuardFromURL($request);

            $redirectTo = null;
            if ($guard    == 'web') {
                $redirectTo     = 'admin';
            }

            if ($redirectTo != null) return route('loginPage', $redirectTo);

            return route('login');
        } else {
            return abort(response()->json([
                'status'    => false,
                'message'   => 'Unauthenticated Access..!!',
                'data'      => []
            ], 401));
        }
    }

    protected function unauthenticated($request, array $guards)
    {
        if ($request->is('api/*')) {
            return abort(response()->json([
                'status'    => false,
                'message'   => 'Unauthenticated Access..!!',
                'data'      => []
            ], 401));
        } else {
            throw new AuthenticationException(
                'Unauthenticated.',
                $guards,
                $this->redirectTo($request)
            );
        }
    }
}
