<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PriceSetting;
use App\Models\ZonePriceSetting;
use App\Models\Recharge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ActicvSleb;


class selloshipService implements CourierServiceInterface
{



    
public function getServiceability_bulk(array $params): array
{
    // Handle bulk data - expect array of order_ids or order data
    $results = [];
    
    // Check if we have multiple orders
    if (isset($params['order_ids']) && is_array($params['order_ids'])) {
        // Process multiple order IDs
        foreach ($params['order_ids'] as $orderId) {
            try {
                $orderResult = $this->getServiceability(['order_id' => $orderId]);
                $results = array_merge($results, $orderResult);
            } catch (\Exception $e) {
                // Log error but continue processing other orders
                \Log::error("Error processing order {$orderId} in Selloship bulk serviceability: " . $e->getMessage());
                continue;
            }
        }
    } elseif (isset($params['order_id'])) {
        // Single order processing (backward compatibility)
        $results = $this->getServiceability($params);
    } else {
        // Invalid parameters
        return [];
    }
    
    return $results;
}

private function processSingleOrderServiceability(array $params): array
{
    // dd($params);
    $order = Order::findOrFail($params['order_id']);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pincodeToCheck = $consignee['pincode'];
    $destinationstate = $consignee['state'];

        // Check if Ekart 2KG zone pricing exists for this seller
        // If no data exists, skip this service
        $ekartZoneCheck = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'LogisticProvider' => 'Ekart2KG_selloship',
            'status' => 1
        ])->exists();

        if (!$ekartZoneCheck) {
            return [];
        }

    // Pickup Info
    $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $originPin = $source['pincode'];
    $pickupstate = $source['state'];

    // 🧾 Step 1: Zone Fetch (Case-Insensitive)
    $zoneData = DB::table('pincode_zones')
        ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
        ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
        ->first();
    //   dd($zoneData);
    if (!$zoneData) {
        return [];
    }

    $zone = strtoupper($zoneData->zone);

    // Step 2: Weight & Payment Info
    $weight = (float)$order->package_weight; // grams
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;
    // dd($paymentType);
// dd($order->payment_type);
    // 🧾 Step 3: Get Ekart 2KG Zone Pricing
    $ekartZonePricing = ZonePriceSetting::where([
        'seller_id' => $seller_id,
        'zone' => $zone,
        'LogisticProvider' => 'Ekart2KG_selloship',
        'status' => 1
    ])->first();
// dd($ekartZonePricing);
    if (!$ekartZonePricing) {
        // return [];
    $ekartZonePricing = ZonePriceSetting::where([
    'seller_id' => 14,
    'zone' => $zone,
    'LogisticProvider' => 'Ekart2KG_selloship',
    'status' => 1
     ])->first();
    }

    // 🧾 Step 4: Calculate Ekart 2KG charges based on ZonePriceSetting
    $calculateEkartCharge = function () use ($ekartZonePricing, $weight, $paymentType, $orderAmount) {
        
        if ($paymentType === 'COD') {
            // echo 'cscsc';die;
            // COD Order Logic
            if ($ekartZonePricing->cod_fix_price > 0.00) {
                // Use fixed COD price - NO GST, NO other charges
                $totalPrice = $ekartZonePricing->cod_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable COD price + 18% GST
                $basePrice = $ekartZonePricing->cod_price;
                $prepaid_price = $ekartZonePricing->prepaid_price;
                $basePriceprepaid_price = $basePrice + $prepaid_price;
                $totalWithGST = $basePriceprepaid_price + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        } else {
            // Prepaid Order Logic
            if ($ekartZonePricing->prepaid_fix_price > 0.00) {
                // Use fixed Prepaid price - NO GST, NO other charges
                $totalPrice = $ekartZonePricing->prepaid_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable Prepaid price + 18% GST
                $basePrice = $ekartZonePricing->prepaid_price;
                $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        }
    };

    // 🧮 Step 5: Calculate final charges for Ekart 2KG
    $charges = $calculateEkartCharge();
    

    return [[
        'serviceabilityId' => $pincodeToCheck,
        'courierName'      => 'selloshipEkart2KG',
        'courierCharge'    => $charges['courierCharge'],
        'freightCharges'   => $charges['freightCharges'],
        'codCharge'        => $charges['codCharge'],
        'zone'             => $zone,
        'minWeight'        => $weight,
        'volWeight'        => $weight,
    ]];
}




public function getServiceability(array $params): array
{
    // dd($params);
    $order = Order::findOrFail($params['order_id']);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pincodeToCheck = $consignee['pincode'];
    $destinationstate = $consignee['state'];

        // Check if Ekart 2KG zone pricing exists for this seller
        // If no data exists, skip this service
        $ekartZoneCheck = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'LogisticProvider' => 'Ekart2KG_selloship',
            'status' => 1
        ])->exists();

        if (!$ekartZoneCheck) {
            return [];
        }

    // Pickup Info
    $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $originPin = $source['pincode'];
    $pickupstate = $source['state'];

    // 🧾 Step 1: Zone Fetch (Case-Insensitive)
    $zoneData = DB::table('pincode_zones')
        ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
        ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
        ->first();
    //   dd($zoneData);
    if (!$zoneData) {
        return [];
    }

    $zone = strtoupper($zoneData->zone);

    // Step 2: Weight & Payment Info
    $weight = (float)$order->package_weight; // grams
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;
    // dd($paymentType);
