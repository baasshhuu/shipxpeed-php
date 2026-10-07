<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\PriceSetting;
use App\Models\ZonePriceSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ActicvSleb;


class TekipostService implements CourierServiceInterface
{




    public static function getToken(): ?string
{
    try {
        // 🔹 If token already cached
        if (cache()->has('xpressbees_token')) {
            return cache('xpressbees_token');
        }

        // 🔹 Initialize cURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://app.tekipost.com/api-login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'email' => 'Bashu@shipxpeed.com',
                'password' => 'Shipxpeed@7722',
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // 🔹 Handle cURL errors
        if ($error) {
            \Log::error('XpressBees cURL Error: ' . $error);
            dd('cURL Error: ' . $error);
            return null;
        }

        // 🔹 Decode JSON response
        $data = json_decode($response, true);

        \Log::info('XpressBees API Response', ['response' => $data]);

        // ✅ Corrected: token is inside 'data' key
        if (isset($data['data']['token'])) {
            $token = $data['data']['token'];

            // Cache token for 150 minutes
            cache()->put('xpressbees_token', $token, now()->addMinutes(150));

            return $token;
        } else {
            \Log::warning('XpressBees Token Missing', ['response' => $data]);
            // dd('Token missing: ' . $response);
        }

        return null;
    } catch (\Exception $e) {
        \Log::error('XpressBees Token Exception: ' . $e->getMessage());
        // dd('Exception: ' . $e->getMessage());
        return null;
    }
}








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
                \Log::error("Error processing order {$orderId} in Tekipost bulk serviceability: " . $e->getMessage());
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
    $order = Order::findOrFail($params['order_id']);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pincodeToCheck = $consignee['pincode'];
    $destinationstate = $consignee['state'];

        // Get only the active Tekipost services for this seller
        // Include all possible case variations that might exist in database
        $tekipostServices = [
            'tekipost_Delhivery_5kg', 'Tekipost_Delhivery_5kg', 'Tekipost_Delhivery_5KG',
            'tekipost_Delhivery_10kg', 'Tekipost_Delhivery_10kg', 'Tekipost_Delhivery_10KG', 
            'tekipost_Delhivery_1_KG', 'Tekipost_Delhivery_1_KG', 'tekipost_Delhivery_1_kg',
            'tekipost_Ekart_2_KG_Fixed', 'Tekipost_Ekart_2_KG_Fixed', 'tekipost_Ekart_2_kg_Fixed',
            'tekipost_Amazon_2_kg', 'Tekipost_Amazon_2_kg', 'Tekipost_Amazon_2_KG',
            'tekipost_Amazon_500_GM', 'Tekipost_Amazon_500_GM', 'tekipost_Amazon_500_gm'
        ];
        
