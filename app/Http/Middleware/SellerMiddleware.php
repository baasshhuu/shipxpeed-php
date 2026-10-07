<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class SellerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

	public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('seller')->check()) {
            return $next($request);
        }

        return redirect()->route('seller.login')->with('error', 'You must be logged in as a seller.');
    }
}
