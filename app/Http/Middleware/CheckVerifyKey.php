<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class CheckVerifyKey
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
        $secret_token   = config('constant.secret_token');
        $token = $request->header('x-api-key');
        if ($token != $secret_token) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Secret Token!!',
                'data' => []
            ], 401);
        }

        return $next($request);
    }
}
