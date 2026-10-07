<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\PriceSetting;

class SmartshipService implements CourierServiceInterface
{


    public function getToken()
    {
        $response = Http::asForm()->post('https://oauth.smartship.in/loginToken.php', [
            "username" => "Shipxpeed@gmail.com",
            "password" => md5("Shipxpeed@123456"),
            "client_id" => "CS3MZ0W48HWKCE0QUBWCI4HLXG8ED",
            "client_secret" => "3&LX^PZY&JZ1_VU2*M+(J9&RLK63J6T%$",
            "grant_type" => "password"
        ]);

        $data = $response->json();
        // dd($data);
        return $data['access_token'] ?? null;
    }



public function registerHubs_bulk(array $hubList): array
{
    $results = [];
    $token = $this->getToken(); // Token ek hi baar lena hai

    if (!$token) {
        return [['error' => 'Token not generated']];
    }

    // dd($hubList);
    foreach ($hubList as $hubDetails) {
        // dd($hubDetails);
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->post('https://api.smartship.in/v2/app/Fulfillmentservice/hubRegistration', [
            'hub_details' => $hubDetails
        ]);

        $json = $response->json();

        $result = [
            'hub_name' => $hubDetails['hub_name'] ?? null,
            'status'   => 'failed',
            'hub_id'   => null,
            'message'  => $json['message'] ?? 'Unknown error',
        ];

        if ($response->successful() && $json['status'] == 1 && isset($json['data']['hub_id'])) {
            $result['status'] = 'registered';
            $result['hub_id'] = $json['data']['hub_id'];
            $result['message'] = 'Hub registered successfully';
        } elseif (
            isset($json['data']['message']['info']) &&
            $json['data']['message']['info'] === 'Hub already registered!' &&
            isset($json['data']['message']['registered_hub_id'])
        ) {
            $result['status'] = 'already_registered';
            $result['hub_id'] = $json['data']['message']['registered_hub_id'];
            $result['message'] = 'Hub already registered';
        }

        $results[] = $result;
    }

    return $results;
}

public function getServiceability_bulk(array $orders): array
{
    $token = $this->getToken();
    if (!$token) {
        return [['error' => 'Token not generated']];
    }

    $results = [];
    $hubMap = [];
    $hubList = [];

    // Step 1: Collect all hubs
    foreach ($orders as $params) {
        $order = Order::find($params['order_id']);
        if (!$order) continue;

        $pickupdata = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

        $hubKey = $pickupdata['pincode']; // Can also use full address to uniquely identify
        if (!isset($hubMap[$hubKey])) {
            $hubMap[$hubKey] = $pickupdata;
            $hubList[] = [
                "hub_name"         => $pickupdata['warehouse_name'],
                "pincode"          => $pickupdata['pincode'],
                "city"             => $pickupdata['city'],
                "state"            => $pickupdata['state'],
                "address1"         => $pickupdata['address'],
                "address2"         => $pickupdata['address_2'],
                "hub_phone"        => $pickupdata['phone'],
                "delivery_type_id" => 2
            ];
        }
    }

    // Step 2: Register all hubs in bulk
    $registeredHubs = $this->registerHubs_bulk($hubList);

    // Map pincode to hub_id
    $pincodeToHubId = [];
    foreach ($registeredHubs as $hub) {
        if (isset($hub['hub_id']) && isset($hub['hub_name'])) {
            foreach ($hubMap as $pin => $data) {
                if ($hub['hub_name'] === $data['warehouse_name']) {
                    $pincodeToHubId[$pin] = $hub['hub_id'];
                }
            }
        }
    }

    // Step 3: Process each order for serviceability
    foreach ($orders as $params) {
        try {
            $order = Order::find($params['order_id']);
            if (!$order) {
                $results[] = [
                    'order_id' => $params['order_id'],
                    'error'    => 'Order not found'
                ];
                continue;
            }

            $pickupdata = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
            $pickupPincode = $pickupdata['pincode'];
            $hubId = $pincodeToHubId[$pickupPincode] ?? null;

            if (!$hubId) {
                $results[] = [
                    'order_id' => $params['order_id'],
                    'error'    => 'Hub ID not found'
                ];
                continue;
            }

            $payload = [
                "mid"            => 13374639,
                "requestOrderId" => $params['order_id'],
                "fromPincode"    => $params['origin'],
                "toPincode"      => $params['destination'],
                "hubId"          => $hubId,
                "weight"         => $params['weight'],
                "height"         => $params['height'],
                "width"          => $params['breadth'],
                "packageLength"  => $params['length'],
                "orderType"      => 1,
                "paymentType"    => $params['payment_type'],
                "orderValue"     => $params['order_amount'],
            ];

            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->post('https://api.smartship.in/v1/Merchantcarrierlist', $payload);

            if (!$resp->successful()) {
                $results[] = [
                    'order_id' => $params['order_id'],
                    'error'    => 'API request failed'
                ];
                continue;
            }

            $jsonText = $resp->json('text');
            $json = json_decode($jsonText, true);

            if (empty($json['status']) || !$json['status'] || empty($json['data']['carrier_info'])) {
                $results[] = [
                    'order_id' => $params['order_id'],
                    'error'    => 'No carrier data'
                ];
                continue;
            }

            // Save hub_id to order
            $order->shipper_hub_id = $hubId;
            $order->save();

            $orderAmount = $order->collectable_amount;
            $paymentType = $order->payment_type;
            $sellerId = $order->seller_id;
            $carrierInfo = array_values($json['data']['carrier_info']);

            $services = collect($carrierInfo)->map(function ($carrier) use ($orderAmount, $paymentType, $sellerId, $params) {
                $isBlueDart = str_contains($carrier['carrier_name'], 'Bluedart- Surface');
                $isDTDC = str_contains($carrier['carrier_name'], 'DTDC-SMART');

                $PriceSetting = PriceSetting::where([
                    'seller_id' => $sellerId,
                    'LogisticProvider' => $isBlueDart ? 'Blue Dart' : 'DTDC'
                ])->first();

                $sellerPercentage = $PriceSetting?->shipping_charge ?? 70;
                $codChargePercent = $PriceSetting?->cod_charge_parsent ?? 1.9;
                $codChargeFixed = $PriceSetting?->cod_charge ?? 32;

                // $freight = $carrier['cost'] ?? 0;
                        $freight = ($carrier['cost'] ?? 0) * 1.10;

                $codCharge = ($paymentType === 'cod')
                    ? ($orderAmount > 1400
                        ? ($orderAmount * $codChargePercent / 100)
                        : $codChargeFixed)
                    : 0;

                $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
                $freightWithCod = $freightWithSeller + $codCharge;
                $freightWithGST = $freightWithCod * 1.18;

                return [
                    'serviceabilityId' => $carrier['carrier_id'],
                    'courierName'      => $carrier['carrier_name'],
                    'courierCharge'    => round($freightWithGST, 2),
                    'freightCharges'   => round($freightWithSeller, 2),
                    'codCharge'        => round($codCharge, 2),
                    'minWeight'        => $params['weight'],
                    'volWeight'        => $params['weight'],
                    'carrierType'      => $isBlueDart ? 'blue_dart' : ($isDTDC ? 'dtdc' : 'other'),
                ];
            })->values()->toArray();

            $results[] = [
                'order_id' => $params['order_id'],
                'services' => $services
            ];
        } catch (\Exception $e) {
            $results[] = [
                'order_id' => $params['order_id'],
                'error'    => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    return $results;
}





// public function registerHubs_bulk(array $hubList): array
// {
//     $results = [];
//     $token = $this->getToken(); // Token ek hi baar lena hai
// //    dd($token);
//     if (!$token) {
//         return ['error' => 'Token not generated'];
//     }

//     dd($hubList);
//     foreach ($hubList as $hubDetails) {
//         dd($hubDetails);
//         $response = Http::withHeaders([
//             'Authorization' => "Bearer {$token}",
//             'Accept'        => 'application/json',
//             'Content-Type'  => 'application/json',
//         ])->post('https://api.smartship.in/v2/app/Fulfillmentservice/hubRegistration', [
//             'hub_details' => $hubDetails
//         ]);

//         $json = $response->json();
//           dd($json);
//         // Default result
//         $result = [
//             'hub_name'  => $hubDetails['hub_name'] ?? null,
//             'status'    => 'failed',
//             'hub_id'    => null,
//             'message'   => $json['message'] ?? 'Unknown error',
//         ];

//         // Case 1: Success
//         if ($response->successful() && $json['status'] == 1 && isset($json['data']['hub_id'])) {
//             $result['status'] = 'registered';
//             $result['hub_id'] = $json['data']['hub_id'];
//             $result['message'] = 'Hub registered successfully';
//         }
//         // Case 2: Already Registered
//         elseif (
//             isset($json['data']['message']['info']) &&
//             $json['data']['message']['info'] === 'Hub already registered!' &&
//             isset($json['data']['message']['registered_hub_id'])
//         ) {
//             $result['status'] = 'already_registered';
//             $result['hub_id'] = $json['data']['message']['registered_hub_id'];
//             $result['message'] = 'Hub already registered';
//         }

//         // Add to final results
//         $results[] = $result;
//     }

//     return $results;
// }








//     public function getServiceability_bulk(array $orders): array
// {
//     $token = $this->getToken();
//     if (!$token) {
//         return [];
//     }

//     $results = [];

//     foreach ($orders as $params) {
//         try {
//             $order = Order::find($params['order_id']);
//             if (!$order) {
//                 $results[] = [
//                     'order_id' => $params['order_id'],
//                     'error'    => 'Order not found'
//                 ];
//                 continue;
//             }

//             $pickupdata = is_string($order->pickup)
//                 ? json_decode($order->pickup, true)
//                 : $order->pickup;

//             $hubDetails = [
//                 "hub_name"         => $pickupdata['warehouse_name'],
//                 "pincode"          => $pickupdata['pincode'],
//                 "city"             => $pickupdata['city'],
//                 "state"            => $pickupdata['state'],
//                 "address1"         => $pickupdata['address'],
//                 "address2"         => $pickupdata['address_2'],
//                 "hub_phone"        => $pickupdata['phone'],
//                 "delivery_type_id" => 2
//             ];
            

//             // dd($hubDetails);
//             $hubId = $this->registerHubs_bulk($hubDetails);
//             dd($hubId);
//             if (!$hubId) {
//                 $results[] = [
//                     'order_id' => $params['order_id'],
//                     'error'    => 'Hub registration failed'
//                 ];
//                 continue;
//             }

//             $payload = [
//                 "mid"            => 13374639,
//                 "requestOrderId" => $params['order_id'],
//                 "fromPincode"    => $params['origin'],
//                 "toPincode"      => $params['destination'],
//                 "hubId"          => $hubId,
//                 "weight"         => $params['weight'],
//                 "height"         => $params['height'],
//                 "width"          => $params['breadth'],
//                 "packageLength"  => $params['length'],
//                 "orderType"      => 1,
//                 "paymentType"    => $params['payment_type'],
//                 "orderValue"     => $params['order_amount'],
//             ];

//             $resp = Http::withHeaders([
//                 'Authorization' => "Bearer {$token}",
//                 'Accept'        => 'application/json',
//                 'Content-Type'  => 'application/json',
//             ])->post('https://api.smartship.in/v1/Merchantcarrierlist', $payload);

//             if (!$resp->successful()) {
//                 $results[] = [
//                     'order_id' => $params['order_id'],
//                     'error'    => 'API request failed'
//                 ];
//                 continue;
//             }

//             $jsonText = $resp->json('text');
//             $json = json_decode($jsonText, true);
//              dd($json);
//             if (empty($json['status']) || !$json['status'] || empty($json['data']['carrier_info'])) {
//                 $results[] = [
//                     'order_id' => $params['order_id'],
//                     'error'    => 'No carrier data'
//                 ];
//                 continue;
//             }

//             $order->shipper_hub_id = $hubId;
//             $order->save();

//             $orderAmount = $order->collectable_amount;
//             $paymentType = $order->payment_type;
//             $sellerId = $order->seller_id;
//             $carrierInfo = array_values($json['data']['carrier_info']);

//             $services = collect($carrierInfo)->map(function ($carrier) use ($orderAmount, $paymentType, $sellerId, $params) {
//                 $isBlueDart = str_contains($carrier['carrier_name'], 'Bluedart- Surface');
//                 $isDTDC = str_contains($carrier['carrier_name'], 'DTDC-SMART');

//                 $PriceSetting = PriceSetting::where([
//                     'seller_id' => $sellerId,
//                     'LogisticProvider' => $isBlueDart ? 'Blue Dart' : 'DTDC'
//                 ])->first();

//                 $sellerPercentage = $PriceSetting?->shipping_charge ?? 70;
//                 $codChargePercent = $PriceSetting?->cod_charge_parsent ?? 1.9;
//                 $codChargeFixed = $PriceSetting?->cod_charge ?? 32;

//                 $freight = $carrier['cost'] ?? 0;

//                 $codCharge = 0;
//                 if ($paymentType === 'cod') {
//                     $codCharge = $orderAmount > 1400
//                         ? ($orderAmount * $codChargePercent / 100)
//                         : $codChargeFixed;
//                 }

//                 $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
//                 $freightWithCod = $freightWithSeller + $codCharge;
//                 $freightWithGST = $freightWithCod * 1.18;

//                 return [
//                     'serviceabilityId' => $carrier['carrier_id'],
//                     'courierName'      => $carrier['carrier_name'],
//                     'courierCharge'    => round($freightWithGST, 2),
//                     'freightCharges'   => round($freightWithSeller, 2),
//                     'codCharge'        => round($codCharge, 2),
//                     'minWeight'        => $params['weight'],
//                     'volWeight'        => $params['weight'],
//                     'carrierType'      => $isBlueDart ? 'blue_dart' : ($isDTDC ? 'dtdc' : 'other'),
//                 ];
//             })->values()->toArray();

//             $results[] = [
//                 'order_id' => $params['order_id'],
//                 'services' => $services
//             ];
//         } catch (\Exception $e) {
//             $results[] = [
//                 'order_id' => $params['order_id'],
//                 'error'    => 'Exception: ' . $e->getMessage()
//             ];
//         }
//     }

//     return $results;
// }





public function registerHub(array $hubDetails): ?int
{
    // dd($hubDetails);
    $token = $this->getToken(); // Replace with your actual token method
    if (!$token) {
        return null;
    }

    $response = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ])->post('https://api.smartship.in/v2/app/Fulfillmentservice/hubRegistration', [
        'hub_details' => $hubDetails
    ]);

    $json = $response->json();
    // dd($json); // For debugging, you can remove this after testing

    // ✅ Case 1: Hub registered successfully
    if ($response->successful() && $json['status'] == 1 && isset($json['data']['hub_id'])) {
        return (int) $json['data']['hub_id'];
    }

    // ✅ Case 2: Hub already registered — get existing hub_id
    if (
        isset($json['data']['message']['info']) &&
        $json['data']['message']['info'] === 'Hub already registered!' &&
        isset($json['data']['message']['registered_hub_id'])
    ) {
        return (int) $json['data']['message']['registered_hub_id'];
    }

    // ❌ Any other failure
    // Log::error('Hub Registration Failed', $json);
    return null;
}



public function getServiceability(array $params): array
{
    // Step 1: Get SmartShip Token
    $token = $this->getToken();
    if (!$token) {
        return [];
    }

    $order = Order::findOrFail($params['order_id']);
    $pickupdata = is_string($order->pickup) 
        ? json_decode($order->pickup, true) 
        : $order->pickup;

    // Step 2: Register or get hub ID
    $hubDetails = [
        "hub_name"         => $pickupdata['warehouse_name'],
        "pincode"          => $pickupdata['pincode'],
        "city"             => $pickupdata['city'],
        "state"            => $pickupdata['state'],
        "address1"         => $pickupdata['address'],
        "address2"         => $pickupdata['address_2'],
        "hub_phone"        => $pickupdata['phone'],
        "delivery_type_id" => 2
    ];

    $hubId = $this->registerHub($hubDetails);
    // dd($hubId);
    if (!$hubId) {
        return [];
    }

    // Step 3: Prepare Payload
    $payload = [
        "mid"            => 13374639, // SmartShip Merchant ID
        "requestOrderId" => $params['order_id'],
        "fromPincode"    => $params['origin'],
        "toPincode"      => $params['destination'],
        "hubId"          => $hubId,
        "weight"         => $params['weight'],
        "height"         => $params['height'],
        "width"          => $params['breadth'],
        "packageLength"  => $params['length'],
        "orderType"      => 1, // 1 = forward, 2 = return
        "paymentType"    => $params['payment_type'],
        "orderValue"     => $params['order_amount'],
    ];

    // Step 4: Call SmartShip API
    $resp = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ])->post('https://api.smartship.in/v1/Merchantcarrierlist', $payload);