// dd($order->payment_type);
    // 🧾 Step 3: Get Ekart 2KG Zone Pricing
    $ekartZonePricing = ZonePriceSetting::where([
        'seller_id' => $seller_id,
        'zone' => $zone,
        'LogisticProvider' => 'Ekart2KG_selloship',
        'status' => 1
    ])->first();
// dd($ekartZonePricing);
    if (!$ekartZonePricing) {
        // return [];
    $ekartZonePricing = ZonePriceSetting::where([
    'seller_id' => 14,
    'zone' => $zone,
    'LogisticProvider' => 'Ekart2KG_selloship',
    'status' => 1
     ])->first();
    }

    // 🧾 Step 4: Calculate Ekart 2KG charges based on ZonePriceSetting
    $calculateEkartCharge = function () use ($ekartZonePricing, $weight, $paymentType, $orderAmount) {
        
        if ($paymentType === 'COD') {
            // echo 'cscsc';die;
            // COD Order Logic
            if ($ekartZonePricing->cod_fix_price > 0.00) {
                // Use fixed COD price - NO GST, NO other charges
                $totalPrice = $ekartZonePricing->cod_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable COD price + 18% GST
                $basePrice = $ekartZonePricing->cod_price;
                $prepaid_price = $ekartZonePricing->prepaid_price;
                $basePriceprepaid_price = $basePrice + $prepaid_price;
                $totalWithGST = $basePriceprepaid_price + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        } else {
            // Prepaid Order Logic
            if ($ekartZonePricing->prepaid_fix_price > 0.00) {
                // Use fixed Prepaid price - NO GST, NO other charges
                $totalPrice = $ekartZonePricing->prepaid_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable Prepaid price + 18% GST
                $basePrice = $ekartZonePricing->prepaid_price;
                $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        }
    };

    // 🧮 Step 5: Calculate final charges for Ekart 2KG
    $charges = $calculateEkartCharge();
    

    return [[
        'serviceabilityId' => $pincodeToCheck,
        'courierName'      => 'selloshipEkart2KG',
        'courierCharge'    => $charges['courierCharge'],
        'freightCharges'   => $charges['freightCharges'],
        'codCharge'        => $charges['codCharge'],
        'zone'             => $zone,
        'zone_courier_name' => 'selloshipEkart2KG',
        'minWeight'        => $weight,
        'volWeight'        => $weight,
    ]];
}





protected function fetchAuthToken()
{

        $resp = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://selloship.com/api/lock_actvs/channels/authToken', [
            'username' => 'Bashu@shipxpeed.com',
            'password' => 'Selloship@123',
        ]);

        $json = $resp->json();
        // dd($json);
        if (! $resp->successful() || empty($json['token'])) {
            throw new \RuntimeException("XpressBees login failed: {$resp->body()}");
        }

        return $json['token'];
    // });
}




