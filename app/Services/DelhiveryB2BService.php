<?php
// app/Services/DelhiveryB2BService.php

namespace App\Services;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;


class DelhiveryB2BService implements CourierServiceInterface
{
    /**
     * Check serviceability via Delhivery B2B Pincode API.
     *
     * @param  array  $params  // origin, destination, etc.
     * @return array[]         // normalized serviceability entries
     */

     const API_BASE_URL = 'https://ltl-clients-api.delhivery.com';
    const API_ENDPOINT = '/manifest';
    const TOKEN_CACHE_KEY = 'xpressbees_api_token';
    const TOKEN_CACHE_TTL = 3600;


    protected function fetchAuthToken(): string
    {
        $cfg      = "https://ltl-clients-api.delhivery.com/ums/login";
        $cacheKey = 'xpressbees.token';

        //return Cache::remember($cacheKey, $cfg['token_ttl'], function() use ($cfg) {
            $resp = Http::post($cfg, [
                'username' => "SHIPXPEED3645B2B",
                'password' => "Welcome@123",
            ]);

            $json = $resp->json();
            //  dd($json);
            if (! $resp->successful() || empty($json['data']['jwt'])) {
                throw new \RuntimeException("XpressBees login failed: {$resp->body()}");
            }

            return $json['data']['jwt'];
        //});
    }


    public function getServiceability(array $params): array
    {
     $token = $this->fetchAuthToken();
            //    dd($token);
        // return $params;
        // dd($params);
        $cfg = config('courier_services.delhivery_b2b');
        //   dd($cfg);
        // Construct endpoint URL: /pincode-service/{pincode}
        $url = rtrim($cfg['base_url'], '/') . '/' . $params['destination'];

        // Send GET request with token auth and pickup pincode as query
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->get($url);

        if (! $resp->successful()) {
            return [];
        }
        $json = $resp->json();
        // dd($json);

        if (empty($json['success']) || ! $json['success'] || empty($json['data'])) {
            Log::info('DelhiveryB2BService not serviceable or no data', $json);
            return [];
        }

        $data = $json['data'];
        //dd($data);
        // Respect failed-demand pincodes
        $failList = array_map('strval', $data['b2b_fail_on_demand_pincodes'] ?? []);
        if (in_array((string)$params['destination'], $failList, true)) {
            Log::info('DelhiveryB2BService destination in fail list', ['destination' => $params['destination']]);
            return [];
        }

    $entries = $data['pincode_serviceability_data'] ?? [];
// dd($entries);
 $order = Order::findOrFail($params['order_id']);

$source_pin = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
$consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;

$payment_mode = $order->payment_type;


$length_cm = $order->package_length;
$width_cm = $order->package_breadth;
$height_cm = $order->package_height;
$box_count = 1;
$weight_g = $order->package_weight;
$source_pin = $source_pin['pincode'];
$consignee_pin = $consignee['pincode'];
$inv_amount = $order->order_amount;

$url = 'https://ltl-clients-api.delhivery.com/freight/estimate';
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Accept'        => 'application/json',
])->post($url, [
    "dimensions" => [
        [
            "length_cm" => $length_cm,
            "width_cm" => $width_cm,
            "height_cm" => $height_cm,
            "box_count" => $box_count
        ]
    ],
    "weight_g" => $weight_g,
    "source_pin" => $source_pin,
    "consignee_pin" => $consignee_pin,
    "payment_mode" => $payment_mode,
    "inv_amount" => $inv_amount,
    "cod_amount" => $inv_amount,

    "freight_mode" => "fod"
]);

$shipingcharge = $response->json();
//dd($shipingcharge);


        if (! $resp->successful()) {
            return [];
        }
        $json = $resp->json();
        return collect($entries)
            ->map(fn($item) => [
                'serviceabilityId' => $item['center_code'] ?? null,
                'courierName'      => 'Delhivery B2B',
                'courierCharge'    => $shipingcharge['data']['price_breakup']['base_freight_charge'],
                'freightCharges'   => $shipingcharge['data']['price_breakup']['base_freight_charge'],
                'codCharge'        => 0.0,
                'minWeight'        => $item['wt'] ?? $params['weight'],
                'volWeight'        => $item['wt'] ?? $params['weight'],
            ])
            ->toArray();
    }

 






public function assignOrder($params)
{
//    dd($params);
     $token = $this->fetchAuthToken();

   
    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return redirect()->back()->with('error', 'Unauthorized or inactive seller.');
    }

   
    $client = new \GuzzleHttp\Client([
        'base_uri' => self::API_BASE_URL,
        'timeout' => 60,
        'connect_timeout' => 60,
        'verify' => false,
    ]);

    $requestData = $params;
    // dd($requestData);


//     $data = ;
//     dd($data);
// dd($requestData);
    try {
      
        
        // $response = $client->post(self::API_ENDPOINT, [
        //     'headers' => [
        //         'Authorization' => 'Bearer ' . $token,
        //         'Content-Type'  => 'application/json',
        //         'Accept'   => 'application/json',
        //     ],
        //     'json' => $requestData,
        // ]);


        $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ])
      ->post('https://ltl-clients-api.delhivery.com/manifest', $params);


        //   dd($response);
        $responseData = json_decode($response->getBody(), true);
        dd($responseData);

        if ($responseData['status'] == true) {
            // echo 'asxasx';die;
         $responseData = json_decode($response->getBody(), true);

            return $responseData; // or you can return a specific part like $data['awb_number']
        } else {
            // Handle non-200 status code (error handling)
            return null;
        }
    } catch (\Exception $e) {
        // Log or handle the error
        \Log::error('API Request Failed: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'API Request Failed',
            'message' => $e->getMessage()
        ], 500);
        // return null;
    }
}









//         $json = $resp->json();
// //    dd($json);
//         // If not serviceable, return empty
//         if (empty($json['serviceable']) || ! $json['serviceable']) {
//             return [];
//         }

//         // Normalize into unified format
//         return [[
//             'serviceabilityId'   => $json['serviceability_id'] ?? null,
//             'courierName'        => 'Delhivery B2B',
//             'courierCharge'      => $json['freight_charge']  ?? 0.0,
//             'freightCharges'     => $json['freight_charge']  ?? 0.0,
//             'codCharge'          => $json['cod_charge']      ?? 0.0,
//             'minWeight'          => $json['min_weight']      ?? $params['weight'],
//             'volWeight'          => $json['chargeable_weight'] ?? $params['weight'],
//         ]];
//     }
}