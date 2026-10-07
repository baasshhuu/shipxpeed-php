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


class DtdcService implements CourierServiceInterface
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
                \Log::error("Error processing order {$orderId} in DTDC bulk serviceability: " . $e->getMessage());
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

    $bydefaultDtdcServices = [
        'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
        'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg',
        'DTDC_Air', 'dtdc_air', 'Dtdc_Air'
    ];

        // Get only the active DTDC services for this seller
        // Include all possible case variations that might exist in database
        $dtdcServices = [
            'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
            'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg', 'DTDC_Surface_1KG',
            'DTDC_Air', 'dtdc_air', 'Dtdc_Air', 'DTDC_AIR'
        ];
        
        $activeDtdcServicesRaw = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'status' => 1
        ])->whereIn('LogisticProvider', $dtdcServices)
          ->pluck('LogisticProvider')
          ->toArray();
        
        // Check if default services exist but are inactive
        $inactiveDefaultServices = ActicvSleb::where('seller_id', $order->seller_id)
            ->whereIn('LogisticProvider', $bydefaultDtdcServices)
            ->where('status', 0)
            ->pluck('LogisticProvider')
            ->toArray();
        
        // If no active services found or default services not in active list, merge with default services
        // BUT only if default services are not explicitly inactive
        $defaultServicesLower = array_map('strtolower', $bydefaultDtdcServices);
        $activeServicesLower = array_map('strtolower', $activeDtdcServicesRaw);
        $inactiveDefaultServicesLower = array_map('strtolower', $inactiveDefaultServices);
        
        // Check if any default services are missing from active services
        $missingDefaults = array_diff($defaultServicesLower, $activeServicesLower);
        
        // Remove any default services that are explicitly inactive
        $validMissingDefaults = array_diff($missingDefaults, $inactiveDefaultServicesLower);
        
        if (!empty($validMissingDefaults) && empty($activeDtdcServicesRaw)) {
            // Add default services to active services only if they are not inactive
            $defaultServicesToAdd = array_filter($bydefaultDtdcServices, function($service) use ($inactiveDefaultServicesLower) {
                return !in_array(strtolower($service), $inactiveDefaultServicesLower);
            });
            $activeDtdcServicesRaw = array_merge($activeDtdcServicesRaw, $defaultServicesToAdd);
        }
        
        // Convert to lowercase for case-insensitive comparison
        $activeDtdcServices = array_map('strtolower', $activeDtdcServicesRaw);
        
        // Continue processing even if some services are inactive
        // We'll filter in the loop later