        $activeTekipostServicesRaw = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'status' => 1
        ])->whereIn('LogisticProvider', $tekipostServices)
          ->pluck('LogisticProvider')
          ->toArray();
        
        // Convert to lowercase for case-insensitive comparison
        $activeTekipostServices = array_map('strtolower', $activeTekipostServicesRaw);
        
        // Continue processing even if some services are inactive
        // We'll filter in the loop later

    // Pickup Info
    $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $originPin = $source['pincode'];
    $pickupstate = $source['state'];

    // 🧾 Step 1: Zone Fetch (Case-Insensitive)
    $zoneData = DB::table('pincode_zones')
        ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
        ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
        ->first();
        
    if (!$zoneData) {
        return [];
    }

    $zone = strtoupper($zoneData->zone);

    // Step 2: Weight & Payment Info
    $weight = (float)$order->package_weight; // grams
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;

    // 🧾 Step 3: Calculate Tekipost charges based on ZonePriceSetting for each service
    $calculateTekipostCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
        if (!$zonePricing) {
            return null; // Skip if no pricing found
        }
        
        if ($paymentType === 'COD') {
            // COD Order Logic
            if ($zonePricing->cod_fix_price > 0) {
                // Use fixed COD price - NO GST, NO other charges
                $totalPrice = $zonePricing->cod_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable COD price + 18% GST + COD charge if applicable
                $basePrice = $zonePricing->cod_price;
                $prepaidPrice = $zonePricing->prepaid_price ?? 0;

                // Calculate COD charge if order amount > 1400
                $codCharge = 0;
                if ($orderAmount > 1400) {
                    $codChargePercent = $zonePricing->cod_charge_parsent ?? 0;
                    $codCharge = ($orderAmount * $codChargePercent / 100);
                }
                
                $totalWithGST = ($basePrice + $prepaidPrice + $codCharge) + (($basePrice + $prepaidPrice + $codCharge) * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice + $prepaidPrice, 2),
                    'codCharge'      => round($codCharge, 2),
                ];
            }
        } else {
            // Prepaid Order Logic
            if ($zonePricing->prepaid_fix_price > 0) {
                // Use fixed Prepaid price - NO GST, NO other charges
                $totalPrice = $zonePricing->prepaid_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable Prepaid price + 18% GST
                $basePrice = $zonePricing->prepaid_price;
                $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        }
    };

    // 🧮 Step 4: Build final response with individual pricing for each service
    $slabs = [
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Delhivery 5 KG', 'logisticProvider' => 'tekipost_Delhivery_5kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Delhivery 10 KG', 'logisticProvider' => 'tekipost_Delhivery_10kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Delhivery 1 KG', 'logisticProvider' => 'tekipost_Delhivery_1_KG'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Ekart 2 KG Fixed', 'logisticProvider' => 'tekipost_Ekart_2_KG_Fixed'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 2 KG', 'logisticProvider' => 'tekipost_Amazon_2_kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 500 GM', 'logisticProvider' => 'tekipost_Amazon_500_GM'],
    ];

    $results = [];
    foreach ($slabs as $slab) {
        // Only process services that are active for this seller (case-insensitive check)
        if (!in_array(strtolower($slab['logisticProvider']), $activeTekipostServices)) {
            continue; // Skip inactive services
        }
        
        // Get zone pricing for this specific service
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => $slab['logisticProvider'],
            'status' => 1
        ])->first();

        if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => $slab['logisticProvider'],
        'status' => 1
        ])->first();

            }


        $charges = $calculateTekipostCharge($zonePricing);
        
        // Only add if pricing exists for this service
        if ($charges !== null) {
            $results[] = [
                'serviceabilityId' => $slab['serviceabilityId'],
                'courierName'      => $slab['courierName'],
                'courierCharge'    => $charges['courierCharge'],
                'freightCharges'   => $charges['freightCharges'],
                'codCharge'        => $charges['codCharge'],
                'zone'             => $zone,
                'minWeight'        => $weight,
                'volWeight'        => $weight,
            ];
        }
    }
// dd($results);
    return $results;
}