public function assignOrder($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'ThirdParty';
    // $api_token = $params['api_token'] ?? null;
    $api_token = $this->fetchAuthToken();
        // dd($api_token);
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    if (!$api_token) {
        return ['status' => false, 'message' => 'API token is required.'];
    }
    // Wallet balance check
    $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
    $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
    $walletBalance = $credit - $debit;

    $order = Order::where('id', $order_id)->first();
    if (!$order) {
        return ['status' => false, 'message' => 'Order not found.'];
    }

    if (
        $seller->negative_balance != '1' &&
        ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
    ) {
        return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    }

    // Decode pickup & consignee
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // Prepare items array for the API
    $items = [];
    if ($orderItems && is_array($orderItems)) {
        // echo 'ssx';die;
        foreach ($orderItems as $item) {
            $items[] = [
                "name" => $item['name'] ?? $order->product_name ?? 'Product',
                "skuCode" => $item['sku'] ?? $item['skuCode'] ?? '',
                "quantity" => (int)($item['qty'] ?? 0),
                "itemPrice" => (float)($item['price'] ?? $item['itemPrice'] ?? 0),
                "description" => $item['description'] ?? 'Product description'
            ];
        }
    } else {
                // echo 'sscscscsx';die;

        // Default item if no items found
        $items[] = [
            "name" => $order->product_name ?? 'Product',
            "skuCode" => "",
            "quantity" => 1,
            "itemPrice" => (float)$order->collectable_amount,
            "description" => "Product description"
        ];
    }

    // Prepare the waybill API payload
    $waybillPayload = [
        "Shipment" => [
            "items" => $items,
            "height" => (string)($order->package_height ?? 10),
            "length" => (string)($order->package_length ?? 10),
            "weight" => (string)max(0.5, ($order->package_weight ?? 500) / 1000), // Convert to kg
            "breadth" => (string)($order->package_breadth ?? 10),
            "orderCode" => $order->order_number,
            "orderDate" => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
            "channelCode" => "ONLINE",
            "channelName" => $seller->business_name ?? "Store",
            "invoiceCode" => $order->order_number,
            "numberOfBoxes" => 1,
            "fullFilllmentTat" => Carbon::now()->addDays(3)->format('Y-m-d H:i:s')
        ],
        "paymentMode" => strtoupper($order->payment_type) === 'COD' ? 'COD' : 'PREPAID',
        "serviceType" => "Standard",
        "totalAmount" => (string)$order->collectable_amount,
        "currencyCode" => "INR",
        "collectableAmount" => strtoupper($order->payment_type) === 'COD' ? (string)$order->collectable_amount : "0",
        "returnShipmentFlag" => false,
        "pickupAddressDetails" => [
            "city" => $pickup['city'],
            "name" => $pickup['name'],
            "phone" => $pickup['phone'],
            "state" => $pickup['state'],
            "country" => "India",
            "pincode" => (string)$pickup['pincode'],
            "address1" => $pickup['address']
        ],
        "returnAddressDetails" => [
            "city" => $pickup['city'],
            "name" => $pickup['name'],
            "phone" => $pickup['phone'],
            "state" => $pickup['state'],
            "country" => "India",
            "pincode" => (string)$pickup['pincode'],
            "address1" => $pickup['address']
        ],
        "deliveryAddressDetails" => [
            "city" => $consignee['city'],
            "name" => $consignee['name'],
            "phone" => $consignee['phone'],
            "state" => $consignee['state'],
            "country" => "India",
            "pincode" => (string)$consignee['pincode'],
            "address1" => $consignee['address']
        ]
    ];
        // dd($waybillPayload);
    try {
        $url = "https://selloship.com/api/lock_actvs/channels/waybill"; // Replace with actual API endpoint
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $api_token,
        ])->post($url, $waybillPayload);
      
        $responseData = $response->json();
        // dd($responseData);
        if (!$response->successful() || ($responseData['status'] ?? '') !== 'SUCCESS') {
            return [
                'status' => false, 
                'message' => 'Third party API order creation failed', 
                'data' => $responseData
            ];
        }

        if ($responseData['status'] === 'SUCCESS' && !empty($responseData['waybill'])) {
            $awb = $responseData['waybill'];

            // Update order
            $order->courier_id = strtolower($provider_name);
            $order->all_courier_name = $provider_name;
            $order->awb_number = $awb;
            $order->shipping_date = now()->format('Y-m-d');
            $order->save();

            // Deduct from wallet
            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order created via third party API'
            ]);

            return [
                'status' => true,
                'message' => 'Order successfully assigned to ' . $provider_name,
                'couriername' => strtolower($provider_name),
                'awb_number' => $awb,
                'label_url' => $responseData['shippingLabel'] ?? null,
                'route_code' => $responseData['routingCode'] ?? null,
                'tracking_status' => 'Created',
                'courier_name' => $responseData['courierName'] ?? $provider_name
            ];
        }

        return [
            'status' => false,
            'message' => 'Invalid response from third party API',
            'data' => $responseData
        ];

    } catch (\Exception $e) {
        // dd($e->getMessage());
        Log::error('Third party API assignment failed: ' . $e->getMessage());
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage()
        ];
    }
}