//  echo 'sxsx';die;
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
        $actualWeight = (float)$order->package_weight; // grams
    $volumetricWeight = ($length * $width * $height) / 5000; // volumetric weight in grams
    $weight = max($actualWeight, $volumetricWeight); // use whichever is higher
    // $weight = (float)$order->package_weight; // grams
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;
// dd($order->payment_type);
    // 🧾 Step 3: Get DTDC Zone Pricing for all 3 services
    $dtdcServices = [
        'DTDC_Surface_500gm',
        'DTDC_Surface_1kg', 
        'DTDC_Air'
    ];

    // 🧾 Step 4: Calculate DTDC charges based on ZonePriceSetting for each service
    $calculateDtdcCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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
               

                $basePrice = $zonePricing->cod_price;
                $prepaidPrice = $zonePricing->prepaid_price ?? 0;

                // Total base charges (COD base + Prepaid base)
                $baseTotal = $basePrice + $prepaidPrice;

                // Calculate COD charge if order amount > 1400
                $codCharge = 0;
                if ($orderAmount > 1400) {
                    $codChargePercent = $zonePricing->cod_charge_parsent ?? 0;
                    $codCharge = ($orderAmount * $codChargePercent / 100);
                }

                // Final amount with GST
                $subTotal = $baseTotal + $codCharge;
                $totalWithGST = $subTotal + ($subTotal * 18 / 100);

                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($baseTotal, 2), // base + prepaid
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

    // 🧮 Step 5: Build final response with individual pricing for each service
    $slabs = [
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 500gm', 'logisticProvider' => 'DTDC_Surface_500gm'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 1kg', 'logisticProvider' => 'DTDC_Surface_1kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Air', 'logisticProvider' => 'DTDC_Air'],
    ];

    $results = [];
    foreach ($slabs as $slab) {
        // Only process services that are active for this seller (case-insensitive check)
        if (!in_array(strtolower($slab['logisticProvider']), $activeDtdcServices)) {
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

        $charges = $calculateDtdcCharge($zonePricing);
        
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


// private function processSingleOrderServiceability(array $params): array
// {
//     $order = Order::findOrFail($params['order_id']);
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $pincodeToCheck = $consignee['pincode'];
//     $destinationstate = $consignee['state'];

//         // Get only the active DTDC services for this seller
//         // Include all possible case variations that might exist in database
//         $dtdcServices = [
//             'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
//             'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg', 'DTDC_Surface_1KG',
//             'DTDC_Air', 'dtdc_air', 'Dtdc_Air', 'DTDC_AIR'
//         ];
        
//         $activeDtdcServicesRaw = ActicvSleb::where([
//             'seller_id' => $order->seller_id,
//             'status' => 1
//         ])->whereIn('LogisticProvider', $dtdcServices)
//           ->pluck('LogisticProvider')
//           ->toArray();
        
//         // Convert to lowercase for case-insensitive comparison
//         $activeDtdcServices = array_map('strtolower', $activeDtdcServicesRaw);
        
//         // Continue processing even if some services are inactive
//         // We'll filter in the loop later
// //  echo 'sxsx';die;
//     // Pickup Info
//     $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $originPin = $source['pincode'];
//     $pickupstate = $source['state'];

//     // 🧾 Step 1: Zone Fetch (Case-Insensitive)
//     $zoneData = DB::table('pincode_zones')
//         ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
//         ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
//         ->first();
//     //   dd($zoneData);
//     if (!$zoneData) {
//         return [];
//     }

//     $zone = strtoupper($zoneData->zone);

//     // Step 2: Weight & Payment Info
//     $weight = (float)$order->package_weight; // grams
//     $orderAmount = $order->collectable_amount;
//     $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
//     $seller_id = $order->seller_id;
// // dd($order->payment_type);
//     // 🧾 Step 3: Get DTDC Zone Pricing for all 3 services
//     $dtdcServices = [
//         'DTDC_Surface_500gm',
//         'DTDC_Surface_1kg', 
//         'DTDC_Air'
//     ];

//     // 🧾 Step 4: Calculate DTDC charges based on ZonePriceSetting for each service
//     $calculateDtdcCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
//         if (!$zonePricing) {
//             return null; // Skip if no pricing found
//         }
        
//         if ($paymentType === 'COD') {
//             // COD Order Logic
//             if ($zonePricing->cod_fix_price > 0) {
//                 // Use fixed COD price - NO GST, NO other charges
//                 $totalPrice = $zonePricing->cod_fix_price;
//                 return [
//                     'courierCharge'  => round($totalPrice, 2),
//                     'freightCharges' => round($totalPrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             } else {
            

//                 $basePrice = $zonePricing->cod_price;
//                 $prepaidPrice = $zonePricing->prepaid_price ?? 0;

//                 // Total base charges (COD base + Prepaid base)
//                 $baseTotal = $basePrice + $prepaidPrice;

//                 // Calculate COD charge if order amount > 1400
//                 $codCharge = 0;
//                 if ($orderAmount > 1400) {
//                     $codChargePercent = $zonePricing->cod_charge_parsent ?? 0;
//                     $codCharge = ($orderAmount * $codChargePercent / 100);
//                 }

//                 // Final amount with GST
//                 $subTotal = $baseTotal + $codCharge;
//                 $totalWithGST = $subTotal + ($subTotal * 18 / 100);

//                 return [
//                     'courierCharge'  => round($totalWithGST, 2),
//                     'freightCharges' => round($baseTotal, 2), // base + prepaid
//                     'codCharge'      => round($codCharge, 2),
//                 ];

//             }
//         } else {
//             // Prepaid Order Logic
//             if ($zonePricing->prepaid_fix_price > 0) {
//                 // Use fixed Prepaid price - NO GST, NO other charges
//                 $totalPrice = $zonePricing->prepaid_fix_price;
//                 return [
//                     'courierCharge'  => round($totalPrice, 2),
//                     'freightCharges' => round($totalPrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             } else {
//                 // Use variable Prepaid price + 18% GST
//                 $basePrice = $zonePricing->prepaid_price;
//                 $totalWithGST = $basePrice + ($basePrice * 18 / 100);
//                 return [
//                     'courierCharge'  => round($totalWithGST, 2),
//                     'freightCharges' => round($basePrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             }
//         }
//     };

//     // 🧮 Step 5: Build final response with individual pricing for each service
//     $slabs = [
//         ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 500gm', 'logisticProvider' => 'DTDC_Surface_500gm'],
//         ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 1kg', 'logisticProvider' => 'DTDC_Surface_1kg'],
//         ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Air', 'logisticProvider' => 'DTDC_Air'],
//     ];

//     $results = [];
//     foreach ($slabs as $slab) {
//         // Only process services that are active for this seller (case-insensitive check)
//         if (!in_array(strtolower($slab['logisticProvider']), $activeDtdcServices)) {
//             continue; // Skip inactive services
//         }
        
//         // Get zone pricing for this specific service
//         $zonePricing = ZonePriceSetting::where([
//             'seller_id' => $seller_id,
//             'zone' => $zone,
//             'LogisticProvider' => $slab['logisticProvider'],
//             'status' => 1
//         ])->first();

//         if (!$zonePricing) {
//         $zonePricing = ZonePriceSetting::where([
//         'seller_id' => 14,
//         'zone' => $zone,
//         'LogisticProvider' => $slab['logisticProvider'],
//         'status' => 1
//         ])->first();
//             }

//         $charges = $calculateDtdcCharge($zonePricing);
        
//         // Only add if pricing exists for this service
//         if ($charges !== null) {
//             $results[] = [
//                 'serviceabilityId' => $slab['serviceabilityId'],
//                 'courierName'      => $slab['courierName'],
//                 'courierCharge'    => $charges['courierCharge'],
//                 'freightCharges'   => $charges['freightCharges'],
//                 'codCharge'        => $charges['codCharge'],
//                 'zone'             => $zone,
//                 'minWeight'        => $weight,
//                 'volWeight'        => $weight,
//             ];
//         }
//     }
// // dd($results);
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

    $bydefaultDtdcServices = [
        'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
        'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg',
        'DTDC_Air', 'dtdc_air', 'Dtdc_Air'
    ];

        // Get only the active DTDC services for this seller
        // Include all possible case variations that might exist in database
        $dtdcServices = [
            'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
            'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg', 'DTDC_Surface_1KG',
            'DTDC_Air', 'dtdc_air', 'Dtdc_Air', 'DTDC_AIR'
        ];
        
        $activeDtdcServicesRaw = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'status' => 1
        ])->whereIn('LogisticProvider', $dtdcServices)
          ->pluck('LogisticProvider')
          ->toArray();
        
        // Check if default services exist but are inactive
        $inactiveDefaultServices = ActicvSleb::where('seller_id', $order->seller_id)
            ->whereIn('LogisticProvider', $bydefaultDtdcServices)
            ->where('status', 0)
            ->pluck('LogisticProvider')
            ->toArray();
        
        // If no active services found or default services not in active list, merge with default services
        // BUT only if default services are not explicitly inactive
        $defaultServicesLower = array_map('strtolower', $bydefaultDtdcServices);
        $activeServicesLower = array_map('strtolower', $activeDtdcServicesRaw);
        $inactiveDefaultServicesLower = array_map('strtolower', $inactiveDefaultServices);
        
        // Check if any default services are missing from active services
        $missingDefaults = array_diff($defaultServicesLower, $activeServicesLower);
        
        // Remove any default services that are explicitly inactive
        $validMissingDefaults = array_diff($missingDefaults, $inactiveDefaultServicesLower);
        
        if (!empty($validMissingDefaults) && empty($activeDtdcServicesRaw)) {
            // Add default services to active services only if they are not inactive
            $defaultServicesToAdd = array_filter($bydefaultDtdcServices, function($service) use ($inactiveDefaultServicesLower) {
                return !in_array(strtolower($service), $inactiveDefaultServicesLower);
            });
            $activeDtdcServicesRaw = array_merge($activeDtdcServicesRaw, $defaultServicesToAdd);
        }
        
        // Convert to lowercase for case-insensitive comparison
        $activeDtdcServices = array_map('strtolower', $activeDtdcServicesRaw);
        
        // Continue processing even if some services are inactive
        // We'll filter in the loop later
