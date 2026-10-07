<?php
// app/Services/ShadowfaxService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\PriceSetting;
use App\Models\ZonePriceSetting;
use Illuminate\Support\Facades\DB;
use App\Models\ActicvSleb;


class ShadowfaxService implements CourierServiceInterface
{

    const API_BASE_URL = 'https://dale.shadowfax.in';
    const API_ENDPOINT = '/api/v3/clients/orders/';
    const TOKEN_CACHE_KEY = 'xpressbees_api_token';
    const TOKEN_CACHE_TTL = 3600;


    /**
     * @param  array  $params  // origin, destination, etc.
     * @return array[]         // list of normalized slabs (probably only one)
     */






    
    
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
                \Log::error("Error processing order {$orderId} in Shadowfax bulk serviceability: " . $e->getMessage());
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

    // Check if Shadowfax zone pricing is configured for this seller
    $shadowfaxZoneCheck = ActicvSleb::where([
        'seller_id' => $order->seller_id,
        'LogisticProvider' => 'Shadowfax',
        'status' => 1
    ])->exists();
    //  echo 'uhhkui';die;
    if (!$shadowfaxZoneCheck) {
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

    // 🧾 Step 3: Calculate Shadowfax charges based on ZonePriceSetting with weight slabs
    $calculateShadowfaxCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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

    // Get zone pricing for Shadowfax service
    $zonePricing = ZonePriceSetting::where([
        'seller_id' => $seller_id,
        'zone' => $zone,
        'LogisticProvider' => 'Shadowfax',
        'status' => 1
    ])->first();
            if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => 'Shadowfax',
        'status' => 1
        ])->first();
            }

    $charges = $calculateShadowfaxCharge($zonePricing);
    
    // Only return result if pricing exists for this service
    if ($charges === null) {
        return [];
    }

    return [[
        'serviceabilityId' => $pincodeToCheck,
        'courierName'      => 'Shadowfax',
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

    // Check if Shadowfax zone pricing is configured for this seller
    $shadowfaxZoneCheck = ActicvSleb::where([
        'seller_id' => $order->seller_id,
        'LogisticProvider' => 'Shadowfax',
        'status' => 1
    ])->exists();
    //  echo 'uhhkui';die;
    if (!$shadowfaxZoneCheck) {
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

    // 🧾 Step 3: Calculate Shadowfax charges based on ZonePriceSetting with weight slabs
    $calculateShadowfaxCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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

    // Get zone pricing for Shadowfax service
    $zonePricing = ZonePriceSetting::where([
        'seller_id' => $seller_id,
        'zone' => $zone,
        'LogisticProvider' => 'Shadowfax',
        'status' => 1
    ])->first();
            if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => 'Shadowfax',
        'status' => 1
        ])->first();
            }

    $charges = $calculateShadowfaxCharge($zonePricing);
    
    // Only return result if pricing exists for this service
    if ($charges === null) {
        return [];
    }

    return [[
        'serviceabilityId' => $pincodeToCheck,
        'courierName'      => 'Shadowfax',
        'courierCharge'    => $charges['courierCharge'],
        'freightCharges'   => $charges['freightCharges'],
        'codCharge'        => $charges['codCharge'],
        'zone'             => $zone,
        'zone_courier_name' => 'Shadowfax',
        'minWeight'        => $weight,
        'volWeight'        => $weight,
    ]];
}


// public function getServiceability(array $params): array
// { 
//     // dd($params);
//     $order = Order::findOrFail($params['order_id']);
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $pincodeToCheck = $consignee['pincode'];
//     $destinationState = $consignee['state'];
//         $PriceSettingactiveselb = PriceSetting::where([
//             'seller_id' => $order->seller_id,
//             'LogisticProvider' => 'Shadowfax'
//         ])->first();

//         // If PriceSetting exists AND disabled
//         if ($PriceSettingactiveselb && $PriceSettingactiveselb->status == '0') {
//             return []; // skip and bypass
//         }
//     // Pickup Info
//     $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $originPin = $source['pincode'];
//     $pickupState = $source['state'];

//     // 🧭 Step 1: Find Zone (Case-insensitive)
//     $zoneData = DB::table('pincode_zones')
//         ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupState)])
//         ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationState)])
//         ->first();

//     if (!$zoneData) {
//         return [];
//     }

//     $zone = strtoupper($zoneData->zone);

//     // 🧮 Step 2: Zone-wise base prices (500gm)
//     $zonePrices500gm = [
//         'A' => 22,
//         'B' => 26,
//         'C' => 32,
//         'D' => 37,
//         'E' => 42,
//     ];