public function getServiceability(array $params): array
{
    // dd($params);
    $order = Order::findOrFail($params['order_id']);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pincodeToCheck = $consignee['pincode'];
    $destinationstate = $consignee['state'];

        // Get only the active Tekipost services for this seller
        // Include all possible case variations that might exist in database
        $tekipostServices = [
            'tekipost_Delhivery_5kg', 'Tekipost_Delhivery_5kg', 'Tekipost_Delhivery_5KG',
            'tekipost_Delhivery_10kg', 'Tekipost_Delhivery_10kg', 'Tekipost_Delhivery_10KG', 
            'tekipost_Delhivery_1_KG', 'Tekipost_Delhivery_1_KG', 'tekipost_Delhivery_1_kg',
            'tekipost_Ekart_2_KG_Fixed', 'Tekipost_Ekart_2_KG_Fixed', 'tekipost_Ekart_2_kg_Fixed',
            'tekipost_Amazon_2_kg', 'Tekipost_Amazon_2_kg', 'Tekipost_Amazon_2_KG',
            'tekipost_Amazon_500_GM', 'Tekipost_Amazon_500_GM', 'tekipost_Amazon_500_gm'
        ];
        
        $activeTekipostServicesRaw = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'status' => 1
        ])->whereIn('LogisticProvider', $tekipostServices)
          ->pluck('LogisticProvider')
          ->toArray();
        
        // Convert to lowercase for case-insensitive comparison
        $activeTekipostServices = array_map('strtolower', $activeTekipostServicesRaw);
        
        // Continue processing even if some services are inactive
        // We'll filter in the loop later

    // Pickup Info
    $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $originPin = $source['pincode'];
    $pickupstate = $source['state'];

    // 🧾 Step 1: Zone Fetch (Case-Insensitive)
    $zoneData = DB::table('pincode_zones')
        ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
        ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
        ->first();
        
    if (!$zoneData) {
        return [];
    }

    $zone = strtoupper($zoneData->zone);

    // Step 2: Weight & Payment Info
    $weight = (float)$order->package_weight; // grams
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;

    // 🧾 Step 3: Calculate Tekipost charges based on ZonePriceSetting for each service
    $calculateTekipostCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
        if (!$zonePricing) {
            return null; // Skip if no pricing found
        }
        
        if ($paymentType === 'COD') {
            // COD Order Logic
            if ($zonePricing->cod_fix_price > 0) {
                // Use fixed COD price - NO GST, NO other charges
                $totalPrice = $zonePricing->cod_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable COD price + 18% GST + COD charge if applicable
                $basePrice = $zonePricing->cod_price;
                $prepaidPrice = $zonePricing->prepaid_price ?? 0;

                // Calculate COD charge if order amount > 1400
                $codCharge = 0;
                if ($orderAmount > 1400) {
                    $codChargePercent = $zonePricing->cod_charge_parsent ?? 0;
                    $codCharge = ($orderAmount * $codChargePercent / 100);
                }
                
                $totalWithGST = ($basePrice + $prepaidPrice + $codCharge) + (($basePrice + $prepaidPrice + $codCharge) * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice + $prepaidPrice, 2),
                    'codCharge'      => round($codCharge, 2),
                ];
            }
        } else {
            // Prepaid Order Logic
            if ($zonePricing->prepaid_fix_price > 0) {
                // Use fixed Prepaid price - NO GST, NO other charges
                $totalPrice = $zonePricing->prepaid_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable Prepaid price + 18% GST
                $basePrice = $zonePricing->prepaid_price;
                $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        }
    };

    // 🧮 Step 4: Build final response with individual pricing for each service
    $slabs = [
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Delhivery 5 KG', 'logisticProvider' => 'tekipost_Delhivery_5kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Delhivery 10 KG', 'logisticProvider' => 'tekipost_Delhivery_10kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Delhivery 1 KG', 'logisticProvider' => 'tekipost_Delhivery_1_KG'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Ekart 2 KG Fixed', 'logisticProvider' => 'tekipost_Ekart_2_KG_Fixed'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 2 KG', 'logisticProvider' => 'tekipost_Amazon_2_kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 500 GM', 'logisticProvider' => 'tekipost_Amazon_500_GM'],
    ];

    $results = [];
    foreach ($slabs as $slab) {
        // Only process services that are active for this seller (case-insensitive check)
        if (!in_array(strtolower($slab['logisticProvider']), $activeTekipostServices)) {
            continue; // Skip inactive services
        }
        
        // Get zone pricing for this specific service
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => $slab['logisticProvider'],
            'status' => 1
        ])->first();

        if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => $slab['logisticProvider'],
        'status' => 1
        ])->first();

            }


        $charges = $calculateTekipostCharge($zonePricing);
        
        // Only add if pricing exists for this service
        if ($charges !== null) {
            $results[] = [
                'serviceabilityId' => $slab['serviceabilityId'],
                'courierName'      => $slab['courierName'],
                'courierCharge'    => $charges['courierCharge'] + 5,
                'freightCharges'   => $charges['freightCharges'],
                'codCharge'        => $charges['codCharge'],
                'zone'             => $zone,
                'zone_courier_name' => $slab['logisticProvider'],
                'minWeight'        => $weight,
                'volWeight'        => $weight,
            ];
        }
    }
// dd($results);
    return $results;
}






// public function getServiceability(array $params): array
// {
//     $token = $this->getToken();
//     if (!$token) {
//         return [];
//     }

//     $order = Order::findOrFail($params['order_id']);
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $order_items = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

//     if (!empty($order_items) && is_array($order_items)) {
//         $item  = $order_items[0];
//         $name  = $item['name'] ?? '';
//         $sku   = $item['sku'] ?? '';
//         $qty   = $item['qty'] ?? 0;
//         $price = $item['price'] ?? 0;
//     }

