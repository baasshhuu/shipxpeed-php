<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PriceSetting;
use App\Models\Recharge;
use Carbon\Carbon;
use App\Models\Warehouse;
use App\Models\ZonePriceSetting;
use App\Models\ActicvSleb;

class ShiprocketService implements CourierServiceInterface
{ 

    /**
     * Check serviceability via Boxd Pincode API.
     *
     * @param  array  $params  // origin, destination, etc.
     * @return array[]         // normalized serviceability entries
     */



public function shiprocketLogin()
{
    $url = "https://apiv2.shiprocket.in/v1/external/auth/login";
    $payload = [
        "email" => "vk0553723@gmail.com",
        "password" => "YQcM!PpO#l2fF0@D"
    ];
    $response = Http::withHeaders([
        'Content-Type' => 'application/json'
    ])->post($url, $payload);
    // dd($response);
    return $response->json();
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
                Log::error("Error processing order {$orderId} in bulk serviceability: " . $e->getMessage());
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


public function processSingleOrderServiceability(array $params): array
{
    // dd($params);
    $order = Order::findOrFail($params['order_id']);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pincodeToCheck = $consignee['pincode'];
    $destinationstate = $consignee['state'];
    $length = $order->package_length;
    $width = $order->package_breadth;
    $height = $order->package_height;


        $bydefoltshiprocketServices = [
        'shiprocket_Delhivery', 'Shiprocket_Delhivery', 'shiprocket_delhivery',
        'shiprocket_Xpressbee', 'Shiprocket_Xpressbee', 'shiprocket_xpressbee',
        ];
    // Get only the active Shiprocket services for this seller
    // Include all possible case variations that might exist in database
    $shiprocketServices = [
        'shiprocket_Delhivery', 'Shiprocket_Delhivery', 'shiprocket_delhivery',
        'shiprocket_Xpressbee', 'Shiprocket_Xpressbee', 'shiprocket_xpressbee',
        'shiprocket_Bluedart', 'Shiprocket_Bluedart', 'shiprocket_bluedart',
        'shiprocket_Bluedart_1kg', 'Shiprocket_Bluedart_1kg', 'shiprocket_bluedart_1kg'
    ];
    
    $activeShiprocketServicesRaw = ActicvSleb::where('seller_id', $order->seller_id)
        ->whereIn('LogisticProvider', $shiprocketServices)
        ->where('status', 1)
        ->pluck('LogisticProvider')
        ->toArray();
    // dd($activeShiprocketServicesRaw);
    
    // Check if default services exist but are inactive
    $inactiveDefaultServices = ActicvSleb::where('seller_id', $order->seller_id)
        ->whereIn('LogisticProvider', $bydefoltshiprocketServices)
        ->where('status', 0)
        ->pluck('LogisticProvider')
        ->toArray();
    
    // If no active services found or default services not in active list, merge with default services
    // BUT only if default services are not explicitly inactive
    $defaultServicesLower = array_map('strtolower', $bydefoltshiprocketServices);
    $activeServicesLower = array_map('strtolower', $activeShiprocketServicesRaw);
    $inactiveDefaultServicesLower = array_map('strtolower', $inactiveDefaultServices);
    
    // Check if any default services are missing from active services
    $missingDefaults = array_diff($defaultServicesLower, $activeServicesLower);
    
    // Remove any default services that are explicitly inactive
    $validMissingDefaults = array_diff($missingDefaults, $inactiveDefaultServicesLower);
    
    if (!empty($validMissingDefaults) && empty($activeShiprocketServicesRaw)) {
        // Add default services to active services only if they are not inactive
        $defaultServicesToAdd = array_filter($bydefoltshiprocketServices, function($service) use ($inactiveDefaultServicesLower) {
            return !in_array(strtolower($service), $inactiveDefaultServicesLower);
        });
        $activeShiprocketServicesRaw = array_merge($activeShiprocketServicesRaw, $defaultServicesToAdd);
    }
    
    // Convert to lowercase for case-insensitive comparison
    $activeShiprocketServices = array_map('strtolower', $activeShiprocketServicesRaw);
    
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
    // dd($zone);
    // Step 2: Weight & Payment Info
    $actualWeight = (float)$order->package_weight; // grams
    $volumetricWeight = ($length * $width * $height) / 5000; // volumetric weight in grams
    $weight = max($actualWeight, $volumetricWeight); // use whichever is higher
    // dd($weight);
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;

    // 🧾 Step 3: Calculate Shiprocket charges based on ZonePriceSetting with weight slabs
    $calculateShiprocketCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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

    // Get zone pricing for Shiprocket services
    $results = [];
    $shiprocketServicesList = ['shiprocket_Delhivery', 'shiprocket_Xpressbee', 'shiprocket_Bluedart', 'shiprocket_Bluedart_1kg'];
    $serviceNames = ['Delhivery_Shiprocket 250gms', 'Xpressbee 250gms', 'Bluedart 2kg surface', 'Bluedart 1 KG'];

    foreach ($shiprocketServicesList as $index => $service) {
        // Only process services that are active for this seller (case-insensitive check)
        if (!in_array(strtolower($service), $activeShiprocketServices)) {
            continue; // Skip inactive services
        }
        
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => $service,
            'status' => 1
        ])->first();

                if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => $service,
        'status' => 1
        ])->first();
            }

        $charges = $calculateShiprocketCharge($zonePricing);
        
        // Only add result if pricing exists for this service
        if ($charges !== null) {
            $results[] = [
                'serviceabilityId' => $pincodeToCheck,
                'courierName'      => $serviceNames[$index],
                'courierCharge'    => $charges['courierCharge'],
                'freightCharges'   => $charges['freightCharges'],
                'codCharge'        => $charges['codCharge'],
                'zone'             => $zone,
                'zone_courier_name'             => $serviceNames[$index],
                'minWeight'        => $weight,
                'volWeight'        => $weight,
            ];
        }
    }
    //  dd($results);
    return $results;
}