//     // 🧾 Step 3: Weight & Payment info
//     $weight = (float)$order->package_weight ?? 500; // grams
//     $orderAmount = $order->collectable_amount;
//     $paymentType = $order->payment_type === 'cod' ? 'COD' : 'Pre-paid';
//     $seller_id = $order->seller_id;

//     // 🧮 Step 4: Fetch Seller-specific pricing
//     $priceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'Shadowfax'])->first();
//     $sellerPercentage = $priceSetting->shipping_charge ?? 70;
//     $codChargePercent = $priceSetting->cod_charge_parsent ?? 1.9;
//     $codChargeFixed = $priceSetting->cod_charge ?? 28;

//     // 🧮 Step 5: Calculate Base Freight (per 500g slab)
//     $baseRate = $zonePrices500gm[$zone] ?? 0;
//     $slabs = max(1, ceil($weight / 500));
//     $freight = $baseRate * $slabs;

//     // 🧮 Step 6: Apply COD, Seller %, GST
//     $codCharge = 0;
//     if ($paymentType === 'COD') {
//         $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercent / 100) : $codChargeFixed;
//     }

//     // Seller margin added
//     $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
//     $freightWithCOD = $freightWithSeller + $codCharge;

//     // Add GST
//     $freightWithGST = $freightWithCOD + ($freightWithCOD * 18 / 100);

   
//     // 🧾 Step 7: Prepare Final Response
//     return [[
//         'serviceabilityId' => $pincodeToCheck,
//         'courierName'      => 'Shadowfax',
//         'courierCharge'    => round($freightWithGST, 2),
//         'freightCharges'   => round($freightWithSeller, 2),
//         'codCharge'        => round($codCharge, 2),
//         'zone'             => $zone,
//         'minWeight'        => $weight,
//         'volWeight'        => $weight,
//     ]];
// }




