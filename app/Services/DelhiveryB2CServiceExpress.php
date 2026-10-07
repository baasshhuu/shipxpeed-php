<?php
// app/Services/DelhiveryB2BService.php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PriceSetting;
use App\Models\Recharge;
use App\Models\ZonePriceSetting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ActicvSleb;

class DelhiveryB2CServiceExpress implements CourierServiceInterface
{


    /**
     * Check serviceability via Delhivery B2B Pincode API.
     *
     * @param  array  $params  // origin, destination, etc.
     * @return array[]         // normalized serviceability entries
     */

    const API_BASE_URL = 'https://track.delhivery.com';
    const API_ENDPOINT = '/c/api/pin-codes/json/?parameters';
    const TOKEN_CACHE_KEY = 'xpressbees_api_token';
    const TOKEN_CACHE_TTL = 3600;



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
                    \Log::error("Error processing order {$orderId} in DelhiveryB2C bulk serviceability: " . $e->getMessage());
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

        // Check if Delhivery Air zone pricing is configured for this seller
        $delhiveryAirZoneCheck = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'LogisticProvider' => 'Delhivery_Air',
            'status' => 1
        ])->exists();

        if (!$delhiveryAirZoneCheck) {
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

        if (!$zoneData) {
            return [];
        }

        $zone = strtoupper($zoneData->zone);

        // Step 2: Weight & Payment Info
        $weight = (float)$order->package_weight; // grams
        $orderAmount = $order->collectable_amount;
        $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
        $seller_id = $order->seller_id;

        // 🧾 Step 3: Calculate Delhivery Air charges based on ZonePriceSetting with weight slabs
        $calculateDelhiveryAirCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
            if (!$zonePricing) {
                return null; // Skip if no pricing found
            }
            
            // Calculate weight slabs (500gm each)
            $slabs = max(1, ceil($weight / 500));
            
            if ($paymentType === 'COD') {
                // COD Order Logic
                if ($zonePricing->cod_fix_price > 0) {
                    // Use fixed COD price multiplied by weight slabs - NO GST, NO other charges
                    $totalPrice = $zonePricing->cod_fix_price * $slabs;
                    return [
                        'courierCharge'  => round($totalPrice, 2),
                        'freightCharges' => round($totalPrice, 2),
                        'codCharge'      => 0,
                    ];
                } else {
                    // Use variable COD price multiplied by weight slabs + 18% GST + COD charge if applicable
                    $basePrice = $zonePricing->cod_price * $slabs;
                    $prepaidPrice = $zonePricing->prepaid_price * $slabs;
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
                    // Use fixed Prepaid price multiplied by weight slabs - NO GST, NO other charges
                    $totalPrice = $zonePricing->prepaid_fix_price * $slabs;
                    return [
                        'courierCharge'  => round($totalPrice, 2),
                        'freightCharges' => round($totalPrice, 2),
                        'codCharge'      => 0,
                    ];
                } else {
                    // Use variable Prepaid price multiplied by weight slabs + 18% GST
                    $basePrice = $zonePricing->prepaid_price * $slabs;
                    $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                    return [
                        'courierCharge'  => round($totalWithGST, 2),
                        'freightCharges' => round($basePrice, 2),
                        'codCharge'      => 0,
                    ];
                }
            }
        };

        // Get zone pricing for Delhivery Air service
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => 'Delhivery_Air',
            'status' => 1
        ])->first();
        
        if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => 'Delhivery_Air',
        'status' => 1
        ])->first();
            }



        $charges = $calculateDelhiveryAirCharge($zonePricing);
        
        // Only return result if pricing exists for this service
        if ($charges === null) {
            return [];
        }

        return [[
            'serviceabilityId' => $pincodeToCheck,
            'courierName'      => 'Delhivery B2C (Express)',
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

        // Check if Delhivery Air zone pricing is configured for this seller
        $delhiveryAirZoneCheck = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'LogisticProvider' => 'Delhivery_Air',
            'status' => 1
        ])->exists();

        if (!$delhiveryAirZoneCheck) {
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

        if (!$zoneData) {
            return [];
        }

        $zone = strtoupper($zoneData->zone);

        // Step 2: Weight & Payment Info
        $weight = (float)$order->package_weight; // grams
        $orderAmount = $order->collectable_amount;
        $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
        $seller_id = $order->seller_id;

        // 🧾 Step 3: Calculate Delhivery Air charges based on ZonePriceSetting with weight slabs
        $calculateDelhiveryAirCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
            if (!$zonePricing) {
                return null; // Skip if no pricing found
            }
            
            // Calculate weight slabs (500gm each)
            $slabs = max(1, ceil($weight / 500));
            
            if ($paymentType === 'COD') {
                // COD Order Logic
                if ($zonePricing->cod_fix_price > 0) {
                    // Use fixed COD price multiplied by weight slabs - NO GST, NO other charges
                    $totalPrice = $zonePricing->cod_fix_price * $slabs;
                    return [
                        'courierCharge'  => round($totalPrice, 2),
                        'freightCharges' => round($totalPrice, 2),
                        'codCharge'      => 0,
                    ];
                } else {
                    // Use variable COD price multiplied by weight slabs + 18% GST + COD charge if applicable
                    $basePrice = $zonePricing->cod_price * $slabs;
                    $prepaidPrice = $zonePricing->prepaid_price * $slabs;
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
                    // Use fixed Prepaid price multiplied by weight slabs - NO GST, NO other charges
                    $totalPrice = $zonePricing->prepaid_fix_price * $slabs;
                    return [
                        'courierCharge'  => round($totalPrice, 2),
                        'freightCharges' => round($totalPrice, 2),
                        'codCharge'      => 0,
                    ];
                } else {
                    // Use variable Prepaid price multiplied by weight slabs + 18% GST
                    $basePrice = $zonePricing->prepaid_price * $slabs;
                    $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                    return [
                        'courierCharge'  => round($totalWithGST, 2),
                        'freightCharges' => round($basePrice, 2),
                        'codCharge'      => 0,
                    ];
                }
            }
        };

        // Get zone pricing for Delhivery Air service
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => 'Delhivery_Air',
            'status' => 1
        ])->first();
        
        if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => 'Delhivery_Air',
        'status' => 1
        ])->first();
            }



        $charges = $calculateDelhiveryAirCharge($zonePricing);
        
        // Only return result if pricing exists for this service
        if ($charges === null) {
            return [];
        }

        return [[
            'serviceabilityId' => $pincodeToCheck,
            'courierName'      => 'Delhivery B2C (Express)',
            'courierCharge'    => $charges['courierCharge'],
            'freightCharges'   => $charges['freightCharges'],
            'codCharge'        => $charges['codCharge'],
            'zone'             => $zone,
            'zone_courier_name' => 'Delhivery_Air',
            'minWeight'        => $weight,
            'volWeight'        => $weight,
        ]];
    }

