<?php
// app/Services/DelhiveryB2BService.php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PriceSetting;
use App\Models\Recharge;
use Carbon\Carbon;
use App\Models\Warehouse;
use App\Models\ZonePriceSetting;
use Illuminate\Support\Facades\DB;
use App\Models\ActicvSleb;

class BoxdService implements CourierServiceInterface
{


    /**
     * Check serviceability via Boxd Pincode API.
     *
     * @param  array  $params  // origin, destination, etc.
     * @return array[]         // normalized serviceability entries
     */

    const API_BASE_URL = 'https://backend.boxdlogistics.in';
    const API_ENDPOINT = '/vendor/v1/shipment/shipment_rate_time';
    const TOKEN_CACHE_KEY = 'boxd_api_token';
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
                \Log::error("Error processing order {$orderId} in Boxd bulk serviceability: " . $e->getMessage());
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





public function getServiceability(array $params): array
{
    $order = Order::findOrFail($params['order_id']);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pincodeToCheck = $consignee['pincode'];
    $destinationstate = $consignee['state'];

    // Get only the active ParcelX services for this seller
    // Include all possible case variations that might exist in database
    $parcelxServices = [
        'boxd_Bluedart_500gm',
        'Ekart500gm_boxd'
    ];
    
    $activeParcelxServicesRaw = ActicvSleb::where([
        'seller_id' => $order->seller_id,
        'status' => 1
    ])->whereIn('LogisticProvider', $parcelxServices)
      ->pluck('LogisticProvider')
      ->toArray();
    //   dd($activeParcelxServicesRaw);
    
    // Convert to lowercase for case-insensitive comparison
    $activeParcelxServices = array_map('strtolower', $activeParcelxServicesRaw);
    // dd($activeParcelxServices);
    //   dd($activeParcelxServices);
    // Debug: Check what active services we found
    // dd('Active services for seller ' . $order->seller_id . ':', $activeParcelxServices);
    
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

    // 🧾 Step 3: Calculate ParcelX charges based on ZonePriceSetting for each service
    $calculateParcelxCharge = function ($zonePricing, $weightSlab = 500) use ($weight, $paymentType, $orderAmount) {
        if (!$zonePricing) {
            return null; // Skip if no pricing found
        }
        
        // Calculate weight slabs based on service type
        // For each service, calculate how many slabs are needed based on their specific weight interval
        $slabs = max(1, ceil($weight / $weightSlab));
        
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

    // 🧮 Step 4: Build final response with individual pricing for each service
    $slabs = [
   
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Ekart500gm_boxd', 'logisticProvider' => 'Ekart500gm_boxd', 'weightSlab' => 500],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Bluedartbox_500gm', 'logisticProvider' => 'boxd_bluedart_500gm', 'weightSlab' => 500],
    ];

    $results = [];
    foreach ($slabs as $slab) {
        // Only process services that are active for this seller (case-insensitive check)
        if (!in_array(strtolower($slab['logisticProvider']), $activeParcelxServices)) {
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

        $charges = $calculateParcelxCharge($zonePricing, $slab['weightSlab']);
        
        // Debug: Check what's happening with each service
        // dd('Processing: ' . $slab['logisticProvider'], 'Zone Pricing:', $zonePricing, 'Charges:', $charges);
        
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
//  dd($results);
    return $results;
}







public function registerHub(array $hubDetails)
{
    // Authenticated Seller ID
    $sellerId = Auth::guard('seller')->id();
    // dd($hubDetails);

    // Check for existing warehouse record
    $warehouse = Warehouse::where('seller_id', $sellerId)
        // ->where('address_title', $hubDetails['hub_name'])
        ->where('pincode', $hubDetails['pincode'])
        ->where('phone', $hubDetails['hub_phone'])
        ->first();
        // dd($warehouse->box_d_address_id);
    // ✅ Return existing box_d_address_id if present
    if ($warehouse && !empty($warehouse->box_d_address_id)) {
        return $warehouse->box_d_address_id;
    }
    // echo 'scscs';die;
    $url = 'https://backend.boxdlogistics.in/vendor/v1/shipment/add_warehouse';

    $payload = [
        "full_name" => $hubDetails['hub_name'],
        "pincode"   => $hubDetails['pincode'],
        "address"   => $hubDetails['address1'],
        "email"     => $hubDetails['email'],
        "mobile"    => $hubDetails['hub_phone'],
    ];

    $response = Http::withHeaders([
        'Access-Control-Allow-Origin' => '*',
        'Content-Type'                => 'application/json',
        'secretkey'                   => 'POVHFT',
        'customerid'                  => 'c1754533690129',
    ])->post($url, $payload);

    if (!$response->successful()) {
        return 'RANDOM-FALLBACK-ID-001';  // Random fallback ID
    }

    $responseData = $response->json();
    //  dd($responseData);
    if ($responseData['status'] && isset($responseData['output']['address_id'])) {
        $addressId = $responseData['output']['address_id'];
        if ($warehouse) {
            // echo'sdsdsdd';die;
            $warehouse->box_d_address_id = $addressId;
            $warehouse->save();
            // dd($warehouse);
        }
        return $addressId;
    }
            // echo'vicky';die;



    // Fallback address_id if API failed or no address_id in response
    return 'RANDOM-FALLBACK-ID-001';
}




    public function assignOrder($params)
    {
// dd($params);
        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'];
        // dd($provider_name);
        if ($provider_name == 'Ekart 500gm') {
            // $productId = "1746709645240";
            // $carrierId = "1738577045";
            $courierId = "Ekart 500gm";
            $productId = "1770095900731";
            $carrierId = "1765284951";
            $logistic_name = 'Ekart 500gm';
        } elseif ($provider_name == 'BlueDart Air 500 GM') {
            $productId = "1762763121502";
            $carrierId = "1738577045";
            $courierId = "678b3ac540f9b7f91a8b4c3f";
            $logistic_name = 'BlueDart Air 500 GM';
        } elseif ($provider_name == 'Delhivery Air' || $provider_name == 'Delhivery 250gms') {
            // Both use the same working configuration
            $productId = "1753163038641";
            $carrierId = "1656013556";
            $courierId = "1456367975";
            $logistic_name = 'Delhivery 250gms';
        } else {
            // echo 'xsxsx';die;
            return ['status' => false, 'message' => 'Invalid provider name.'];

        }


        $seller = Auth::guard('seller')->user();
    // dd($seller);
        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
        }

        // Wallet balance check
        $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
        $walletBalance = $credit - $debit;

        $order = Order::where('id', $order_id)->first();
    //  dd((float)($order->package_weight / 1000));
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

        // Register warehouse and get address ID
        $addressId = $this->registerHub([
            "hub_name" => $pickup['warehouse_name'],
            "pincode" => $pickup['pincode'],
            "address1" => $pickup['address'],
            "hub_phone" => $pickup['phone'],
            "email" => $pickup['email'] ?? 'warehouse@example.com'
        ]);
        // dd($addressId);
        if (!$addressId) {
            $addressId = "1738673293717";
            Log::warning('Using fallback address_id as warehouse registration failed');
        }


        // Prepare items array
        $items = [];
        $totalValue = 0;
        foreach ($orderItems as $item) {
            $itemValue = (float)($item['price'] ?? 0);
            $totalValue += $itemValue;
            $items[] = [
                "item_value" => $itemValue,
                "item_name" => $item['name'],
                "item_quantity" => (int)($item['qty'] ?? 1)
            ];
        }

        // $codAmount = ($order->payment_type === 'cod') ? $order->collectable_amount : 0;
        // $paymentMode = ($order->payment_type === 'cod') ? 'cod' : 'prepaid';
        $paymentType = strtolower($order->payment_type);
        $codAmount = ($paymentType === 'cod') ? $order->collectable_amount : 0;
        $paymentMode = ($paymentType === 'cod') ? 'cod' : 'prepaid';

    //   dd($addressId);
        // Create shipment payload - match exactly with working curl request
        $payload = [
            "address_id" => $addressId,
            "order_number" => $order->order_number,
            // "order_number" => "cs3434",

            "items" => $items,
            "type" => "Parcel",
            "receiver_name" => $consignee['name'],
            "receiver_address" => $consignee['address_2'] ?? $consignee['address'],
            "receiver_email" => $consignee['email'] ?? 'customer@example.com',
            "receiver_mobile" => $consignee['phone'],
            "receiver_pincode" => $consignee['pincode'],
            "payment_mode" => $paymentMode,
            "mode" => "Domestic",
            "total_amount" => (float)$order->collectable_amount,
            "cod_amount" => (float)$codAmount,
            // "total_weight" => (float)max(1, ($order->package_weight / 1000)),
            "total_weight" => (float)($order->package_weight / 1000),

            "courier_id" => $courierId,
            "product_id" => $productId,
            "carrier_id" => $carrierId,
            "height" => (float)($order->package_height ?? 1),
            "length" => (float)($order->package_length ?? 1),
            "width" => (float)($order->package_breadth ?? 1)
        ];
// dd($payload);
        // Validate and clean data to avoid "suspicious order" errors
        if (empty($payload['receiver_name']) || strlen($payload['receiver_name']) < 2) {
            return ['status' => false, 'message' => 'Invalid receiver name'];
        }

        if (empty($payload['receiver_address']) || strlen($payload['receiver_address']) < 5) {
            return ['status' => false, 'message' => 'Invalid receiver address'];
        }

        if (!preg_match('/^[0-9]{10}$/', $payload['receiver_mobile'])) {
            return ['status' => false, 'message' => 'Invalid mobile number format'];
        }

        if (!preg_match('/^[0-9]{6}$/', $payload['receiver_pincode'])) {
            return ['status' => false, 'message' => 'Invalid pincode format'];
        }

        // Clean and sanitize data to match working format
        $payload['receiver_name'] = preg_replace('/[^a-zA-Z\s]/', '', $payload['receiver_name']); // Remove special chars
        $payload['receiver_address'] = preg_replace('/[^\w\s\-\,\.]/', '', $payload['receiver_address']); // Clean address
        $payload['receiver_mobile'] = preg_replace('/[^0-9]/', '', $payload['receiver_mobile']); // Only digits

        // Ensure email is valid format
        if (!filter_var($payload['receiver_email'], FILTER_VALIDATE_EMAIL)) {
            $payload['receiver_email'] = 'customer@example.com';
        }

        // For debugging - try exact working configuration
        // Clean and sanitize data to avoid "suspicious order" errors
        $payload['receiver_name'] = preg_replace('/[^a-zA-Z\s]/', '', $payload['receiver_name']); // Remove special chars
        $payload['receiver_address'] = preg_replace('/[^\w\s\-\,\.]/', '', $payload['receiver_address']); // Clean address
        $payload['receiver_mobile'] = preg_replace('/[^0-9]/', '', $payload['receiver_mobile']); // Only digits

        // Ensure email is valid format
        if (!filter_var($payload['receiver_email'], FILTER_VALIDATE_EMAIL)) {
            $payload['receiver_email'] = 'customer@example.com';
        }
        // dd($payload);

        try {
            $response = Http::withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'application/json',
                'secretkey' => 'POVHFT',
                'customerid' => 'c1754533690129',
            ])->post('https://backend.boxdlogistics.in/vendor/v1/shipment/new_shipment_create', $payload);

// dd($response->json());
            if (!$response->successful()) {
                Log::error('Boxd Shipment API Error', ['response' => $response->body()]);
                return [
                    'status' => false,
                    'message' => 'Boxd API request failed'
                ];
            }

            $responseData = $response->json();
            // dd($responseData); // Remove this line for production

            if (isset($responseData['status']) && $responseData['status'] == true) {
                // Save the tracking details
                $order->awb_number = $responseData['awb_number'] ?? null;
                $order->courier_id = 'boxd';
                $order->all_courier_name = $logistic_name;
                $order->smartship_tracking_url = $responseData['label'] ?? null; // Changed from label_url to label
                $order->shipping_date = Carbon::now()->format('Y-m-d');

                $order->save();

                // Wallet debit
                Recharge::create([
                    'seller_id' => $seller->id,
                    'type' => 'Debit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order created',

                ]);

                return [
                    'status' => true,
                    'message' => 'Order successfully assigned to Boxd.',
                    'couriername' => 'boxd',
                    'awb_number' => $responseData['awb_number'] ?? null,
                    'label_url' => $responseData['label'] ?? null, // Changed from label_url to label
                    'route_code' => $responseData['route_code'] ?? null,
                    'tracking_status' => $responseData['tracking_status'] ?? null,
                ];
            }

            return [
                'status' => false,
                'message' => $responseData['message'] ?? 'Boxd API error.',
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'API Request Failed: ' . $e->getMessage(),
            ];
        }
    }

    public function cancelShipment($awb)
    {
        // dd($awb);
        $seller = Auth::guard('seller')->user();
        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found with AWB: ' . $awb,
            ];
        }

        $response = Http::withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'secretkey' => 'POVHFT',
            'customerid' => 'c1754533690129',
        ])->asForm()->post('https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_cancel', [
            'awb_number' => $awb,
            'cancel_reason' => 'Order cancellation requested by seller'
        ]);