public function assignOrder($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'Shadowfax';
    $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    // 🧾 Wallet Balance Calculation
    $credit = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');
    $debit = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');
    $walletBalance = $credit - $debit;

    // 🧾 Fetch Order by ID
    $order = Order::find($order_id);
    if (!$order) {
        return ['status' => false, 'message' => 'Order not found.'];
    }

    if (
        $seller->negative_balance != '1' &&
        ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
    ) {
        return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    }

    // Decode pickup/consignee/order items
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // 🧾 Prepare Shadowfax Payload
    $shadowfaxPayload = [
        "order_type" => "marketplace",
        "order_details" => [
            "client_order_id"       => $order->order_number,
            "actual_weight"         => (float)($order->package_weight ?? 1000),
            "volumetric_weight"     => round((($order->package_length * $order->package_breadth * $order->package_height) / 5000), 3),
            "product_value"         => (float)$order->order_amount,
            "payment_mode"          => strtolower($order->payment_type) == 'cod' ? 'COD' : 'Prepaid',
            "cod_amount"            => strtolower($order->payment_type) == 'cod' ? (float)$order->collectable_amount : 0,
            "promised_delivery_date" => null,
            "total_amount"          => (float)$order->order_amount,
            "eway_bill"             => $order->eway_bill ?? null,
            "gstin_number"          => $seller->gstin ?? null,
            "order_service"         => null,
        ],
        "customer_details" => [
            "name"              => $consignee['name'] ?? '',
            "contact"           => $consignee['phone'] ?? '',
            "address_line_1"    => $consignee['address'] ?? '',
            "address_line_2"    => $consignee['address_2'] ?? '',
            "city"              => $consignee['city'] ?? '',
            "state"             => $consignee['state'] ?? '',
            "pincode"           => (string)$consignee['pincode'],
            "alternate_contact" => $consignee['phone'] ?? null,
            "latitude"          => null,
            "longitude"         => null,
        ],
        "pickup_details" => [
            "name"           => $pickup['name'] ?? '',
            "contact"        => $pickup['phone'] ?? '',
            "address_line_1" => $pickup['address'] ?? '',
            "address_line_2" => $pickup['address_2'] ?? '',
            "city"           => $pickup['city'] ?? '',
            "state"          => $pickup['state'] ?? '',
            "pincode"        => (string)$pickup['pincode'],
            "latitude"       => null,
            "longitude"      => null,
            "unique_code"    => null,
        ],
        "rts_details" => [
            "name"           => $pickup['name'] ?? '',
            "contact"        => $pickup['phone'] ?? '',
            "address_line_1" => $pickup['address'] ?? '',
            "address_line_2" => $pickup['address_2'] ?? '',
            "city"           => $pickup['city'] ?? '',
            "state"          => $pickup['state'] ?? '',
            "pincode"        => (string)$pickup['pincode'],
            "email"          => $pickup['email'] ?? 'support@example.com',
            "latitude"       => null,
            "longitude"      => null,
            "unique_code"    => null,
        ],
        "product_details" => array_map(function ($item) {
            return [
                "hsn_code" => $item['hsn'] ?? null,
                "invoice_no" => $item['invoice_no'] ?? null,
                "sku_name" => $item['name'] ?? $item['sku_name'] ?? 'Product',
                "sku_id" => $item['sku'] ?? $item['sku_id'] ?? null,
                "category" => $item['category'] ?? null,
                "price" => $item['price'] ?? 0,
                "seller_details" => [
                    "seller_name" => $item['seller_name'] ?? null,
                    "seller_state" => $item['seller_state'] ?? null,
                    "gstin_number" => $item['gstin_number'] ?? null,
                ],
                "taxes" => [
                    "cgst" => $item['cgst'] ?? 0,
                    "sgst" => $item['sgst'] ?? 0,
                    "igst" => $item['igst'] ?? 0,
                    "total_tax" => $item['total_tax'] ?? 0,
                ],
                "additional_details" => [
                    "requires_extra_care" => $item['requires_extra_care'] ?? null,
                    "type_extra_care" => $item['type_extra_care'] ?? null,
                    "quantity" => $item['quantity'] ?? 1,
                ],
            ];
        }, $orderItems ?? []),
    ];
//  dd($shadowfaxPayload);
    // 🛰️ API Request
    try {
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://dale.shadowfax.in',
            'timeout' => 60,
            'connect_timeout' => 60,
            'verify' => false,
        ]);

        $response = $client->post('api/v3/clients/orders/', [
            'headers' => [
                'Authorization' => 'Token ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json' => $shadowfaxPayload,
        ]);

        $responseData = json_decode($response->getBody(), true);
    //   dd($responseData);
        // ✅ Success Handling
        if (!empty($responseData['message']) && $responseData['message'] === "Success") {
            $awb = $responseData['data']['awb_number'] ?? null;

            // Update Order
            $order->courier_id = 'shadowfax';
            $order->all_courier_name = 'Shadowfax';
            $order->awb_number = $awb;
            $order->shipping_date = now()->format('Y-m-d');
            $order->save();

            // Debit Wallet
            Recharge::create([
                'seller_id'   => $seller->id,
                'type'        => 'Debit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
                'description' => 'Order created'
            ]);

            return [
                'status'          => true,
                'message'         => 'Order successfully assigned to Shadowfax.',
                'couriername'     => 'shadowfax',
                'awb_number'      => $awb,
                'tracking_status' => 'Created',
                'api_response'    => $responseData,
            ];
        } else {
            return [
                'status'  => false,
                'message' => 'Shadowfax API returned error.',
                'data'    => $responseData,
            ];
        }

    } catch (\Exception $e) {
        // dd($e->getMessage());
        \Log::error('Shadowfax API Request Failed: ' . $e->getMessage());
        return [
            'status'  => false,
            'message' => 'Shadowfax API Request Failed: ' . $e->getMessage(),
        ];
    }
}


// public function assignOrder($params)
// {

//     // dd($params);
//     $order_id = $params['order_id'];
//     $provider_name = $params['provider_name'] ?? 'Shadowfax';
//     $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

//     $seller = Auth::guard('seller')->user();
//     if (!$seller || $seller->status != 1) {
//         return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
//     }

//     // 🧾 Wallet Balance Check
//     $credit = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $debit = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $walletBalance = $credit - $debit;

//     // 🧾 Fetch Order
//     $order = Order::find($order_id);
//     if (!$order) {
//         return ['status' => false, 'message' => 'Order not found.'];
//     }

//     if (
//         $seller->negative_balance != '1' &&
//         ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
//     ) {
//         return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
//     }

//     // Decode Pickup & Consignee JSON
//     $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

//     // 🧩 Shadowfax Payload
//     $shadowfaxPayload = [
//         "order_type" => "marketplace",
//         "order_details" => [
//             "client_order_id"       => $order->order_number,
//             "actual_weight"         => (float)($order->package_weight ?? 1000),
//             "volumetric_weight"     => round((($order->package_length * $order->package_breadth * $order->package_height) / 5000), 3),
//             "product_value"         => (float)$order->order_amount,
//             "payment_mode"          => strtolower($order->payment_type) == 'cod' ? 'COD' : 'Prepaid',
//             "cod_amount"            => strtolower($order->payment_type) == 'cod' ? (float)$order->collectable_amount : 0,
//             "promised_delivery_date" => null,
//             "total_amount"          => (float)$order->order_amount,
//             "eway_bill"             => $order->eway_bill ?? null,
//             "gstin_number"          => $seller->gstin ?? null,
//             "order_service"         => null,
//         ],

