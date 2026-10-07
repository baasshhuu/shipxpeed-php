<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class Manifestiation extends Controller
{
    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        $transactions = Recharge::where('seller_id', $sellerId)->latest()->get();
        $totalAmount = $seller && $seller->status == 1
            ? Recharge::where('seller_id', $seller->id)->sum('amount')
            : 0;
        return view('sellerdashboard.manifestation.create', compact('transactions', 'seller', 'totalAmount'));
    }

    public function add()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where('seller_id', $sellerId)->latest()->get();
        $totalAmount = $seller && $seller->status == 1
            ? Recharge::where('seller_id', $seller->id)->sum('amount')
            : 0;

        return view('sellerdashboard.warehouse.create', compact('transactions', 'seller', 'totalAmount'));
    }

public function createManifest(Request $request)
{
    $request->validate([
        'pickup_location' => 'required|array',
        'shipments' => 'required|array|min:1'
    ]);

    try {
        $client = new \GuzzleHttp\Client();

        $payload = [
            'pickup_location' => $request->pickup_location,
            'shipments' => $request->shipments
        ];

        $response = $client->post('https://track.delhivery.com/api/cmu/create.json', [
            'headers' => [
                'Authorization' => 'Token 8db602386ed6a872cc18a51b489c32ecb27d0eb4',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'format' => 'json',
                'data' => json_encode($payload)
            ],
            'verify' => false
        ]);

        $responseBody = $response->getBody()->getContents();
        $data = json_decode($responseBody, true);

        return redirect()->back()->with([
            'api_response' => $data,
            'success' => 'Manifest created successfully.'
        ]);

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        $error = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
        return redirect()->back()->with([
            'error' => 'Manifest creation failed.',
            'api_error' => $error
        ]);
    }
}

}
