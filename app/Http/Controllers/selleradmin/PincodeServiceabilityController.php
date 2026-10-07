<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PincodeServiceabilityController extends Controller
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
        return view('sellerdashboard.pincodeservce.find', compact('transactions', 'seller', 'totalAmount'));
    }
    public function pincodeservice(Request $request)
    {
        $request->validate([
            'pincode' => 'required'
        ]);
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where('seller_id', $sellerId)
            ->latest()
            ->get();
        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        try {
            $client = new Client();
            $response = $client->get("https://track.delhivery.com/c/api/pin-codes/json", [
                'query' => [
                    'token' => '8db602386ed6a872cc18a51b489c32ecb27d0eb4',
                    'filter_codes' => $request->pincode,
                ],
                'verify' => false,
            ]);

            $data = json_decode($response->getBody(), true);
            $deliveryCodes = $data['delivery_codes'] ?? [];

            return view('sellerdashboard.pincodeservce.find', compact('deliveryCodes','transactions', 'seller', 'totalAmount'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to fetch serviceability info.');
        }
    }
}
