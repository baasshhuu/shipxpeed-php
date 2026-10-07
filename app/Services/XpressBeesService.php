<?php
// app/Services/XpressBeesService.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\PriceSetting;

class XpressBeesService implements CourierServiceInterface
{

    const API_BASE_URL = 'https://shipment.xpressbees.com/';
    const API_ENDPOINT = '/api/shipments2';
    const TOKEN_CACHE_KEY = 'xpressbees_api_token';
    const TOKEN_CACHE_TTL = 3600;


    protected function fetchAuthToken(): string
    {
        $cfg      = config('courier_services.xpressbees');
        $cacheKey = 'xpressbees.token';

        //return Cache::remember($cacheKey, $cfg['token_ttl'], function() use ($cfg) {
            $resp = Http::post($cfg['login_url'], [
                'email' => $cfg['username'],
                'password' => $cfg['password'],
            ]);

            $json = $resp->json();
            if (! $resp->successful() || empty($json['data'])) {
                throw new \RuntimeException("XpressBees login failed: {$resp->body()}");
            }

            return $json['data'];
        //});
    }

    /**
     * @param  array  $params  // origin,destination,payment_type,order_amount,weight,length,breadth,height
     * @return array           // a list of normalized entries
     */

public function getServiceability_bulk(array $orders): array
{
    $cfg = config('courier_services.xpressbees');
    $token = $this->fetchAuthToken();
    $allResults = [];

    foreach ($orders as $params) {
        $orderId = $params['order_id'] ?? null;
        if (!$orderId) continue;

        try {
            $order = Order::findOrFail($orderId);

            $apiPayload = [
                'order_id'     => $orderId,
                'payment_type' => $params['payment_type'],
                'order_amount' => $params['order_amount'],
                'origin'       => $params['origin'],
                'destination'  => $params['destination'],
                'weight'       => $params['weight'],
                'length'       => $params['length'],
                'breadth'      => $params['breadth'],
                'height'       => $params['height'],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->post($cfg['service_url'], $apiPayload);

            if (!$response->successful()) {
                $allResults[] = [
                    'order_id' => $orderId,
                    'error'    => 'Service request failed'
                ];
                continue;
            }

            $json = $response->json();
            if (empty($json['status']) || !$json['status'] || empty($json['data'])) {
                $allResults[] = [
                    'order_id' => $orderId,
                    'error'    => 'No serviceable data returned'
                ];
                continue;
            }

            $orderAmount = $params['order_amount'];
            $paymentType = $params['payment_type'];
            $seller_id = $order->seller_id;

            $services = collect($json['data'])->map(function ($item) use ($orderAmount, $paymentType, $seller_id) {
                $isAirService = str_contains($item['name'], 'Air');
                $isSameDay = str_contains($item['name'], 'Same Day');

                $PriceSetting = PriceSetting::where([
                    'seller_id' => $seller_id,
                    'LogisticProvider' => $isAirService ? 'XpressBees Air' : 'XpressBees'
                ])->first();

                $sellerPercentage = $PriceSetting?->shipping_charge ?? 70;
                $codChargePercent = $PriceSetting?->cod_charge_parsent ?? 1.9;
                $codChargeFixed = $PriceSetting?->cod_charge ?? 32;

                $freightplus = ($item['freight_charges'] ?? 0.0) / 1.18;
          $freight = $freightplus + ($freightplus * 0.10);            // 10% बढ़ाया

                $codCharge = 0;
                if ($paymentType === 'cod') {
                    $codCharge = $orderAmount > 1400
                        ? ($orderAmount * $codChargePercent / 100)
                        : $codChargeFixed;
                }

                $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
                $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
                $freightWithGST = $freightWithSellerCodCharge * 1.18;

                return [
                    'serviceabilityId' => $item['id'],
                    'courierName'      => $item['name'],
                    'courierCharge'    => round($freightWithGST, 2),
                    'freightCharges'   => round($freightWithSeller, 2),
                    'codCharge'        => round($codCharge, 2),
                    'minWeight'        => $item['min_weight'],
                    'volWeight'        => $item['chargeable_weight'],
                ];
            })->toArray();

            $allResults[] = [
                'order_id' => $orderId,
                'services' => $services
            ];
        } catch (\Exception $e) {
            Log::error('XpressBees Serviceability Error', [
                'order_id' => $orderId,
                'error'    => $e->getMessage()
            ]);

            $allResults[] = [
                'order_id' => $orderId,
                'error'    => 'Exception occurred: ' . $e->getMessage()
            ];
        }
    }

    return $allResults;
}



    

//     public function getServiceability_bulk(array $orders): array
// {
//     // dd($orders);
//     $cfg = config('courier_services.xpressbees');
//     $token = $this->fetchAuthToken();

//     $results = [];

//     foreach ($orders as $params) {
//         $orderId = $params['order_id'] ?? null;
//         if (!$orderId) {
//             continue;
//         }

//         $order = Order::find($orderId);
//         if (!$order) {
//             $results[$orderId] = ['error' => 'Order not found'];
//             continue;
//         }



//         $apiPayload = [
//             'order_id'       => $orderId,
//             'payment_type'   => $params['payment_type'],
//             'order_amount' => $params['order_amount'],
//             'origin' => $params['origin'],
//             'destination'   => $params['destination'],
//             'weight'         => $params['weight'],
//             'length'         => $params['length'],
//             'breadth'        => $params['breadth'],
//             'height'         => $params['height'],
//         ];





//         $response = Http::withHeaders([
//             'Authorization' => "Bearer {$token}",
//             'Accept'        => 'application/json',
//             'Content-Type'  => 'application/json',
//         ])->post($cfg['service_url'], $apiPayload);

//         //  dd($response->json());
//         if (!$response->successful()) {
//             $results[$orderId] = ['error' => 'Service request failed'];
//             continue;
//         }

//         $json = $response->json();
//         // dd($json);
//         if (empty($json['status']) || !$json['status'] || empty($json['data'])) {
//             $results[$orderId] = ['error' => 'No serviceable data returned'];
//             continue;
//         }

//         $orderAmount = $params['order_amount'];
//         $paymentType = $params['payment_type'];
//         $seller_id = $order->seller_id;

//         $services = collect($json['data'])->map(function ($item) use ($orderAmount, $paymentType, $seller_id) {
//             $isAirService = str_contains($item['name'], 'Air');
//             $isSameDay = str_contains($item['name'], 'Same Day');

//             $PriceSetting = PriceSetting::where([
//                 'seller_id' => $seller_id,
//                 'LogisticProvider' => $isAirService ? 'XpressBees Air' : 'XpressBees'
//             ])->first();

//             $sellerPercentage = $PriceSetting?->shipping_charge ?? 70;
//             $codChargePercent = $PriceSetting?->cod_charge_parsent ?? 1.9;
//             $codChargeFixed = $PriceSetting?->cod_charge ?? 32;

//             $freight = ($item['freight_charges'] ?? 0.0) / 1.18;

//             $codCharge = 0;
//             if ($paymentType === 'cod') {
//                 $codCharge = $orderAmount > 1400
//                     ? ($orderAmount * $codChargePercent / 100)
//                     : $codChargeFixed;
//             }

//             $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
//             $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//             $freightWithGST = $freightWithSellerCodCharge * 1.18;

//             return [
//                 'serviceabilityId' => $item['id'],
//                 'courierName'      => $item['name'],
//                 'courierCharge'    => round($freightWithGST, 2),
//                 'freightCharges'   => round($freightWithSeller, 2),
//                 'codCharge'        => round($codCharge, 2),
//                 'minWeight'        => $item['min_weight'],
//                 'volWeight'        => $item['chargeable_weight'],
//                 'serviceType'      => $isAirService ? 'air' : ($isSameDay ? 'same_day' : 'regular'),
//             ];
//         });

//         $results[$orderId] = $services->toArray();
//     }

//     return $results;
// }




// public function getServiceability_bulk(array $orderIds): array
// {
//     // dd($orderIds);
//     $cfg = config('courier_services.xpressbees');
//     $token = $this->fetchAuthToken();

//     $results = [];

//     foreach ($orderIds as $orderId) {
//         // dd($orderId);
//         $order = Order::find($orderId);
//         if (!$order) {
//             $results[$orderId] = ['error' => 'Order not found'];
//             continue;
//         }

//         $pickupdata = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

//         // Build request payload
//         $params = [
//             'order_id'       => $order->id,
//             'payment_type'   => $order->payment_type,
//             'collectable_amount' => $order->collectable_amount,
//             'pickup_pincode' => $pickupdata['pincode'] ?? '',
//             'drop_pincode'   => $order->pincode,
//             'weight'         => $order->weight,
//             'length'         => $order->length,
//             'breadth'        => $order->breadth,
//             'height'         => $order->height,
//         ];

//         $response = Http::withHeaders([
//             'Authorization' => "Bearer {$token}",
//             'Accept'        => 'application/json',
//             'Content-Type'  => 'application/json',
//         ])->post($cfg['service_url'], $params);

//         if (!$response->successful()) {
//             $results[$orderId] = ['error' => 'Service request failed'];
//             continue;
//         }

//         $json = $response->json();
//         // dd($json);
//         if (empty($json['status']) || !$json['status'] || empty($json['data'])) {
//             $results[$orderId] = ['error' => 'No serviceable data returned'];
//             continue;
//         }

//         $orderAmount = $order->collectable_amount;
//         $paymentType = $order->payment_type;
//         $seller_id = $order->seller_id;

//         $services = collect($json['data'])->map(function ($item) use ($orderAmount, $paymentType, $seller_id) {
//             $isAirService = str_contains($item['name'], 'Air');
//             $isSameDay = str_contains($item['name'], 'Same Day');

//             $PriceSetting = PriceSetting::where([
//                 'seller_id' => $seller_id,
//                 'LogisticProvider' => $isAirService ? 'XpressBees Air' : 'XpressBees'
//             ])->first();

//             $sellerPercentage = $PriceSetting?->shipping_charge ?? 70;
//             $codChargePercent = $PriceSetting?->cod_charge_parsent ?? 1.9;
//             $codChargeFixed = $PriceSetting?->cod_charge ?? 32;

//             $freight = ($item['freight_charges'] ?? 0.0) / 1.18;

//             $codCharge = 0;
//             if ($paymentType === 'cod') {
//                 $codCharge = $orderAmount > 1400
//                     ? ($orderAmount * $codChargePercent / 100)
//                     : $codChargeFixed;
//             }

//             $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
//             $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//             $freightWithGST = $freightWithSellerCodCharge * 1.18;

//             return [
//                 'serviceabilityId' => $item['id'],
//                 'courierName'      => $item['name'],
//                 'courierCharge'    => round($freightWithGST, 2),
//                 'freightCharges'   => round($freightWithSeller, 2),
//                 'codCharge'        => round($codCharge, 2),
//                 'minWeight'        => $item['min_weight'],
//                 'volWeight'        => $item['chargeable_weight'],
//                 'serviceType'      => $isAirService ? 'air' : ($isSameDay ? 'same_day' : 'regular'),
//             ];
//         });

//         $results[$orderId] = $services->toArray();
//     }

//     return $results;
// }






    public function getServiceability(array $params): array
{
    $cfg = config('courier_services.xpressbees');
    $token = $this->fetchAuthToken();

    $resp = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ])->post($cfg['service_url'], $params);
//  dd($resp->json());
    if (!$resp->successful()) {
        return [];
    }