public function cancelShipment($awb)
{
    try {
        // NEW Waybill Cancellation API URL (update base URL)
        $url = "https://selloship.com/api/lock_actvs/channels/cancel";

        // Your API Token
    $api_token = $this->fetchAuthToken();

        $seller = Auth::guard('seller')->user();
        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found for AWB: ' . $awb,
                'responseCode' => 404
            ];
        }

        // New payload
        $payload = [
            "waybill" => $awb
        ];

        // Make POST request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $apiToken,
        ])->post($url, $payload);

        $responseData = $response->json();

        // Check for SUCCESS
        if ($response->successful() && ($responseData['status'] ?? '') === 'SUCCESS') {

            // Update order
            $order->order_status = 'cancelled';
            $order->save();

            // Refund seller
            Recharge::create([
                'seller_id'   => $seller->id,
                'type'        => 'Credit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
                'description' => 'Order cancelled via Waybill API',
            ]);

            return [
                'status' => true,
                'message' => $responseData['errorMessage'] ?? 'Shipment cancelled successfully.',
                'awb_number' => $awb,
                'response' => $responseData,
                'responseCode' => $response->status(),
            ];
        }

        // Failure scenario
        return [
            'status' => false,
            'message' => $responseData['errorMessage'] ?? 'Failed to cancel shipment.',
            'response' => $responseData,
            'responseCode' => $response->status(),
        ];

    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage(),
        ];
    }
}


// public function cancelShipment($awb)
// {
//     try {
//         // DTDC Production API URL
//         $url = "http://pxapi.dtdc.in/api/customer/integration/consignment/cancel";

//         // DTDC API credentials (replace with actual)
//         $apiKey = "a74cd3ae095ad603dc6d506fb30dcf";
//         $customerCode = "GL11173"; // Your DTDC customer code

//         $seller = Auth::guard('seller')->user();
//         $order = Order::where('awb_number', $awb)->first();

//         if (!$order) {
//             return [
//                 'status' => false,
//                 'message' => 'Order not found for AWB: ' . $awb,
//                 'responseCode' => 404
//             ];
//         }

//         // Prepare request payload for a single AWB
//         $payload = [
//             "AWBNo" => [$awb],
//             "customerCode" => $customerCode
//         ];

//         // Send POST request
//         $response = Http::withHeaders([
//             'Content-Type' => 'application/json',
//             'api-key' => $apiKey,
//         ])->post($url, $payload);

//         $responseData = $response->json();
//             // dd($responseData);
//         // Check if cancellation succeeded
//         if ($response->successful() && ($responseData['status'] ?? '') === 'OK' && ($responseData['success'] ?? false)) {
//             $consignment = $responseData['successConsignments'][0] ?? null;