// private function getServiceabilityesm by defolt vala logic nhi h (array $params): array
// {
//     $order = Order::findOrFail($params['order_id']);
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $pincodeToCheck = $consignee['pincode'];
//     $destinationstate = $consignee['state'];
//     $length = $order->package_length;
//     $width = $order->package_breadth;
//     $height = $order->package_height;
//     // Get only the active Shiprocket services for this seller
//     // Include all possible case variations that might exist in database
//     $shiprocketServices = [
//         'shiprocket_Delhivery', 'Shiprocket_Delhivery', 'shiprocket_delhivery',
//         'shiprocket_Xpressbee', 'Shiprocket_Xpressbee', 'shiprocket_xpressbee',
//         'shiprocket_Bluedart', 'Shiprocket_Bluedart', 'shiprocket_bluedart',
//         'shiprocket_Bluedart_1kg', 'Shiprocket_Bluedart_1kg', 'shiprocket_bluedart_1kg'
//     ];
    
//     $activeShiprocketServicesRaw = ActicvSleb::where('seller_id', $order->seller_id)
//         ->whereIn('LogisticProvider', $shiprocketServices)
//         ->where('status', 1)
//         ->pluck('LogisticProvider')
//         ->toArray();
    
//     // Convert to lowercase for case-insensitive comparison
//     $activeShiprocketServices = array_map('strtolower', $activeShiprocketServicesRaw);
    
//     // Continue processing even if some services are inactive
//     // We'll filter in the loop later

//     // Pickup Info
//     $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $originPin = $source['pincode'];
//     $pickupstate = $source['state'];

//     // 🧾 Step 1: Zone Fetch (Case-Insensitive)
//     $zoneData = DB::table('pincode_zones')
//         ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
//         ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
//         ->first();

//     if (!$zoneData) {
//         return [];
//     }

//     $zone = strtoupper($zoneData->zone);
//     // dd($zone);
//     // Step 2: Weight & Payment Info
//     // $weight = (float)$order->package_weight; // grams

//         $actualWeight = (float)$order->package_weight; // grams
//     $volumetricWeight = ($length * $width * $height) / 5000; // volumetric weight in grams
//     $weight = max($actualWeight, $volumetricWeight); // use whichever is higher

//     $orderAmount = $order->collectable_amount;
//     $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
//     $seller_id = $order->seller_id;

//     // 🧾 Step 3: Calculate Shiprocket charges based on ZonePriceSetting with weight slabs
//     $calculateShiprocketCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
//         if (!$zonePricing) {
//             return null; // Skip if no pricing found
//         }
        
//         // Calculate weight slabs (500gm each)
//         $slabs = max(1, ceil($weight / 500));
        
//         if ($paymentType === 'COD') {      
//             // COD Order Logic
//             if ($zonePricing->cod_fix_price > 0) {
//                 // Use fixed COD price multiplied by weight slabs - NO GST, NO other charges
//                 $totalPrice = $zonePricing->cod_fix_price * $slabs;
//                 return [
//                     'courierCharge'  => round($totalPrice, 2),
//                     'freightCharges' => round($totalPrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             } else {
//                 // Use variable COD price multiplied by weight slabs + 18% GST + COD charge if applicable
//                 $basePrice = $zonePricing->cod_price * $slabs;
//                 $prepaidPrice = $zonePricing->prepaid_price * $slabs;

//                 // Calculate COD charge if order amount > 1400
//                 $codCharge = 0;
//                 if ($orderAmount > 1400) {
//                     $codChargePercent = $zonePricing->cod_charge_parsent ?? 0;
//                     $codCharge = ($orderAmount * $codChargePercent / 100);
//                 }
                
//                 $totalWithGST = ($basePrice + $prepaidPrice + $codCharge) + (($basePrice + $prepaidPrice + $codCharge) * 18 / 100);
//                 return [
//                     'courierCharge'  => round($totalWithGST, 2),
//                     'freightCharges' => round($basePrice + $prepaidPrice, 2),
//                     'codCharge'      => round($codCharge, 2),
//                 ];
//             }
//         } else {
//             // Prepaid Order Logic
//             if ($zonePricing->prepaid_fix_price > 0) {
//                 // Use fixed Prepaid price multiplied by weight slabs - NO GST, NO other charges
//                 $totalPrice = $zonePricing->prepaid_fix_price * $slabs;
//                 return [
//                     'courierCharge'  => round($totalPrice, 2),
//                     'freightCharges' => round($totalPrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             } else {
//                 // Use variable Prepaid price multiplied by weight slabs + 18% GST
//                 $basePrice = $zonePricing->prepaid_price * $slabs;
//                 $totalWithGST = $basePrice + ($basePrice * 18 / 100);
//                 return [
//                     'courierCharge'  => round($totalWithGST, 2),
//                     'freightCharges' => round($basePrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             }
//         }
//     };

//     // Get zone pricing for Shiprocket services
//     $results = [];
//     $shiprocketServicesList = ['shiprocket_Delhivery', 'shiprocket_Xpressbee', 'shiprocket_Bluedart', 'shiprocket_Bluedart_1kg'];
//     $serviceNames = ['Delhivery_Shiprocket 250gms', 'Xpressbee 250gms', 'Bluedart 2kg surface', 'Bluedart 1 KG'];

//     foreach ($shiprocketServicesList as $index => $service) {
//         // Only process services that are active for this seller (case-insensitive check)
//         if (!in_array(strtolower($service), $activeShiprocketServices)) {
//             continue; // Skip inactive services
//         }
        