//     public function getServiceability(array $params): array
// {
//     $token = "a6cd5bb955fddcb41757ec23ee92cf62b6650607";

//     $url = 'https://track.delhivery.com/c/api/pin-codes/json/';

//     $order = Order::findOrFail($params['order_id']);
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $pincodeToCheck = $consignee['pincode'];
//         $PriceSettingactiveselb = PriceSetting::where([
//             'seller_id' => $order->seller_id,
//             'LogisticProvider' => 'Delhivery Air'
//         ])->first();

//         // If PriceSetting exists AND disabled
//         if ($PriceSettingactiveselb && $PriceSettingactiveselb->status == '0') {
//             return []; // skip and bypass
//         }
//     // Pincode Serviceability Check
//     $response = Http::withHeaders([
//         'Authorization' => 'Token ' . $token,
//         'Content-Type' => 'application/json',
//     ])->get($url, [
//         'filter_codes' => $pincodeToCheck,
//     ]);

//     if (!$response->successful()) {
//         Log::error('Delhivery B2C API Error', ['response' => $response->body()]);
//         return [];
//     }

//     $json = $response->json();
//     $codes = $json['delivery_codes'][0] ?? [];
//     if (empty($codes)) {
//         return [];
//     }

//     // Rate Calculation
//     $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $originPin = $source['pincode'];
//     $destinationPin = $consignee['pincode'];
//     $weight = $order->package_weight;
//     // $paymentType = strtolower($order->payment_type);
//     $payment = $order->payment_type;
//     if ($payment === 'cod') {
//             $paymentType = 'COD';
//         }else {
//             $paymentType = 'Pre-paid';
//         }
//     $orderAmount = $order->collectable_amount;

