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
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $seller = SellerList::where('email', $request->email)->first();

    if (!$seller || !Hash::check($request->password, $seller->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    // ✅ Token generate karo
    $token = base64_encode($seller->id . '|' . now());

    // ✅ Token save kar do
    $seller->api_token = $token;
    $seller->save();

    return response()->json([
        'success' => true,
        'token' => $token,
    ]);
}




}