//         $zonePricing = ZonePriceSetting::where([
//             'seller_id' => $seller_id,
//             'zone' => $zone,
//             'LogisticProvider' => $service,
//             'status' => 1
//         ])->first();

//                 if (!$zonePricing) {
//         $zonePricing = ZonePriceSetting::where([
//         'seller_id' => 14,
//         'zone' => $zone,
//         'LogisticProvider' => $service,
//         'status' => 1
//         ])->first();
//             }

//         $charges = $calculateShiprocketCharge($zonePricing);
        
//         // Only add result if pricing exists for this service
//         if ($charges !== null) {
//             $results[] = [
//                 'serviceabilityId' => $pincodeToCheck,
//                 'courierName'      => $serviceNames[$index],
//                 'courierCharge'    => $charges['courierCharge'],
//                 'freightCharges'   => $charges['freightCharges'],
//                 'codCharge'        => $charges['codCharge'],
//                 'zone'             => $zone,
//                 'minWeight'        => $weight,
//                 'volWeight'        => $weight,
//             ];
//         }
//     }
    
//     return $results;
// }






public function getServiceability(array $params): array
{
    // dd($params);
    $order = Order::findOrFail($params['order_id']);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pincodeToCheck = $consignee['pincode'];
    $destinationstate = $consignee['state'];
    $length = $order->package_length;
    $width = $order->package_breadth;
    $height = $order->package_height;


        $bydefoltshiprocketServices = [
        'shiprocket_Delhivery', 'Shiprocket_Delhivery', 'shiprocket_delhivery',
        'shiprocket_Xpressbee', 'Shiprocket_Xpressbee', 'shiprocket_xpressbee',
        ];
    // Get only the active Shiprocket services for this seller
    // Include all possible case variations that might exist in database
    $shiprocketServices = [
        'shiprocket_Delhivery', 'Shiprocket_Delhivery', 'shiprocket_delhivery',
        'shiprocket_Xpressbee', 'Shiprocket_Xpressbee', 'shiprocket_xpressbee',
        'shiprocket_Bluedart', 'Shiprocket_Bluedart', 'shiprocket_bluedart',
        'shiprocket_Bluedart_1kg', 'Shiprocket_Bluedart_1kg', 'shiprocket_bluedart_1kg'
    ];
    
    $activeShiprocketServicesRaw = ActicvSleb::where('seller_id', $order->seller_id)
        ->whereIn('LogisticProvider', $shiprocketServices)
        ->where('status', 1)
        ->pluck('LogisticProvider')
        ->toArray();
    // dd($activeShiprocketServicesRaw);
    
    // Check if default services exist but are inactive
    $inactiveDefaultServices = ActicvSleb::where('seller_id', $order->seller_id)
        ->whereIn('LogisticProvider', $bydefoltshiprocketServices)
        ->where('status', 0)
        ->pluck('LogisticProvider')
        ->toArray();
    
    // If no active services found or default services not in active list, merge with default services
    // BUT only if default services are not explicitly inactive
    $defaultServicesLower = array_map('strtolower', $bydefoltshiprocketServices);
    $activeServicesLower = array_map('strtolower', $activeShiprocketServicesRaw);
    $inactiveDefaultServicesLower = array_map('strtolower', $inactiveDefaultServices);
    
    // Check if any default services are missing from active services
    $missingDefaults = array_diff($defaultServicesLower, $activeServicesLower);
    
    // Remove any default services that are explicitly inactive
    $validMissingDefaults = array_diff($missingDefaults, $inactiveDefaultServicesLower);
    
    if (!empty($validMissingDefaults) && empty($activeShiprocketServicesRaw)) {
        // Add default services to active services only if they are not inactive
        $defaultServicesToAdd = array_filter($bydefoltshiprocketServices, function($service) use ($inactiveDefaultServicesLower) {
            return !in_array(strtolower($service), $inactiveDefaultServicesLower);
        });
        $activeShiprocketServicesRaw = array_merge($activeShiprocketServicesRaw, $defaultServicesToAdd);
    }
    
    // Convert to lowercase for case-insensitive comparison
    $activeShiprocketServices = array_map('strtolower', $activeShiprocketServicesRaw);
    
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
    // dd($zone);
    // Step 2: Weight & Payment Info
    $actualWeight = (float)$order->package_weight; // grams
    $volumetricWeight = ($length * $width * $height) / 5000; // volumetric weight in grams
    $weight = max($actualWeight, $volumetricWeight); // use whichever is higher
    // dd($weight);
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;

    // 🧾 Step 3: Calculate Shiprocket charges based on ZonePriceSetting with weight slabs
    $calculateShiprocketCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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

    // Get zone pricing for Shiprocket services
    $results = [];
    $shiprocketServicesList = ['shiprocket_Delhivery', 'shiprocket_Xpressbee', 'shiprocket_Bluedart', 'shiprocket_Bluedart_1kg'];
    $serviceNames = ['Delhivery_Shiprocket 250gms', 'Xpressbee 250gms', 'Bluedart 2kg surface', 'Bluedart 1 KG'];

    foreach ($shiprocketServicesList as $index => $service) {
        // Only process services that are active for this seller (case-insensitive check)
        if (!in_array(strtolower($service), $activeShiprocketServices)) {
            continue; // Skip inactive services
        }
        
        $zonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => $service,
            'status' => 1
        ])->first();

                if (!$zonePricing) {
        $zonePricing = ZonePriceSetting::where([
        'seller_id' => 14,
        'zone' => $zone,
        'LogisticProvider' => $service,
        'status' => 1
        ])->first();
            }

        $charges = $calculateShiprocketCharge($zonePricing);
        
        // Only add result if pricing exists for this service
        if ($charges !== null) {
            $results[] = [
                'serviceabilityId' => $pincodeToCheck,
                'courierName'      => $serviceNames[$index],
                'courierCharge'    => $charges['courierCharge'],
                'freightCharges'   => $charges['freightCharges'],
                'codCharge'        => $charges['codCharge'],
                'zone'             => $zone,
                'zone_courier_name'             => $serviceNames[$index],

                'minWeight'        => $weight,
                'volWeight'        => $weight,
            ];
        }
    }
    //  dd($results);
    return $results;
}






