<?php

namespace App\Support\ApiV1;

use App\Models\SellerList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

/**
 * One response shape for every v1 endpoint:
 *   success: {"success": true,  "message": "...", "data": {...}}
 *   error:   {"success": false, "message": "...", "error": {"code": "...", "fields": {...}}}
 */
class Respond
{
    public static function ok($data = [], string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status, [], JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $message, int $status = 400, string $code = 'bad_request', array $extra = []): JsonResponse
    {
        $err = ['code' => $code] + $extra;
        return response()->json(['success' => false, 'message' => $message, 'error' => $err], $status, [], JSON_UNESCAPED_SLASHES);
    }

    public static function invalid(Validator $v): JsonResponse
    {
        $errors = $v->errors()->toArray();
        $first = collect($errors)->flatten()->first() ?: 'Validation failed';
        return self::error($first, 422, 'validation_failed', ['fields' => $errors]);
    }

    /** Indian mobile as 10 digits: drops spaces, dashes and a +91 / 91 / 0 prefix only when it is a prefix. */
    public static function phone10($phone): string
    {
        $d = preg_replace('/\D/', '', (string) $phone);
        return strlen($d) > 10 ? substr($d, -10) : $d;
    }

    public static function seller(Request $request): SellerList
    {
        return $request->attributes->get('api_seller');
    }

    /**
     * Run one of the panel's existing API controller actions for this client and return its JSON as an array.
     * Used so v1 books, cancels, tracks and prices through exactly the same code as the panel.
     */
    public static function legacy(Request $request, string $controller, string $method, array $input): array
    {
        $seller = self::seller($request);
        $sub = Request::create('/' . $request->path(), 'POST', $input);
        $sub->headers->set('Authorization', (string) $seller->api_token);
        $sub->headers->set('Accept', 'application/json');
        $sub->attributes->set('api_seller', $seller);
        $sub->setUserResolver(fn () => $seller);
        Auth::guard('seller')->setUser($seller);

        $res = app()->call([app($controller), $method], ['request' => $sub]);

        if ($res instanceof JsonResponse) {
            $data = $res->getData(true);
            $data['_status'] = $res->getStatusCode();
            return $data;
        }
        if ($res instanceof \Symfony\Component\HttpFoundation\Response) {
            return ['success' => false, 'message' => 'Unexpected response', '_status' => $res->getStatusCode(), '_raw' => mb_substr((string) $res->getContent(), 0, 500)];
        }
        return is_array($res) ? $res : ['success' => false, 'message' => 'Unexpected response', '_status' => 500];
    }

    /** Legacy controllers use success / status / responseCode inconsistently. */
    public static function legacyOk(array $r): bool
    {
        if (array_key_exists('success', $r)) {
            return (bool) $r['success'];
        }
        if (array_key_exists('status', $r)) {
            return (bool) $r['status'];
        }
        return ($r['_status'] ?? 500) < 400;
    }

    /** HTTP status to pass on for a failed legacy call. */
    public static function legacyStatus(array $r, int $default = 422): int
    {
        $s = (int) ($r['_status'] ?? 0);
        return $s >= 400 ? $s : $default;
    }
}
