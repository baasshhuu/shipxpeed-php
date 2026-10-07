<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PickupRequestController extends Controller
{
    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where('seller_id', $sellerId)
            ->latest()
            ->get();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }

        return view('sellerdashboard.pickuprequest.index', compact('transactions', 'seller', 'totalAmount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pickup_time' => 'required',
            'pickup_date' => 'required|date',
            'pickup_location' => 'required|string',
            'expected_package_count' => 'required|integer|min:1',
        ]);

        try {
            $client = new Client([
                'base_uri' => 'https://track.delhivery.com/',
                'timeout' => 60,
                'verify' => false,
            ]);

            $response = $client->post('fm/request/new/', [
                'headers' => [
                    'Authorization' => 'Token 8db602386ed6a872cc18a51b489c32ecb27d0eb4',
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'pickup_time' => $request->pickup_time,
                    'pickup_date' => $request->pickup_date,
                    'pickup_location' => $request->pickup_location,
                    'expected_package_count' => (int) $request->expected_package_count,
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);

            Log::info('Delhivery Pickup API Success', ['response' => $responseBody]);

            return redirect()->back()->with('success', 'Pickup request sent successfully!');
        } catch (RequestException $e) {
            $errorMessage = 'Failed to send pickup request.';

            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody(), true);
                Log::error('Delhivery Pickup API Error Response', ['response' => $errorBody]);
                if (isset($errorBody['prepaid'])) {
                    $errorMessage = $errorBody['prepaid'];
                } elseif (isset($errorBody['message'])) {
                    $errorMessage = $errorBody['message'];
                }
            } else {
                Log::error('Delhivery Pickup API Error', ['error' => $e->getMessage()]);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }
}