public function createWarehouse($pickup, $sellerId)
{
    // dd($pickup, $sellerId);
    try {
        // Generate unique pickup location name (max 36 chars for Shiprocket)
        $baseName = preg_replace('/[^A-Za-z0-9]/', '', $pickup['warehouse_name'] ?? $pickup['name'] ?? 'WH');
        $baseName = substr($baseName, 0, 10); // Limit base name to 10 chars
        $timestamp = substr(time(), -6); // Last 6 digits of timestamp
        $pickupLocationName = $baseName . $sellerId . $timestamp;
        
        // Ensure pickup location name is within 36 character limit
        if (strlen($pickupLocationName) > 36) {
            $pickupLocationName = substr($pickupLocationName, 0, 36);
        }
        
        // First check if warehouse already exists in database
        $existingWarehouse = Warehouse::where('seller_id', $sellerId)
            ->where('pincode', $pickup['pincode'])
            ->where('address_line1', $pickup['address'])
            ->where('shiprocket_pickup_id', '!=', null)
            ->first();
        //    dd($existingWarehouse);
        if ($existingWarehouse) {
            // echo 'existing warehouse found';die;
            // Return existing warehouse pickup location name
            return [
                'status' => true,
                'pickup_location' => $existingWarehouse->shiprocket_pickup_id,
                'message' => 'Using existing warehouse'
            ];
        }

        // Validate required fields
        if (empty($pickup['name']) || empty($pickup['phone']) || empty($pickup['pincode']) || empty($pickup['address'])) {
        //    dd($pickup['name']);
            return [
                'status' => false,
                'message' => 'Missing required warehouse fields: name, phone, pincode, address'
            ];
        }
        // dd($pickup['name']);
//   echo 'xsvickyxxs';die;
        // Get Shiprocket auth token
        $authResponse = $this->shiprocketLogin();
        if (!isset($authResponse['token'])) {
            return [
                'status' => false,
                'message' => 'Failed to authenticate with Shiprocket'
            ];
        }
        $token = $authResponse['token'];

        // Create warehouse in local database first
        $warehouse = Warehouse::create([
            'seller_id' => $sellerId,
            'name' => $pickup['warehouse_name'] ?? $pickup['name'],
            'phone' => $pickup['phone'],
            'address_title' => $pickup['address'],

            'pincode' => $pickup['pincode'],
            'city' => $pickup['city'] ?? '',
            'state' => $pickup['state'] ?? '',
            'country' => 'India',
            'address_line1' => $pickup['address'],
            'address_line2' => $pickup['address_2'] ?? '',
            'registered_name' => $pickup['name'],
            'shiprocket_pickup_location' => $pickupLocationName,
            'return_address' => $pickup['address'],
            'return_pin' => $pickup['pincode'],
            'return_city' => $pickup['city'] ?? '',
            'return_state' => $pickup['state'] ?? '',
            'return_country' => 'India',
        ]);

        // Prepare Shiprocket pickup location payload
        $pickupPayload = [
            "pickup_location" => $pickupLocationName,
            "name" => $pickup['name'],
            "email" => $pickup['email'] ?? 'seller@example.com',
            "phone" => $pickup['phone'],
            "address" => $pickup['address'],
            "address_2" => $pickup['address_2'] ?? '',
            "city" => $pickup['city'] ?? '',
            "state" => $pickup['state'] ?? '',
            "country" => "India",
            "pin_code" => $pickup['pincode']
        ];
    //   dd($pickupPayload);
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apiv2.shiprocket.in/v1/external/settings/company/addpickup', $pickupPayload);

        $responseData = $response->json();
        // dd($responseData);
        if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
            // $shiprocketPickupId = $responseData['pickup_id'] ?? null;
             $shiprocketPickupId = $responseData['address']['pickup_code'] ?? null;

            if ($shiprocketPickupId) {
                // Update local warehouse with Shiprocket pickup ID
                $warehouse->update([
                    'shiprocket_pickup_id' => $shiprocketPickupId
                ]);

                return [
                    'status' => true,
                    'shiprocket_pickup_id' => $pickupLocationName,
                    'pickup_location' => $shiprocketPickupId,
                    'local_warehouse_id' => $warehouse->id,
                    'response' => $responseData
                ];
            }
        }

        // If Shiprocket API failed, delete the local warehouse record
        $warehouse->delete();

        return [
            'status' => false,
            'message' => 'Failed to create pickup location on Shiprocket: ' . ($responseData['message'] ?? 'Unknown error'),
            'response' => $responseData
        ];

    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Warehouse creation failed: ' . $e->getMessage()
        ];
    }
}



public function assignOrder($params)
{
    // dd($params);
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'Xpressbee 250gms';
    
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
        return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    }

    // Decode pickup & consignee
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    // dd($pickup);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;
// dd($consignee);
    // Get Shiprocket auth token
    $authResponse = $this->shiprocketLogin();

    if (!isset($authResponse['token'])) {
        return ['status' => false, 'message' => 'Failed to authenticate with Shiprocket'];
    }
    $token = $authResponse['token'];