//         "customer_details" => [
//             "name"              => $consignee['name'] ?? '',
//             "contact"           => $consignee['phone'] ?? '',
//             "address_line_1"    => $consignee['address'] ?? '',
//             "address_line_2"    => $consignee['address_2'] ?? '',
//             "city"              => $consignee['city'] ?? '',
//             "state"             => $consignee['state'] ?? '',
//             "pincode"           => (string)$consignee['pincode'],
//             "alternate_contact" => $consignee['phone'] ?? null,
//             "latitude"          => null,
//             "longitude"         => null,
//         ],

//         "pickup_details" => [
//             "name"           => $pickup['name'] ?? '',
//             "contact"        => $pickup['phone'] ?? '',
//             "address_line_1" => $pickup['address'] ?? '',
//             "address_line_2" => $pickup['address_2'] ?? '',
//             "city"           => $pickup['city'] ?? '',
//             "state"          => $pickup['state'] ?? '',
//             "pincode"        => (string)$pickup['pincode'],
//             "latitude"       => null,
//             "longitude"      => null,
//             "unique_code"    => null,
//         ],

//         "rts_details" => [
//             "name"           => $pickup['name'] ?? '',
//             "contact"        => $pickup['phone'] ?? '',
//             "address_line_1" => $pickup['address'] ?? '',
//             "address_line_2" => $pickup['address_2'] ?? '',
//             "city"           => $pickup['city'] ?? '',
//             "state"          => $pickup['state'] ?? '',
//             "pincode"        => (string)$pickup['pincode'],
//             "email"          => $pickup['email'] ?? 'support@example.com',
//             "latitude"       => null,
//             "longitude"      => null,
//             "unique_code"    => null,
//         ],

//         "product_details" => array_map(function ($item) {
//             return [
//                 "hsn_code" => $item['hsn'] ?? null,
//                 "invoice_no" => $item['invoice_no'] ?? null,
//                 "sku_name" => $item['name'] ?? $item['sku_name'] ?? 'Product',
//                 "sku_id" => $item['sku'] ?? $item['sku_id'] ?? null,
//                 "category" => $item['category'] ?? null,
//                 "price" => $item['price'] ?? 0,
//                 "seller_details" => [
//                     "seller_name" => $item['seller_name'] ?? null,
//                     "seller_state" => $item['seller_state'] ?? null,
//                     "gstin_number" => $item['gstin_number'] ?? null,
//                 ],
//                 "taxes" => [
//                     "cgst" => $item['cgst'] ?? 0,
//                     "sgst" => $item['sgst'] ?? 0,
//                     "igst" => $item['igst'] ?? 0,
//                     "total_tax" => $item['total_tax'] ?? 0,
//                 ],
//                 "additional_details" => [
//                     "requires_extra_care" => $item['requires_extra_care'] ?? null,
//                     "type_extra_care" => $item['type_extra_care'] ?? null,
//                     "quantity" => $item['quantity'] ?? 1,
//                 ],
//             ];
//         }, $orderItems ?? []),
//     ];
// //    dd($shadowfaxPayload);
//     // 🔗 API Call
//     try {
//         $response = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//             'Content-Type'  => 'application/json',
//             'Accept'        => 'application/json',
//         ])->post('https://api.shadowfax.in/shipments/create', $shadowfaxPayload);

//         $responseData = $response->json();
//         // $responseData = json_decode($response->getBody(), true);
//     //  dd($responseData);
//         if (!empty($responseData['message']) && $responseData['message'] === "Success") {
//             $awb = $responseData['data']['awb_number'] ?? null;

//             $order->courier_id = 'shadowfax';
//             $order->all_courier_name = 'Shadowfax';
//             $order->awb_number = $awb;
//             $order->shipping_date = now()->format('Y-m-d');
//             $order->save();

//             Recharge::create([
//                 'seller_id'   => $seller->id,
//                 'type'        => 'Debit',
//                 'amount'      => $order->seller_amount_walate,
//                 'status'      => 1,
//                 'description' => 'Shadowfax Order created'
//             ]);

