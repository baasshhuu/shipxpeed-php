<?php

namespace App\Http\Controllers\Shipxpeedapi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\SellerList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

public function login(Request $request)
{
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422);
    }

    $seller = SellerList::where('email', $request->email)->first();

    if (!$seller || !Hash::check($request->password, $seller->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    // Reuse the seller's current token so every integration of the same account keeps working.
    // Issue a new random one only when there is none yet, or the old guessable format
    // (base64 of "<seller id>|<timestamp>") is still in use. Rotate from the panel: Settings > API.
    $token = (string) $seller->api_token;
    $decoded = base64_decode($token, true);
    if ($token === '' || ($decoded !== false && preg_match('/^\d+\|/', $decoded))) {
        $token = self::newToken();
        $seller->api_token = $token;
        $seller->save();
    }

    return response()->json([
        'success' => true,
        'token' => $token,
        'token_type' => 'Bearer',
    ]);
}

public static function newToken(): string
{
    do {
        // 32 chars: same length as the old tokens, so it fits the existing api_token column
        $token = 'sxp_' . \Illuminate\Support\Str::random(28);
    } while (SellerList::where('api_token', $token)->exists());

    return $token;
}




}