//     // Prepare payload for Tekipost API
//     $payload = [
//         "paymentMode"      => $order->payment_type === 'cod' ? 0 : 1,
//         "pickupPinCode"    => (int)($pickup['pincode'] ?? 110092),
//         "deliveryPinCode"  => (int)($consignee['pincode'] ?? 110092),
//         "apprWeight"       => (float)(($order->package_weight ?? 1500) / 1000),
//         "b2c_length"       => (float)($order->package_length ?? 1),
//         "b2c_breadth"      => (float)($order->package_breadth ?? 2),
//         "b2c_height"       => (float)($order->package_height ?? 3),
//         "total_Weight"     => (float)(($order->package_weight ?? 1500) / 1000),
//         "declaredValue"    => (float)($order->collectable_amount ?? 10),
//         "box_shipment"     => $params['box_shipment'] ?? [
//             [
//                 "no_of_box"       => $qty,
//                 "length"          => $order->package_length ?? 1,
//                 "breadth"         => $order->package_breadth ?? 1,
//                 "height"          => $order->package_height ?? 1,
//                 "each_box_weight" => (($order->package_weight ?? 1500) / 1000),
//             ]
//         ]
//     ];

//     $response = Http::withHeaders([
//         'Authorization' => "Bearer {$token}",
//         'Accept'        => 'application/json',
//         'Content-Type'  => 'application/json',
//     ])->post('https://app.tekipost.com/api-calculate-price', $payload);

//     if (!$response->successful()) {
//         return [];
//     }

//     $data = $response->json();
//     if (!isset($data['success']) || !$data['success'] || empty($data['data']['rates']['list'])) {
//         return [];
//     }

//     // Base skip (default)
//     $skipLogistics = ['Blue Dart_0.5 KG', 'Delhivery_1 KG', 'Ekart_0.5 KG Fixed'];

//     $sellerId = $order->seller_id;

//     // ⛔ EXTRA: Seller ke sabhi status=0 logistics ko skipList me add kar do
//     $inactive = PriceSetting::where('seller_id', $sellerId)
//          ->where('status', '0')
//         ->pluck('LogisticProvider')
//         ->toArray();
// //  dd($inactive);
//     // Merge inactive → skip list
//     $skipLogistics = array_merge($skipLogistics, $inactive);
       
//     // Remove duplicates
//     $skipLogistics = array_unique($skipLogistics);
//     // dd($skipLogistics);
//     // Filter Tekipost API rates
//     $rates = collect($data['data']['rates']['list'])
//         ->reject(function ($service) use ($skipLogistics) {
//             return in_array($service['logistic'] ?? '', $skipLogistics);
//         })
//         ->values()
//         ->toArray();
//         dd($rates);
//     $orderAmount = $order->collectable_amount;
//     $paymentType = $order->payment_type;

//     return collect($rates)->map(function ($service) use ($orderAmount, $paymentType, $sellerId, $order) {

//         $logisticName = $service['logistic'] ?? 'Tekipost';

//         // Seller pricing settings
//         $PriceSetting = PriceSetting::where([
//             'seller_id'        => $sellerId,
//             'LogisticProvider' => $logisticName
//         ])->first();

//         $sellerPercentage = $PriceSetting->shipping_charge ?? 70;
//         $codChargePercent = $PriceSetting->cod_charge_parsent ?? 1.9;
//         $codChargeFixed   = $PriceSetting->cod_charge ?? 32;

//         // Base freight
//         $baseCost = $service['addittional_charges']['freight_charge'] ?? 0;
//         $freight  = $baseCost * 1.10; // add 10%

//         // Fixed courier price logic
//         if ($PriceSetting && $PriceSetting->fixed_courier_price > 0) {

//             $weightInKg = $order->package_weight / 1000;
//             $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // every 500g

//             $freightWithGST = $PriceSetting->fixed_courier_price * $weightMultiplier;
//             $freightWithSeller = 10 * $weightMultiplier;
//             $codCharge = 10 * $weightMultiplier;

//         } else {

//             $codCharge = 0;
//             if ($paymentType === 'cod') {
//                 $codCharge = $orderAmount > 1400
//                     ? ($orderAmount * $codChargePercent / 100)
//                     : $codChargeFixed;
//             }

//             $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
//             $freightWithCod    = $freightWithSeller + $codCharge;
//             $freightWithGST    = $freightWithCod * 1.18;
//         }