//             return [
//                 'status'          => true,
//                 'message'         => 'Order successfully assigned to Shadowfax.',
//                 'couriername'     => 'shadowfax',
//                 'awb_number'      => $awb,
//                 'tracking_status' => 'Created',
//                 'api_response'    => $responseData,
//             ];
//         } else {
//             return [
//                 'status'  => false,
//                 'message' => 'Shadowfax API returned error.',
//                 'data'    => $responseData,
//             ];
//         }
//     } catch (\Exception $e) {
//         \Log::error('Shadowfax API Request Failed: ' . $e->getMessage());
//         return [
//             'status'  => false,
//             'message' => 'Shadowfax API Request Failed: ' . $e->getMessage(),
//         ];
//     }
// }





// public function assignOrder($params)
// {
//     $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

//     $seller = Auth::guard('seller')->user();
//     if (!$seller || $seller->status != 1) {
//         return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
//     }

//     // 🧾 Step 1: Wallet Balance Calculation
//     $credit = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $debit = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $walletBalance = $credit - $debit;

//     // 🧾 Step 2: Fetch Order by order_number
//     $order = Order::where('order_number', $params->order_details->client_order_id)->first();
//     if (!$order) {
//         return ['status' => false, 'message' => 'Order not found.'];
//     }

//     $orderAmount = $order->seller_amount_walate ?? 0;

//     // 🧾 Step 3: Wallet balance validation (with negative balance check)
//     if (
//         $seller->negative_balance != '1' &&
//         ($walletBalance < $orderAmount || $walletBalance < 150)
//     ) {
//         return [
//             'status' => false,
//             'message' => 'Insufficient wallet balance. Please recharge your wallet.',
//         ];
//     }

//     // 🧾 Step 4: API Client setup
//     $client = new \GuzzleHttp\Client([
//         'base_uri' => self::API_BASE_URL,
//         'timeout' => 60,
//         'connect_timeout' => 60,
//         'verify' => false,
//     ]);

//     $requestData = $params;

//     try {
//         $response = $client->post(self::API_ENDPOINT, [
//             'headers' => [
//                 'Authorization' => 'Token ' . $token,
//                 'Content-Type'  => 'application/json',
//                 'Accept'        => 'application/json',
//             ],
//             'json' => $requestData,
//         ]);

//         $responseData = json_decode($response->getBody(), true);

//         // 🧾 Step 5: Handle API Response
//         if (!empty($responseData['message']) && $responseData['message'] === "Success") {

//             $order->courier_id = 'shadowfax';
//             $order->all_courier_name = 'Shadowfax';
//             $order->awb_number = $responseData['data']['awb_number'] ?? null;
//             $order->shipping_date = now()->format('Y-m-d');
//             $order->save();

//             // Deduct wallet amount
//             Recharge::create([
//                 'seller_id'   => $seller->id,
//                 'type'        => 'Debit',
//                 'amount'      => $orderAmount,
//                 'status'      => 1,
//                 'description' => 'Shadowfax Order created'
//             ]);

