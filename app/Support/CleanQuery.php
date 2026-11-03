<?php

namespace App\Support;

/**
 * The page's own URL filters only. SettingMiddleware / PermissionMiddleware merge site settings and
 * permissions into the request input, which for GET requests is the query bag — so request()->query()
 * and paginator->withQueryString() would copy them into links. Use this instead.
 */
class CleanQuery
{
    public const DROP = ['site_settings', 'permission', 'page', '_token'];

    public static function get(array $extraDrop = []): array
    {
        $out = [];
        foreach (request()->query() as $k => $v) {
            if (in_array($k, self::DROP, true) || in_array($k, $extraDrop, true)) {
                continue;
            }
            if (is_scalar($v) || (is_array($v) && $v === array_filter($v, 'is_scalar') && count($v) <= 500)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