//     $seller_id = $order->seller_id;
//     $PriceSetting = PriceSetting::where(['seller_id' => $seller_id,'LogisticProvider'=> 'Delhivery Air'])->first();
//     $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 30;
//     $codChargePercent = $PriceSetting ? $PriceSetting->cod_charge_parsent : 1.9;
//     $codChargeFixed = $PriceSetting ? $PriceSetting->cod_charge : 32;

//     $rates = collect(['E' => 'Express'])->flatMap(function ($modeName, $modeCode) use (
//         $token, $originPin, $destinationPin, $weight, $params, $orderAmount, $paymentType, $sellerPercentage, $codChargePercent, $codChargeFixed, $PriceSetting
//     ) {
//         // $resp = Http::withHeaders([
//         //     'Authorization' => 'Token ' . $token,
//         // ])->get('https://track.delhivery.com/api/kinko/v1/invoice/charges/.json', [
//         //     'o_pin' => $originPin,
//         //     'd_pin' => $destinationPin,
//         //     'cgm'   => $weight,
//         //     'ss'    => 'Delivered',
//         //     'md'    => $modeCode,
//         // ]);

//             $resp = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//         ])->get("https://track.delhivery.com/api/kinko/v1/invoice/charges/.json?md={$modeCode}&ss=Delivered&d_pin={$destinationPin}&o_pin={$originPin}&cgm={$weight}&pt={$paymentType}");


//         // dd($resp->json());
//         if (!$resp->successful()) {
//             Log::error("Delhivery Rate API Error for $modeName", ['response' => $resp->body()]);
//             return [];
//         }

//         $data = $resp->json();
//         if (empty($data[0]['total_amount'])) return [];

//         $codCharge = 0;
//         if ($paymentType === 'COD') {
//             $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercent / 100) : $codChargeFixed;
//         }

        
//          if ($PriceSetting && isset($PriceSetting->fixed_courier_price) && $PriceSetting->fixed_courier_price > 0) {
//                 // Calculate weight multiplier based on 500g intervals
//                 $weightInKg = $weight / 1000; // Convert grams to kg
//                 $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
                
//                 // Use fixed price with weight multiplier
//                 $freightWithGST = $PriceSetting->fixed_courier_price * $weightMultiplier;
//                 $freightWithSeller = 10 * $weightMultiplier;
//                 $codCharge = 10 * $weightMultiplier;
//             } else {


//         $freight_charges = $data[0]['charge_DL'] ?? 0.0;
//         // $freight = $freight_charges;
//         $freight = $freight_charges * 1.10;

//         $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
//         $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//         $freightWithGST =  $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);
//             }
//         return [[
//             'serviceabilityId' => $destinationPin,
//             'courierName'      => 'Delhivery B2C (' . $modeName . ')',
//             'courierCharge'    => round($freightWithGST, 2),
//             'freightCharges'   => round($freightWithSeller, 2),
//             'codCharge'        => round($codCharge, 2),
//             'minWeight'        => $data[0]['charged_weight'],
//             'volWeight'        => $data[0]['charged_weight'],
//         ]];
//     });

