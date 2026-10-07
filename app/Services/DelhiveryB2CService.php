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


class DelhiveryB2CService implements CourierServiceInterface
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

        // Check if Delhivery zone pricing is configured for this seller
        $delhiveryZoneCheck = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'LogisticProvider' => 'Delhivery',
            'status' => 1
        ])->exists();
// dd($delhiveryZoneCheck);
        if (!$delhiveryZoneCheck) {
            return [];
        }

        // Pickup Info
        $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
        $originPin = $source['pincode'];
        $pickupstate = $source['state'];
    //    echo 'xsxsxs';die;
    // dd($pickupstate, $destinationstate);
        // 🧾 Step 1: Zone Fetch (Case-Insensitive)
        $zoneData = DB::table('pincode_zones')
            ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
            ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
            ->first();
//  dd($zoneData);
        if (!$zoneData) {
            return [];
        }

        $zone = strtoupper($zoneData->zone);

        // Step 2: Weight & Payment Info
        $weight = (float)$order->package_weight; // grams
        $orderAmount = $order->collectable_amount;
        $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
        $seller_id = $order->seller_id;

        // 🧾 Step 3: Calculate Delhivery charges based on ZonePriceSetting with weight slabs
        $calculateDelhiveryCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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

        // Get zone pricing for Delhivery service
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => 'Delhivery',
            'status' => 1
        ])->first();

        if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => 'Delhivery',
        'status' => 1
        ])->first();
    }


        $charges = $calculateDelhiveryCharge($zonePricing);
        
        // Only return result if pricing exists for this service
        if ($charges === null) {
            return [];
        }

        return [[
            'serviceabilityId' => $pincodeToCheck,
            'courierName'      => 'Delhivery',
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

        // Check if Delhivery zone pricing is configured for this seller
        $delhiveryZoneCheck = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'LogisticProvider' => 'Delhivery',
            'status' => 1
        ])->exists();
// dd($delhiveryZoneCheck);
        if (!$delhiveryZoneCheck) {
            return [];
        }

        // Pickup Info
        $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
        $originPin = $source['pincode'];
        $pickupstate = $source['state'];
    //    echo 'xsxsxs';die;
    // dd($pickupstate, $destinationstate);
        // 🧾 Step 1: Zone Fetch (Case-Insensitive)
        $zoneData = DB::table('pincode_zones')
            ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
            ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
            ->first();
//  dd($zoneData);
        if (!$zoneData) {
            return [];
        }

        $zone = strtoupper($zoneData->zone);

        // Step 2: Weight & Payment Info
        $weight = (float)$order->package_weight; // grams
        $orderAmount = $order->collectable_amount;
        $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
        $seller_id = $order->seller_id;

        // 🧾 Step 3: Calculate Delhivery charges based on ZonePriceSetting with weight slabs
        $calculateDelhiveryCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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

        // Get zone pricing for Delhivery service
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => 'Delhivery',
            'status' => 1
        ])->first();

        if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => 'Delhivery',
        'status' => 1
        ])->first();
    }


        $charges = $calculateDelhiveryCharge($zonePricing);
        
        // Only return result if pricing exists for this service
        if ($charges === null) {
            return [];
        }

        return [[
            'serviceabilityId' => $pincodeToCheck,
            'courierName'      => 'Delhivery',
            'courierCharge'    => $charges['courierCharge'],
            'freightCharges'   => $charges['freightCharges'],
            'codCharge'        => $charges['codCharge'],
            'zone'             => $zone,
            'zone_courier_name'             => 'Delhivery',
            'minWeight'        => $weight,
            'volWeight'        => $weight,
        ]];
    }


//     public function getServiceability(array $params): array
//     {
//         // dd($params);
//                 // $PriceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'Delhivery'])->first();

//         $token = "a6cd5bb955fddcb41757ec23ee92cf62b6650607";

//         $url = 'https://track.delhivery.com/c/api/pin-codes/json/';