    $json = $resp->json();

    if (empty($json['status']) || !$json['status'] || empty($json['data'])) {
        return [];
    }

    $order = Order::findOrFail($params['order_id']);
    $orderAmount = $order->collectable_amount;
    $paymentType = $order->payment_type;
    $seller_id = $order->seller_id;

    return collect($json['data'])->map(function ($item) use ($orderAmount, $paymentType, $seller_id) {
        // Determine service type
        $isAirService = str_contains($item['name'], 'Air');
        $isSameDay = str_contains($item['name'], 'Same Day');
        
        // Get appropriate pricing settings
        if ($isAirService) {
            $PriceSetting = PriceSetting::where([
                'seller_id' => $seller_id, 
                'LogisticProvider' => 'XpressBees Air'
            ])->first();
        } elseif ($isSameDay) {
            $PriceSetting = PriceSetting::where([
                'seller_id' => $seller_id, 
                'LogisticProvider' => 'XpressBees'
            ])->first();
        } else {
            $PriceSetting = PriceSetting::where([
                'seller_id' => $seller_id, 
                'LogisticProvider' => 'XpressBees'
            ])->first();
        }

        // Fallback to defaults if no specific pricing found
        $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 70;
        $codChargePercent = $PriceSetting ? $PriceSetting->cod_charge_parsent : 1.9;
        $codChargeFixed = $PriceSetting ? $PriceSetting->cod_charge : 32;

        // Calculate charges
        $freightplus = ($item['freight_charges'] ?? 0.0) / 1.18;
          $freight = $freightplus + ($freightplus * 0.10);            // 10% बढ़ाया

        $codCharge = 0;
        if ($paymentType === 'cod') {
            $codCharge = $orderAmount > 1400 
                ? ($orderAmount * $codChargePercent / 100) 
                : $codChargeFixed;
        }

        $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
        $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
        $freightWithGST = $freightWithSellerCodCharge * 1.18; // Add GST

        return [
            'serviceabilityId' => $item['id'],
            'courierName'      => $item['name'],
            'courierCharge'    => round($freightWithGST, 2),
            'freightCharges'   => round($freightWithSeller, 2),
            'codCharge'        => round($codCharge, 2),
            'minWeight'        => $item['min_weight'],
            'volWeight'        => $item['chargeable_weight'],
            'serviceType'      => $isAirService ? 'air' : ($isSameDay ? 'same_day' : 'regular'),
        ];
    })->toArray();
}

   