    // dd($resp->json());
    if (!$resp->successful()) {
        return [];
    }

    $json = $resp->json();
    $jsonText = $resp->json('text');
    $json = json_decode($jsonText, true);
    //  dd($json);
    if (empty($json['status']) || !$json['status'] || empty($json['data']['carrier_info'])) {
        return [];
    }

    $carrierInfo = array_values($json['data']['carrier_info']);
    //  dd($carrierInfo);
    // Step 5: Get order & seller info
    $order->shipper_hub_id = $hubId; 
    $order->save();
    $orderAmount = $order->collectable_amount;
    $paymentType = $order->payment_type;
    $sellerId = $order->seller_id;

    return collect($carrierInfo)->map(function ($carrier) use ($orderAmount, $paymentType, $sellerId, $params) {
        // Determine carrier type and get appropriate pricing
        $isBlueDart = str_contains($carrier['carrier_name'], 'Bluedart- Surface');
        $isDTDC = str_contains($carrier['carrier_name'], 'DTDC-SMART');
        
        if ($isBlueDart) {
            $PriceSetting = PriceSetting::where([
                'seller_id' => $sellerId, 
                'LogisticProvider' => 'Blue Dart'
            ])->first();
        } else {
       $PriceSetting = PriceSetting::where([
                'seller_id' => $sellerId, 
                'LogisticProvider' => 'DTDC'
            ])->first();
        }

        // Fallback to defaults if no specific pricing found
        $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 70;
        $codChargePercent = $PriceSetting ? $PriceSetting->cod_charge_parsent : 1.9;
        $codChargeFixed = $PriceSetting ? $PriceSetting->cod_charge : 32;

        // Calculate charges
        // $freight = $carrier['cost'] ?? 0;
        $freight = ($carrier['cost'] ?? 0) * 1.10;

        $codCharge = 0;
        if ($paymentType === 'cod') {
            $codCharge = $orderAmount > 1400 
                ? ($orderAmount * $codChargePercent / 100) 
                : $codChargeFixed;
        }

        $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
        $freightWithCod = $freightWithSeller + $codCharge;
        $freightWithGST = $freightWithCod * 1.18; // Simplified GST calculation

        return [
            'serviceabilityId' => $carrier['carrier_id'],
            'courierName'      => $carrier['carrier_name'],
            'courierCharge'    => round($freightWithGST, 2),
            'freightCharges'   => round($freightWithSeller, 2),
            'codCharge'        => round($codCharge, 2),
            'minWeight'        => $params['weight'],
            'volWeight'        => $params['weight'],
            'carrierType'      => $isBlueDart ? 'blue_dart' : ($isDTDC ? 'dtdc' : 'other'),
        ];
    })->values()->toArray();
}






 
public function assignOrder($params)
{
    // dd($params);
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'];
    // dd($provider_name);
    $token = $this->getToken();
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

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
    return [
        'status'  => false,
        'message'  => 'Insufficient wallet balance. Please recharge your wallet.',
    ];
}


    // if ($walletBalance < $order->seller_amount_walate || $walletBalance < 150) {
    //     return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    // }

    $orderproduct_name = is_string($order->order_items)
    ? json_decode($order->order_items, true)
    : $order->order_items;
     $product = $orderproduct_name[0]; // Access the first product in the array

    // Check if orderconsignee is a string and decode it