//             return [
//                 'status'        => true,
//                 'message'       => 'Order successfully assigned to Shadowfax.',
//                 'couriername'   => 'shadowfax',
//                 'awb_number'    => $responseData['data']['awb_number'] ?? null,
//                 'tracking_status' => 'Created',
//                 'api_response'  => $responseData,
//             ];
//         } else {
//             return [
//                 'status'  => false,
//                 'message' => 'Shadowfax API returned error.',
//                 'data'    => $responseData,
//             ];
//         }
//     } catch (\Exception $e) {
//         \Log::error('Shadowfax API Request Failed: ' . $e->getMessage());
//         return [
//             'status'  => false,
//             'message' => 'Shadowfax API Request Failed: ' . $e->getMessage(),
//         ];
//     }
// }


    // public function assignOrderShadowfax($params)
    // {
    //         // dd($params);
    //     $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

    //     $seller = Auth::guard('seller')->user();
    //     if (!$seller || $seller->status != 1) {
    //         return redirect()->back()->with('error', 'Unauthorized or inactive seller.');
    //     }
    //     // Get wallet balance
    // $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
    //     ->where('status', 1)
    //     ->where('type', 'Credit')
    //     ->sum('amount');
    // $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
    //     ->where('type', 'Debit')
    //     ->sum('amount');
    // $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;
    // $order = Order::where('order_number', $params->order_details->client_order_id)->first();
    // // dd($order);

    //     $orderamount = $order->seller_amount_walate;

    // if (!$order) {
    //      return [
    //         'status'  => false,
    //         'message' => 'error', 'Order not found.',
    //     ];
    // }
    // if ($walletBalance < $orderamount) {
    //         return [
    //         'status'  => false,
    //         'message' => 'error', 'Insufficient wallet balance. Please recharge your wallet.',
    //     ];
    // }

    //     $client = new \GuzzleHttp\Client([
    //         'base_uri' => self::API_BASE_URL,
    //         'timeout' => 60,
    //         'connect_timeout' => 60,
    //         'verify' => false,
    //     ]);
    //     $requestData = $params;
    //     try {

    //         $response = $client->post(self::API_ENDPOINT, [
    //             'headers' => [
    //                 'Authorization' => 'Token ' . $token,
    //                 'Content-Type'  => 'application/json',
    //                 'Accept'   => 'application/json',
    //             ],
    //             'json' => $requestData,
    //         ]);
    //         //   dd($response);
    //         $responseData = json_decode($response->getBody(), true);
    //         //  dd($responseData);

    //         // if ($responseData['message'] === "Success") {
    //             if (!empty($responseData['message']) && $responseData['message'] === "Success") {
    //                 // echo 'fvsdvv';die;
    //          //   $id = Order::where('order_number', $params->order_number)->first();
    //             $id = Order::where('order_number', $params->order_details->client_order_id)->first();
                         
    //             $order = Order::find($id->id);
    //             $order->courier_id = 'shadowfax';
    //             $order->save();

    //             Recharge::create([
    //                     'seller_id'   => $seller->id,
    //                     'type'        => 'Debit',
    //                     'amount'      => $orderamount,
    //                     'status'      => 1,
    //                 ]);
    //             // echo 'asxasx';die;
    //             $responseData = json_decode($response->getBody(), true);

    //             return $responseData;
    //         } else {

    //          return [
    //             'status'  => false,
    //             'message' => 'API returned error.',
    //             'data'    => $responseData,
    //         ];
    //         }
    //     } catch (\Exception $e) {
    //         // Log or handle the error
    //         \Log::error('API Request Failed: ' . $e->getMessage());

    //         return [
    //             'status' => false,
    //             'message' => 'API Request Failed: ' . $e->getMessage()
    //         ];
    //     }
    // }




    // public function cancelShipment(string $awb): array
    // {
    //     // dd($awb);
    //     $url   = "https://private-anon-9a4bb70501-sfxreversepickupsellerdelivery.apiary-mock.com/api/v2/clients/requests/mark_cancel";
    //     // $token = $this->fetchAuthToken();
    //     $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

    //     $response = Http::withHeaders([
    //         'Authorization' => "Token {$token}",
    //         'Accept'        => 'application/json',
    //         'Content-Type'  => 'application/json',
    //     ])
    //         ->post($url, [
    //             'request_id' => $awb,
    //         ]);
    //         // dd($response);
    //     $responseData = json_decode($response->getBody(), true);
    //     //   dd($responseData);
    //     if (! $response->successful()) {
    //         return [
    //             'error'   => 'XpressBees cancel API error',
    //             'status'  => $response->status(),
    //             'message' => $response->body(),
    //         ];
    //     }
    //   $seller = Auth::guard('seller')->user();
    //   $order = Order::where('awb_number', $awb)->first();
    //   $order->order_status = 'cancelled';
    //   $order->save();
    //     Recharge::create([
    //             'seller_id'   => $seller->id,
    //             'type'        => 'Credit',
    //             'amount'      => $order->seller_amount_walate,
    //             'status'      => 1,
    //             'description' => 'cancelled',

    //         ]);
    //     // dd($response);
    //     return $response->json();
    // }

 

//     public function cancelShipment($awb)
// {
//     try {
//         // 🔗 Shadowfax Cancel API URL
//         $url = "https://dale.shadowfax.in/api/v3/clients/orders/cancel/";

//         // 🔑 Shadowfax Token
//         $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

//         // 🔍 Find seller & order
//         $seller = Auth::guard('seller')->user();
//         $order = Order::where('awb_number', $awb)->first();

//         if (!$order) {
//             return [
//                 'status' => false,
//                 'message' => 'Order not found for AWB: ' . $awb,
//                 'responseCode' => 404
//             ];
//         }

//         // 🧾 Prepare payload
//         $payload = [
//             "request_id" => $awb,
//             "cancel_remarks" => "Request cancelled by customer",
//         ];

//         // 🌐 Make API Request (same as cURL)
//         $response = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//             'Content-Type'  => 'application/json',
//         ])->post($url, $payload);