public function assignOrder($params)
{
//  dd($params);

    $token = $this->fetchAuthToken();
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return redirect()->back()->with('error', 'Unauthorized or inactive seller.');
    }

    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;

    // Get order
    $order = Order::where('order_number', $params->order_number)->first();

    if (!$order) {
         return [
            'status'  => false,
            'message' => 'error', 'Order not found.',
        ];
        // return redirect()->back()->with('error', 'Order not found.');
    }

    // if ($walletBalance < $order->seller_amount_walate || $walletBalance < 150) {
    //         return [
    //         'status'  => false,
    //         'message' => 'error', 'Insufficient wallet balance. Please recharge your wallet.',
    //     ];
    // }

    if (
    $seller->negative_balance != '1' && 
    ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
) {
    return [
        'status'  => false,
        'message'  => 'Insufficient wallet balance. Please recharge your wallet.',
    ];
}


    // echo 'asxasx';die;

    // Prepare API request
    $client = new \GuzzleHttp\Client([
        'base_uri'        => self::API_BASE_URL,
        'timeout'         => 600,
        'connect_timeout' => 600,
        'verify'          => false,
    ]);
// $data = json_encode($params, true);
//      dd($data);
    try {
        // echo 'asxasx';die;
        $response = $client->post(self::API_ENDPOINT, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json' => $params,
        ]);
    //  dd($response);

        $responseData = json_decode($response->getBody(), true);
        //   dd($responseData);
        if (!empty($responseData['status']) && $responseData['status'] === true) {
            $order->courier_id = 'xpressbees';
            $order->save();
            Recharge::create([
                'seller_id'   => $seller->id,
                'type'        => 'Debit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
            ]);

            return $responseData;
        } else {
            return [
                'status'  => false,
                'message' => 'API returned error.',
                'data'    => $responseData,
            ];
        }

    } catch (\Exception $e) {
        // dd($e->getMessage());
        // echo $e->getMessage();
        return [
            'status'  => false,
            'message' => 'API Request Failed: ' . $e->getMessage(),
        ];
    }
}