//             if ($consignment['success'] ?? false) {
//                 // Update order
//                 $order->order_status = 'cancelled';
//                 $order->save();

//                 // Refund seller
//                 Recharge::create([
//                     'seller_id'   => $seller->id,
//                     'type'        => 'Credit',
//                     'amount'      => $order->seller_amount_walate,
//                     'status'      => 1,
//                     'description' => 'Order cancelled',
//                 ]);

//                 return [
//                     'status' => true,
//                     'message' => 'DTDC shipment cancelled successfully.',
//                     'awb_number' => $awb,
//                     'response' => $responseData,
//                     'responseCode' => $response->status(),
//                 ];
//             }
//         }

//         // If cancellation failed
//         return [
//             'status' => false,
//             'message' => $responseData['error'] ?? 'Failed to cancel shipment.',
//             'response' => $responseData,
//             'responseCode' => $response->status(),
//         ];

//     } catch (\Exception $e) {
//         return [
//             'status' => false,
//             'message' => 'API Request Failed: ' . $e->getMessage(),
//         ];
//     }
// }






    public function fetchNdrData()
    {
        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->post('https://shipment.xpressbees.com/api/ndr', [
                'verify'  => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . "cscscs",
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'body' => '',
            ]);

            $data = json_decode($response->getBody(), true);
            // dd($data);

            if (!empty($data['status']) && $data['status'] === true) {
                // You can store this in DB or pass to view
                return response()->json([
                    'status' => true,
                    'ndr_data' => $data['data']
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'NDR API call failed',
                    'response' => $data
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'API Request failed: ' . $e->getMessage()
            ]);
        }
    }

    
public function reversegetServiceability(array $params): array
{
       if (!empty($params)) {
        return [];
    }
    $order = Order::findOrFail($params['order_id']);

    // Consignee Info
    $consignee = is_string($order->consignee) 
        ? json_decode($order->consignee, true) 
        : $order->consignee;

    $destinationPin = $consignee['pincode'];

    // Pickup Info
    $source = is_string($order->pickup) 
        ? json_decode($order->pickup, true) 
        : $order->pickup;

    $originPin = $source['pincode'];

    // Seller Price Settings
    $PriceSetting = PriceSetting::where([
        'seller_id' => $order->seller_id,
        'LogisticProvider' => 'Delhivery'
    ])->first();

    // Disable check
    if ($PriceSetting && $PriceSetting->status == '0') {
        return [];
    }

    // Weight & Payment
    $weight = (float)$order->package_weight;
    $orderAmount = $order->collectable_amount;
    $paymentType = $order->payment_type === 'cod' ? 'COD' : 'Pre-paid';

    // Seller Settings
    $sellerPercentage = $PriceSetting->shipping_charge ?? 30;
    $codChargePercent = $PriceSetting->cod_charge_parsent ?? 1.9;
    $codChargeFixed   = $PriceSetting->cod_charge ?? 32;

    // ------------------------------------
    // FIXED RATE FOR ALL – ONLY ONE SLAB
    // ------------------------------------
    $baseRate = 100; // Fixed base rate

    // COD + GST + Seller Margin Calculation
    $applyCharges = function ($freight_charges) use (
        $PriceSetting, $sellerPercentage, $codChargePercent, $codChargeFixed,
        $paymentType, $orderAmount, $weight
    ) {

        // 1) If seller has fixed courier rate
        if ($PriceSetting && $PriceSetting->fixed_courier_price > 0) {

            $weightInKg = $weight / 1000;
            $weightMultiplier = max(1, ceil($weightInKg / 0.5));

            $freightWithGST = $PriceSetting->fixed_courier_price * $weightMultiplier;
            $freightWithSeller = 10 * $weightMultiplier;
            $codCharge = 10 * $weightMultiplier;

            return [
                'courierCharge'  => round($freightWithGST, 2),
                'freightCharges' => round($freightWithSeller, 2),
                'codCharge'      => round($codCharge, 2),
            ];
        }

        // 2) Normal calculation
        $freight = $freight_charges * 1.10; // Add 10%
        $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);

        $codCharge = 0;
        if ($paymentType === 'COD') {
            $codCharge = $orderAmount > 1400
                ? ($orderAmount * $codChargePercent / 100)
                : $codChargeFixed;
        }

        $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
        $freightWithGST = $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);

        return [
            'courierCharge'  => round($freightWithGST, 2),
            'freightCharges' => round($freightWithSeller, 2),
            'codCharge'      => round($codCharge, 2),
        ];
    };

    // Calculate Charges
    $charges = $applyCharges($baseRate);

    // ----------------------
    // FINAL SINGLE RESPONSE
    // ----------------------
    return [[
        'serviceabilityId' => $destinationPin,
        'courierName'      => 'Delhivery Reverse',
        'courierCharge'    => $charges['courierCharge'],
        'freightCharges'   => $charges['freightCharges'],
        'codCharge'        => $charges['codCharge'],
        'minWeight'        => $weight,
        'volWeight'        => $weight,
    ]];
}