$orderconsignee_details = is_string($order->consignee)
    ? json_decode($order->consignee, true)
    : $order->consignee;

if($order->payment_type == 'cod'){
     $orderamount = $order->collectable_amount;
}else{
     $orderamount = 0;
}

if($provider_name == 'DTDC-SMART'){
 $preferred_carriers = 355;
}else{
     $preferred_carriers = 279;
}

    // Example product details structure (replace with actual order items)
    $productDetails = [
        [
            "client_product_reference_id" => $order->order_number,
            "product_name"                => $product['name'],
            "product_category"            => $product['name'],
            "product_hsn_code"            => $product['sku'],
            "product_quantity"            => $product['qty'],
            "product_invoice_value"       => "300",
            "product_gst_tax_rate"        => "5",
            "product_taxable_value"       => "100",
            "product_sgst_amount"         => "2",
            "product_sgst_tax_rate"       => "2",
            "product_cgst_amount"         => "2",
            "product_cgst_tax_rate"       => "2",
        ]
    ];

    $payload = [
        'request_info' => [
            'client_id' => 'CS3MZ0W48HWKCE0QUBWCI4HLXG8ED',
            'run_type'  => 'create',
        ],
        'orders' => [
            [
                'client_order_reference_id'   => $order->order_number,
                'shipment_type'               => 1,
                'order_collectable_amount'    => $orderamount,
                'total_order_value'           => $order->collectable_amount,
                'payment_type'                => $order->payment_type,

                'package_order_weight'        => $order->package_weight ?? 0,
                'package_order_length'        => $order->package_length ?? 0,
                'package_order_height'        => $order->package_height ?? 0,
                'package_order_width'         => $order->package_breadth ?? 0,

                'shipper_hub_id'              => $order->shipper_hub_id ?? '28329',
                'shipper_gst_no'              => $seller->gst ?? '29ABCDE1234F2Z5',
                'order_invoice_date'          => now()->format('d-m-Y'),
                'order_invoice_number'        => 'INV-' . $order->id,
                'is_return_qc'                => "0",
                'return_reason_id'            => "0",
                'order_meta' => [
                    'preferred_carriers' => [1,2,$preferred_carriers]
                ],
                'product_details' => $productDetails,
                'consignee_details' => [
                    'consignee_name'             =>$orderconsignee_details['name'],
                    'consignee_phone'            => $orderconsignee_details['phone'],
                    'consignee_email'            => "admin@example.com",
                    'consignee_complete_address' => $orderconsignee_details['address_2'],
                    'consignee_pincode'          => $orderconsignee_details['pincode'],
                ],
            ]
        ]
    ];

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post('https://api.smartship.in/v2/app/Fulfillmentservice/orderRegistrationOneStep', $payload);

    $responseData = json_decode($response->getBody(), true);