public function assignOrder_bulk($params)
{
    $token = $this->fetchAuthToken();
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return [
            'status' => false,
            'message' => 'Unauthorized or inactive seller.'
        ];
    }

    // Calculate wallet balance
    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');
    
    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');
    
    $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;

    // Prepare response arrays
    $success = [];
    $failures = [];
    $totalAmount = 0;

    // Convert params to array if it's not already
    $paramsArray = is_array($params) ? $params : [$params];
    //   dd($paramsArray);
    // First validate all orders and calculate total amount
    foreach ($paramsArray as $param) {
        try {
            // Handle both object and array parameters
            $orderNumber = is_object($param) ? 
                ($param->order_number ?? null) : 
                (is_array($param) ? ($param['order_number'] ?? null) : $param);
            
            if (!$orderNumber) {
                $failures[] = [
                    'order_number' => 'N/A',
                    'message' => 'Missing order number in parameters'
                ];
                continue;
            }

            $order = Order::where('order_number', $orderNumber)->first();
            
            if (!$order) {
                $failures[] = [
                    'order_number' => $orderNumber,
                    'message' => 'Order not found'
                ];
                continue;
            }
            
            $totalAmount += $order->seller_amount_walate;
        } catch (\Exception $e) {
            $failures[] = [
                'order_number' => $orderNumber ?? 'N/A',
                'message' => 'Validation error: ' . $e->getMessage()
            ];
        }
    }

    // Check wallet balance for all orders
    if ($walletBalance < $totalAmount || $walletBalance < (150 * count($paramsArray))) {
        return [
            'status' => false,
            'message' => 'Insufficient wallet balance for bulk orders',
            'required_amount' => $totalAmount,
            'available_balance' => $walletBalance
        ];
    }

    $client = new \GuzzleHttp\Client([
        'base_uri'        => self::API_BASE_URL,
        'timeout'         => 600,
        'connect_timeout' => 600,
        'verify'          => false,
    ]);

    // Process each order
    foreach ($paramsArray as $param) {
        $orderNumber = is_object($param) ? 
            ($param->order_number ?? null) : 
            (is_array($param) ? ($param['order_number'] ?? null) : $param);
        
        try {
            $order = Order::where('order_number', $orderNumber)->first();
            if (!$order) {
                $failures[] = [
                    'order_number' => $orderNumber,
                    'message' => 'Order not found during processing'
                ];
                continue;
            }

            // Prepare API payload
            $apiPayload = is_object($param) ? (array)$param : $param;
            if (is_string($apiPayload)) {
                $apiPayload = ['order_number' => $apiPayload];
            }

            $response = $client->post(self::API_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => $apiPayload,
            ]);

            $responseData = json_decode($response->getBody(), true);
            //   dd($responseData);
            if (!empty($responseData['status']) && $responseData['status'] === true) {
                // Update all order fields from response
                $order->courier_id = 'xpressbees';
                $order->awb_number = $responseData['data']['awb_number'] ?? null;
                $order->courier_order_id = $responseData['data']['order_id'] ?? null;
                $order->shipment_id = $responseData['data']['shipment_id'] ?? null;
                $order->co_courier_id = $responseData['data']['courier_id'] ?? null;
                $order->courier_name = $responseData['data']['courier_name'] ?? null;
                $order->status = $responseData['data']['status'] ?? null;
                $order->additional_info = $responseData['data']['additional_info'] ?? null;
                $order->co_payment_type = $responseData['data']['payment_type'] ?? null;
                $order->fwd_destination_code = $responseData['data']['fwd_destination_code'] ?? null;
                $order->label = $responseData['data']['label'] ?? null;
                $order->manifest = $responseData['data']['manifest'] ?? null;
                // $order->seller_amount_walate = $param['courier_charge'] ?? $order->seller_amount_walate;
                $order->save();
                
                // Create debit record
                Recharge::create([
                    'seller_id' => $seller->id,
                    'type'      => 'Debit',
                    'amount'    => $order->seller_amount_walate,
                    'status'    => 1,
                ]);
                
                $success[] = [
                    'order_number' => $order->order_number,
                    'awb_number' => $order->awb_number,
                    'message' => 'Successfully assigned',
                    'response_data' => $responseData // Include full response if needed
                ];
            } else {
                $failures[] = [
                    'order_number' => $order->order_number,
                    'message' => $responseData['message'] ?? 'API returned error',
                    'response_data' => $responseData // Include error details
                ];
            }
        } catch (\Exception $e) {
            $failures[] = [
                'order_number' => $orderNumber,
                'message' => 'API Request Failed: ' . $e->getMessage()
            ];
        }
    }

    return [
        'status' => count($failures) === 0,
        'success_count' => count($success),
        'fail_count' => count($failures),
        'success_orders' => $success,
        'failed_orders' => $failures,
        'total_amount_debited' => $totalAmount
    ];
}


