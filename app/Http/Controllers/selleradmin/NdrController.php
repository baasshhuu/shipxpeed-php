<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NdrController extends Controller
{
    const API_BASE_URL = 'https://shipment.xpressbees.com/';
    const NDR_ENDPOINT = '/api/ndr';
    const NDR_CREATE_ENDPOINT = '/api/ndr/create';  // New endpoint for creating NDR
    const TOKEN_CACHE_KEY = 'xpressbees_api_token';
    const TOKEN_CACHE_TTL = 3600;
    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where('seller_id', $sellerId)
            ->latest()
            ->get();
        $totalAmount = $seller && $seller->status == 1
            ? Recharge::where('seller_id', $seller->id)->sum('amount')
            : 0;

        $ndrData = [];

        try {
            $client = new Client([
                'base_uri' => self::API_BASE_URL,
                'timeout' => 60,
                'connect_timeout' => 60,
                'verify' => false,
            ]);

            $response = $client->post(self::NDR_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getApiToken(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $ndrData = json_decode($response->getBody(), true);

            if (!isset($ndrData['data']) || !is_array($ndrData['data'])) {
                $ndrData = [];
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch NDR data', ['error' => $e->getMessage()]);
            $ndrData = [];
        }

        return view('sellerdashboard.ndr.index', compact('transactions', 'seller', 'totalAmount', 'ndrData'));
    }

    public function create()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where('seller_id', $sellerId)
            ->latest()
            ->get();
        $totalAmount = $seller && $seller->status == 1
            ? Recharge::where('seller_id', $seller->id)->sum('amount')
            : 0;

        return view('sellerdashboard.ndr.create', compact('transactions', 'seller', 'totalAmount'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'awb' => 'required',
            'action' => 'required',
            're_attempt_date' => 'required|date',
        ]);


        Log::info('Submitting NDR create request', [
            'awb' => $request->awb,
            'action' => $request->action,
            're_attempt_date' => $request->re_attempt_date,
        ]);

        try {

            $client = new Client([
                'base_uri' => self::API_BASE_URL,
                'timeout' => 60,
                'connect_timeout' => 60,
                'verify' => false,
            ]);


            $response = $client->post(self::NDR_CREATE_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getApiToken(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'awb' => $request->awb,
                    'action' => $request->action,
                    'action_data' => [
                        're_attempt_date' => $request->re_attempt_date,
                    ],
                ],
            ]);

            $ndrData = json_decode($response->getBody(), true);

            Log::info('NDR create request response', ['response' => $ndrData]);

            if ($ndrData['awb']['status'] == false) {
                Log::error('API error for AWB', ['response' => $ndrData]);
                return redirect()->back()->with('error', 'Error: ' . $ndrData['awb']['message']);
            }
            return redirect()->route('seller.ndr')->with('success', 'NDR created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create NDR', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to create NDR');
        }
    }



    protected function getApiToken()
    {
        $token = Cache::get(self::TOKEN_CACHE_KEY);
        if (!$token || $this->isTokenExpired($token)) {
            $token = $this->refreshApiToken();
            Cache::put(self::TOKEN_CACHE_KEY, $token, self::TOKEN_CACHE_TTL);
        }
        return $token;
    }

    protected function isTokenExpired($token)
    {
        try {
            $payload = json_decode(base64_decode(explode('.', $token)[1]));
            return $payload->exp < time();
        } catch (\Exception $e) {
            return true;
        }
    }

    protected function refreshApiToken()
    {
        return env('XPRESSBEES_API_TOKEN', 'your-default-hardcoded-token');
    }
}