public function assignOrderbulk($params)
{
    // Handle bulk order assignment
    if (isset($params['order_ids']) && is_array($params['order_ids'])) {
        return $this->processBulkOrderAssignment($params);
    }
    
    // Handle single order (backward compatibility)
    if (isset($params['order_id'])) {
        return $this->processSingleOrderAssignmentBulk($params);
    }
    
    return ['status' => false, 'message' => 'No valid order data provided.'];
}

private function processBulkOrderAssignment($params)
{
    $orderIds = $params['order_ids'];
    $provider_name = $params['provider_name'] ?? 'ThirdParty';
    $results = [];
    $successCount = 0;
    $failureCount = 0;

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    // Fetch auth token once for all orders
    try {
        $api_token = $this->fetchAuthToken();
        if (!$api_token) {
            return ['status' => false, 'message' => 'Failed to obtain API token.'];
        }
    } catch (\Exception $e) {
        return ['status' => false, 'message' => 'API token fetch failed: ' . $e->getMessage()];
    }

    foreach ($orderIds as $orderId) {
        try {
            $singleParams = [
                'order_id' => $orderId,
                'provider_name' => $provider_name,
                'api_token' => $api_token
            ];
            
            $result = $this->processSingleOrderAssignmentBulk($singleParams);
            
            if ($result['status']) {
                $successCount++;
                $results[] = "Order {$orderId}: Success - AWB: " . ($result['awb_number'] ?? 'N/A');
            } else {
                $failureCount++;
                $results[] = "Order {$orderId}: Failed - " . $result['message'];
            }
            
        } catch (\Exception $e) {
            $failureCount++;
            $results[] = "Order {$orderId}: Exception - " . $e->getMessage();
        }
    }

    return [
        'status' => $successCount > 0,
        'message' => "Bulk assignment completed. Success: {$successCount}, Failed: {$failureCount}",
        'couriername' => strtolower($provider_name),
        'results' => $results,
        'success_count' => $successCount,
        'failure_count' => $failureCount
    ];
}