// public function assignOrder_bulk($params)
// {
//     $token = $this->fetchAuthToken();
//     $seller = Auth::guard('seller')->user();

//     if (!$seller || $seller->status != 1) {
//         return [
//             'status' => false,
//             'message' => 'Unauthorized or inactive seller.'
//         ];
//     }

//     // Calculate wallet balance
//     $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');
    
//     $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');
    
//     $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;

//     // Prepare response arrays
//     $success = [];
//     $failures = [];
//     $totalAmount = 0;

//     // Convert params to array if it's not already
//     $paramsArray = is_array($params) ? $params : [$params];
    
//     // First validate all orders and calculate total amount
//     foreach ($paramsArray as $param) {
//         try {
//             // Handle both object and array parameters
//             $orderNumber = is_object($param) ? 
//                 ($param->order_number ?? null) : 
//                 (is_array($param) ? ($param['order_number'] ?? null) : $param);
            
//             if (!$orderNumber) {
//                 $failures[] = [
//                     'order_number' => 'N/A',
//                     'message' => 'Missing order number in parameters'
//                 ];
//                 continue;
//             }

//             $order = Order::where('order_number', $orderNumber)->first();
            
//             if (!$order) {
//                 $failures[] = [
//                     'order_number' => $orderNumber,
//                     'message' => 'Order not found'
//                 ];
//                 continue;
//             }
            
//             $totalAmount += $order->seller_amount_walate;
//         } catch (\Exception $e) {
//             $failures[] = [
//                 'order_number' => $orderNumber ?? 'N/A',
//                 'message' => 'Validation error: ' . $e->getMessage()
//             ];
//         }
//     }

//     // Check wallet balance for all orders
//     if ($walletBalance < $totalAmount || $walletBalance < (150 * count($paramsArray))) {
//         return [
//             'status' => false,
//             'message' => 'Insufficient wallet balance for bulk orders',
//             'required_amount' => $totalAmount,
//             'available_balance' => $walletBalance
//         ];
//     }

//     $client = new \GuzzleHttp\Client([
//         'base_uri'        => self::API_BASE_URL,
//         'timeout'         => 600,
//         'connect_timeout' => 600,
//         'verify'          => false,
//     ]);

//     // Process each order
//     foreach ($paramsArray as $param) {
//         $orderNumber = is_object($param) ? 
//             ($param->order_number ?? null) : 
//             (is_array($param) ? ($param['order_number'] ?? null) : $param);
        
//         try {
//             $order = Order::where('order_number', $orderNumber)->first();
//             if (!$order) {
//                 $failures[] = [
//                     'order_number' => $orderNumber,
//                     'message' => 'Order not found during processing'
//                 ];
//                 continue;
//             }

//             // Prepare API payload
//             $apiPayload = is_object($param) ? (array)$param : $param;
//             if (is_string($apiPayload)) {
//                 $apiPayload = ['order_number' => $apiPayload];
//             }

