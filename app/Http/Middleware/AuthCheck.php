<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {

            if ($request->session()->has('locked') && request()->is('*lock') == false && request()->is('*logout') == false) {
                $redirectTo = null;
                if (auth('web')->check()) {
                    $redirectTo = '/lock';
                }

                if ($redirectTo != null) {
                    return redirect($redirectTo);
                }
            }
            return $next($request);
        }

        abort(401);
    }
}