//         try {
//     $response = Http::withHeaders([
//         'secretkey' => 'POVHFT',
//         'customerid' => 'c1754533690129',
//         'Content-Type' => 'application/x-www-form-urlencoded',
//     ])->asForm()->post(
//         'https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_cancel',
//         [
//             'awb_number' => $awb,
//             'cancel_reason' => 'Order cancellation requested by seller'
//         ]
//     );

// } catch (\Exception $e) {

//     return [
//         'status' => false,
//         'message' => 'API connection failed: ' . $e->getMessage()
//     ];
// }

        // dd($response->json());

        if ($response->successful()) {
            $responseData = $response->json();

            // Check if the cancellation was successful in the response
            if (isset($responseData['status']) && $responseData['status'] == true) {
                $order->order_status = 'cancelled';
                $order->save();

                // Credit back to seller wallet
                Recharge::create([
                    'seller_id' => $seller->id,
                    'type' => 'Credit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order cancelled',

                ]);

                return [
                    'status' => true,
                    'message' => 'Shipment cancelled successfully',
                    'data' => $responseData,
                    'responseCode' => $response->status()
                ];
            } else {
                return [
                    'status' => false,
                    'message' => $responseData['message'] ?? 'Cancellation failed',
                    'data' => $responseData,
                    'responseCode' => $response->status()
                ];
            }
        }

        return [
            'status' => false,
            'message' => 'API request failed',
            'responseCode' => $response->status(),
            'error' => $response->body()
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













public function assignOrderbulk($params)
{
    // dd($params);
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
    $provider_name = $params['provider_name'] ?? 'BlueDart Air 500 GM';
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
        'couriername' => 'boxd',
        'results' => $results,
        'success_count' => $successCount,
        'failure_count' => $failureCount
    ];
}