//  echo 'sxsx';die;
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
        $actualWeight = (float)$order->package_weight; // grams
    $volumetricWeight = ($length * $width * $height) / 5000; // volumetric weight in grams
    $weight = max($actualWeight, $volumetricWeight); // use whichever is higher
    // $weight = (float)$order->package_weight; // grams
    $orderAmount = $order->collectable_amount;
    $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $order->seller_id;
// dd($order->payment_type);
    // 🧾 Step 3: Get DTDC Zone Pricing for all 3 services
    $dtdcServices = [
        'DTDC_Surface_500gm',
        'DTDC_Surface_1kg', 
        'DTDC_Air'
    ];

    // 🧾 Step 4: Calculate DTDC charges based on ZonePriceSetting for each service
    $calculateDtdcCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
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
               

                $basePrice = $zonePricing->cod_price;
                $prepaidPrice = $zonePricing->prepaid_price ?? 0;

                // Total base charges (COD base + Prepaid base)
                $baseTotal = $basePrice + $prepaidPrice;

                // Calculate COD charge if order amount > 1400
                $codCharge = 0;
                if ($orderAmount > 1400) {
                    $codChargePercent = $zonePricing->cod_charge_parsent ?? 0;
                    $codCharge = ($orderAmount * $codChargePercent / 100);
                }

                // Final amount with GST
                $subTotal = $baseTotal + $codCharge;
                $totalWithGST = $subTotal + ($subTotal * 18 / 100);

                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($baseTotal, 2), // base + prepaid
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

    // 🧮 Step 5: Build final response with individual pricing for each service
    $slabs = [
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 500gm', 'logisticProvider' => 'DTDC_Surface_500gm'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 1kg', 'logisticProvider' => 'DTDC_Surface_1kg'],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Air', 'logisticProvider' => 'DTDC_Air'],
    ];

    $results = [];
    foreach ($slabs as $slab) {
        // Only process services that are active for this seller (case-insensitive check)
        if (!in_array(strtolower($slab['logisticProvider']), $activeDtdcServices)) {
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

        $charges = $calculateDtdcCharge($zonePricing);
        
        // Only add if pricing exists for this service
        if ($charges !== null) {
            $results[] = [
                'serviceabilityId' => $slab['serviceabilityId'],
                'courierName'      => $slab['courierName'],
                'courierCharge'    => $charges['courierCharge'],
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


// public function getServiceability es m by defolt active vala nhi h (array $params): array
// {
//     // dd($params);
//     $order = Order::findOrFail($params['order_id']);
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $pincodeToCheck = $consignee['pincode'];
//     $destinationstate = $consignee['state'];

//         // Get only the active DTDC services for this seller
//         // Include all possible case variations that might exist in database
//         $dtdcServices = [
//             'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
//             'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg', 'DTDC_Surface_1KG',
//             'DTDC_Air', 'dtdc_air', 'Dtdc_Air', 'DTDC_AIR'
//         ];
        
//         $activeDtdcServicesRaw = ActicvSleb::where([
//             'seller_id' => $order->seller_id,
//             'status' => 1
//         ])->whereIn('LogisticProvider', $dtdcServices)
//           ->pluck('LogisticProvider')
//           ->toArray();
        
//         // Convert to lowercase for case-insensitive comparison
//         $activeDtdcServices = array_map('strtolower', $activeDtdcServicesRaw);
        
//         // Continue processing even if some services are inactive
//         // We'll filter in the loop later
// //  echo 'sxsx';die;
//     // Pickup Info
//     $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $originPin = $source['pincode'];
//     $pickupstate = $source['state'];

//     // 🧾 Step 1: Zone Fetch (Case-Insensitive)
//     $zoneData = DB::table('pincode_zones')
//         ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
//         ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
//         ->first();
//     //   dd($zoneData);
//     if (!$zoneData) {
//         return [];
//     }

//     $zone = strtoupper($zoneData->zone);

//     // Step 2: Weight & Payment Info
//     $weight = (float)$order->package_weight; // grams
//     $orderAmount = $order->collectable_amount;
//     $paymentType = strtolower($order->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
//     $seller_id = $order->seller_id;
// // dd($order->payment_type);
//     // 🧾 Step 3: Get DTDC Zone Pricing for all 3 services
//     $dtdcServices = [
//         'DTDC_Surface_500gm',
//         'DTDC_Surface_1kg', 
//         'DTDC_Air'
//     ];

//     // 🧾 Step 4: Calculate DTDC charges based on ZonePriceSetting for each service
//     $calculateDtdcCharge = function ($zonePricing) use ($weight, $paymentType, $orderAmount) {
//         if (!$zonePricing) {
//             return null; // Skip if no pricing found
//         }
        
//         if ($paymentType === 'COD') {
//             // COD Order Logic
//             if ($zonePricing->cod_fix_price > 0) {
//                 // Use fixed COD price - NO GST, NO other charges
//                 $totalPrice = $zonePricing->cod_fix_price;
//                 return [
//                     'courierCharge'  => round($totalPrice, 2),
//                     'freightCharges' => round($totalPrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             } else {
               

//                 $basePrice = $zonePricing->cod_price;
//                 $prepaidPrice = $zonePricing->prepaid_price ?? 0;

//                 // Total base charges (COD base + Prepaid base)
//                 $baseTotal = $basePrice + $prepaidPrice;

//                 // Calculate COD charge if order amount > 1400
//                 $codCharge = 0;
//                 if ($orderAmount > 1400) {
//                     $codChargePercent = $zonePricing->cod_charge_parsent ?? 0;
//                     $codCharge = ($orderAmount * $codChargePercent / 100);
//                 }

//                 // Final amount with GST
//                 $subTotal = $baseTotal + $codCharge;
//                 $totalWithGST = $subTotal + ($subTotal * 18 / 100);

//                 return [
//                     'courierCharge'  => round($totalWithGST, 2),
//                     'freightCharges' => round($baseTotal, 2), // base + prepaid
//                     'codCharge'      => round($codCharge, 2),
//                 ];

//             }
//         } else {
//             // Prepaid Order Logic
//             if ($zonePricing->prepaid_fix_price > 0) {
//                 // Use fixed Prepaid price - NO GST, NO other charges
//                 $totalPrice = $zonePricing->prepaid_fix_price;
//                 return [
//                     'courierCharge'  => round($totalPrice, 2),
//                     'freightCharges' => round($totalPrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             } else {
//                 // Use variable Prepaid price + 18% GST
//                 $basePrice = $zonePricing->prepaid_price;
//                 $totalWithGST = $basePrice + ($basePrice * 18 / 100);
//                 return [
//                     'courierCharge'  => round($totalWithGST, 2),
//                     'freightCharges' => round($basePrice, 2),
//                     'codCharge'      => 0,
//                 ];
//             }
//         }
//     };

//     // 🧮 Step 5: Build final response with individual pricing for each service
//     $slabs = [
//         ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 500gm', 'logisticProvider' => 'DTDC_Surface_500gm'],
//         ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Surface 1kg', 'logisticProvider' => 'DTDC_Surface_1kg'],
//         ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'DTDC Air', 'logisticProvider' => 'DTDC_Air'],
//     ];

//     $results = [];
//     foreach ($slabs as $slab) {
//         // Only process services that are active for this seller (case-insensitive check)
//         if (!in_array(strtolower($slab['logisticProvider']), $activeDtdcServices)) {
//             continue; // Skip inactive services
//         }
        
//         // Get zone pricing for this specific service
//         $zonePricing = ZonePriceSetting::where([
//             'seller_id' => $seller_id,
//             'zone' => $zone,
//             'LogisticProvider' => $slab['logisticProvider'],
//             'status' => 1
//         ])->first();

//         if (!$zonePricing) {
//         $zonePricing = ZonePriceSetting::where([
//         'seller_id' => 14,
//         'zone' => $zone,
//         'LogisticProvider' => $slab['logisticProvider'],
//         'status' => 1
//         ])->first();
//             }

//         $charges = $calculateDtdcCharge($zonePricing);
        
//         // Only add if pricing exists for this service
//         if ($charges !== null) {
//             $results[] = [
//                 'serviceabilityId' => $slab['serviceabilityId'],
//                 'courierName'      => $slab['courierName'],
//                 'courierCharge'    => $charges['courierCharge'],
//                 'freightCharges'   => $charges['freightCharges'],
//                 'codCharge'        => $charges['codCharge'],
//                 'zone'             => $zone,
//                 'minWeight'        => $weight,
//                 'volWeight'        => $weight,
//             ];
//         }
//     }
// // dd($results);
//     return $results;
// }





public function assignOrder($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'DTDC';
    //    dd($provider_name);
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
    // dd($consignee);
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;
// B2C SMART EXPRESS//primeeyam
    if($provider_name == "DTDC Surface 500gm"){
        $service_type_id = "B2C SMART EXPRESS";
    }elseif($provider_name == "DTDC Surface 1kg"){
        $service_type_id = "B2C SMART EXPRESS";
    }elseif($provider_name == "DTDC Air"){
        $service_type_id = "B2C PRIORITY";
    }else{
        $service_type_id = "B2C SMART EXPRESS";
      }
    // Prepare DTDC payload
    $dtdcPayload = [
        "consignments" => [
            [
                "customer_code" => "GL11173",
                "service_type_id" => $service_type_id,
                "load_type" => "NON-DOCUMENT",
                "description" => $order->product_name ?? 'Shipment',
                "dimension_unit" => "cm",
                "length" => $order->package_length ?? 20,
                "width" => $order->package_breadth ?? 20,
                "height" => $order->package_height ?? 20,
                "weight_unit" => "kg",
                "weight" => max(0.5, ($order->package_weight ?? 1000)/1000),
                "declared_value" => $order->collectable_amount ?? 0,
                "num_pieces" => 1,

                "origin_details" => [
                    "name" => $pickup['name'],
                    "phone" => $pickup['phone'],
                    "alternate_phone" => $pickup['phone'],
                    "address_line_1" => $pickup['address'],
                    "address_line_2" => $pickup['address_2'],
                    "pincode" => (string)$pickup['pincode'],
                    "city" => $pickup['city'],
                    "state" => $pickup['state'] ?? 'Delhi',
                ],

                "destination_details" => [
                    "name" => $consignee['name'],
                    "phone" => $consignee['phone'],
                    "alternate_phone" => $consignee['phone'],
                    "address_line_1" => $consignee['address'],
                    "address_line_2" => $consignee['address_2'],
                    "pincode" => (string)$consignee['pincode'],
                    "city" => $consignee['city'],
                    "state" => $consignee['state']
                ],

                "return_details" => [
                    "address_line_1" => $pickup['address'],
                    "address_line_2" => $pickup['address_2'],
                    "city_name" => $pickup['city'],
                    "name" => $pickup['name'],
                    "phone" => $pickup['phone'],
                    "pincode" => (string)$pickup['pincode'],
                    "state_name" => $pickup['state'] ?? 'Delhi',
                    "email" => $pickup['email'] ?? 'test@gmail.com',
                    "alternate_phone" => $pickup['phone']
                ],

                "customer_reference_number" => $order->order_number,
                // "cod_collection_mode" => $order->payment_type == 'cod' ? 'CASH' : '',
                // "cod_amount" => $order->payment_type == 'cod' ? $order->collectable_amount : 0,
                "cod_collection_mode" => strtolower($order->payment_type) === 'cod' ? 'CASH' : '',
                "cod_amount" => strtolower($order->payment_type) === 'cod' ? $order->collectable_amount : 0,

                "commodity_id" => "89",
                "eway_bill" => "",
                "is_risk_surcharge_applicable" => false,
                "invoice_number" => $order->order_number,
                "invoice_date" => now()->format('d M Y'),
                "reference_number" => "",
            ]
        ]
    ];
    // dd($dtdcPayload);
    try {
        $url = "https://pxapi.dtdc.in/api/customer/integration/consignment/softdata";
        $response = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'api-key'        => 'a74cd3ae095ad603dc6d506fb30dcf',
            'x-access-token' => 'GL017_trk_json:521ce7881cb576b9a084489e02534e2e',
        ])->post($url, $dtdcPayload);

        $responseData = $response->json();
        // dd($responseData);
        if (!$response->successful() || ($responseData['status'] ?? '') != 'OK') {
            return ['status'=>false, 'message'=>'DTDC Order creation failed', 'data'=>$responseData];
        }

        if ($responseData['status'] == 'OK' && !empty($responseData['data'][0]['reference_number'])) {
            $awb = $responseData['data'][0]['reference_number']; // main AWB

            $order->courier_id = 'dtdc';
            $order->all_courier_name = $provider_name;
            $order->awb_number = $awb;
            $order->shipping_date = now()->format('Y-m-d');
            $order->save();

            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order created'
            ]);

            return [
            'status' => true,
            'message' => 'Order successfully assigned to DTDC.',
            'couriername' => 'dtdc',
            'awb_number' => $responseData['data'][0]['reference_number'] ?? null,
            // 'label_url' => $responseData['data'][0]['barCodeData'] ?? null, // DTDC may return base64 label or empty
            // 'route_code' => $responseData['data'][0]['courier_partner'] ?? null,
            'tracking_status' => $responseData['data'][0]['success'] ? 'Created' : 'Failed',
        ];


            // return ['status' => true, 'message' => 'Order successfully created on DTDC', 'awb' => $awb, 'response' => $responseData];
        }


        // Save order info
        // $order->courier_id = 'dtdc';
        // $order->all_courier_name = 'DTDC';
        // $order->awb_number = $responseData['consignment_no'] ?? null;
        // $order->shipping_date = now()->format('Y-m-d');
        // $order->save();

        // // Wallet debit
        // Recharge::create([
        //     'seller_id' => $seller->id,
        //     'type' => 'Debit',
        //     'amount' => $order->seller_amount_walate,
        //     'status' => 1,
        //     'description' => 'DTDC Order created'
        // ]);

        // return ['status'=>true,'message'=>'Order successfully created on DTDC','response'=>$responseData];

    } catch (\Exception $e) {
        return ['status'=>false,'message'=>'API Request Failed: '.$e->getMessage()];
    }
}