//     return $rates->toArray();
// }







    public function assignOrder($orderPayload)
    {
        // dd($orderPayload);
        $orderPayload = json_decode(json_encode($orderPayload), true);
        $pickup = $orderPayload['pickup_location'];
 

        $seller = Auth::guard('seller')->user();
        $order_number = $orderPayload['shipments'][0]['order'] ?? null;
        $order = Order::where(['order_number' => $order_number,'seller_id' => $seller->id])->first();
        $seller_amount_walate = $order->seller_amount_walate ?? 0;

        $warehousePayload = [
            "name" => $pickup['name'],
            "email" => "test@gmail.com",
            "phone" => $pickup['phone'],
            "address" => $pickup['add'],
            "city" => $pickup['city'],
            "country" => $pickup['country'] ?? 'India',
            "pin" => (string) $pickup['pin_code'],
            "return_address" => $pickup['add'],
            "return_pin" => (string) $pickup['pin_code'],
            "return_city" => $pickup['city'],
            "return_state" => $orderPayload['shipments'][0]['state'] ?? 'Rajasthan',
            "return_country" => $pickup['country'] ?? 'India',
        ];

        try {
            // Step 1: Create Warehouse
            $warehouseUrl = "https://track.delhivery.com/api/backend/clientwarehouse/create/";

            $warehouseResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
            ])->post($warehouseUrl, $warehousePayload);

            $warehouseData = $warehouseResponse->json();

            // Step 2: Check if warehouse already exists error
            if (
                isset($warehouseData['error'][0]) &&
                str_contains($warehouseData['error'][0], 'already exists CLIENT_STORES_CREATE')
            ) {
                // Allowed — continue to create order
            } elseif (!$warehouseResponse->successful() || $warehouseData['success'] === false) {
                // Some other warehouse error
                return [
                    'status' => false,
                    'message' => 'Warehouse creation failed: ' . ($warehouseData['error'][0] ?? 'Unknown error')
                ];
            }



            
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;


    if (
    $seller->negative_balance != '1' && 
    ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
) {
    return [
        'status'  => false,
        'message'  => 'Insufficient wallet balance. Please recharge your wallet.',
    ];
}


            // if ($walletBalance < $seller_amount_walate || $walletBalance < 150) {
            //     return [
            //         'status' => false,
            //         'message' => 'Order creation failed: Insufficient wallet balance. Please recharge your wallet.'
            //     ];
            // }
   
            $orderCreateUrl = "https://track.delhivery.com/api/cmu/create.json";

            $orderResponse = Http::asForm()->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
            ])->post($orderCreateUrl, [
                'format' => 'json',
                'data' => json_encode($orderPayload),
            ]);


            $responseData = $orderResponse->json();
            // dd($responseData);

                    if (!$orderResponse->successful() || ($responseData['success'] ?? false) === false) {
            //  echo 'scascac';die;
            return [
                'status' => false,
                'message' => 'Order creation failed: ' . ($responseData['rmk'] ?? 'Unknown error')
            ];
        }

            // if (!$orderResponse->successful()) {
            //     return [
            //         'status' => false,
            //         'message' => 'Order creation failed: ' . ($responseData['error'] ?? 'Unknown error')
            //     ];
            // }

               $order->courier_id = 'delhivery_b2c';
            $order->shipping_date = Carbon::now()->format('Y-m-d');

                $order->save();
                Recharge::create([
                        'seller_id'   => $seller->id,
                        'type'        => 'Debit',
                        'amount'      => $seller_amount_walate,
                        'status'      => 1,
                        'description' => 'Order created'
                    ]);



            return $responseData; // or return $responseData['packages'][0]['awb'] etc.
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'API Request Failed: ' . $e->getMessage()
            ];
        }
    }






    