// dd($responseData);
if (!empty($responseData['status']) && $responseData['status'] == 1) {
    $orderData = $responseData['data']['success_order_details']['orders'][0] ?? null;

if ($orderData) {
    $order = Order::find($order_id);

    $order->awb_number = $orderData['awb_number'] ?? null;
    $order->courier_id = 'smartship'; // or $orderData['carrier_code']
    $order->smarship_courier_id = $provider_name; // or $orderData['carrier_code']
    $order->smartship_tracking_url = $responseData['data']['success_order_details']['shipping_info']['label_url'] ?? null;
    $order->save();

    // Agar awb_number 0 ya null nahi hai tabhi balance deduct karega
    if (!empty($orderData['awb_number']) && $orderData['awb_number'] != 0) {
        Recharge::create([
            'seller_id' => $seller->id,
            'type'      => 'Debit',
            'amount'    => $order->seller_amount_walate,
            'status'    => 1,
        ]);
    }

    return [
        'status'      => true,
        'message'     => 'Order successfully assigned.',
        'couriername' => 'smart',
        'awb_number'  => $orderData['awb_number'] ?? null,
    ];
}


    // if ($orderData) {
    //     $order = Order::find($order_id);

    //     $order->awb_number = $orderData['awb_number'] ?? null;
    //     $order->courier_id = 'smartship'; // or $orderData['carrier_code']
    //      $order->smarship_courier_id = $provider_name; // or $orderData['carrier_code']
    //     $order->smartship_tracking_url = $responseData['data']['success_order_details']['shipping_info']['label_url'] ?? null;
    //     $order->save();

    //     Recharge::create([
    //         'seller_id' => $seller->id,
    //         'type'      => 'Debit',
    //         'amount'    => $order->seller_amount_walate,
    //         'status'    => 1,
    //     ]);

    //     return [
    //         'status'     => true,
    //         'message'    => 'Order successfully assigned.',
    //          'couriername' => 'smart',
    //         'awb_number' => $orderData['awb_number'] ?? null,
    //     ];
    // }




}


        return [
            'status'  => false,
            'message' => 'Smartship API error.',
            'data'    => $responseData,
        ];

    } catch (\Exception $e) {
        return [
            'status'  => false,
            'message' => 'API Request Failed: ' . $e->getMessage(),
        ];
    }
}