public function cancelShipment($awb)
{
    try {
        // DTDC Production API URL
        $url = "http://pxapi.dtdc.in/api/customer/integration/consignment/cancel";

        // DTDC API credentials (replace with actual)
        $apiKey = "a74cd3ae095ad603dc6d506fb30dcf";
        $customerCode = "GL11173"; // Your DTDC customer code

        $seller = Auth::guard('seller')->user();
        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found for AWB: ' . $awb,
                'responseCode' => 404
            ];
        }

        // Prepare request payload for a single AWB
        $payload = [
            "AWBNo" => [$awb],
            "customerCode" => $customerCode
        ];

        // Send POST request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $apiKey,
        ])->post($url, $payload);

        $responseData = $response->json();
            // dd($responseData);
        // Check if cancellation succeeded
        if ($response->successful() && ($responseData['status'] ?? '') === 'OK' && ($responseData['success'] ?? false)) {
            $consignment = $responseData['successConsignments'][0] ?? null;

            if ($consignment['success'] ?? false) {
                // Update order
                $order->order_status = 'cancelled';
                $order->save();

                // Refund seller
                Recharge::create([
                    'seller_id'   => $seller->id,
                    'type'        => 'Credit',
                    'amount'      => $order->seller_amount_walate,
                    'status'      => 1,
                    'description' => 'Order cancelled',
                ]);

                return [
                    'status' => true,
                    'message' => 'DTDC shipment cancelled successfully.',
                    'awb_number' => $awb,
                    'response' => $responseData,
                    'responseCode' => $response->status(),
                ];
            }
        }

        // If cancellation failed
        return [
            'status' => false,
            'message' => $responseData['error'] ?? 'Failed to cancel shipment.',
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
    $provider_name = $params['provider_name'] ?? 'DTDC';
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
        'couriername' => 'dtdc',
        'results' => $results,
        'success_count' => $successCount,
        'failure_count' => $failureCount
    ];
}

