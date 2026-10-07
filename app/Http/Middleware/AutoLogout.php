<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoLogout
{
    protected $timeout = 60; // 2 minutes

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {

            $lastActivity = session('lastActivityTime');

            if ($lastActivity && now()->diffInSeconds($lastActivity) > $this->timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('home')
                    ->with('status', 'Session expired due to inactivity.');
            }

            session(['lastActivityTime' => now()]);
        }

        return $next($request);
    }
}