private function processSingleOrderAssignmentBulk($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'];
    // dd($provider_name);
    if ($provider_name == 'BlueDart Air 500 GM') {
        // $productId = "1746709645240";
        // $carrierId = "1738577045";
        $courierId = "Ekart 500gm";
        $productId = "1770095900731";
        $carrierId = "1765284951";
        $logistic_name = 'BlueDart Air 500 GM';
          } elseif ($provider_name == 'BlueDart 500 GM') {
            $productId = "1762763121502";
            $carrierId = "1738577045";
            $courierId = "678b3ac540f9b7f91a8b4c3f";
            $logistic_name = 'BlueDart 500 GM';
    } elseif ($provider_name == 'Delhivery Air' || $provider_name == 'Delhivery 250gms') {
        // Both use the same working configuration
        $productId = "1753163038641";
        $carrierId = "1656013556";
        $courierId = "1456367975";
        $logistic_name = 'Delhivery 250gms';
    } else {

        return ['status' => false, 'message' => 'Invalid provider name.'];

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
//  dd((float)($order->package_weight / 1000));
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

    // Register warehouse and get address ID
    $addressId = $this->registerHub([
        "hub_name" => $pickup['warehouse_name'],
        "pincode" => $pickup['pincode'],
        "address1" => $pickup['address'],
        "hub_phone" => $pickup['phone'],
        "email" => $pickup['email'] ?? 'warehouse@example.com'
    ]);
    // dd($addressId);
    if (!$addressId) {
        $addressId = "1738673293717";
        // Log::warning('Using fallback address_id as warehouse registration failed');
    }


    // Prepare items array
    $items = [];
    $totalValue = 0;
    foreach ($orderItems as $item) {
        $itemValue = (float)($item['price'] ?? 0);
        $totalValue += $itemValue;
        $items[] = [
            "item_value" => $itemValue,
            "item_name" => $item['name'],
            "item_quantity" => (int)($item['qty'] ?? 1)
        ];
    }

    // $codAmount = ($order->payment_type === 'cod') ? $order->collectable_amount : 0;
    // $paymentMode = ($order->payment_type === 'cod') ? 'cod' : 'prepaid';
    $paymentType = strtolower($order->payment_type);
    $codAmount = ($paymentType === 'cod') ? $order->collectable_amount : 0;
    $paymentMode = ($paymentType === 'cod') ? 'cod' : 'prepaid';

//   dd($addressId);
    // Create shipment payload - match exactly with working curl request
    $payload = [
        "address_id" => $addressId,
        "order_number" => $order->order_number,
        // "order_number" => "cs3434",

        "items" => $items,
        "type" => "Parcel",
        "receiver_name" => $consignee['name'],
        "receiver_address" => $consignee['address_2'] ?? $consignee['address'],
        "receiver_email" => $consignee['email'] ?? 'customer@example.com',
        "receiver_mobile" => $consignee['phone'],
        "receiver_pincode" => $consignee['pincode'],
        "payment_mode" => $paymentMode,
        "mode" => "Domestic",
        "total_amount" => (float)$order->collectable_amount,
        "cod_amount" => (float)$codAmount,
        // "total_weight" => (float)max(1, ($order->package_weight / 1000)),
        "total_weight" => (float)($order->package_weight / 1000),

        "courier_id" => $courierId,
        "product_id" => $productId,
        "carrier_id" => $carrierId,
        "height" => (float)($order->package_height ?? 1),
        "length" => (float)($order->package_length ?? 1),
        "width" => (float)($order->package_breadth ?? 1)
    ];
// dd($payload);
    // Validate and clean data to avoid "suspicious order" errors
    if (empty($payload['receiver_name']) || strlen($payload['receiver_name']) < 2) {
        return ['status' => false, 'message' => 'Invalid receiver name'];
    }

    if (empty($payload['receiver_address']) || strlen($payload['receiver_address']) < 5) {
        return ['status' => false, 'message' => 'Invalid receiver address'];
    }

    if (!preg_match('/^[0-9]{10}$/', $payload['receiver_mobile'])) {
        return ['status' => false, 'message' => 'Invalid mobile number format'];
    }

    if (!preg_match('/^[0-9]{6}$/', $payload['receiver_pincode'])) {
        return ['status' => false, 'message' => 'Invalid pincode format'];
    }

    // Clean and sanitize data to match working format
    $payload['receiver_name'] = preg_replace('/[^a-zA-Z\s]/', '', $payload['receiver_name']); // Remove special chars
    $payload['receiver_address'] = preg_replace('/[^\w\s\-\,\.]/', '', $payload['receiver_address']); // Clean address
    $payload['receiver_mobile'] = preg_replace('/[^0-9]/', '', $payload['receiver_mobile']); // Only digits

    // Ensure email is valid format
    if (!filter_var($payload['receiver_email'], FILTER_VALIDATE_EMAIL)) {
        $payload['receiver_email'] = 'customer@example.com';
    }

    // For debugging - try exact working configuration
    // Clean and sanitize data to avoid "suspicious order" errors
    $payload['receiver_name'] = preg_replace('/[^a-zA-Z\s]/', '', $payload['receiver_name']); // Remove special chars
    $payload['receiver_address'] = preg_replace('/[^\w\s\-\,\.]/', '', $payload['receiver_address']); // Clean address
    $payload['receiver_mobile'] = preg_replace('/[^0-9]/', '', $payload['receiver_mobile']); // Only digits

    // Ensure email is valid format
    if (!filter_var($payload['receiver_email'], FILTER_VALIDATE_EMAIL)) {
        $payload['receiver_email'] = 'customer@example.com';
    }
    // dd($payload);

    try {
        $response = Http::withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/json',
            'secretkey' => 'POVHFT',
            'customerid' => 'c1754533690129',
        ])->post('https://backend.boxdlogistics.in/vendor/v1/shipment/new_shipment_create', $payload);

// dd($response->json());
        if (!$response->successful()) {
            Log::error('Boxd Shipment API Error', ['response' => $response->body()]);
            return [
                'status' => false,
                'message' => 'Boxd API request failed'
            ];
        }

        $responseData = $response->json();
        // dd($responseData); // Remove this line for production

        if (isset($responseData['status']) && $responseData['status'] == true) {
            // Save the tracking details
            $order->awb_number = $responseData['awb_number'] ?? null;
            $order->courier_id = 'boxd';
            $order->all_courier_name = $logistic_name;
            $order->smartship_tracking_url = $responseData['label'] ?? null; // Changed from label_url to label
            $order->shipping_date = Carbon::now()->format('Y-m-d');

            $order->save();

            // Wallet debit
            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order created',

            ]);

            return [
                'status' => true,
                'message' => 'Order successfully assigned to Boxd.',
                'couriername' => 'boxd',
                'awb_number' => $responseData['awb_number'] ?? null,
                'label_url' => $responseData['label'] ?? null, // Changed from label_url to label
                'route_code' => $responseData['route_code'] ?? null,
                'tracking_status' => $responseData['tracking_status'] ?? null,
            ];
        }

        return [
            'status' => false,
            'message' => $responseData['message'] ?? 'Boxd API error.',
            'data' => $responseData,
        ];
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage(),
        ];
    }
}








}