//         return [
//             'serviceabilityId' => $service['logistic_id'] ?? 'tekipost',
//             'courierName'      => $logisticName,
//             'courierCharge'    => round($freightWithGST, 2),
//             'freightCharges'   => round($freightWithSeller, 2),
//             'codCharge'        => round($codCharge, 2),
//             'minWeight'        => $order->package_weight,
//             'volWeight'        => $order->package_weight,
//             'carrierType'      => 'tekipost',
//             'estimatedDays'    => $service['estimated_days'] ?? 'N/A',
//         ];

//     })->values()->toArray();
// }






    public function registerHub(array $hubDetails)
    {
        $token = $this->getToken();
        if (!$token) {
            return null;
        }
        // dd($hubDetails);
        // dd($hubDetails['hub_name']);
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->post('https://app.tekipost.com/api-warehouse-add', [
            // "id" => 1, // Static or you can make it dynamic
            // "id" => (int) substr(uniqid('', true), -6), // extract 6-digit unique number
            "warehouse_name" => $hubDetails['hub_name'],
            "contact_person_name" => $hubDetails['contact_person_name'],
            "contact_no" => (int) $hubDetails['hub_phone'],
            "address_line_1" => $hubDetails['address1'],
            "address_line_2" => $hubDetails['address2'] ?? '',
            "landmark" => $hubDetails['landmark'] ?? '',
            "pincode" => $hubDetails['pincode'],
            "city" => $hubDetails['city'],
            "state" => $hubDetails['state']
        ]);

        $json = $response->json();
        // dd($json);

        if ($response->successful() && isset($json['data']['id'])) {
            return (int) $json['data']['id'];
        }

        if (isset($json['data']['id'])) {
            return (int) $json['data']['id'];
        }

        return null;
    }



    public function assignOrder($params)
    {
        //dd($params);
        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'];

        $token = $this->getToken();
        $seller = Auth::guard('seller')->user();

        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
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
            return [
                'status'  => false,
                'message'  => 'Insufficient wallet balance. Please recharge your wallet.',
            ];
        }


        // Decode order item & consignee
        $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;
        $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
        $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

        // Register warehouse and get sender address ID
        $sender_address_id = $this->registerHub([
            "hub_name" => $pickup['warehouse_name'],
            "pincode" => $pickup['pincode'],
            "city" => $pickup['city'],
            "state" => $pickup['state'],
            "address1" => $pickup['address'],
            "address2" => $pickup['address_2'] ?? '',
            "hub_phone" => $pickup['phone'],
            "contact_person_name" => $pickup['contact_person_name'] ?? 'Admin'
        ]);
        // dd($sender_address_id);

        if (!$sender_address_id) {
            return ['status' => false, 'message' => 'Failed to register warehouse.'];
        }
        // dd($sender_address_id);

        $productDetails = [];
        foreach ($orderItems as $item) {
            $productDetails[] = [
                "sku_number" => (int) ($item['sku'] ?? 0),
                "product_name" => $item['name'],
                "product_quantity" => (int) ($item['qty'] ?? 0),
                "product_value" => (int) ($item['price'] ?? 100)
            ];
        }

        // $codAmount = ($order->payment_type === 'cod') ? $order->collectable_amount : 0;

        // if ($order->payment_type === 'cod') {
        //     $order_type = "1"; 
        // } else {
        //     $order_type = "0"; 
        // }
        $paymentType = strtolower($order->payment_type);

        $codAmount = ($paymentType === 'cod') ? $order->collectable_amount : 0;

        if ($paymentType === 'cod') {
            $order_type = "1";
        } else {
            $order_type = "0";
        }


        if ($provider_name === 'Delhivery 1 KG') {
            $logistic_id = 38; // Set to 38 for Delhivery_1 KG
            $logistic_name = 'Delhivery_1KG';

        } else if ($provider_name === 'Delhivery 10 KG') {
            $logistic_id = 24; // Set to 24 for Delhivery_10kg
            $logistic_name = 'Delhivery_10kg';
        } else if ($provider_name === 'Delhivery 5 KG') {
            $logistic_id = 23; // Set to 23 for Delhivery_5kg
            $logistic_name = 'Delhivery_5kg';
        } else if ($provider_name === 'Blue Dart_0.5 KG') {
            $logistic_id = 16; // Set to 16 for Blue Dart_0.5 KG
            $logistic_name = 'Blue Dart_0.5 KG';
        } else if ($provider_name === 'Amazon 2 KG') {
            $logistic_id = 13; // Set to 13 for Amazon_2 KG
            $logistic_name = 'Amazon_2 KG';
        } else if ($provider_name === 'Amazon 500 GM') {
            $logistic_id = 11; // Set to 11 for Amazon_0.5 KG
            $logistic_name = 'Amazon_0.5 KG';
        } else if ($provider_name === 'Ekart 2 KG Fixed') {
            $logistic_id = 34; // Set to 34 for Ekart 2 KG
            $logistic_name = 'Ekart 2 KG';
        }else {
            $logistic_name = 'Delhivery_1 KG';
            $logistic_id = 38; // Default to 38 if no specific provider
        }
        // dd($logistic_id);
        // Final payload matching the cURL structure exactly
        $payload = [
            // "isorder" => 1,
            "logistic_id" => $logistic_id, //pass this when specific logistic to be assign else dont pass any variables in payload
            "consignee_name" => $consignee['name'],
            "mobile_no" => (int) $consignee['phone'],
            "alternate_mobile_no" => (int) $consignee['phone'],
            "email_id" => $consignee['email'] ?? "customer@example.com",
            "receiver_address" => $consignee['address_2'],
            "receiver_pincode" => (int) $consignee['pincode'],
            "receiver_city" => $consignee['city'],
            "receiver_state" => strtolower($consignee['state']), // Ensure lowercase to match cURL example
            "receiver_landmark" => $consignee['landmark'] ?? '',
            "customer_order_no" => $order->order_number,
            "order_type" => $order_type,
            "product_quantity" => array_sum(array_column($orderItems, 'qty')),
            "cod_amount" => $codAmount,
            "physical_weight" => (float) ($order->package_weight / 1000), // Convert grams to kg
            "product_length" => (float) $order->package_length,
            "product_width" => (float) $order->package_breadth,
            "product_height" => (float) $order->package_height,
            "hsn_number" => $orderItems[0]['sku'] ?? 'HSN001',
            "order_value" => (float) $order->collectable_amount,
            "productdetatis" => $productDetails, // Changed from "productdetails" to "productdetatis"
            "sender_address_id" => $sender_address_id,
            "return_address_same_as_pickup_address" => 1,
            "return_consignee_name" => $pickup['contact_person_name'] ?? 'Return Admin',
            "return_mobile_no" => (int) $pickup['phone'],
            "return_alternate_mobile_no" => (int) $pickup['phone'],
            "return_address" => $pickup['address'],
            "return_pincode" => (int) $pickup['pincode'],
            "return_city" => strtolower($pickup['city']), // Ensure lowercase
            "return_state" => strtolower($pickup['state']), // Ensure lowercase
            "return_landmark" => $pickup['landmark'] ?? ''
        ];

        // dd($payload);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://app.tekipost.com/api-b2c-single-order', $payload);



            $responseData = $response->json();
            //    dd($responseData);
            // Check for successful response using 'status'
            if (isset($responseData['status']) && $responseData['status'] == 1) {
                // Save the tracking number as AWB number
                $order->awb_number = $responseData['tracking_number'] ?? null;
                $order->courier_id = 'tekipost';
                $order->all_courier_name = $logistic_name;

                $order->smartship_tracking_url = $responseData['label_url'] ?? null;
                // $order->smartship_courier_id = $provider_name ?? null;
                $order->shipping_date = Carbon::now()->format('Y-m-d');

                $order->save();
                // Wallet debit
                Recharge::create([
                    'seller_id' => $seller->id,
                    'type' => 'Debit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order created'
                ]);
                return [
                    'status' => true,
                    // 'message' => $responseData['message'] ?? 'Order successfully assigned to Tekipost.',
                    'couriername' => 'tekipost',
                    'awb_number' => $responseData['tracking_number'] ?? null,
                    // 'label_url' => $order->smartship_tracking_url,
                    // 'freight_charges' => $responseData['freight_charges'] ?? null,
                ];
            }

            // If not successful
            return [
                'status' => false,
                'message' => $responseData['message'] ?? 'Tekipost API error.',
                'data' => $responseData,
            ];



            return [
                'status' => false,
                'message' => 'Tekipost API error: ' . ($responseData['message'] ?? 'Unknown error'),
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'API Request Failed: ' . $e->getMessage(),
            ];
        }
    }



    public function cancelShipment(string $awb): array
    {
        $token = $this->getToken();
        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            return [
                'error' => 'Order not found',
                'status' => 404,
                'message' => 'Order with AWB number not found',
            ];
        }

        $url = 'https://app.tekipost.com/api-delete-order';

        $payload = [
            "awb_no" => $awb
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        $responseData = $response->json();
    //   dd($responseData);
        if (
            !$response->successful() ||
            !isset($responseData['success']) ||
            $responseData['success'] !== true
        ) {
            return [
                'error'   => 'Tekipost Delete API error',
                'status'  => $response->status(),
                'message' => $responseData['message'] ?? 'Unknown error',
            ];
        }

        // Update local DB
        $seller = Auth::guard('seller')->user();
        $order->order_status = 'cancelled';
        $order->save();

        Recharge::create([
            'seller_id' => $seller->id,
            'type'      => 'Credit',
            'amount'    => $order->seller_amount_walate,
            'status'    => 1,
            'description' => 'Order cancelled',

        ]);

        return [
            'success' => true,
            'message' => $responseData['message'] ?? 'Order deleted successfully.',
            'order_id' => $order->order_number,
        ];
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
























    public function registerHubbulk(array $hubDetails)
    {
        $token = $this->getToken();
        if (!$token) {
            return null;
        }
        // dd($hubDetails);
        // dd($hubDetails['hub_name']);
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->post('https://app.tekipost.com/api-warehouse-add', [
            // "id" => 1, // Static or you can make it dynamic
            // "id" => (int) substr(uniqid('', true), -6), // extract 6-digit unique number
            "warehouse_name" => $hubDetails['hub_name'],
            "contact_person_name" => $hubDetails['contact_person_name'],
            "contact_no" => (int) $hubDetails['hub_phone'],
            "address_line_1" => $hubDetails['address1'],
            "address_line_2" => $hubDetails['address2'] ?? '',
            "landmark" => $hubDetails['landmark'] ?? '',
            "pincode" => $hubDetails['pincode'],
            "city" => $hubDetails['city'],
            "state" => $hubDetails['state']
        ]);

        $json = $response->json();
        // dd($json);

        if ($response->successful() && isset($json['data']['id'])) {
            return (int) $json['data']['id'];
        }

        if (isset($json['data']['id'])) {
            return (int) $json['data']['id'];
        }

        return null;
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
        $provider_name = $params['provider_name'] ?? '';
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        $seller = Auth::guard('seller')->user();
        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
        }

        // Get reusable token
        $token = $this->getToken();
        if (!$token) {
            return ['status' => false, 'message' => 'Failed to get authentication token.'];
        }

        foreach ($orderIds as $orderId) {
            try {
                $singleParams = [
                    'order_id' => $orderId,
                    'provider_name' => $provider_name
                ];
                
                $result = $this->processSingleOrderAssignmentBulk($singleParams, $token);
                
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
            'couriername' => 'tekipost',
            'results' => $results,
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }

    private function processSingleOrderAssignmentBulk($params, $token = null)
    {
        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'] ?? '';

        if (!$token) {
            $token = $this->getToken();
        }
        
        $seller = Auth::guard('seller')->user();
        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
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
            return [
                'status'  => false,
                'message'  => 'Insufficient wallet balance. Please recharge your wallet.',
            ];
        }

        // Decode order item & consignee
        $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;
        $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
        $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

        // Register warehouse and get sender address ID
        $sender_address_id = $this->registerHubbulk([
            "hub_name" => $pickup['warehouse_name'] ?? $pickup['name'],
            "pincode" => $pickup['pincode'],
            "city" => $pickup['city'],
            "state" => $pickup['state'],
            "address1" => $pickup['address'],
            "address2" => $pickup['address_2'] ?? '',
            "hub_phone" => $pickup['phone'],
            "contact_person_name" => $pickup['contact_person_name'] ?? 'Admin'
        ]);

        if (!$sender_address_id) {
            return ['status' => false, 'message' => 'Failed to register warehouse.'];
        }

        $productDetails = [];
        foreach ($orderItems as $item) {
            $productDetails[] = [
                "sku_number" => (int) ($item['sku'] ?? 0),
                "product_name" => $item['name'],
                "product_quantity" => (int) ($item['qty'] ?? 0),
                "product_value" => (int) ($item['price'] ?? 100)
            ];
        }

        $paymentType = strtolower($order->payment_type);
        $codAmount = ($paymentType === 'cod') ? $order->collectable_amount : 0;
        $order_type = ($paymentType === 'cod') ? "1" : "0";

        // Determine logistic details based on provider name
        $logisticDetails = $this->getLogisticDetails($provider_name);

        // Final payload
        $payload = [
            "logistic_id" => $logisticDetails['logistic_id'],
            "consignee_name" => $consignee['name'],
            "mobile_no" => (int) $consignee['phone'],
            "alternate_mobile_no" => (int) $consignee['phone'],
            "email_id" => $consignee['email'] ?? "customer@example.com",
            "receiver_address" => $consignee['address'] . ' ' . ($consignee['address_2'] ?? ''),
            "receiver_pincode" => (int) $consignee['pincode'],
            "receiver_city" => $consignee['city'],
            "receiver_state" => strtolower($consignee['state']),
            "receiver_landmark" => $consignee['landmark'] ?? '',
            "customer_order_no" => $order->order_number,
            "order_type" => $order_type,
            "product_quantity" => array_sum(array_column($orderItems, 'qty')),
            "cod_amount" => $codAmount,
            "physical_weight" => (float) ($order->package_weight / 1000),
            "product_length" => (float) $order->package_length,
            "product_width" => (float) $order->package_breadth,
            "product_height" => (float) $order->package_height,
            "hsn_number" => $orderItems[0]['sku'] ?? 'HSN001',
            "order_value" => (float) $order->collectable_amount,
            "productdetatis" => $productDetails,
            "sender_address_id" => $sender_address_id,
            "return_address_same_as_pickup_address" => 1,
            "return_consignee_name" => $pickup['contact_person_name'] ?? 'Return Admin',
            "return_mobile_no" => (int) $pickup['phone'],
            "return_alternate_mobile_no" => (int) $pickup['phone'],
            "return_address" => $pickup['address'],
            "return_pincode" => (int) $pickup['pincode'],
            "return_city" => strtolower($pickup['city']),
            "return_state" => strtolower($pickup['state']),
            "return_landmark" => $pickup['landmark'] ?? ''
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://app.tekipost.com/api-b2c-single-order', $payload);

            $responseData = $response->json();
            
            if (isset($responseData['status']) && $responseData['status'] == 1) {
                $order->awb_number = $responseData['tracking_number'] ?? null;
                $order->courier_id = 'tekipost';
                $order->all_courier_name = $logisticDetails['logistic_name'];
                $order->smartship_tracking_url = $responseData['label_url'] ?? null;
                $order->shipping_date = Carbon::now()->format('Y-m-d');
                $order->save();

                // Wallet debit
                Recharge::create([
                    'seller_id' => $seller->id,
                    'type' => 'Debit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order created'
                ]);

                return [
                    'status' => true,
                    'couriername' => 'tekipost',
                    'awb_number' => $responseData['tracking_number'] ?? null,
                ];
            }

            return [
                'status' => false,
                'message' => $responseData['message'] ?? 'Tekipost API error.',
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'API Request Failed: ' . $e->getMessage(),
            ];
        }
    }

    private function getLogisticDetails($provider_name)
    {
        switch ($provider_name) {
            case 'Delhivery 1 KG':
                return ['logistic_id' => 38, 'logistic_name' => 'Delhivery_1KG'];
            case 'Delhivery 10 KG':
                return ['logistic_id' => 24, 'logistic_name' => 'Delhivery_10kg'];
            case 'Delhivery 5 KG':
                return ['logistic_id' => 23, 'logistic_name' => 'Delhivery_5kg'];
            case 'Blue Dart_0.5 KG':
                return ['logistic_id' => 16, 'logistic_name' => 'Blue Dart_0.5 KG'];
            case 'Amazon 2 KG':
                return ['logistic_id' => 13, 'logistic_name' => 'Amazon_2 KG'];
            case 'Amazon 500 GM':
                return ['logistic_id' => 11, 'logistic_name' => 'Amazon_0.5 KG'];
            case 'Ekart 2 KG Fixed':
                return ['logistic_id' => 34, 'logistic_name' => 'Ekart 2 KG'];
            default:
                return ['logistic_id' => 38, 'logistic_name' => 'Delhivery_1 KG'];
        }
    }



















}