public function assignOrder_bulk($params)
{
    $token = $this->getToken();
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    // Check wallet balance for all orders
    $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
    $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
    $walletBalance = $credit - $debit;

    $orderIds = is_array($params['order_id']) ? $params['order_id'] : [$params['order_id']];
    $provider_name = $params['provider_name'] ?? 'DTDC-SMART';
    $preferred_carriers = ($provider_name == 'DTDC-SMART') ? 355 : 279;

    $successOrders = [];
    $failedOrders = [];
    $totalAmount = 0;
    
    // First validate all orders and calculate total amount
    foreach ($orderIds as $orderId) {
        $order = Order::find($orderId);
        if (!$order) {
            $failedOrders[$orderId] = 'Order not found';
            continue;
        }
        $totalAmount += $order->seller_amount_walate;
    }

    // Check if wallet has enough balance for all orders
    if ($walletBalance < $totalAmount || $walletBalance < (150 * count($orderIds))) {
        return [
            'status' => false,
            'message' => 'Insufficient wallet balance for bulk orders. Required: ₹' . $totalAmount . ', Available: ₹' . $walletBalance
        ];
    }

    // Prepare bulk payload
    $bulkPayload = [
        'request_info' => [
            'client_id' => 'CS3MZ0W48HWKCE0QUBWCI4HLXG8ED',
            'run_type'  => 'create',
        ],
        'orders' => []
    ];

    foreach ($orderIds as $orderId) {
        $order = Order::find($orderId);
        if (!$order) continue;

        $orderproduct_name = is_string($order->order_items)
            ? json_decode($order->order_items, true)
            : $order->order_items;
        $product = $orderproduct_name[0] ?? ['name' => '', 'sku' => '', 'qty' => 1];

        $orderconsignee_details = is_string($order->consignee)
            ? json_decode($order->consignee, true)
            : $order->consignee;

        $orderamount = ($order->payment_type == 'cod') ? $order->collectable_amount : 0;

        $bulkPayload['orders'][] = [
            'client_order_reference_id'   => $order->order_number,
            'shipment_type'               => 1,
            'order_collectable_amount'    => $orderamount,
            'total_order_value'           => $order->collectable_amount ?? 0,
            'payment_type'                => $order->payment_type,
            'package_order_weight'        => $order->package_weight ?? 0,
            'package_order_length'        => $order->package_length ?? 0,
            'package_order_height'        => $order->package_height ?? 0,
            'package_order_width'         => $order->package_breadth ?? 0,
            'shipper_hub_id'              => $order->shipper_hub_id ?? '28329',
            'shipper_gst_no'              => $seller->gst ?? '29ABCDE1234F2Z5',
            'order_invoice_date'          => now()->format('d-m-Y'),
            'order_invoice_number'        => 'INV-' . $order->id,
            'is_return_qc'                => "0",
            'return_reason_id'            => "0",
            'order_meta' => [
                'preferred_carriers' => [1, 2, $preferred_carriers]
            ],
            'product_details' => [
                [
                    "client_product_reference_id" => $order->order_number,
                    "product_name"                => $product['name'],
                    "product_category"            => $product['name'],
                    "product_hsn_code"            => $product['sku'],
                    "product_quantity"            => $product['qty'],
                    "product_invoice_value"       => "300",
                    "product_gst_tax_rate"        => "5",
                    "product_taxable_value"       => "100",
                    "product_sgst_amount"         => "2",
                    "product_sgst_tax_rate"       => "2",
                    "product_cgst_amount"         => "2",
                    "product_cgst_tax_rate"       => "2",
                ]
            ],
            'consignee_details' => [
                'consignee_name'             => $orderconsignee_details['name'],
                'consignee_phone'            => $orderconsignee_details['phone'],
                'consignee_email'            => "admin@example.com",
                'consignee_complete_address' => $orderconsignee_details['address_2'],
                'consignee_pincode'          => $orderconsignee_details['pincode'],
            ]
        ];
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post('https://api.smartship.in/v2/app/Fulfillmentservice/orderRegistrationOneStep', $bulkPayload);

        $responseData = json_decode($response->getBody(), true);

        if (!empty($responseData['status']) && $responseData['status'] == 1) {
            $successOrders = $responseData['data']['success_order_details']['orders'] ?? [];
            $failedOrders = $responseData['data']['failed_order_details']['orders'] ?? [];

            // Process successful orders
            foreach ($successOrders as $orderData) {
                $order = Order::where('order_number', $orderData['client_order_reference_id'])->first();
                if ($order) {
                    $order->awb_number = $orderData['awb_number'] ?? null;
                    $order->courier_id = 'smartship';
                    $order->smarship_courier_id = $provider_name;
                    $order->smartship_tracking_url = $responseData['data']['success_order_details']['shipping_info']['label_url'] ?? null;
                    $order->save();

                    Recharge::create([
                        'seller_id' => $seller->id,
                        'type'      => 'Debit',
                        'amount'    => $order->seller_amount_walate,
                        'status'    => 1,
                    ]);
                }
            }

            return [
                'status' => true,
                'message' => 'Bulk order processing completed',
                'data' => [
                    'success_count' => count($successOrders),
                    'failed_count' => count($failedOrders),
                    'success_orders' => $successOrders,
                    'failed_orders' => $failedOrders
                ]
            ];
        }

        return [
            'status'  => false,
            'message' => 'Smartship API error in bulk processing.',
            'data'    => $responseData,
        ];

    } catch (\Exception $e) {
        return [
            'status'  => false,
            'message' => 'API Request Failed: ' . $e->getMessage(),
        ];
    }
}




