<?php

namespace App\Http\Middleware;

use App\Models\SellerList;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Seller API authentication + white-labelling.
 *
 * - Accepts "Authorization: <token>" or "Authorization: Bearer <token>".
 * - Rejects missing / unknown tokens with a JSON 401, normalises the header to the raw token
 *   (the API controllers look the seller up by it) and exposes the seller as "api_seller".
 * - Partner-network courier IDs are shown to clients as sxc_<id> / sxf_<id> (same convention as
 *   ShipcluesService) and turned back into shipclues_<id> / fship_<id> on the way in - the form
 *   every version of the API controllers accepts.
 *   Partner names are removed from JSON responses (links untouched).
 */
class SellerApiToken
{
    /** internal prefix => public prefix (outgoing) */
    private const ID_PREFIXES = ['shipclues_' => 'sxc_', 'fship_' => 'sxf_'];

    /** public prefix => internal prefix (incoming) */
    private const INCOMING_PREFIXES = ['sxc_' => 'shipclues_', 'sxf_' => 'fship_'];

    private const PARTNER_WORDS = '(shipclues|fship)';

    public function handle(Request $request, Closure $next)
    {
        $token = trim((string) $request->header('Authorization', ''));
        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        if ($token === '') {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token required',
                'responseCode' => 401,
            ], 401);
        }

        $seller = SellerList::where('api_token', $token)->first();
        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
                'responseCode' => 401,
            ], 401);
        }

        $request->headers->set('Authorization', $token);
        $request->attributes->set('api_seller', $seller);

        if (is_string($request->input('courier_id')) && $request->input('courier_id') !== '') {
            $request->merge(['courier_id' => self::toInternalId($request->input('courier_id'))]);
        }

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setData(self::whiteLabel($response->getData(true)));
        }

        return $response;
    }

    public static function toInternalId(string $id): string
    {
        foreach (self::INCOMING_PREFIXES as $public => $internal) {
            if (str_starts_with($id, $public)) {
                return $internal . substr($id, strlen($public));
            }
        }
        return $id;
    }

    public static function whiteLabel($value)
    {
        if (is_array($value)) {
            unset($value['couriername'], $value['provider_code']);
            foreach ($value as $k => $v) {
                $value[$k] = self::whiteLabel($v);
            }
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return $value;
        }

        // courier IDs: shipclues_14 -> sx1_14
        if (preg_match('/^' . self::PARTNER_WORDS . '_(\w+)$/i', $value, $m)) {
            return self::ID_PREFIXES[strtolower($m[1]) . '_'] . $m[2];
        }

        // never touch links
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        if (preg_match('/^\s*' . self::PARTNER_WORDS . '\s*$/i', $value)) {
            return 'Courier partner';
        }

        $out = preg_replace('/\b(via|to|from)\s+' . self::PARTNER_WORDS . '\b|\b' . self::PARTNER_WORDS . '\b/i', ' ', $value, -1, $count);

        return $count ? trim(preg_replace('/\s{2,}/', ' ', $out)) : $value;
    }
}