//         $order = Order::findOrFail($params['order_id']);
//         $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//         $pincodeToCheck = $consignee['pincode'];
//         // echo "Pincode is serviceable.";die;
//         $PriceSettingactiveselb = PriceSetting::where([
//             'seller_id' => $order->seller_id,
//             'LogisticProvider' => 'Delhivery'
//         ])->first();

//         // If PriceSetting exists AND disabled
//         if ($PriceSettingactiveselb && $PriceSettingactiveselb->status == '0') {
//             return []; // skip and bypass
//         }
//         // Pincode Serviceability Check
//         $response = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//             'Content-Type' => 'application/json',
//         ])->get($url, [
//             'filter_codes' => $pincodeToCheck,
//         ]);
    
//         if (!$response->successful()) {
//             Log::error('Delhivery B2C API Error', ['response' => $response->body()]);
//             return [];
//         }

//         $json = $response->json();
//         // dd($json);
//         $codes = $json['delivery_codes'][0] ?? [];
//         if (empty($codes)) {
//             return [];
//         }
//         // echo "Pincode is serviceable.";die;
//         // Rate Calculation
//         $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//         $originPin = $source['pincode'];
//         // dd($originPin);
//         $destinationPin = $consignee['pincode'];
//         $weight = $order->package_weight;
//         // $paymentType = strtolower($order->payment_type);
//         $orderAmount = $order->collectable_amount;
//         $payment = $order->payment_type;
//         if ($payment === 'cod') {
//             $paymentType = 'COD';
//         } else {
//             $paymentType = 'Pre-paid';
//         }
//         $seller_id = $order->seller_id;
//         $PriceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'Delhivery'])->first();
//         $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 30;
//         $codChargePercent = $PriceSetting ? $PriceSetting->cod_charge_parsent : 1.9;
//         $codChargeFixed = $PriceSetting ? $PriceSetting->cod_charge : 32;

//         $rates = collect(['S' => 'Surface'])->flatMap(function ($modeName, $modeCode) use (
//             $token,
//             $originPin,
//             $destinationPin,
//             $weight,
//             $params,
//             $orderAmount,
//             $paymentType,
//             $sellerPercentage,
//             $codChargePercent,
//             $codChargeFixed,
//             $PriceSetting
//         ) {

//             $resp = Http::withHeaders([
//                 'Authorization' => 'Token ' . $token,
//             ])->get("https://track.delhivery.com/api/kinko/v1/invoice/charges/.json?md={$modeCode}&ss=Delivered&d_pin={$destinationPin}&o_pin={$originPin}&cgm={$weight}&pt={$paymentType}");

// // 
//             //    dd($resp->json());
//             if (!$resp->successful()) {
//                 Log::error("Delhivery Rate API Error for $modeName", ['response' => $resp->body()]);
//                 return [];
//             }

//             $data = $resp->json();
//             if (empty($data[0]['total_amount'])) return [];

//             $codCharge = 0;
//             if ($paymentType === 'COD') {
//                 $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercent / 100) : $codChargeFixed;
//             }

//          if ($PriceSetting && isset($PriceSetting->fixed_courier_price) && $PriceSetting->fixed_courier_price > 0) {
//                 // Calculate weight multiplier based on 500g intervals
//                 $weightInKg = $weight / 1000; // Convert grams to kg
//                 $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
                
//                 // Use fixed price with weight multiplier
//                 $freightWithGST = $PriceSetting->fixed_courier_price * $weightMultiplier;
//                 $freightWithSeller = 10 * $weightMultiplier;
//                 $codCharge = 10 * $weightMultiplier;
//             } else {



//             $freight_charges = $data[0]['charge_DL'] ?? 0.0;
//             $freight = $freight_charges * 1.10;
//             $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
//             $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//             $freightWithGST =  $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);
//             }
//             //   dd($freightWithGST);
//             return [[
//                 'serviceabilityId' => $destinationPin,
//                 'courierName'      => 'Delhivery B2C (' . $modeName . ')',
//                 'courierCharge'    => round($freightWithGST, 2),
//                 'freightCharges'   => round($freightWithSeller, 2),
//                 'codCharge'        => round($codCharge, 2),
//                 'minWeight'        => $data[0]['charged_weight'],
//                 'volWeight'        => $data[0]['charged_weight'],
//             ]];
//         });

