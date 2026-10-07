<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('web')->user()->load('permission');
        $data = @$user->permission->collect();

        $request->merge(['permission' => $data]);
        return $next($request);
    }
}