//             $response = $client->post(self::API_ENDPOINT, [
//                 'headers' => [
//                     'Authorization' => 'Bearer ' . $token,
//                     'Content-Type'  => 'application/json',
//                     'Accept'        => 'application/json',
//                 ],
//                 'json' => $apiPayload,
//             ]);

//             $responseData = json_decode($response->getBody(), true);

//             if (!empty($responseData['status']) && $responseData['status'] === true) {
//                 $order->courier_id = 'xpressbees';
//                 $order->awb_number = $responseData['data']['awb_number'] ?? null;
//                 $order->courier_order_id = $responseData['data']['order_id'] ?? null;
//                 $order->save();
                
//                 Recharge::create([
//                     'seller_id' => $seller->id,
//                     'type'      => 'Debit',
//                     'amount'    => $order->seller_amount_walate,
//                     'status'    => 1,
//                 ]);
                
//                 $success[] = [
//                     'order_number' => $order->order_number,
//                     'awb_number' => $order->awb_number,
//                     'message' => 'Successfully assigned'
//                 ];
//             } else {
//                 $failures[] = [
//                     'order_number' => $order->order_number,
//                     'message' => $responseData['message'] ?? 'API returned error'
//                 ];
//             }
//         } catch (\Exception $e) {
//             $failures[] = [
//                 'order_number' => $orderNumber,
//                 'message' => 'API Request Failed: ' . $e->getMessage()
//             ];
//         }
//     }

//     return [
//         'status' => count($failures) === 0,
//         'success_count' => count($success),
//         'fail_count' => count($failures),
//         'success_orders' => $success,
//         'failed_orders' => $failures
//     ];
// }



// public function assignOrder_bulk($params)
// {
//     $token = $this->fetchAuthToken();
//     $seller = Auth::guard('seller')->user();

//     if (!$seller || $seller->status != 1) {
//         return [
//             'status' => false,
//             'message' => 'Unauthorized or inactive seller.',
//         ];
//     }

//     $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;

//     // Wrap single order in array if needed
//     $orders = isset($params[0]) ? $params : [$params];

//     $results = [];

//     foreach ($orders as $orderParams) {
//         $orderNumber = is_array($orderParams) ? $orderParams['order_number'] : $orderParams->order_number ?? null;

//         $order = Order::where('order_number', $orderNumber)->first();
//         if (!$order) {
//             $results[] = [
//                 'status' => false,
//                 'order_number' => $orderNumber,
//                 'message' => 'Order not found.',
//             ];
//             continue;
//         }

//         if ($walletBalance < $order->seller_amount_walate || $walletBalance < 150) {
//             $results[] = [
//                 'status' => false,
//                 'order_number' => $orderNumber,
//                 'message' => 'Insufficient wallet balance. Please recharge your wallet.',
//             ];
//             continue;
//         }

//         try {
//             $client = new \GuzzleHttp\Client([
//                 'base_uri'        => self::API_BASE_URL,
//                 'timeout'         => 600,
//                 'connect_timeout' => 600,
//                 'verify'          => false,
//             ]);

//             $response = $client->post(self::API_ENDPOINT, [
//                 'headers' => [
//                     'Authorization' => 'Bearer ' . $token,
//                     'Content-Type'  => 'application/json',
//                     'Accept'        => 'application/json',
//                 ],
//                 'json' => $orderParams,
//             ]);

//             $responseData = json_decode($response->getBody(), true);

//             if (!empty($responseData['status']) && $responseData['status'] === true) {
//                 $order->courier_id = 'xpressbees'; // or dynamic based on logic
//                 $order->save();

//                 Recharge::create([
//                     'seller_id' => $seller->id,
//                     'type'      => 'Debit',
//                     'amount'    => $order->seller_amount_walate,
//                     'status'    => 1,
//                 ]);

//                 $results[] = [
//                     'status' => true,
//                     'order_number' => $orderNumber,
//                     'message' => 'Courier assigned',
//                     'awb_number' => $responseData['awb_number'] ?? null,
//                     'response' => $responseData,
//                 ];
//             } else {
//                 $results[] = [
//                     'status' => false,
//                     'order_number' => $orderNumber,
//                     'message' => 'API returned error',
//                     'response' => $responseData,
//                 ];
//             }
//         } catch (\Exception $e) {
//             $results[] = [
//                 'status' => false,
//                 'order_number' => $orderNumber,
//                 'message' => 'API Request Failed: ' . $e->getMessage(),
//             ];
//         }
//     }

//     return $results;
// }