//         $responseData = $response->json();
//         dd($responseData);

//         // ✅ Check Success
//         if ($response->successful() && !empty($responseData['status']) && $responseData['status'] == 'success') {
//             // Update order in DB
//             $order->order_status = 'cancelled';
//             // $order->shipping_status = 'cancelled';
//             $order->save();

//             // Refund seller
//             Recharge::create([
//                 'seller_id'   => $seller->id,
//                 'type'        => 'Credit',
//                 'amount'      => $order->seller_amount_walate,
//                 'status'      => 1,
//                 'description' => 'Shadowfax order cancelled and wallet refunded',
//             ]);

//             return [
//                 'status' => true,
//                 'message' => 'Shadowfax shipment cancelled successfully.',
//                 'awb_number' => $awb,
//                 'response' => $responseData,
//                 'responseCode' => $response->status(),
//             ];
//         }

//         // ❌ If cancellation failed
//         return [
//             'status' => false,
//             'message' => $responseData['message'] ?? 'Failed to cancel Shadowfax shipment.',
//             'response' => $responseData,
//             'responseCode' => $response->status(),
//         ];

//     } catch (\Exception $e) {
//         \Log::error('Shadowfax Cancel API Failed: ' . $e->getMessage());

//         return [
//             'status' => false,
//             'message' => 'Shadowfax API Request Failed: ' . $e->getMessage(),
//         ];
//     }
// }

 


public function cancelShipment($awb)
{
    try {
        $url = "https://dale.shadowfax.in/api/v3/clients/orders/cancel/";
        $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

        $seller = Auth::guard('seller')->user();
        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found for AWB: ' . $awb,
                'responseCode' => 404
            ];
        }

        $payload = [
            "request_id" => $awb,
            "cancel_remarks" => "Request cancelled by customer",
        ];

        // Use DELETE method as per Shadowfax API documentation
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $payload);

        $responseData = $response->json();
        // dd($responseData);
        // ✅ Check if cancellation succeeded
        if ($response->successful()) {
            $order->order_status = 'cancelled';
            $order->save();

            Recharge::create([
                'seller_id'   => $seller->id,
                'type'        => 'Credit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
                'description' => 'Shadowfax order cancelled',
            ]);

            return [
                'status' => true,
                'message' => 'Shadowfax shipment cancelled successfully',
                'awb_number' => $awb,
                'response' => $responseData,
                'responseCode' => $response->status(),
            ];
        }

        // ❌ If cancellation failed
        return [
            'status' => false,
            'message' => $responseData['message'] ?? 'Failed to cancel shipment.',
            'response' => $responseData,
            'responseCode' => $response->status(),
        ];

    } catch (\Exception $e) {
        \Log::error('Shadowfax Cancel API Failed: ' . $e->getMessage());

        return [
            'status' => false,
            'message' => 'Shadowfax API Request Failed: ' . $e->getMessage(),
        ];
    }
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
    $provider_name = $params['provider_name'] ?? 'Shadowfax';
    $results = [];
    $successCount = 0;
    $failureCount = 0;

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    foreach ($orderIds as $orderId) {
        try {
            $singleParams = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
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
        'couriername' => 'shadowfax',
        'results' => $results,
        'success_count' => $successCount,
        'failure_count' => $failureCount
    ];
}