public function assignOrder_bulkold($params)
{
    $token = $this->getToken();
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    // Check wallet balance for all orders
    $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
    $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
    $walletBalance = $credit - $debit;

    $orderIds = is_array($params['order_id']) ? $params['order_id'] : [$params['order_id']];
    $provider_name = $params['provider_name'] ?? 'DTDC-SMART';
    $preferred_carriers = ($provider_name == 'DTDC-SMART') ? 355 : 279;

    $successOrders = [];
    $failedOrders = [];
    $totalAmount = 0;
    
    // First validate all orders and calculate total amount
    foreach ($orderIds as $orderId) {
        $order = Order::find($orderId);
        if (!$order) {
            $failedOrders[$orderId] = 'Order not found';
            continue;
        }
        $totalAmount += $order->seller_amount_walate;
    }

    // Check if wallet has enough balance for all orders
    if ($walletBalance < $totalAmount || $walletBalance < (150 * count($orderIds))) {
        return [
            'status' => false,
            'message' => 'Insufficient wallet balance for bulk orders. Required: ₹' . $totalAmount . ', Available: ₹' . $walletBalance
        ];
    }

    // Prepare bulk payload
    $bulkPayload = [
        'request_info' => [
            'client_id' => 'CS3MZ0W48HWKCE0QUBWCI4HLXG8ED',
            'run_type'  => 'create',
        ],
        'orders' => []
    ];

    foreach ($orderIds as $orderId) {
        $order = Order::find($orderId);
        if (!$order) continue;

        $orderproduct_name = is_string($order->order_items)
            ? json_decode($order->order_items, true)
            : $order->order_items;
        $product = $orderproduct_name[0] ?? ['name' => '', 'sku' => '', 'qty' => 1];

        $orderconsignee_details = is_string($order->consignee)
            ? json_decode($order->consignee, true)
            : $order->consignee;

        $orderamount = ($order->payment_type == 'cod') ? $order->collectable_amount : 0;

        $bulkPayload['orders'][] = [
            'client_order_reference_id'   => $order->order_number,
            'shipment_type'               => 1,
            'order_collectable_amount'    => $orderamount,
            'total_order_value'           => $order->collectable_amount ?? 0,
            'payment_type'                => $order->payment_type,
            'package_order_weight'        => $order->package_weight ?? 0,
            'package_order_length'        => $order->package_length ?? 0,
            'package_order_height'        => $order->package_height ?? 0,
            'package_order_width'         => $order->package_breadth ?? 0,
            'shipper_hub_id'              => $order->shipper_hub_id ?? '28329',
            'shipper_gst_no'              => $seller->gst ?? '29ABCDE1234F2Z5',
            'order_invoice_date'          => now()->format('d-m-Y'),
            'order_invoice_number'        => 'INV-' . $order->id,
            'is_return_qc'                => "0",
            'return_reason_id'            => "0",
            'order_meta' => [
                'preferred_carriers' => [1, 2, $preferred_carriers]
            ],
            'product_details' => [
                [
                    "client_product_reference_id" => $order->order_number,
                    "product_name"                => $product['name'],
                    "product_category"            => $product['name'],
                    "product_hsn_code"            => $product['sku'],
                    "product_quantity"            => $product['qty'],
                    "product_invoice_value"       => "300",
                    "product_gst_tax_rate"        => "5",
                    "product_taxable_value"       => "100",
                    "product_sgst_amount"         => "2",
                    "product_sgst_tax_rate"       => "2",
                    "product_cgst_amount"         => "2",
                    "product_cgst_tax_rate"       => "2",
                ]
            ],
            'consignee_details' => [
                'consignee_name'             => $orderconsignee_details['name'],
                'consignee_phone'            => $orderconsignee_details['phone'],
                'consignee_email'            => "admin@example.com",
                'consignee_complete_address' => $orderconsignee_details['address_2'],
                'consignee_pincode'          => $orderconsignee_details['pincode'],
            ]
        ];
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post('https://api.smartship.in/v2/app/Fulfillmentservice/orderRegistrationOneStep', $bulkPayload);

        $responseData = json_decode($response->getBody(), true);

        if (!empty($responseData['status']) && $responseData['status'] == 1) {
            $successOrders = $responseData['data']['success_order_details']['orders'] ?? [];
            $failedOrders = $responseData['data']['failed_order_details']['orders'] ?? [];

            // Process successful orders
            foreach ($successOrders as $orderData) {
                $order = Order::where('order_number', $orderData['client_order_reference_id'])->first();
                if ($order) {
                    $order->awb_number = $orderData['awb_number'] ?? null;
                    $order->courier_id = 'smartship';
                    $order->smarship_courier_id = $provider_name;
                    $order->smartship_tracking_url = $responseData['data']['success_order_details']['shipping_info']['label_url'] ?? null;
                    $order->save();

                    Recharge::create([
                        'seller_id' => $seller->id,
                        'type'      => 'Debit',
                        'amount'    => $order->seller_amount_walate,
                        'status'    => 1,
                    ]);
                }
            }

            return [
                'status' => true,
                'message' => 'Bulk order processing completed',
                'data' => [
                    'success_count' => count($successOrders),
                    'failed_count' => count($failedOrders),
                    'success_orders' => $successOrders,
                    'failed_orders' => $failedOrders
                ]
            ];
        }

        return [
            'status'  => false,
            'message' => 'Smartship API error in bulk processing.',
            'data'    => $responseData,
        ];

    } catch (\Exception $e) {
        return [
            'status'  => false,
            'message' => 'API Request Failed: ' . $e->getMessage(),
        ];
    }
}