//   dd($token);
    // Prepare order items for Shiprocket
    $shiprocketOrderItems = [];
    $subTotal = 0;
    foreach ($orderItems as $item) {
        $itemPrice = (float)($item['price'] ?? 0);
        $itemQty = (int)($item['qty'] ?? 1);
        $subTotal += $itemPrice * $itemQty;
        
        $shiprocketOrderItems[] = [
            "name" => $item['name'] ?? 'Product',
            "sku" => $item['sku'] ?? 'SKU123',
            "units" => $itemQty,
            "selling_price" => $itemPrice,
            "discount" => 0,
            "tax" => 0,
            "hsn" => "441122",
            "weight" => (float)(($order->package_weight ?? 500) / 1000) // Convert grams to kg
        ];
    }

    try {
        // STEP 0: Create Pickup Location/Warehouse in Shiprocket using createWarehouse function
        $warehouseResult = $this->createWarehouse($pickup, $seller->id);
        //  dd($warehouseResult);
        if (!$warehouseResult['status']) {
            return [
                'status' => false,
                'message' => 'Failed to create/get warehouse: ' . $warehouseResult['message']
            ];
        }
        
        $pickupLocationName = $warehouseResult['pickup_location'];
    // dd($pickupLocationName);
    // dd($consignee['email']);
        // STEP 1: Create Order in Shiprocket
        $shiprocketPayload = [
            "order_id" => $order->order_number,
            "order_date" => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s'),
            // "pickup_location" => $pickupLocationName, // Use dynamic pickup location from createWarehouse
            "pickup_location" => (string) $pickupLocationName,

            "channel_id" => "", // Optional
            "comment" => "Order from " . ($seller->name ?? 'Seller'),
            
            // Billing details
            "billing_customer_name" => $consignee['name'] ?? '',
            "billing_last_name" => "",
            "billing_address" => $consignee['address'] ?? '',
            "billing_address_2" => $consignee['address_2'] ?? '',
            "billing_city" => $consignee['city'] ?? '',
            "billing_pincode" => $consignee['pincode'] ?? '',
            "billing_state" => $consignee['state'] ?? '',
            "billing_country" => "India",
            // "billing_email" => $consignee['email'] ?? 'vk0553723@gmail.com',
            "billing_email" => (!empty($consignee['email']) && strtolower($consignee['email']) !== 'na')
                    ? $consignee['email']
                    : 'vk0553723@gmail.com',

            "billing_phone" => $consignee['phone'] ?? '',
            
            // Shipping details (same as billing)
            "shipping_is_billing" => true,
            "shipping_customer_name" => "",
            "shipping_last_name" => "",
            "shipping_address" => "",
            "shipping_address_2" => "",
            "shipping_city" => "",
            "shipping_pincode" => "",
            "shipping_country" => "",
            "shipping_state" => "",
            "shipping_email" => "",
            "shipping_phone" => "",
            
            "order_items" => $shiprocketOrderItems,
            "payment_method" => ucfirst(strtolower($order->payment_type ?? 'COD')),
            "shipping_charges" => 0,
            "giftwrap_charges" => 0,
            "transaction_charges" => 0,
            "total_discount" => 0,
            "sub_total" => (float)$subTotal,
            "length" => (float)($order->package_length ?? 10),
            "breadth" => (float)($order->package_breadth ?? 5),
            "height" => (float)($order->package_height ?? 2),
            "weight" => (float)(($order->package_weight ?? 1000) / 1000) // Convert grams to kg
        ];
// dd($shiprocketPayload);
        $createOrderResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apiv2.shiprocket.in/v1/external/orders/create/adhoc', $shiprocketPayload);

        $orderResponseData = $createOrderResponse->json();
        //  dd($orderResponseData);
        if (!$createOrderResponse->successful()) {
            return [
                'status' => false, 
                'message' => 'Shiprocket Create Order API failed',
                'data' => $orderResponseData
            ];
        }

        // Check if order was created successfully
        if (!isset($orderResponseData['order_id'])) {
            return [
                'status' => false,
                'message' => 'Failed to create order with Shiprocket',
                'data' => $orderResponseData
            ];
        }

        $shiprocket_order_id = $orderResponseData['order_id'];

        // STEP 2: Generate AWB (Assign Courier)
        $awbPayload = [
            "shipment_id" => $orderResponseData['shipment_id'] ?? null,
            "courier_id" => $this->getCourierIdByProvider($provider_name)
        ];
        
        $awbResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apiv2.shiprocket.in/v1/external/courier/assign/awb', $awbPayload);

        $awbResponseData = $awbResponse->json();
        //  dd($awbResponseData);
        if ($awbResponse->successful() && 
            isset($awbResponseData['awb_assign_status']) && 
            $awbResponseData['awb_assign_status'] == 1 &&
            isset($awbResponseData['response']['data']['awb_code'])) {
                //    echo 'vicky';die;

            // Successfully got AWB - extract from nested structure
            $responseData = $awbResponseData['response']['data'];
            $awb_number = $responseData['awb_code'];
            $shipment_id = $responseData['shipment_id'] ?? $orderResponseData['shipment_id'] ?? null;
            
            // STEP 3: Generate Pickup after successful AWB assignment
            $pickupPayload = [
                "shipment_id" => [$shipment_id]
            ];
            
            $pickupResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->post('https://apiv2.shiprocket.in/v1/external/courier/generate/pickup', $pickupPayload);

            $pickupResponseData = $pickupResponse->json();
            $pickupMessage = '';
            $pickupToken = '';
            
            if ($pickupResponse->successful() && 
                isset($pickupResponseData['pickup_status']) && 
                $pickupResponseData['pickup_status'] == 1) {
                $pickupMessage = $pickupResponseData['response']['data'] ?? 'Pickup generated successfully';
                $pickupToken = $pickupResponseData['response']['pickup_token_number'] ?? '';
            }
            
            // Update order with all Shiprocket details
            $order->courier_id = 'shiprocket';
            $order->all_courier_name = $provider_name;
            $order->awb_number = $awb_number;
            $order->shipping_date = Carbon::now()->format('Y-m-d');
            // $order->shiprocket_order_id = $shiprocket_order_id;
            // $order->shiprocket_shipment_id = $shipment_id;
            $order->save();

            // Debit wallet
            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order created'
            ]);

            return [
                'status' => true,
                'message' => 'Order successfully created, AWB assigned and pickup scheduled via Shiprocket',
                'couriername' => 'shiprocket',
                // 'order_id' => $shiprocket_order_id,
                // 'shipment_id' => $shipment_id,
                'awb_number' => $awb_number,
                'pickup_message' => $pickupMessage,
                'pickup_token_number' => $pickupToken,
                // 'courier_company_id' => $responseData['courier_company_id'] ?? null,
                // 'courier_name' => $responseData['courier_name'] ?? $provider_name,
                // 'routing_code' => $responseData['routing_code'] ?? null,
                // 'freight_charges' => $responseData['freight_charges'] ?? null,
                // 'status' => 'PICKUP_SCHEDULED'
            ];
        } else {
            // AWB assignment failed, but order was created
            $order->courier_id = 'shiprocket';
            $order->all_courier_name = $provider_name;
            $order->shiprocket_order_id = $shiprocket_order_id;
            $order->shiprocket_shipment_id = $orderResponseData['shipment_id'] ?? null;
            $order->save();

            return [
                'status' => true,
                'message' => 'Order created but AWB assignment failed. Manual assignment required.',
                'couriername' => 'shiprocket',
                'order_id' => $shiprocket_order_id,
                'shipment_id' => $orderResponseData['shipment_id'] ?? null,
                'awb_error' => $awbResponseData,
                'status' => 'ORDER_CREATED_AWB_PENDING'
            ];
        }
    //    echo 'cscscs';die;
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage()
        ];
    }
}