public function assignOrder_bulk($orderPayloads)
{
    // Convert input to array if it's not already
    if (is_object($orderPayloads)) {
        $orderPayloads = [$orderPayloads]; // Convert single object to array
    } elseif (!is_array($orderPayloads)) {
        return [
            'status' => false,
            'message' => 'Invalid input format. Expected array or object.'
        ];
    }

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return [
            'status' => false,
            'message' => 'Unauthorized or inactive seller.'
        ];
    }

    // Calculate wallet balance once for all orders
    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');
    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');
    $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;

    $success = [];
    $failures = [];
    $totalAmount = 0;

    // First pass: validate all orders and calculate total amount
    foreach ($orderPayloads as $orderPayload) {
        try {
            $orderPayload = json_decode(json_encode($orderPayload), true);
            $order_number = $orderPayload['shipments'][0]['order'] ?? null;
            $order = Order::where(['order_number' => $order_number, 'seller_id' => $seller->id])->first();

            if (!$order) {
                $failures[] = [
                    'order_number' => $order_number ?? 'N/A',
                    'message' => 'Order not found'
                ];
                continue;
            }

            $totalAmount += $order->seller_amount_walate ?? 0;
        } catch (\Exception $e) {
            $failures[] = [
                'order_number' => $order_number ?? 'N/A',
                'message' => 'Validation error: ' . $e->getMessage()
            ];
        }
    }

    // Check wallet balance for all orders
    if ($walletBalance < $totalAmount || $walletBalance < (150 * count($orderPayloads))) {
        return [
            'status' => false,
            'message' => 'Insufficient wallet balance for bulk orders',
            'required_amount' => $totalAmount,
            'available_balance' => $walletBalance
        ];
    }

    // Process each order
    foreach ($orderPayloads as $orderPayload) {
        $orderPayload = json_decode(json_encode($orderPayload), true);
        $order_number = $orderPayload['shipments'][0]['order'] ?? null;
        
        try {
            $order = Order::where(['order_number' => $order_number, 'seller_id' => $seller->id])->first();
            if (!$order) {
                $failures[] = [
                    'order_number' => $order_number,
                    'message' => 'Order not found during processing'
                ];
                continue;
            }

            $pickup = $orderPayload['pickup_location'];
            $warehousePayload = [
                "name" => $pickup['name'],
                "email" => "test@gmail.com",
                "phone" => $pickup['phone'],
                "address" => $pickup['add'],
                "city" => $pickup['city'],
                "country" => $pickup['country'] ?? 'India',
                "pin" => $pickup['pin_code'],
                "return_address" => $pickup['add'],
                "return_pin" => $pickup['pin_code'],
                "return_city" => $pickup['city'],
                "return_state" => $orderPayload['shipments'][0]['state'] ?? 'Rajasthan',
                "return_country" => $pickup['country'] ?? 'India',
            ];

            // Create warehouse (if needed)
            $warehouseResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
            ])->post("https://track.delhivery.com/api/backend/clientwarehouse/create/", $warehousePayload);

            $warehouseData = $warehouseResponse->json();

            if (
                isset($warehouseData['error'][0]) &&
                str_contains($warehouseData['error'][0], 'already exists CLIENT_STORES_CREATE')
            ) {
                // Warehouse exists - continue
            } elseif (!$warehouseResponse->successful() || ($warehouseData['success'] ?? false) === false) {
                $failures[] = [
                    'order_number' => $order_number,
                    'message' => 'Warehouse error: ' . ($warehouseData['error'][0] ?? 'Unknown')
                ];
                continue;
            }

            // Create order
            $orderResponse = Http::asForm()->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
            ])->post("https://track.delhivery.com/api/cmu/create.json", [
                'format' => 'json',
                'data' => json_encode($orderPayload),
            ]);

            $responseData = $orderResponse->json();

            if (!$orderResponse->successful() || ($responseData['success'] ?? false) === false) {
                $failures[] = [
                    'order_number' => $order_number,
                    'message' => 'Order creation failed: ' . ($responseData['rmk'] ?? 'Unknown')
                ];
                continue;
            }

            // Update order and debit wallet
            $awb = $responseData['packages'][0]['waybill'] ?? null;
            $order->courier_id = 'delhivery_b2c';
            $order->awb_number = $awb;
            $order->save();

            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
            ]);

            $success[] = [
                'order_number' => $order_number,
                'awb_number' => $awb,
                'message' => 'Successfully processed'
            ];

        } catch (\Exception $e) {
            $failures[] = [
                'order_number' => $order_number,
                'message' => 'Exception: ' . $e->getMessage()
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










    public function cancelShipmentBulk(array $awbs): array
{
    $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607'; // Ideally store securely in .env or config
    $seller = Auth::guard('seller')->user();

    $results = [
        'success' => [],
        'failed'  => [],
    ];

    foreach ($awbs as $awb) {
        $order = Order::where('awb_number', $awb)->first();

        if (! $order) {
            $results['failed'][] = [
                'awb'     => $awb,
                'message' => 'Order not found'
            ];
            continue;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $token,
            'Content-Type'  => 'application/json',
        ])->post('https://track.delhivery.com/api/p/edit', [
            'waybill'      => $awb,
            'cancellation' => 'true',
        ]);

        if ($response->successful()) {
            // Update order status
            $order->order_status = 'cancelled';
            $order->save();

            // Add recharge entry
            Recharge::create([
                'seller_id' => $seller->id,
                'type'      => 'Credit',
                'amount'    => $order->seller_amount_walate,
                'status'    => 1,
                'description' => 'Order cancelled',

            ]);

            $results['success'][] = [
                'awb'     => $awb,
                'message' => 'Cancelled successfully'
            ];
        } else {
            $results['failed'][] = [
                'awb'     => $awb,
                'message' => $response->json()['error'] ?? 'API error',
                'code'    => $response->status()
            ];
        }
    }

    return [
        'status'  => count($results['failed']) === 0,
        'summary' => $results,
    ];
}






// public function assignOrder_bulk(array $bulkOrderPayloads)
// {
//     $seller = Auth::guard('seller')->user();

//     if (!$seller || $seller->status != 1) {
//         return [
//             'status' => false,
//             'message' => 'Unauthorized or inactive seller.'
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

//     $success = [];
//     $failures = [];

//     foreach ($bulkOrderPayloads as $orderPayload) {
//         try {
//             $orderPayload = json_decode(json_encode($orderPayload), true);
//             $pickup = $orderPayload['pickup_location'];
//             $order_number = $orderPayload['shipments'][0]['order'] ?? null;

//             $order = Order::where(['order_number' => $order_number, 'seller_id' => $seller->id])->first();

//             if (!$order) {
//                 $failures[] = "Order {$order_number}: Not found.";
//                 continue;
//             }

//             $seller_amount_walate = $order->seller_amount_walate ?? 0;

//             // Check wallet balance again before every order (optional: use running balance)
//             if ($walletBalance < $seller_amount_walate || $walletBalance < 150) {
//                 $failures[] = "Order {$order_number}: Insufficient wallet balance.";
//                 continue;
//             }

//             // Step 1: Create Warehouse
//             $warehousePayload = [
//                 "name"            => $pickup['name'],
//                 "email"           => "test@gmail.com",
//                 "phone"           => $pickup['phone'],
//                 "address"         => $pickup['add'],
//                 "city"            => $pickup['city'],
//                 "country"         => $pickup['country'] ?? 'India',
//                 "pin"             => $pickup['pin_code'],
//                 "return_address"  => $pickup['add'],
//                 "return_pin"      => $pickup['pin_code'],
//                 "return_city"     => $pickup['city'],
//                 "return_state"    => $orderPayload['shipments'][0]['state'] ?? 'Rajasthan',
//                 "return_country"  => $pickup['country'] ?? 'India',
//             ];

//             $warehouseResponse = Http::withHeaders([
//                 'Content-Type'  => 'application/json',
//                 'Accept'        => 'application/json',
//                 'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
//             ])->post("https://track.delhivery.com/api/backend/clientwarehouse/create/", $warehousePayload);

//             $warehouseData = $warehouseResponse->json();

//             if (
//                 isset($warehouseData['error'][0]) &&
//                 str_contains($warehouseData['error'][0], 'already exists CLIENT_STORES_CREATE')
//             ) {
//                 // Warehouse already exists, continue
//             } elseif (!$warehouseResponse->successful() || ($warehouseData['success'] ?? false) === false) {
//                 $failures[] = "Order {$order_number}: Warehouse creation failed - " . ($warehouseData['error'][0] ?? 'Unknown');
//                 continue;
//             }

//             // Step 2: Create Order
//             $orderResponse = Http::asForm()->withHeaders([
//                 'Accept'        => 'application/json',
//                 'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
//             ])->post("https://track.delhivery.com/api/cmu/create.json", [
//                 'format' => 'json',
//                 'data'   => json_encode($orderPayload),
//             ]);

//             $responseData = $orderResponse->json();

//             if (!$orderResponse->successful() || ($responseData['success'] ?? false) === false) {
//                 $failures[] = "Order {$order_number}: Order creation failed - " . ($responseData['rmk'] ?? 'Unknown');
//                 continue;
//             }

//             $awb = $responseData['packages'][0]['waybill'] ?? null;

//             $order->courier_id = 'delhivery_b2c';
//             $order->awb_number = $awb;
//             $order->save();

//             // Deduct from wallet
//             Recharge::create([
//                 'seller_id' => $seller->id,
//                 'type'      => 'Debit',
//                 'amount'    => $seller_amount_walate,
//                 'status'    => 1,
//             ]);

//             // Reduce balance from memory
//             $walletBalance -= $seller_amount_walate;

//             $success[] = "Order {$order_number} → AWB: {$awb}";
//         } catch (\Exception $e) {
//             $order_number = $orderPayload['shipments'][0]['order'] ?? 'Unknown';
//             $failures[] = "Order {$order_number}: Exception - " . $e->getMessage();
//         }
//     }

//     return [
//         'status'   => true,
//         'success'  => $success,
//         'failures' => $failures
//     ];
// }









public function fetchNdrData()
{
    try {
        $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607'; // 🔁 Replace with real token
        $uplId = 'UPL70200521839149515'; // 🔁 Replace with real UPL ID

        $url = "https://track.delhivery.com/api/cmu/get_bulk_upl/{$uplId}?verbose=true";

        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => "Token $token",
            'Content-Type'  => 'application/json',
        ])->get($url);

        // dd($response);

        if (! $response->successful()) {
            return response()->json([
                'status' => false,
                'message' => "API Error: " . $response->status(),
                'response' => $response->json()
            ]);
        }

        // ✅ Extract only AWB numbers
        $data = $response->json();
        // dd($data);
        $awbNumbers = [];

        if (!empty($data['packages']) && is_array($data['packages'])) {
            foreach ($data['packages'] as $pkg) {
                if (!empty($pkg['waybill'])) {
                    $awbNumbers[] = $pkg['waybill'];
                }
            }
        }

        return response()->json([
            'status' => true,
            'awb_numbers' => $awbNumbers
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage()
        ]);
    }
}



// public function fetchNdrData()
// {
//     try {
//         $client = new \GuzzleHttp\Client();

//         $response = $client->post('https://shipment.xpressbees.com/api/ndr', [
//             'verify'  => false,
//             'headers' => [
//                 'Authorization' => 'Bearer ' . "cscscs",
//                 'Accept'        => 'application/json',
//                 'Content-Type'  => 'application/json',
//             ],
//             'body' => '', 
//         ]);

//         $data = json_decode($response->getBody(), true);
//         // dd($data);

//         if (!empty($data['status']) && $data['status'] === true) {
//             // You can store this in DB or pass to view
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
//         return response()->json([
//             'status' => false,
//             'message' => 'API Request failed: ' . $e->getMessage()
//         ]);
//     }
// }



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

}
