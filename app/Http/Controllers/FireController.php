<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FireController extends Controller
{

    public function index(Request $request)
    {
    }

    public function storeToken(Request $request)
    {
        return response()->json([
            'status'    => true,
            'message'   => "Success",
            'data'      => ['token'     => $request->input('token')]
        ]);
    }

    public function sendWebNotification(Request $request)
    {
        return response()->json([
            'success' => true
        ]);
    }





    public function updateToken(Request $request)
    {
        try {
            $request->user()->update(['fcm_token' => $request->token]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}