public function cancelShipment(string $awb): array
{
    $token = $this->getToken();
    $order = Order::where('awb_number', $awb)->first();

    $url = 'http://api.smartship.in/v2/app/Fulfillmentservice/orderCancellation';

    $payload = [
        "orders" => [
            "client_order_reference_ids" => [
                $order->order_number ?? null
            ],
            "request_order_ids" => []
        ]
    ]; 

    $response = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
        'Content-Type'  => 'application/json',
    ])->post($url, $payload);

    $responseData = $response->json();

    if (
        !$response->successful() ||
        ($responseData['status'] ?? 0) != 1 ||
        empty($responseData['data']['order_cancellation_details']['successful']) ||
        !in_array($order->order_number, $responseData['data']['order_cancellation_details']['successful'])
    ) {
        return [
            'error'   => 'SmartShip Cancel API error or order not in success list',
            'status'  => $response->status(),
            'message' => $responseData['message'] ?? 'Unknown error',
        ];
    }

    // Step 5: Update local DB
    $seller = Auth::guard('seller')->user();
    if ($order) {
        $order->order_status = 'cancelled';
        $order->save();

        Recharge::create([
            'seller_id' => $seller->id,
            'type'      => 'Credit',
            'amount'    => $order->seller_amount_walate,
            'status'    => 1,
        ]);
    }

    return [
        'success' => true,
        'message' => 'Order cancelled successfully.',
        'order_id' => $order->order_number,
    ];
}