//         return $rates->toArray();
//     }


public function reversegetServiceability(array $params): array
{
    // dd($params);
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
    // dd([
    //     'serviceabilityId' => $destinationPin,
    //     'courierName'      => 'Delhivery Reverse',
    //     'courierCharge'    => $charges['courierCharge'],
    //     'freightCharges'   => $charges['freightCharges'],
    //     'codCharge'        => $charges['codCharge'],
    //     'minWeight'        => $weight,
    //     'volWeight'        => $weight,
    // ]);
    return [[
        'serviceabilityId' => $destinationPin,
        'courierName'      => 'Delhivery',
        'courierCharge'    => $charges['courierCharge'],
        'freightCharges'   => $charges['freightCharges'],
        'codCharge'        => $charges['codCharge'],
        'minWeight'        => $weight,
        'volWeight'        => $weight,
    ]];
}





    public function assignOrder($orderPayload)
    {
        // dd();
// dd($orderPayload);
        $orderPayload = json_decode(json_encode($orderPayload), true);
        $pickup = $orderPayload['pickup_location'];
        //  dd($orderPayload);
        $seller = Auth::guard('seller')->user();
        $order_number = $orderPayload['shipments'][0]['order'] ?? null;
        $order = Order::where(['order_number' => $order_number, 'seller_id' => $seller->id])->first();
        $seller_amount_walate = $order->seller_amount_walate ?? 0;
        // dd($order);


        $warehousePayload = [
            "name" => $pickup['name'],
            "email" => "test@gmail.com",
            "phone" => $pickup['phone'],
            "address" => $pickup['add'],
            "city" => $pickup['city'],
            "country" => $pickup['country'] ?? 'India',
            "pin" => (string) $pickup['pin_code'],
            "return_address" =>  $pickup['add'],
            "return_pin" => (string) $pickup['pin_code'],
            "return_city" => $pickup['city'],
            "return_state" => $orderPayload['shipments'][0]['state'] ?? 'Rajasthan',
            "return_country" => $pickup['country'] ?? 'India',
        ];
    //  dd($warehousePayload);
        try {
            // Step 1: Create Warehouse
            $warehouseUrl = "https://track.delhivery.com/api/backend/clientwarehouse/create/";

            $warehouseResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
            ])->post($warehouseUrl, $warehousePayload);

            $warehouseData = $warehouseResponse->json();
            //  dd($warehouseData);
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



            $order->courier_id = 'delhivery_b2c';
            $order->all_courier_name = 'delhivery_b2c';

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










    public function cancelShipment($awb)
    {
        $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607'; // <-- Replace with actual token

        $seller = Auth::guard('seller')->user();
        $order = Order::where('awb_number', $awb)->first();

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $token,
            'Content-Type'  => 'application/json',
        ])->post('https://track.delhivery.com/api/p/edit', [
            'waybill'      => $awb,
            'cancellation' => 'true',
        ]);

        if ($response->successful()) {

            $order->order_status = 'cancelled';
            $order->save();

            Recharge::create([
                'seller_id'   => $seller->id,
                'type'        => 'Credit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
                'description' => 'Order cancelled',
            ]);

            return [
                'status' => true,
                'message' => 'Order cancelled successfully',
                'data' => $response->json(),
                'responseCode' => $response->status()
            ];
        }

        return [
            'status' => false,
            'message' => $response->json()['error'] ?? 'Unknown error',
            'responseCode' => $response->status(),
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























    
    public function assignOrderbulk($params)
{
    // Handle bulk order assignment
    if (isset($params['shipments']) && is_array($params['shipments'])) {
        return $this->processBulkOrderAssignment($params);
    }
    
    // Handle single order (backward compatibility) - if params contains order data directly
    if (isset($params['pickup_location']) || isset($params['order_id'])) {
        return $this->processSingleOrderAssignmentBulk($params);
    }
    
    return ['status' => false, 'message' => 'No valid order data provided.'];
}

private function processBulkOrderAssignment($params)
{
    $shipments = $params['shipments'];
    $results = [];
    $successCount = 0;
    $failureCount = 0;

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    foreach ($shipments as $shipment) {
        try {
            // Reconstruct single order payload for each shipment
            $singleOrderPayload = [
                'pickup_location' => $params['pickup_location'] ?? null,
                'shipments' => [$shipment]
            ];
            
            $result = $this->processSingleOrderAssignmentBulk($singleOrderPayload);
            
            if ($result['status'] !== false && isset($result['packages'][0]['waybill'])) {
                $successCount++;
                $awb = $result['packages'][0]['waybill'];
                $order_number = $shipment['order'] ?? 'N/A';
                $results[] = "Order {$order_number}: Success - AWB: {$awb}";
            } else {
                $failureCount++;
                $order_number = $shipment['order'] ?? 'N/A';
                $message = $result['message'] ?? $result['rmk'] ?? 'Unknown error';
                $results[] = "Order {$order_number}: Failed - {$message}";
            }
            
        } catch (\Exception $e) {
            $failureCount++;
            $order_number = $shipment['order'] ?? 'N/A';
            $results[] = "Order {$order_number}: Exception - " . $e->getMessage();
        }
    }

    return [
        'status' => $successCount > 0,
        'message' => "Bulk assignment completed. Success: {$successCount}, Failed: {$failureCount}",
        'couriername' => 'delhivery_b2c',
        'results' => $results,
        'success_count' => $successCount,
        'failure_count' => $failureCount
    ];
}

private function processSingleOrderAssignmentBulk($orderPayload)
{
    $orderPayload = json_decode(json_encode($orderPayload), true);
    $pickup = $orderPayload['pickup_location'];

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    $order_number = $orderPayload['shipments'][0]['order'] ?? null;
    $order = Order::where(['order_number' => $order_number, 'seller_id' => $seller->id])->first();
    
    if (!$order) {
        return ['status' => false, 'message' => 'Order not found for order number: ' . $order_number];
    }
    
    $seller_amount_walate = $order->seller_amount_walate ?? 0;

    $warehousePayload = [
        "name" => $pickup['name'],
        "email" => "test@gmail.com",
        "phone" => $pickup['phone'],
        "address" => $pickup['add'],
        "city" => $pickup['city'],
        "country" => $pickup['country'] ?? 'India',
        "pin" => (string) $pickup['pin_code'],
        "return_address" =>  $pickup['add'],
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

        $orderCreateUrl = "https://track.delhivery.com/api/cmu/create.json";

        $orderResponse = Http::asForm()->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
        ])->post($orderCreateUrl, [
            'format' => 'json',
            'data' => json_encode($orderPayload),
        ]);

        $responseData = $orderResponse->json();

        if (!$orderResponse->successful() || ($responseData['success'] ?? false) === false) {
            return [
                'status' => false,
                'message' => 'Order creation failed: ' . ($responseData['rmk'] ?? 'Unknown error')
            ];
        }

        $order->courier_id = 'delhivery_b2c';
        $order->all_courier_name = 'delhivery_b2c';
        $order->shipping_date = Carbon::now()->format('Y-m-d');
        
        // Extract AWB number from response and update order
        if (isset($responseData['packages'][0]['waybill'])) {
            $order->awb_number = $responseData['packages'][0]['waybill'];
        }
        
        $order->save();
        
        Recharge::create([
            'seller_id'   => $seller->id,
            'type'        => 'Debit',
            'amount'      => $seller_amount_walate,
            'status'      => 1,
            'description' => 'Order created'
        ]);

        return $responseData;
        
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage()
        ];
    }
}

}