private function getCourierIdByProvider($provider_name)
{
    // dd($provider_name);
    // Map provider names to Shiprocket courier IDs
    $courierMapping = [
        'Xpressbee 250gms' => 724,  // Xpressbees Surface
        // 'Delhivery_Shiprocket 250gms' => 724,  // Delhivery Surface
                'Delhivery_ 250gms' => 751,  // Delhivery Surface

        'Bluedart 2kg surface' => 603,  // BlueDart Surface
        'Bluedart 1 KG' => 55,  // BlueDart Air
    ];

    return $courierMapping[$provider_name] ?? 751; // Default to Xpressbees
}



    public function cancelShipment($awb)
    {
        $seller = Auth::guard('seller')->user();
        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found with AWB: ' . $awb,
            ];
        }

        // Get Shiprocket auth token
        $authResponse = $this->shiprocketLogin();
        if (!isset($authResponse['token'])) {
            return [
                'status' => false,
                'message' => 'Failed to authenticate with Shiprocket'
            ];
        }
        $token = $authResponse['token'];

        // Cancel shipment using Shiprocket API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apiv2.shiprocket.in/v1/external/orders/cancel/shipment/awbs', [
            'awbs' => [$awb]
        ]);
// dd($response->json());
        if ($response->successful()) {
            $responseData = $response->json();
                  
           
            $validMessages = [
            "Shipment(s) have been cancelled",
            "Shipment(s) cancellation is in progress. Please wait for some time."
        ];

        if (
            $response->status() == 200 &&
            isset($responseData['message']) &&
            in_array($responseData['message'], $validMessages)
        ) {
            // Update order status
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
                'message' => 'Shipment cancelled successfully via Shiprocket',
                'data' => $responseData,
                'responseCode' => $response->status()
            ];
        }

            
            else {
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
            'message' => 'Shiprocket API request failed',
            'responseCode' => $response->status(),
            'error' => $response->body()
        ];
    }



    