private function processSingleOrderAssignmentBulk($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'Shadowfax';
    $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

    $seller = Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    // Wallet Balance Calculation
    $credit = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');
    $debit = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');
    $walletBalance = $credit - $debit;

    // Fetch Order by ID
    $order = Order::find($order_id);
    if (!$order) {
        return ['status' => false, 'message' => 'Order not found.'];
    }

    if (
        $seller->negative_balance != '1' &&
        ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
    ) {
        return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    }

    // Decode pickup/consignee/order items
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // Prepare Shadowfax Payload
    $shadowfaxPayload = [
        "order_type" => "marketplace",
        "order_details" => [
            "client_order_id"       => $order->order_number,
            "actual_weight"         => (float)($order->package_weight ?? 1000),
            "volumetric_weight"     => round((($order->package_length * $order->package_breadth * $order->package_height) / 5000), 3),
            "product_value"         => (float)$order->order_amount,
            "payment_mode"          => strtolower($order->payment_type) == 'cod' ? 'COD' : 'Prepaid',
            "cod_amount"            => strtolower($order->payment_type) == 'cod' ? (float)$order->collectable_amount : 0,
            "promised_delivery_date" => null,
            "total_amount"          => (float)$order->order_amount,
            "eway_bill"             => $order->eway_bill ?? null,
            "gstin_number"          => $seller->gstin ?? null,
            "order_service"         => null,
        ],
        "customer_details" => [
            "name"              => $consignee['name'] ?? '',
            "contact"           => $consignee['phone'] ?? '',
            "address_line_1"    => $consignee['address'] ?? '',
            "address_line_2"    => $consignee['address_2'] ?? '',
            "city"              => $consignee['city'] ?? '',
            "state"             => $consignee['state'] ?? '',
            "pincode"           => (string)$consignee['pincode'],
            "alternate_contact" => $consignee['phone'] ?? null,
            "latitude"          => null,
            "longitude"         => null,
        ],
        "pickup_details" => [
            "name"           => $pickup['name'] ?? '',
            "contact"        => $pickup['phone'] ?? '',
            "address_line_1" => $pickup['address'] ?? '',
            "address_line_2" => $pickup['address_2'] ?? '',
            "city"           => $pickup['city'] ?? '',
            "state"          => $pickup['state'] ?? '',
            "pincode"        => (string)$pickup['pincode'],
            "latitude"       => null,
            "longitude"      => null,
            "unique_code"    => null,
        ],
        "rts_details" => [
            "name"           => $pickup['name'] ?? '',
            "contact"        => $pickup['phone'] ?? '',
            "address_line_1" => $pickup['address'] ?? '',
            "address_line_2" => $pickup['address_2'] ?? '',
            "city"           => $pickup['city'] ?? '',
            "state"          => $pickup['state'] ?? '',
            "pincode"        => (string)$pickup['pincode'],
            "email"          => $pickup['email'] ?? 'support@example.com',
            "latitude"       => null,
            "longitude"      => null,
            "unique_code"    => null,
        ],
        "product_details" => array_map(function ($item) {
            return [
                "hsn_code" => $item['hsn'] ?? null,
                "invoice_no" => $item['invoice_no'] ?? null,
                "sku_name" => $item['name'] ?? $item['sku_name'] ?? 'Product',
                "sku_id" => $item['sku'] ?? $item['sku_id'] ?? null,
                "category" => $item['category'] ?? null,
                "price" => $item['price'] ?? 0,
                "seller_details" => [
                    "seller_name" => $item['seller_name'] ?? null,
                    "seller_state" => $item['seller_state'] ?? null,
                    "gstin_number" => $item['gstin_number'] ?? null,
                ],
                "taxes" => [
                    "cgst" => $item['cgst'] ?? 0,
                    "sgst" => $item['sgst'] ?? 0,
                    "igst" => $item['igst'] ?? 0,
                    "total_tax" => $item['total_tax'] ?? 0,
                ],
                "additional_details" => [
                    "requires_extra_care" => $item['requires_extra_care'] ?? null,
                    "type_extra_care" => $item['type_extra_care'] ?? null,
                    "quantity" => $item['quantity'] ?? 1,
                ],
            ];
        }, $orderItems ?? []),
    ];

    // API Request
    try {
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://dale.shadowfax.in',
            'timeout' => 60,
            'connect_timeout' => 60,
            'verify' => false,
        ]);

        $response = $client->post('api/v3/clients/orders/', [
            'headers' => [
                'Authorization' => 'Token ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json' => $shadowfaxPayload,
        ]);

        $responseData = json_decode($response->getBody(), true);
        
        // Success Handling
        if (!empty($responseData['message']) && $responseData['message'] === "Success") {
            $awb = $responseData['data']['awb_number'] ?? null;

            // Update Order
            $order->courier_id = 'shadowfax';
            $order->all_courier_name = 'Shadowfax';
            $order->awb_number = $awb;
            $order->shipping_date = now()->format('Y-m-d');
            $order->save();

            // Debit Wallet
            Recharge::create([
                'seller_id'   => $seller->id,
                'type'        => 'Debit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
                'description' => 'Order created'
            ]);

            return [
                'status'          => true,
                'message'         => 'Order successfully assigned to Shadowfax.',
                'couriername'     => 'shadowfax',
                'awb_number'      => $awb,
                'tracking_status' => 'Created',
                'api_response'    => $responseData,
            ];
        } else {
            return [
                'status'  => false,
                'message' => 'Shadowfax API returned error.',
                'data'    => $responseData,
            ];
        }

    } catch (\Exception $e) {
        \Log::error('Shadowfax API Request Failed: ' . $e->getMessage());
        return [
            'status'  => false,
            'message' => 'Shadowfax API Request Failed: ' . $e->getMessage(),
        ];
    }
}


}