private function processSingleOrderAssignmentBulk($params)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'DTDC';

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
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // Determine service type based on provider name
    $service_type_id = $this->getServiceTypeId($provider_name);

    // Prepare DTDC payload
    $dtdcPayload = [
        "consignments" => [
            [
                "customer_code" => "GL11173",
                "service_type_id" => $service_type_id,
                "load_type" => "NON-DOCUMENT",
                "description" => $order->product_name ?? 'Shipment',
                "dimension_unit" => "cm",
                "length" => $order->package_length ?? 20,
                "width" => $order->package_breadth ?? 20,
                "height" => $order->package_height ?? 20,
                "weight_unit" => "kg",
                "weight" => max(0.5, ($order->package_weight ?? 1000)/1000),
                "declared_value" => $order->collectable_amount ?? 0,
                "num_pieces" => 1,

                "origin_details" => [
                    "name" => $pickup['name'],
                    "phone" => $pickup['phone'],
                    "alternate_phone" => $pickup['phone'],
                    "address_line_1" => $pickup['address'],
                    "address_line_2" => $pickup['address_2'] ?? '',
                    "pincode" => (string)$pickup['pincode'],
                    "city" => $pickup['city'],
                    "state" => $pickup['state'] ?? 'Delhi',
                ],

                "destination_details" => [
                    "name" => $consignee['name'],
                    "phone" => $consignee['phone'],
                    "alternate_phone" => $consignee['phone'],
                    "address_line_1" => $consignee['address'],
                    "address_line_2" => $consignee['address_2'] ?? '',
                    "pincode" => (string)$consignee['pincode'],
                    "city" => $consignee['city'],
                    "state" => $consignee['state']
                ],

                "return_details" => [
                    "address_line_1" => $pickup['address'],
                    "address_line_2" => $pickup['address_2'] ?? '',
                    "city_name" => $pickup['city'],
                    "name" => $pickup['name'],
                    "phone" => $pickup['phone'],
                    "pincode" => (string)$pickup['pincode'],
                    "state_name" => $pickup['state'] ?? 'Delhi',
                    "email" => $pickup['email'] ?? 'test@gmail.com',
                    "alternate_phone" => $pickup['phone']
                ],

                "customer_reference_number" => $order->order_number,
                "cod_collection_mode" => strtolower($order->payment_type) === 'cod' ? 'CASH' : '',
                "cod_amount" => strtolower($order->payment_type) === 'cod' ? $order->collectable_amount : 0,

                "commodity_id" => "89",
                "eway_bill" => "",
                "is_risk_surcharge_applicable" => false,
                "invoice_number" => $order->order_number,
                "invoice_date" => now()->format('d M Y'),
                "reference_number" => "",
            ]
        ]
    ];

    try {
        $url = "https://pxapi.dtdc.in/api/customer/integration/consignment/softdata";
        $response = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'api-key'        => 'a74cd3ae095ad603dc6d506fb30dcf',
            'x-access-token' => 'GL017_trk_json:521ce7881cb576b9a084489e02534e2e',
        ])->post($url, $dtdcPayload);

        $responseData = $response->json();
        
        if (!$response->successful() || ($responseData['status'] ?? '') != 'OK') {
            return ['status' => false, 'message' => 'DTDC Order creation failed', 'data' => $responseData];
        }

        if ($responseData['status'] == 'OK' && !empty($responseData['data'][0]['reference_number'])) {
            $awb = $responseData['data'][0]['reference_number'];

            $order->courier_id = 'dtdc';
            $order->all_courier_name = $provider_name;
            $order->awb_number = $awb;
            $order->shipping_date = now()->format('Y-m-d');
            $order->save();

            Recharge::create([
                'seller_id' => $seller->id,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order created'
            ]);

            return [
                'status' => true,
                'message' => 'Order successfully assigned to DTDC.',
                'couriername' => 'dtdc',
                'awb_number' => $responseData['data'][0]['reference_number'] ?? null,
                'tracking_status' => $responseData['data'][0]['success'] ? 'Created' : 'Failed',
            ];
        }

        return ['status' => false, 'message' => 'DTDC Order creation failed - invalid response'];

    } catch (\Exception $e) {
        return ['status' => false, 'message' => 'API Request Failed: ' . $e->getMessage()];
    }
}

private function getServiceTypeId($provider_name)
{
    switch ($provider_name) {
        case "DTDC Surface 500gm":
        case "DTDC Surface 1kg":
            return "B2C SMART EXPRESS";
        case "DTDC Air":
            return "B2C PRIORITY";
        default:
            return "B2C SMART EXPRESS";
    }
}







}