public function reversegetServiceability(array $params): array
{
    // dd($params);
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








































public function createWarehousebulk($pickup, $sellerId, $token = null)
{
    try {
        // Generate unique pickup location name (max 36 chars for Shiprocket)
        $baseName = preg_replace('/[^A-Za-z0-9]/', '', $pickup['warehouse_name'] ?? $pickup['name'] ?? 'WH');
        $baseName = substr($baseName, 0, 10);
        $timestamp = substr(time(), -6);
        $pickupLocationName = $baseName . $sellerId . $timestamp;
        
        if (strlen($pickupLocationName) > 36) {
            $pickupLocationName = substr($pickupLocationName, 0, 36);
        }
        
        // Check if warehouse already exists
        $existingWarehouse = Warehouse::where('seller_id', $sellerId)
            ->where('pincode', $pickup['pincode'])
            ->where('address_line1', $pickup['address'])
            ->where('shiprocket_pickup_id', '!=', null)
            ->first();
            
        if ($existingWarehouse) {
            return [
                'status' => true,
                'pickup_location' => $existingWarehouse->shiprocket_pickup_id,
                'message' => 'Using existing warehouse'
            ];
        }

        // Validate required fields
        if (empty($pickup['name']) || empty($pickup['phone']) || empty($pickup['pincode']) || empty($pickup['address'])) {
            return [
                'status' => false,
                'message' => 'Missing required warehouse fields: name, phone, pincode, address'
            ];
        }

        // Use provided token or get new one
        if (!$token) {
            $authResponse = $this->shiprocketLogin();
            if (!isset($authResponse['token'])) {
                return [
                    'status' => false,
                    'message' => 'Failed to authenticate with Shiprocket'
                ];
            }
            $token = $authResponse['token'];
        }

        // Create warehouse in local database first
        $warehouse = Warehouse::create([
            'seller_id' => $sellerId,
            'name' => $pickup['warehouse_name'] ?? $pickup['name'],
            'phone' => $pickup['phone'],
            'address_title' => $pickup['address'],
            'pincode' => $pickup['pincode'],
            'city' => $pickup['city'] ?? '',
            'state' => $pickup['state'] ?? '',
            'country' => 'India',
            'address_line1' => $pickup['address'],
            'address_line2' => $pickup['address_2'] ?? '',
            'registered_name' => $pickup['name'],
            'shiprocket_pickup_location' => $pickupLocationName,
            'return_address' => $pickup['address'],
            'return_pin' => $pickup['pincode'],
            'return_city' => $pickup['city'] ?? '',
            'return_state' => $pickup['state'] ?? '',
            'return_country' => 'India',
        ]);

        // Prepare Shiprocket pickup location payload
        $pickupPayload = [
            "pickup_location" => $pickupLocationName,
            "name" => $pickup['name'],
            "email" => $pickup['email'] ?? 'seller@example.com',
            "phone" => $pickup['phone'],
            "address" => $pickup['address'],
            "address_2" => $pickup['address_2'] ?? '',
            "city" => $pickup['city'] ?? '',
            "state" => $pickup['state'] ?? '',
            "country" => "India",
            "pin_code" => $pickup['pincode']
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apiv2.shiprocket.in/v1/external/settings/company/addpickup', $pickupPayload);

        $responseData = $response->json();
        
        if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
            $shiprocketPickupId = $responseData['address']['pickup_code'] ?? null;

            if ($shiprocketPickupId) {
                $warehouse->update([
                    'shiprocket_pickup_id' => $shiprocketPickupId
                ]);

                return [
                    'status' => true,
                    'shiprocket_pickup_id' => $pickupLocationName,
                    'pickup_location' => $shiprocketPickupId,
                    'local_warehouse_id' => $warehouse->id,
                ];
            }
        }

        // If Shiprocket API failed, delete the local warehouse record
        $warehouse->delete();

        return [
            'status' => false,
            'message' => 'Failed to create pickup location on Shiprocket: ' . ($responseData['message'] ?? 'Unknown error'),
        ];

    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Warehouse creation failed: ' . $e->getMessage()
        ];
    }
}



public function assignOrderbulk($params)
{
    // dd($params);
    // Handle bulk order assignment
    if (isset($params['order_ids']) && is_array($params['order_ids'])) {
        return $this->processBulkOrderAssignment($params);
    }
    
    // Single order processing (backward compatibility)
    return $this->processSingleOrderAssignmentBulk($params);
}

private function getCourierIdByProviderbulk($provider_name)
{
    // dd($provider_name);
    // Map provider names to Shiprocket courier IDs
    $courierMapping = [
        'Xpressbee 250gms' => 724,  // Xpressbees Surface
        // 'Delhivery_Shiprocket 250gms' => 724,  // Delhivery Surface
                'Delhivery_ 250gms' => 751,  // Delhivery Surface

        'Bluedart 2kg surface' => 603,  // BlueDart Surface
        'Bluedart 1 KG' => 55,  
    ];

    return $courierMapping[$provider_name] ?? 751; // Default to Xpressbees
}

private function processBulkOrderAssignment($params)
{
    $order_ids = $params['order_ids'];
    $provider_name = $params['provider_name'] ?? 'Xpressbee 250gms';
    $results = [];
    $successCount = 0;
    $failureCount = 0;
    
    $seller = Auth::guard('seller')->user();
    
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }
    
    // Get Shiprocket auth token once for all orders
    $authResponse = $this->shiprocketLogin();
    if (!isset($authResponse['token'])) {
        return ['status' => false, 'message' => 'Failed to authenticate with Shiprocket'];
    }
    $token = $authResponse['token'];
    
    foreach ($order_ids as $order_id) {
        try {
            // Process each order individually
            $singleOrderParams = [
                'order_id' => $order_id,
                'provider_name' => $provider_name,
                'token' => $token // Pass token to avoid re-authentication
            ];
            
            $result = $this->processSingleOrderAssignmentBulk($singleOrderParams);
            
            if ($result['status']) {
                $successCount++;
            } else {
                $failureCount++;
            }
            
            $results[] = [
                'order_id' => $order_id,
                'status' => $result['status'],
                'message' => $result['message'],
                'awb_number' => $result['awb_number'] ?? null,
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            $failureCount++;
            $results[] = [
                'order_id' => $order_id,
                'status' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'awb_number' => null
            ];
            
            Log::error("Bulk order assignment error for order {$order_id}: " . $e->getMessage());
        }
    }
    
    return [
        'status' => $successCount > 0,
        'message' => "Bulk assignment completed. Success: {$successCount}, Failed: {$failureCount}",
        'summary' => [
            'total_orders' => count($order_ids),
            'successful' => $successCount,
            'failed' => $failureCount
        ],
        'results' => $results
    ];
}