private function processSingleOrderAssignmentBulk($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'ThirdParty';
    $api_token = $params['api_token'] ?? $this->fetchAuthToken();

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    if (!$api_token) {
        return ['status' => false, 'message' => 'API token is required.'];
    }

    // Wallet balance check
    $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
    $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
    $walletBalance = $credit - $debit;

    $order = Order::where('id', $order_id)->first();
    if (!$order) {
        return ['status' => false, 'message' => 'Order not found.'];
    }

    if (
        $seller->negative_balance != '1' &&
        ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
    ) {
        return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    }

    // Decode pickup & consignee
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // Prepare items array for the API
    $items = [];
    if ($orderItems && is_array($orderItems)) {
        foreach ($orderItems as $item) {
            $items[] = [
                "name" => $item['name'] ?? $order->product_name ?? 'Product',
                "skuCode" => $item['sku'] ?? $item['skuCode'] ?? '',
                "quantity" => (int)($item['qty'] ?? 0),
                "itemPrice" => (float)($item['price'] ?? $item['itemPrice'] ?? 0),
                "description" => $item['description'] ?? 'Product description'
            ];
        }
    } else {
        // Default item if no items found
        $items[] = [
            "name" => $order->product_name ?? 'Product',
            "skuCode" => "",
            "quantity" => 1,
            "itemPrice" => (float)$order->collectable_amount,
            "description" => "Product description"
        ];
    }

    // Prepare the waybill API payload
    $waybillPayload = [
        "Shipment" => [
            "items" => $items,
            "height" => (string)($order->package_height ?? 10),
            "length" => (string)($order->package_length ?? 10),
            "weight" => (string)max(0.5, ($order->package_weight ?? 500) / 1000), // Convert to kg
            "breadth" => (string)($order->package_breadth ?? 10),
            "orderCode" => $order->order_number,
            "orderDate" => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
            "channelCode" => "ONLINE",
            "channelName" => $seller->business_name ?? "Store",
            "invoiceCode" => $order->order_number,
            "numberOfBoxes" => 1,
            "fullFilllmentTat" => Carbon::now()->addDays(3)->format('Y-m-d H:i:s')
        ],
        "paymentMode" => strtoupper($order->payment_type) === 'COD' ? 'COD' : 'PREPAID',
        "serviceType" => "Standard",
        "totalAmount" => (string)$order->collectable_amount,
        "currencyCode" => "INR",
        "collectableAmount" => strtoupper($order->payment_type) === 'COD' ? (string)$order->collectable_amount : "0",
        "returnShipmentFlag" => false,
        "pickupAddressDetails" => [
            "city" => $pickup['city'],
            "name" => $pickup['name'],
            "phone" => $pickup['phone'],
            "state" => $pickup['state'],
            "country" => "India",
            "pincode" => (string)$pickup['pincode'],
            "address1" => $pickup['address']
        ],
        "returnAddressDetails" => [
            "city" => $pickup['city'],
            "name" => $pickup['name'],
            "phone" => $pickup['phone'],
            "state" => $pickup['state'],
            "country" => "India",
            "pincode" => (string)$pickup['pincode'],
            "address1" => $pickup['address']
        ],
        "deliveryAddressDetails" => [
            "city" => $consignee['city'],
            "name" => $consignee['name'],
            "phone" => $consignee['phone'],
            "state" => $consignee['state'],
            "country" => "India",
            "pincode" => (string)$consignee['pincode'],
            "address1" => $consignee['address']
        ]
    ];

    try {
        $url = "https://selloship.com/api/lock_actvs/channels/waybill";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $api_token,
        ])->post($url, $waybillPayload);
      
        $responseData = $response->json();

        if (!$response->successful() || ($responseData['status'] ?? '') !== 'SUCCESS') {
            return [
                'status' => false, 
                'message' => 'Third party API order creation failed', 
                'data' => $responseData
            ];
        }

        if ($responseData['status'] === 'SUCCESS' && !empty($responseData['waybill'])) {
            $awb = $responseData['waybill'];

            // Update order
            $order->courier_id = strtolower($provider_name);
            $order->all_courier_name = $provider_name;
            $order->awb_number = $awb;
            $order->shipping_date = now()->format('Y-m-d');
            $order->save();

            // Deduct from wallet
            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order created via third party API'
            ]);

            return [
                'status' => true,
                'message' => 'Order successfully assigned to ' . $provider_name,
                'couriername' => strtolower($provider_name),
                'awb_number' => $awb,
                'label_url' => $responseData['shippingLabel'] ?? null,
                'route_code' => $responseData['routingCode'] ?? null,
                'tracking_status' => 'Created',
                'courier_name' => $responseData['courierName'] ?? $provider_name
            ];
        }

        return [
            'status' => false,
            'message' => 'Invalid response from third party API',
            'data' => $responseData
        ];

    } catch (\Exception $e) {
        Log::error('Third party API assignment failed: ' . $e->getMessage());
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage()
        ];
    }
}




}
