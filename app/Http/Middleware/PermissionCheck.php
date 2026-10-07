<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionCheck
{
    public function handle(Request $request, Closure $next, int $module = 0, string $type = '')
    {
        if (auth('web')->check()) {
            try {

                $permission = $request->permission;

                if (!$permission)              return self::errorNotFound($request);
                if (!$permission->count())     return self::errorNotFound($request);

                $module_permission = $permission->firstWhere('module_id', $module);
                if (!$module_permission)                return self::errorNotFound($request);

                if ($module_permission->allow_all == 1) return $next($request);
                if ($module_permission[$type] == 1)     return $next($request);

                return self::errorNotFound($request);
            } catch (\Throwable $th) {
                return self::errorNotFound($request);
            }
        } else {
            return $next($request);
        }
    }

    protected static function errorNotFound(Request $request)
    {
        if ($request->ajax()) {
            return response()->json([
                'status'    => false,
                'message'   => 'Route not found'
            ], 404);
        }
        return abort(404);
    }
}