private function processSingleOrderAssignmentBulk($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'Xpressbee 250gms';
    $token = $params['token'] ?? null; // Use pre-authenticated token if available
    
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
        return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    }

    // Get auth token if not provided
    if (!$token) {
        $authResponse = $this->shiprocketLogin();
        if (!isset($authResponse['token'])) {
            return ['status' => false, 'message' => 'Failed to authenticate with Shiprocket'];
        }
        $token = $authResponse['token'];
    }

    // Decode pickup & consignee
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // Prepare order items for Shiprocket
    $shiprocketOrderItems = [];
    $subTotal = 0;
    foreach ($orderItems as $item) {
        $itemPrice = (float)($item['price'] ?? 0);
        $itemQty = (int)($item['qty'] ?? 1);
        $subTotal += $itemPrice * $itemQty;
        
        $shiprocketOrderItems[] = [
            "name" => $item['name'] ?? 'Product',
            "sku" => $item['sku'] ?? 'SKU123',
            "units" => $itemQty,
            "selling_price" => $itemPrice,
            "discount" => 0,
            "tax" => 0,
            "hsn" => "441122",
            "weight" => (float)(($order->package_weight ?? 500) / 1000)
        ];
    }

    try {
        // Create Pickup Location/Warehouse
        $warehouseResult = $this->createWarehousebulk($pickup, $seller->id, $token);
        if (!$warehouseResult['status']) {
            return [
                'status' => false,
                'message' => 'Failed to create/get warehouse: ' . $warehouseResult['message']
            ];
        }
        
        $pickupLocationName = $warehouseResult['pickup_location'];

        // Create Order in Shiprocket
        $shiprocketPayload = [
            "order_id" => $order->order_number,
            "order_date" => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s'),
            "pickup_location" => (string) $pickupLocationName,
            "channel_id" => "",
            "comment" => "Bulk Order from " . ($seller->name ?? 'Seller'),
            "billing_customer_name" => $consignee['name'] ?? '',
            "billing_last_name" => "",
            "billing_address" => $consignee['address'] ?? '',
            "billing_address_2" => $consignee['address_2'] ?? '',
            "billing_city" => $consignee['city'] ?? '',
            "billing_pincode" => $consignee['pincode'] ?? '',
            "billing_state" => $consignee['state'] ?? '',
            "billing_country" => "India",
            "billing_email" => (!empty($consignee['email']) && strtolower($consignee['email']) !== 'na')
                    ? $consignee['email'] : 'vk0553723@gmail.com',
            "billing_phone" => $consignee['phone'] ?? '',
            "shipping_is_billing" => true,
            "shipping_customer_name" => "",
            "shipping_last_name" => "",
            "shipping_address" => "",
            "shipping_address_2" => "",
            "shipping_city" => "",
            "shipping_pincode" => "",
            "shipping_country" => "",
            "shipping_state" => "",
            "shipping_email" => "",
            "shipping_phone" => "",
            "order_items" => $shiprocketOrderItems,
            "payment_method" => ucfirst(strtolower($order->payment_type ?? 'COD')),
            "shipping_charges" => 0,
            "giftwrap_charges" => 0,
            "transaction_charges" => 0,
            "total_discount" => 0,
            "sub_total" => (float)$subTotal,
            "length" => (float)($order->package_length ?? 10),
            "breadth" => (float)($order->package_breadth ?? 5),
            "height" => (float)($order->package_height ?? 2),
            "weight" => (float)(($order->package_weight ?? 1000) / 1000)
        ];

        $createOrderResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apiv2.shiprocket.in/v1/external/orders/create/adhoc', $shiprocketPayload);

        $orderResponseData = $createOrderResponse->json();
        
        if (!$createOrderResponse->successful() || !isset($orderResponseData['order_id'])) {
            return [
                'status' => false,
                'message' => 'Failed to create order with Shiprocket',
                'data' => $orderResponseData
            ];
        }

        $shiprocket_order_id = $orderResponseData['order_id'];

        // Generate AWB (Assign Courier)
        $awbPayload = [
            "shipment_id" => $orderResponseData['shipment_id'] ?? null,
            "courier_id" => $this->getCourierIdByProviderbulk($provider_name)
        ];
        
        $awbResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post('https://apiv2.shiprocket.in/v1/external/courier/assign/awb', $awbPayload);

        $awbResponseData = $awbResponse->json();
        
        if ($awbResponse->successful() && 
            isset($awbResponseData['awb_assign_status']) && 
            $awbResponseData['awb_assign_status'] == 1 &&
            isset($awbResponseData['response']['data']['awb_code'])) {

            $responseData = $awbResponseData['response']['data'];
            $awb_number = $responseData['awb_code'];
            $shipment_id = $responseData['shipment_id'] ?? $orderResponseData['shipment_id'] ?? null;
            
            // Generate Pickup
            $pickupResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->post('https://apiv2.shiprocket.in/v1/external/courier/generate/pickup', [
                "shipment_id" => [$shipment_id]
            ]);

            $pickupResponseData = $pickupResponse->json();
            $pickupMessage = '';
            $pickupToken = '';
            
            if ($pickupResponse->successful() && 
                isset($pickupResponseData['pickup_status']) && 
                $pickupResponseData['pickup_status'] == 1) {
                $pickupMessage = $pickupResponseData['response']['data'] ?? 'Pickup generated successfully';
                $pickupToken = $pickupResponseData['response']['pickup_token_number'] ?? '';
            }
            
            // Update order
            $order->update([
                'courier_id' => 'shiprocket',
                'all_courier_name' => $provider_name,
                'awb_number' => $awb_number,
                'shipping_date' => Carbon::now()->format('Y-m-d')
            ]);

            // Debit wallet
            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Bulk order created'
            ]);

            return [
                'status' => true,
                'message' => 'Order successfully created, AWB assigned and pickup scheduled',
                'couriername' => 'shiprocket',
                'awb_number' => $awb_number,
                'pickup_message' => $pickupMessage,
                'pickup_token_number' => $pickupToken,
            ];
        } else {
            // AWB assignment failed
            $order->update([
                'courier_id' => 'shiprocket',
                'all_courier_name' => $provider_name,
                'shiprocket_order_id' => $shiprocket_order_id,
                'shiprocket_shipment_id' => $orderResponseData['shipment_id'] ?? null
            ]);

            return [
                'status' => false,
                'message' => 'Order created but AWB assignment failed',
                'couriername' => 'shiprocket',
                'order_id' => $shiprocket_order_id,
                'shipment_id' => $orderResponseData['shipment_id'] ?? null
            ];
        }
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage()
        ];
    }
}


}