protected function prepareRequestDataFromOrderId($orderId)
{
    $order = Order::where('order_number',$orderId)->first(); // eager load related models if any

    $collectableAmount = $order->payment_type === 'cod'
        ? (float)$order->collectable_amount
        : 0.0;

    $requestData = [
        'order_number' => $order->order_number,
        'unique_order_number' => $order->unique_order_number,
        'shipping_charges' => (float)($order->shipping_charges ?? 0.0),
        'discount' => (float)($order->discount ?? 0.0),
        'cod_charges' => (float)($order->cod_charges ?? 0.0),
        'payment_type' => $order->payment_type,
        'order_amount' => (float)($order->collectable_amount ?? 0.0),
        'package_weight' => (float)($order->package_weight),
        'package_length' => (float)($order->package_length),
        'package_breadth' => (float)($order->package_breadth),
        'package_height' => (float)($order->package_height),
        'request_auto_pickup' => $order->request_auto_pickup,
        'courier_id' => $order->courier_id ?? '',
        'collectable_amount' => $collectableAmount,

        'consignee' => [
            'name' => $order->consignee_name,
            'address' => $order->consignee_address,
            'address_2' => $order->consignee_address_2 ?? '',
            'city' => $order->consignee_city,
            'state' => $order->consignee_state,
            'pincode' => $order->consignee_pincode,
            'phone' => $order->consignee_phone,
        ],

        'pickup' => [
            'warehouse_name' => $order->pickup_warehouse_name ?? '',
            'name' => $order->pickup_name,
            'address' => $order->pickup_address,
            'address_2' => $order->pickup_address_2 ?? '',
            'city' => $order->pickup_city,
            'state' => $order->pickup_state,
            'pincode' => $order->pickup_pincode,
            'phone' => $order->pickup_phone,
        ],

        'is_rto_different' => $order->is_rto_different,

        'order_items' => $order->orderItems->map(function ($item) use ($order) {
            return [
                'name' => $item->name,
                'qty' => (int)$item->qty,
                'sku' => $item->sku ?? '',
                'price' => $order->payment_type === 'cod' ? 0.0 : (float)($item->price ?? 0.0),
            ];
        })->toArray(),
    ];

    if ($order->is_rto_different === 'yes') {
        $requestData['rto'] = [
            'warehouse_name' => $order->rto_warehouse_name,
            'name' => $order->rto_name,
            'address' => $order->rto_address,
            'city' => $order->rto_city,
            'state' => $order->rto_state,
            'pincode' => $order->rto_pincode,
            'phone' => $order->rto_phone,
        ];
    }

    return $requestData;
}





public function cancelShipment(string $awb): array
{
    $url   = "https://shipment.xpressbees.com/api/shipments2/cancel";
    $token = $this->fetchAuthToken();

    $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])
        ->post($url, [
            'awb' => $awb,
        ]);
         $responseData = json_decode($response->getBody(), true);
        //   dd($responseData);
    if (! $response->successful()) {
        return [
            'error'   => 'XpressBees cancel API error',
            'status'  => $response->status(),
            'message' => $response->body(),
        ];
    }
    $seller = Auth::guard('seller')->user();
    $order = Order::where('awb_number', $awb)->first();
    $order->order_status = 'cancelled';
    $order->save();
        Recharge::create([
                'seller_id'   => $seller->id,
                'type'        => 'Credit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
            ]);
// dd($response);
    return $response->json();
}