public function cancelShipmentBulk(array $awbsOrOrderNumbers): array
{
    $token = $this->getToken();
    $seller = Auth::guard('seller')->user();

    // Step 1: Fetch orders by AWB or Order Numbers
    $orders = Order::whereIn('awb_number', $awbsOrOrderNumbers)
                ->get();

    $orderReferenceIds = $orders->pluck('order_number')->filter()->values()->toArray();

    if (empty($orderReferenceIds)) {
        return [
            'status' => false,
            'message' => 'No valid orders found for cancellation.'
        ];
    }

    // Step 2: Build Payload
    $payload = [
        "orders" => [
            "client_order_reference_ids" => $orderReferenceIds,
            "request_order_ids" => []
        ]
    ];

    // Step 3: Send cancellation request
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
        'Content-Type'  => 'application/json',
    ])->post('http://api.smartship.in/v2/app/Fulfillmentservice/orderCancellation', $payload);

    $responseData = $response->json();

    // Step 4: Validate response
    if (
        !$response->successful() ||
        ($responseData['status'] ?? 0) != 1 ||
        empty($responseData['data']['order_cancellation_details']['successful'])
    ) {
        return [
            'status'  => false,
            'message' => 'SmartShip bulk cancellation failed',
            'error'   => $responseData
        ];
    }

    $successfulOrders = $responseData['data']['order_cancellation_details']['successful'] ?? [];
    $failedOrders = [];

    // Step 5: Loop through local orders and update
    foreach ($orders as $order) {
        if (in_array($order->order_number, $successfulOrders)) {
            $order->order_status = 'cancelled';
            $order->save();

            Recharge::create([
                'seller_id' => $seller->id,
                'type'      => 'Credit',
                'amount'    => $order->seller_amount_walate,
                'status'    => 1,
            ]);
        } else {
            $failedOrders[] = $order->order_number;
        }
    }

    return [
        'status'   => true,
        'message'  => count($successfulOrders) . ' orders cancelled successfully via SmartShip.',
        'success'  => $successfulOrders,
        'failed'   => $failedOrders,
        'response' => $responseData
    ];
}




// public function cancelShipment(string $awb): array
// {
//     $url   = "https://shipment.xpressbees.com/api/shipments2/cancel";
//     $token = $this->fetchAuthToken();

//     $response = Http::withHeaders([
//             'Authorization' => "Bearer {$token}",
//             'Accept'        => 'application/json',
//             'Content-Type'  => 'application/json',
//         ])
//         ->post($url, [
//             'awb' => $awb,
//         ]);
//          $responseData = json_decode($response->getBody(), true);
//         //   dd($responseData);
//     if (! $response->successful()) {
//         return [
//             'error'   => 'XpressBees cancel API error',
//             'status'  => $response->status(),
//             'message' => $response->body(),
//         ];
//     }
//     $seller = Auth::guard('seller')->user();
//     $order = Order::where('awb_number', $awb)->first();
//     $order->order_status = 'cancelled';
//     $order->save();
//         Recharge::create([
//                 'seller_id'   => $seller->id,
//                 'type'        => 'Credit',
//                 'amount'      => $order->seller_amount_walate,
//                 'status'      => 1,
//             ]);
// // dd($response);
//     return $response->json();
// }




public function fetchNdrData($params)
{
    try {
        $client = new \GuzzleHttp\Client();
        // $token = $this->fetchAuthToken();

        $response = $client->post('https://shipment.xpressbees.com/api/ndr', [
            'verify'  => false,
            'headers' => [
                'Authorization' => 'Bearer ' . 'asasxaxs',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => [], // ✅ Use this instead of 'body' => ''
        ]);

        $data = json_decode($response->getBody(), true);
        // dd($data);

        if (!empty($data['status']) && $data['status'] === true) {
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




}