public function cancelShipmentBulk(array $awbs): array
{
    $url = "https://shipment.xpressbees.com/api/shipments2/cancel";
    $token = $this->fetchAuthToken();
    $seller = Auth::guard('seller')->user();

    $results = [];

    foreach ($awbs as $awb) {
        try {
            $response = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ])
                ->post($url, ['awb' => $awb]);

            $responseData = json_decode($response->getBody(), true);

            if (! $response->successful()) {
                $results[] = [
                    'awb'     => $awb,
                    'status'  => false,
                    'message' => 'API error: ' . $response->body(),
                ];
                continue;
            }

            // Update order
            $order = Order::where('awb_number', $awb)->first();
            if ($order) {
                $order->order_status = 'cancelled';
                $order->save();

                // Wallet refund
                Recharge::create([
                    'seller_id' => $seller->id,
                    'type'      => 'Credit',
                    'amount'    => $order->seller_amount_walate,
                    'status'    => 1,
                ]);
            }

            $results[] = [
                'awb'     => $awb,
                'status'  => true,
                'message' => 'Cancelled successfully',
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            $results[] = [
                'awb'     => $awb,
                'status'  => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    return $results;
}







public function fetchNdrData($params)
{
    try {
        $client = new \GuzzleHttp\Client();
        $token = $this->fetchAuthToken();

        $response = $client->get('https://shipment.xpressbees.com/api/ndr', [
            'verify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ],
            'query' => [
                'page' => 1,
                'limit' => 100,
                'status' => 'open',
                'sort_by' => 'date',
                'order' => 'desc'
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (!empty($data['status']) && $data['status'] === true && !empty($data['data'])) {
            // Extract only awb numbers
            $awbNumbers = collect($data['data'])
                ->pluck('awb_number')        // pull awb_number from each record
                ->filter()                   // remove nulls or blanks
                ->values();                  // reset index

            return response()->json([
                'status' => true,
                'awb_numbers' => $awbNumbers
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $data['message'] ?? 'NDR API call failed or no data',
                'response' => $data
            ]);
        }

    } catch (\Exception $e) {
        \Log::error('NDR API Exception', ['message' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'API Request failed: ' . $e->getMessage()
        ]);
    }
}




public function fetchNdrDatass($params)
{
    try {
        $client = new \GuzzleHttp\Client();
        $token = $this->fetchAuthToken();

        $response = $client->get('https://shipment.xpressbees.com/api/ndr', [
            'verify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ],
            'query' => [
                'page' => 1,
                'limit' => 100,
                'status' => 'open',
                'sort_by' => 'date',
                   'order' => 'desc'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
       dd($data);
        if (!empty($data['status']) && $data['status'] === true) {
            return response()->json([
                'status' => true,
                'ndr_data' => $data['data']
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $data['message'] ?? 'NDR API call failed',
                'response' => $data
            ]);
        }

    } catch (\Exception $e) {
        dd($e->getMessage());
        \Log::error('NDR API Exception', ['message' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'API Request failed: ' . $e->getMessage()
        ]);
    }
}


public function fetchNdrDatasss($params)
{
    try {
        $client = new \GuzzleHttp\Client();
        $token = $this->fetchAuthToken(); // Assuming this gets a valid XpressBees token

        // Example payload - adjust as per API requirement
        $payload = [
            'from_date' => '2024-06-01',
            'to_date' => '2024-07-12',
            // You can also include 'awb_number' => 'XXXXXXXXXXXX' if needed
        ];

        $response = $client->get('https://shipment.xpressbees.com/api/ndr', [
            'verify' => false, // Skip SSL check (only for dev)
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => $payload,
        ]);

        $data = json_decode($response->getBody(), true);
    dd($data);
        // Log or return API response
        if (!empty($data['status']) && $data['status'] === true) {
            return response()->json([
                'status' => true,
                'ndr_data' => $data['data']
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $data['message'] ?? 'NDR API call failed',
                'response' => $data
            ]);
        }

    } catch (\Exception $e) {
        dd($e->getMessage());
        \Log::error('NDR API Exception', ['message' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'API Request failed: ' . $e->getMessage()
        ]);
    }
}






// public function fetchNdrData($params)
// {
//     try {
//         $client = new \GuzzleHttp\Client();
//         $token = $this->fetchAuthToken(); // Assuming you have this function

//         $payload = [
//             'page' => 1,
//             'limit' => 50,
//             'status' => 'open'  // or 'all' to fetch everything
//         ];

//         $response = $client->post('https://shipment.xpressbees.com/api/ndr', [
//             'verify' => false,
//             'headers' => [
//                 'Authorization' => 'Bearer ' . $token,
//                 'Accept'        => 'application/json',
//                 'Content-Type'  => 'application/json',
//             ],
//             'json' => $payload,
//         ]);

//         $data = json_decode($response->getBody(), true);
//         dd($data);

//         if (!empty($data['status']) && $data['status'] === true) {
//             return response()->json([
//                 'status' => true,
//                 'ndr_data' => $data['data']
//             ]);
//         } else {
//             return response()->json([
//                 'status' => false,
//                 'message' => $data['message'] ?? 'NDR API call failed',
//                 'response' => $data
//             ]);
//         }

//     } catch (\Exception $e) {
//         dd($e->getMessage());
//         \Log::error('NDR API Exception', ['message' => $e->getMessage()]);
//         return response()->json([
//             'status' => false,
//             'message' => 'API Request failed: ' . $e->getMessage()
//         ]);
//     }
// }




// public function fetchNdrData($params)
// {
//     // dd($params);
//     try {
//         $client = new \GuzzleHttp\Client();
//         $token = $this->fetchAuthToken();

//         // echo 'vi';die;

//         $response = $client->post('https://shipment.xpressbees.com/api/ndr', [
//             'verify'  => false,
//             'headers' => [
//                 'Authorization' => 'Bearer ' . $token,
//                 'Accept'        => 'application/json',
//                 'Content-Type'  => 'application/json',
//             ],
//             'json' => [], // ✅ Use this instead of 'body' => ''
//         ]);

//         $data = json_decode($response->getBody(), true);
//         dd($data);

//         if (!empty($data['status']) && $data['status'] === true) {
//             return response()->json([
//                 'status' => true,
//                 'ndr_data' => $data['data']
//             ]);
//         } else {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'NDR API call failed',
//                 'response' => $data
//             ]);
//         }

//     } catch (\Exception $e) {
//         echo 'csacs';die;
//         return response()->json([
//             'status' => false,
//             'message' => 'API Request failed: ' . $e->getMessage()
//         ]);
//     }
// }



}

