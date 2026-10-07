<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PriceSetting;
use App\Models\Recharge;
use App\Models\ZonePriceSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ActicvSleb;


class ParcelxService implements CourierServiceInterface
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
                \Log::error("Error processing order {$orderId} in ParcelX bulk serviceability: " . $e->getMessage());
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

    // Get only the active ParcelX services for this seller
    // Include all possible case variations that might exist in database
    $parcelxServices = [
        'parcel_x_Delhivery', 'Parcel_X_Delhivery', 
        'parcel_x_Amazon', 'Parcel_X_Amazon',
        'parcel_x_Amazon_1kg', 'parcel_x_Amazon_1KG', 'Parcel_X_Amazon_1kg', 'Parcel_X_Amazon_1KG',
        'parcel_x_Amazon_2kg', 'parcel_x_Amazon_2KG', 'Parcel_X_Amazon_2kg', 'Parcel_X_Amazon_2KG'
    ];
    
    $activeParcelxServicesRaw = ActicvSleb::where([
        'seller_id' => $order->seller_id,
        'status' => 1
    ])->whereIn('LogisticProvider', $parcelxServices)
      ->pluck('LogisticProvider')
      ->toArray();
    
    // Convert to lowercase for case-insensitive comparison
    $activeParcelxServices = array_map('strtolower', $activeParcelxServicesRaw);
    
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
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Deliveri 500gm', 'logisticProvider' => 'parcel_x_Delhivery', 'weightSlab' => 500],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 500gm', 'logisticProvider' => 'parcel_x_Amazon', 'weightSlab' => 500],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 1kg', 'logisticProvider' => 'parcel_x_Amazon_1kg', 'weightSlab' => 1000],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 2kg', 'logisticProvider' => 'parcel_x_Amazon_2kg', 'weightSlab' => 2000],
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
                'courierCharge'    => $charges['courierCharge'],
                'freightCharges'   => $charges['freightCharges'],
                'codCharge'        => $charges['codCharge'],
                'zone'             => $zone,
                'minWeight'        => $weight,
                'volWeight'        => $weight,
            ];
        }
    }
//  dd($results);
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
        'parcel_x_Delhivery', 'Parcel_X_Delhivery',
        'parcel_x_Delhivery_250gm', 'Parcel_X_Delhivery_250gm', 'parcel_x_Delhivery_250GM', 'Parcel_X_Delhivery_250GM',
        'parcel_x_Amazon', 'Parcel_X_Amazon',
        'parcel_x_Amazon_1kg', 'parcel_x_Amazon_1KG', 'Parcel_X_Amazon_1kg', 'Parcel_X_Amazon_1KG',
        'parcel_x_Amazon_2kg', 'parcel_x_Amazon_2KG', 'Parcel_X_Amazon_2kg', 'Parcel_X_Amazon_2KG'
    ];
    
    $activeParcelxServicesRaw = ActicvSleb::where([
        'seller_id' => $order->seller_id,
        'status' => 1
    ])->whereIn('LogisticProvider', $parcelxServices)
      ->pluck('LogisticProvider')
      ->toArray();
    
    // Convert to lowercase for case-insensitive comparison
    $activeParcelxServices = array_map('strtolower', $activeParcelxServicesRaw);
    
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
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Deliveri 500gm', 'logisticProvider' => 'parcel_x_Delhivery', 'weightSlab' => 500],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'parcelx_Deliveri 250gm', 'logisticProvider' => 'parcel_x_Delhivery_250gm', 'weightSlab' => 250],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 500gm', 'logisticProvider' => 'parcel_x_Amazon', 'weightSlab' => 500],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 1kg', 'logisticProvider' => 'parcel_x_Amazon_1kg', 'weightSlab' => 1000],
        ['serviceabilityId' => $pincodeToCheck, 'courierName' => 'Amazon 2kg', 'logisticProvider' => 'parcel_x_Amazon_2kg', 'weightSlab' => 2000],
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
//  dd($results);
    return $results;
}




// public function getServiceability(array $params): array
// {
//     $order = Order::findOrFail($params['order_id']);
//     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//     $pincodeToCheck = $consignee['pincode'];
//     $destinationstate = $consignee['state'];

//     // Pickup Info
//     $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
//     $originPin = $source['pincode'];
//     $pickupstate = $source['state'];



//     // 🧮 Step 2: Fixed Pricing for New Services
//     $deliveriPrice = 36.5;  // Deliveri 500gm base price
//     $amazonPrice = 34;      // Amazon 500gm base price
//     $amazon1kgPrice = 100;  // Amazon 1kg base price
//     $amazon2kgPrice = 200;  // Amazon 2kg base price

//     // Step 3: Weight & Payment Info
//     $weight = (float)$order->package_weight; // grams
//     $orderAmount = $order->collectable_amount;
//     $paymentType = $order->payment_type === 'cod' ? 'COD' : 'Pre-paid';
//     $seller_id = $order->seller_id;

//     // Get PriceSetting for Deliveri 500gm
//     $PriceSettingDeliveri = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'parcel_x_Delhivery'])->first();
//     $sellerPercentageDeliveri = $PriceSettingDeliveri->shipping_charge ?? 30;
//     $codChargePercentDeliveri = $PriceSettingDeliveri->cod_charge_parsent ?? 1.9;
//     $codChargeFixedDeliveri = $PriceSettingDeliveri->cod_charge ?? 32;

//     // Get PriceSetting for Amazon 500gm
//     $PriceSettingAmazon = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'parcel_x_Amazon'])->first();
//     $sellerPercentageAmazon = $PriceSettingAmazon->shipping_charge ?? 30;
//     $codChargePercentAmazon = $PriceSettingAmazon->cod_charge_parsent ?? 1.9;
//     $codChargeFixedAmazon = $PriceSettingAmazon->cod_charge ?? 32;

//     // Get PriceSetting for Amazon 1kg
//     $PriceSettingAmazon1kg = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'parcel_x_Amazon_1kg'])->first();
//     $sellerPercentageAmazon1kg = $PriceSettingAmazon1kg->shipping_charge ?? 30;
//     $codChargePercentAmazon1kg = $PriceSettingAmazon1kg->cod_charge_parsent ?? 1.9;
//     $codChargeFixedAmazon1kg = $PriceSettingAmazon1kg->cod_charge ?? 32;

//     // Get PriceSetting for Amazon 2kg
//     $PriceSettingAmazon2kg = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'parcel_x_Amazon_2kg'])->first();
//     $sellerPercentageAmazon2kg = $PriceSettingAmazon2kg->shipping_charge ?? 30;
//     $codChargePercentAmazon2kg = $PriceSettingAmazon2kg->cod_charge_parsent ?? 1.9;
//     $codChargeFixedAmazon2kg = $PriceSettingAmazon2kg->cod_charge ?? 32;

//     // 🧾 Step 4: Price Calculation Function for different slabs
//     $calculateCharge500gm = function ($baseRate) use ($weight) {
//         if ($weight <= 500) {
//             return $baseRate;
//         }
//         $multiplier = ceil($weight / 500);
//         return $baseRate * $multiplier;
//     };

//     $calculateCharge1kg = function ($baseRate) use ($weight) {
//         if ($weight <= 1000) {
//             return $baseRate;
//         }
//         $multiplier = ceil($weight / 1000);
//         return $baseRate * $multiplier;
//     };

//     $calculateCharge2kg = function ($baseRate) use ($weight) {
//         if ($weight <= 2000) {
//             return $baseRate;
//         }
//         $multiplier = ceil($weight / 2000);
//         return $baseRate * $multiplier;
//     };

//     // 🧮 Step 5: Freight Charges for Each Service
//     $deliveriCharge = $calculateCharge500gm($deliveriPrice);
//     $amazonCharge = $calculateCharge500gm($amazonPrice);
//     $amazon1kgCharge = $calculateCharge1kg($amazon1kgPrice);
//     $amazon2kgCharge = $calculateCharge2kg($amazon2kgPrice);

//     // 🧾 Step 6: Function for COD/GST calculation for Deliveri
//     $applyChargesDeliveri = function ($freight_charges) use ($PriceSettingDeliveri, $sellerPercentageDeliveri, $codChargePercentDeliveri, $codChargeFixedDeliveri, $paymentType, $orderAmount, $order) {
//         // Check if fixed courier price is set
//         if ($PriceSettingDeliveri && isset($PriceSettingDeliveri->fixed_courier_price) && $PriceSettingDeliveri->fixed_courier_price > 0) {
//             // Calculate weight multiplier based on 500g intervals
//             $weightInKg = $order->package_weight / 1000; // Convert grams to kg
//             $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
            
//             // Use fixed price with weight multiplier
//             $freightWithGST = $PriceSettingDeliveri->fixed_courier_price * $weightMultiplier;
//             $freightWithSeller = 10 * $weightMultiplier;
//             $codCharge = 10 * $weightMultiplier;
            
//             return [
//                 'courierCharge'  => round($freightWithGST, 2),
//                 'freightCharges' => round($freightWithSeller, 2),
//                 'codCharge'      => round($codCharge, 2),
//             ];
//         }
        
//         // Default calculation if no fixed price
//         $freight = $freight_charges * 1.10;
//         $freightWithSeller = $freight + ($freight * $sellerPercentageDeliveri / 100);

//         $codCharge = 0;
//         if ($paymentType === 'COD') {
//             $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercentDeliveri / 100) : $codChargeFixedDeliveri;
//         }

//         $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//         $freightWithGST = $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);

//         return [
//             'courierCharge'  => round($freightWithGST, 2),
//             'freightCharges' => round($freightWithSeller, 2),
//             'codCharge'      => round($codCharge, 2),
//         ];
//     };

//     // 🧾 Step 7: Function for COD/GST calculation for Amazon
//     $applyChargesAmazon = function ($freight_charges) use ($PriceSettingAmazon, $sellerPercentageAmazon, $codChargePercentAmazon, $codChargeFixedAmazon, $paymentType, $orderAmount, $order) {
//         // Check if fixed courier price is set
//         if ($PriceSettingAmazon && isset($PriceSettingAmazon->fixed_courier_price) && $PriceSettingAmazon->fixed_courier_price > 0) {
//             // Calculate weight multiplier based on 500g intervals
//             $weightInKg = $order->package_weight / 1000; // Convert grams to kg
//             $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
            
//             // Use fixed price with weight multiplier
//             $freightWithGST = $PriceSettingAmazon->fixed_courier_price * $weightMultiplier;
//             $freightWithSeller = 10 * $weightMultiplier;
//             $codCharge = 10 * $weightMultiplier;
            
//             return [
//                 'courierCharge'  => round($freightWithGST, 2),
//                 'freightCharges' => round($freightWithSeller, 2),
//                 'codCharge'      => round($codCharge, 2),
//             ];
//         }
        
//         // Default calculation if no fixed price
//         $freight = $freight_charges * 1.10;
//         $freightWithSeller = $freight + ($freight * $sellerPercentageAmazon / 100);

//         $codCharge = 0;
//         if ($paymentType === 'COD') {
//             $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercentAmazon / 100) : $codChargeFixedAmazon;
//         }

//         $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//         $freightWithGST = $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);

//         return [
//             'courierCharge'  => round($freightWithGST, 2),
//             'freightCharges' => round($freightWithSeller, 2),
//             'codCharge'      => round($codCharge, 2),
//         ];
//     };

//     // 🧾 Step 7: Function for COD/GST calculation for Amazon 1kg
//     $applyChargesAmazon1kg = function ($freight_charges) use ($PriceSettingAmazon1kg, $sellerPercentageAmazon1kg, $codChargePercentAmazon1kg, $codChargeFixedAmazon1kg, $paymentType, $orderAmount, $order) {
//         // Check if fixed courier price is set
//         if ($PriceSettingAmazon1kg && isset($PriceSettingAmazon1kg->fixed_courier_price) && $PriceSettingAmazon1kg->fixed_courier_price > 0) {
//             // Calculate weight multiplier based on 1kg intervals
//             $weightInKg = $order->package_weight / 1000; // Convert grams to kg
//             $weightMultiplier = max(1, ceil($weightInKg / 1.0)); // Every 1kg interval, minimum 1
            
//             // Use fixed price with weight multiplier
//             $freightWithGST = $PriceSettingAmazon1kg->fixed_courier_price * $weightMultiplier;
//             $freightWithSeller = 10 * $weightMultiplier;
//             $codCharge = 10 * $weightMultiplier;
            
//             return [
//                 'courierCharge'  => round($freightWithGST, 2),
//                 'freightCharges' => round($freightWithSeller, 2),
//                 'codCharge'      => round($codCharge, 2),
//             ];
//         }
        
//         // Default calculation if no fixed price
//         $freight = $freight_charges * 1.10;
//         $freightWithSeller = $freight + ($freight * $sellerPercentageAmazon1kg / 100);

//         $codCharge = 0;
//         if ($paymentType === 'COD') {
//             $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercentAmazon1kg / 100) : $codChargeFixedAmazon1kg;
//         }

//         $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//         $freightWithGST = $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);

//         return [
//             'courierCharge'  => round($freightWithGST, 2),
//             'freightCharges' => round($freightWithSeller, 2),
//             'codCharge'      => round($codCharge, 2),
//         ];
//     };

//     // 🧾 Step 8: Function for COD/GST calculation for Amazon 2kg
//     $applyChargesAmazon2kg = function ($freight_charges) use ($PriceSettingAmazon2kg, $sellerPercentageAmazon2kg, $codChargePercentAmazon2kg, $codChargeFixedAmazon2kg, $paymentType, $orderAmount, $order) {
//         // Check if fixed courier price is set
//         if ($PriceSettingAmazon2kg && isset($PriceSettingAmazon2kg->fixed_courier_price) && $PriceSettingAmazon2kg->fixed_courier_price > 0) {
//             // Calculate weight multiplier based on 2kg intervals
//             $weightInKg = $order->package_weight / 1000; // Convert grams to kg
//             $weightMultiplier = max(1, ceil($weightInKg / 2.0)); // Every 2kg interval, minimum 1
            
//             // Use fixed price with weight multiplier
//             $freightWithGST = $PriceSettingAmazon2kg->fixed_courier_price * $weightMultiplier;
//             $freightWithSeller = 10 * $weightMultiplier;
//             $codCharge = 10 * $weightMultiplier;
            
//             return [
//                 'courierCharge'  => round($freightWithGST, 2),
//                 'freightCharges' => round($freightWithSeller, 2),
//                 'codCharge'      => round($codCharge, 2),
//             ];
//         }
        
//         // Default calculation if no fixed price
//         $freight = $freight_charges * 1.10;
//         $freightWithSeller = $freight + ($freight * $sellerPercentageAmazon2kg / 100);

//         $codCharge = 0;
//         if ($paymentType === 'COD') {
//             $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercentAmazon2kg / 100) : $codChargeFixedAmazon2kg;
//         }

//         $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
//         $freightWithGST = $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);

//         return [
//             'courierCharge'  => round($freightWithGST, 2),
//             'freightCharges' => round($freightWithSeller, 2),
//             'codCharge'      => round($codCharge, 2),
//         ];
//     };

//     // 🧮 Step 9: Build Final Response
//     $results = [];

//     // Calculate charges for Deliveri 500gm - only if PriceSetting exists and status is 1
//     if ($PriceSettingDeliveri && $PriceSettingDeliveri->status == 1) {
//         $deliveriCharges = $applyChargesDeliveri($deliveriCharge);
//         $results[] = [
//             'serviceabilityId' => $pincodeToCheck,
//             'courierName'      => 'Deliveri 500gm',
//             'courierCharge'    => $deliveriCharges['courierCharge'],
//             'freightCharges'   => $deliveriCharges['freightCharges'],
//             'codCharge'        => $deliveriCharges['codCharge'],
//             'minWeight'        => $weight,
//             'volWeight'        => $weight,
//         ];
//     }

//     // Calculate charges for Amazon 500gm - only if PriceSetting exists and status is 1
//     if ($PriceSettingAmazon && $PriceSettingAmazon->status == 1) {
//         $amazonCharges = $applyChargesAmazon($amazonCharge);
//         $results[] = [
//             'serviceabilityId' => $pincodeToCheck,
//             'courierName'      => 'Amazon 500gm',
//             'courierCharge'    => $amazonCharges['courierCharge'],
//             'freightCharges'   => $amazonCharges['freightCharges'],
//             'codCharge'        => $amazonCharges['codCharge'],
//             'minWeight'        => $weight,
//             'volWeight'        => $weight,
//         ];
//     }

//     // Calculate charges for Amazon 1kg - only if PriceSetting exists and status is 1
//     if ($PriceSettingAmazon1kg && $PriceSettingAmazon1kg->status == 1) {
//         $amazon1kgCharges = $applyChargesAmazon1kg($amazon1kgCharge);
//         $results[] = [
//             'serviceabilityId' => $pincodeToCheck,
//             'courierName'      => 'Amazon 1kg',
//             'courierCharge'    => $amazon1kgCharges['courierCharge'],
//             'freightCharges'   => $amazon1kgCharges['freightCharges'],
//             'codCharge'        => $amazon1kgCharges['codCharge'],
//             'minWeight'        => $weight,
//             'volWeight'        => $weight,
//         ];
//     }

//     // Calculate charges for Amazon 2kg - only if PriceSetting exists and status is 1
//     if ($PriceSettingAmazon2kg && $PriceSettingAmazon2kg->status == 1) {
//         $amazon2kgCharges = $applyChargesAmazon2kg($amazon2kgCharge);
//         $results[] = [
//             'serviceabilityId' => $pincodeToCheck,
//             'courierName'      => 'Amazon 2kg',
//             'courierCharge'    => $amazon2kgCharges['courierCharge'],
//             'freightCharges'   => $amazon2kgCharges['freightCharges'],
//             'codCharge'        => $amazon2kgCharges['codCharge'],
//             'minWeight'        => $weight,
//             'volWeight'        => $weight,
//         ];
//     }

//     // dd($results);
//     return $results;
// }














public function createWarehouse($pickup, $sellerId)
{
    try {
        // Generate unique address title to avoid conflicts (no special characters)
        $baseName = preg_replace('/[^A-Za-z0-9]/', '', $pickup['warehouse_name'] ?? $pickup['name'] ?? 'Warehouse');
        $addressTitle = $baseName . $sellerId . time();
        
        // First check if warehouse already exists in database
        $existingWarehouse = DB::table('warehouses')
            ->where('seller_id', $sellerId)
            ->where('pincode', $pickup['pincode'])
            ->where('address_line1', $pickup['address'])
            ->where('parcelx_warehouse_id', '!=', null)
            ->first();
            // $existingWarehouse = DB::table('warehouses')
            // ->where('seller_id', $sellerId)
            // ->where('pincode', $pickup['pincode'])
            // ->where('address_line1', $pickup['address'])
            // ->whereNotNull('parcelx_warehouse_id')
            // ->first();


        if ($existingWarehouse) {
            // Return existing warehouse ID
            return [
                'status' => true,
                'warehouse_id' => $existingWarehouse->parcelx_warehouse_id,
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
//  dd($pickup);
        // Create warehouse in local database first
        $warehouseId = DB::table('warehouses')->insertGetId([
            'seller_id' => $sellerId,
            'name' => $pickup['warehouse_name'] ?? $pickup['name'],
            'phone' => $pickup['phone'],
            'pincode' => $pickup['pincode'],
            'city' => $pickup['city'] ?? '',
            'state' => $pickup['state'] ?? '',
            'country' => 'India',
            'address_line1' => $pickup['address'],
            'address_line2' => $pickup['address_2'] ?? '',
            'registered_name' => $pickup['name'],
            'address_title' => $addressTitle,
            'return_address' => $pickup['address'],
            'return_pin' => $pickup['pincode'],
            'return_city' => $pickup['city'] ?? '',
            'return_state' => $pickup['state'] ?? '',
            'return_country' => 'India',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Prepare ParcelX API payload
        $warehousePayload = [
            "address_title" => $addressTitle,
            "sender_name" => $pickup['name'],
            "full_address" => $pickup['address'] . (isset($pickup['address_2']) ? ', ' . $pickup['address_2'] : ''),
            "phone" => $pickup['phone'],
            "pincode" => $pickup['pincode']
        ];

        $url = "https://app.parcelx.in/api/v3/create_warehouse";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
        ])->post($url, $warehousePayload);

        $responseData = $response->json();
        // dd($responseData);
        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
            $parcelxWarehouseId = $responseData['data']['pick_address_id'] ?? null;
            
            if ($parcelxWarehouseId) {
                // Update local warehouse with ParcelX warehouse ID
                DB::table('warehouses')
                    ->where('id', $warehouseId)
                    ->update([
                        'parcelx_warehouse_id' => $parcelxWarehouseId,
                        'updated_at' => now()
                    ]);

                return [
                    'status' => true,
                    'warehouse_id' => $parcelxWarehouseId,
                    'local_warehouse_id' => $warehouseId,
                    'response' => $responseData
                ];
            }
        }

        // If ParcelX API failed, delete the local warehouse record
        DB::table('warehouses')->where('id', $warehouseId)->delete();

        return [
            'status' => false,
            'message' => 'Failed to create warehouse on ParcelX: ' . ($responseData['responsemsg'] ?? 'Unknown error'),
            'response' => $responseData
        ];

    } catch (\Exception $e) {

        // dd($e->getMessage());
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
    $provider_name = $params['provider_name'] ?? 'ParcelX';
    // dd($provider_name);
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
            // echo 'ssxssxs';die;

        return ['status' => false, 'message' => 'Insufficient wallet balance. Please recharge your wallet.'];
    }

    // Decode pickup & consignee first
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

    // Create warehouse using pickup address from order
    $warehouseResult = $this->createWarehouse($pickup, $seller->id);
    // dd($warehouseResult);
    //  dd($warehouseResult);
    if (!$warehouseResult['status']) {
        return ['status' => false, 'message' => 'Failed to create warehouse: ' . $warehouseResult['message']];
    }
    $warehouseId = $warehouseResult['warehouse_id'];
    // dd($warehouseId);
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // Determine express type based on provider name
    $express_type = "surface";
    if($provider_name == 'Delhivery 500gm'){
    $courier_code = "PXDEL01";
    }else if($provider_name == 'Delhivery 250gms'){
    $courier_code = "PXDEL01";
    }else{
    $courier_code = "PXA01";

    }

    // if (strpos($provider_name, 'Air') !== false) {
    //     $express_type = "air";
    // }

    // Prepare products array from order items
    $products = [];
    if (!empty($orderItems) && is_array($orderItems)) {
        foreach ($orderItems as $item) {
            $products[] = [
                "product_sku" => $item['sku'] ?? $order->order_number,
                "product_name" => $item['name'] ?? ($order->product_name ?? 'Product'),
                "product_value" => (string)($item['price'] ?? ($order->collectable_amount ?? 0)),
                "product_hsnsac" => $item['hsn'] ?? "",
                "product_taxper" => (int)($item['tax_rate'] ?? 0),
                "product_category" => $item['category'] ?? "General",
                "product_quantity" => (string)($item['quantity'] ?? 1),
                "product_description" => $item['description'] ?? ""
            ];
        }
    } else {
        // Default product if no items found
        $products[] = [
            "product_sku" => $order->order_number,
            "product_name" => $order->product_name ?? 'Product',
            "product_value" => (string)($order->collectable_amount ?? 0),
            "product_hsnsac" => "",
            "product_taxper" => 0,
            "product_category" => "General",
            "product_quantity" => "1",
            "product_description" => ""
        ];
    }

    if ($provider_name == 'Delhivery 250gms') {
    $shipment_weight = ["0.25"];
} else {
    $shipment_weight = ["0.5"];
}

    // Prepare ParcelX payload
    $parcelxPayload = [
        "client_order_id" => $order->order_number,
        "consignee_emailid" => $consignee['email'] ?? "",
        "consignee_pincode" => (string)$consignee['pincode'],
        "consignee_mobile" => $consignee['phone'],
        "consignee_phone" => $consignee['alternate_phone'] ?? "",
        "consignee_address1" => $consignee['address'],
        "consignee_address2" => $consignee['address_2'] ?? "",
        "consignee_name" => $consignee['name'],
        "invoice_number" => $order->order_number,
        "express_type" => $express_type,
        "pick_address_id" => $warehouseId, // Dynamic warehouse ID from createWarehouse
        "return_address_id" => $warehouseId,
        // "cod_amount" => $order->payment_type == 'cod' ? (string)$order->collectable_amount : "0",
        "cod_amount" => strtolower($order->payment_type) === 'cod'
        ? (string)$order->collectable_amount
        : "0",

        "tax_amount" => "0",
        "mps" => "0",
        "courier_type" => 1,
        "courier_code" => $courier_code,
        "products" => $products,
        "address_type" => "Home",
        // "payment_mode" => $order->payment_type == 'cod' ? 'Cod' : 'Prepaid',
        "payment_mode" => strtolower($order->payment_type) === 'cod' ? 'Cod' : 'Prepaid',

        "order_amount" => (string)($order->collectable_amount ?? 0),
        "extra_charges" => "0",
        // "shipment_width" => [(string)($order->package_breadth ?? 1)],
        // "shipment_height" => [(string)($order->package_height ?? 1)],
        // "shipment_length" => [(string)($order->package_length ?? 1)],
        // "shipment_weight" => [(string)($order->package_weight ?? 500)]
        // "shipment_weight" => [(string)(($order->package_weight ?? 500) / 1000)],



            // "shipment_weight" => ["0.5"],
                "shipment_weight" => $shipment_weight,

            "shipment_length" => ["10"],
            "shipment_height" => ["10"],
            "shipment_width" => ["10"],
    ];
//  dd($parcelxPayload);
    try {
        $url = "https://app.parcelx.in/api/v3/order/create_order";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl', // Replace with actual token
        ])->post($url, $parcelxPayload);

        $responseData = $response->json();
        //   dd($responseData);
        if (!$response->successful()) {
            return ['status' => false, 'message' => 'ParcelX Order creation failed', 'data' => $responseData];
        }

        // Check if order was created successfully - API returns 'status' not 'success'
        if (isset($responseData['status']) && $responseData['status'] === true) {
            $awb = $responseData['data']['awb_number'] ?? null;
            $orderNumber = $responseData['data']['order_number'] ?? null;
            $courierName = $responseData['data']['courier_name'] ?? 'ParcelX';
            $partnerName = $responseData['data']['partner_display_name'] ?? 'ParcelX';
            $routingCode = $responseData['data']['routing_code'] ?? '';
            $paymentMode = $responseData['data']['payment_mode'] ?? '';

            if ($awb) {
                $order->courier_id = 'parcelx';
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
                    'message' => 'Order successfully assigned to ' . $partnerName . ' via ParcelX.',
                    'couriername' => 'parcelx',
                    'partner_name' => $partnerName,
                    'awb_number' => $awb,
                    'order_number' => $orderNumber,
                    'routing_code' => $routingCode,
                    'payment_mode' => $paymentMode,
                    'tracking_status' => 'Created',
                    'response' => $responseData
                ];
            }
        }

        return ['status' => false, 'message' => 'ParcelX Order creation failed', 'data' => $responseData];

    } catch (\Exception $e) {
        return ['status' => false, 'message' => 'API Request Failed: ' . $e->getMessage()];
    }
}



public function cancelShipment($awb)
{
    try {
        $seller = Auth::guard('seller')->user();
        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found for AWB: ' . $awb,
                'responseCode' => 404
            ];
        }

        // ParcelX API URL for cancellation
        $url = "https://app.parcelx.in/api/v3/order/cancel_order";

        // Prepare ParcelX payload
        $payload = [
            "awb" => $awb
        ];

        // Send POST request to ParcelX
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
        ])->post($url, $payload);

        $responseData = $response->json();
    //   dd($responseData);
        // Check if cancellation succeeded - ParcelX uses 'status' not 'success'
        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
            // Update order status
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
                'message' => 'ParcelX shipment cancelled successfully.',
                'awb_number' => $awb,
                'response' => $responseData,
                'responseCode' => $response->status(),
            ];
        }

        // If cancellation failed
        return [
            'status' => false,
            'message' => $responseData['message'] ?? 'Failed to cancel shipment.',
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




























public function createWarehousebulk($pickup, $sellerId)
{
    try {
        // Generate unique address title to avoid conflicts (no special characters)
        $baseName = preg_replace('/[^A-Za-z0-9]/', '', $pickup['warehouse_name'] ?? $pickup['name'] ?? 'Warehouse');
        $addressTitle = $baseName . $sellerId . time();
        
        // First check if warehouse already exists in database
        $existingWarehouse = DB::table('warehouses')
            ->where('seller_id', $sellerId)
            ->where('pincode', $pickup['pincode'])
            ->where('address_line1', $pickup['address'])
            ->where('parcelx_warehouse_id', '!=', null)
            ->first();

        if ($existingWarehouse) {
            // Return existing warehouse ID
            return [
                'status' => true,
                'warehouse_id' => $existingWarehouse->parcelx_warehouse_id,
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

        // Create warehouse in local database first
        $warehouseId = DB::table('warehouses')->insertGetId([
            'seller_id' => $sellerId,
            'name' => $pickup['warehouse_name'] ?? $pickup['name'],
            'phone' => $pickup['phone'],
            'pincode' => $pickup['pincode'],
            'city' => $pickup['city'] ?? '',
            'state' => $pickup['state'] ?? '',
            'country' => 'India',
            'address_line1' => $pickup['address'],
            'address_line2' => $pickup['address_2'] ?? '',
            'registered_name' => $pickup['name'],
            'address_title' => $addressTitle,
            'return_address' => $pickup['address'],
            'return_pin' => $pickup['pincode'],
            'return_city' => $pickup['city'] ?? '',
            'return_state' => $pickup['state'] ?? '',
            'return_country' => 'India',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Prepare ParcelX API payload
        $warehousePayload = [
            "address_title" => $addressTitle,
            "sender_name" => $pickup['name'],
            "full_address" => $pickup['address'] . (isset($pickup['address_2']) ? ', ' . $pickup['address_2'] : ''),
            "phone" => $pickup['phone'],
            "pincode" => $pickup['pincode']
        ];

        $url = "https://app.parcelx.in/api/v3/create_warehouse";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
        ])->post($url, $warehousePayload);

        $responseData = $response->json();
        
        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
            $parcelxWarehouseId = $responseData['data']['pick_address_id'] ?? null;
            
            if ($parcelxWarehouseId) {
                // Update local warehouse with ParcelX warehouse ID
                DB::table('warehouses')
                    ->where('id', $warehouseId)
                    ->update([
                        'parcelx_warehouse_id' => $parcelxWarehouseId,
                        'updated_at' => now()
                    ]);

                return [
                    'status' => true,
                    'warehouse_id' => $parcelxWarehouseId,
                    'local_warehouse_id' => $warehouseId,
                    'response' => $responseData
                ];
            }
        }

        // If ParcelX API failed, delete the local warehouse record
        DB::table('warehouses')->where('id', $warehouseId)->delete();

        return [
            'status' => false,
            'message' => 'Failed to create warehouse on ParcelX: ' . ($responseData['responsemsg'] ?? 'Unknown error'),
            'response' => $responseData
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
    $provider_name = $params['provider_name'] ?? 'ParcelX';
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
        'couriername' => 'parcelx',
        'results' => $results,
        'success_count' => $successCount,
        'failure_count' => $failureCount
    ];
}

private function processSingleOrderAssignmentBulk($params)
{      
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'ParcelX';

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

    // Decode pickup & consignee first
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

    // Create warehouse using pickup address from order
    $warehouseResult = $this->createWarehousebulk($pickup, $seller->id);
    if (!$warehouseResult['status']) {
        return ['status' => false, 'message' => 'Failed to create warehouse: ' . $warehouseResult['message']];
    }
    $warehouseId = $warehouseResult['warehouse_id'];

    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // Determine express type based on provider name
    $express_type = "surface";
    if($provider_name == 'Delhivery 500gm'){
        $courier_code = "PXDEL01";
    } else {
        $courier_code = "PXA01";
    }

    // Prepare products array from order items
    $products = [];
    if (!empty($orderItems) && is_array($orderItems)) {
        foreach ($orderItems as $item) {
            $products[] = [
                "product_sku" => $item['sku'] ?? $order->order_number,
                "product_name" => $item['name'] ?? ($order->product_name ?? 'Product'),
                "product_value" => (string)($item['price'] ?? ($order->collectable_amount ?? 0)),
                "product_hsnsac" => $item['hsn'] ?? "",
                "product_taxper" => (int)($item['tax_rate'] ?? 0),
                "product_category" => $item['category'] ?? "General",
                "product_quantity" => (string)($item['quantity'] ?? 1),
                "product_description" => $item['description'] ?? ""
            ];
        }
    } else {
        // Default product if no items found
        $products[] = [
            "product_sku" => $order->order_number,
            "product_name" => $order->product_name ?? 'Product',
            "product_value" => (string)($order->collectable_amount ?? 0),
            "product_hsnsac" => "",
            "product_taxper" => 0,
            "product_category" => "General",
            "product_quantity" => "1",
            "product_description" => ""
        ];
    }

    // Prepare ParcelX payload
    $parcelxPayload = [
        "client_order_id" => $order->order_number,
        "consignee_emailid" => $consignee['email'] ?? "",
        "consignee_pincode" => (string)$consignee['pincode'],
        "consignee_mobile" => $consignee['phone'],
        "consignee_phone" => $consignee['alternate_phone'] ?? "",
        "consignee_address1" => $consignee['address'],
        "consignee_address2" => $consignee['address_2'] ?? "",
        "consignee_name" => $consignee['name'],
        "invoice_number" => $order->order_number,
        "express_type" => $express_type,
        "pick_address_id" => $warehouseId,
        "return_address_id" => $warehouseId,
        "cod_amount" => strtolower($order->payment_type) === 'cod'
            ? (string)$order->collectable_amount
            : "0",
        "tax_amount" => "0",
        "mps" => "0",
        "courier_type" => 1,
        "courier_code" => $courier_code,
        "products" => $products,
        "address_type" => "Home",
        "payment_mode" => strtolower($order->payment_type) === 'cod' ? 'Cod' : 'Prepaid',
        "order_amount" => (string)($order->collectable_amount ?? 0),
        "extra_charges" => "0",
        "shipment_weight" => ["0.5"],
        "shipment_length" => ["10"],
        "shipment_height" => ["10"],
        "shipment_width" => ["10"],
    ];

    try {
        $url = "https://app.parcelx.in/api/v3/order/create_order";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
        ])->post($url, $parcelxPayload);

        $responseData = $response->json();

        if (!$response->successful()) {
            return ['status' => false, 'message' => 'ParcelX Order creation failed', 'data' => $responseData];
        }

        // Check if order was created successfully - API returns 'status' not 'success'
        if (isset($responseData['status']) && $responseData['status'] === true) {
            $awb = $responseData['data']['awb_number'] ?? null;
            $orderNumber = $responseData['data']['order_number'] ?? null;
            $courierName = $responseData['data']['courier_name'] ?? 'ParcelX';
            $partnerName = $responseData['data']['partner_display_name'] ?? 'ParcelX';
            $routingCode = $responseData['data']['routing_code'] ?? '';
            $paymentMode = $responseData['data']['payment_mode'] ?? '';

            if ($awb) {
                $order->courier_id = 'parcelx';
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
                    'message' => 'Order successfully assigned to ' . $partnerName . ' via ParcelX.',
                    'couriername' => 'parcelx',
                    'partner_name' => $partnerName,
                    'awb_number' => $awb,
                    'order_number' => $orderNumber,
                    'routing_code' => $routingCode,
                    'payment_mode' => $paymentMode,
                    'tracking_status' => 'Created',
                    'response' => $responseData
                ];
            }
        }

        return ['status' => false, 'message' => 'ParcelX Order creation failed', 'data' => $responseData];

    } catch (\Exception $e) {
        return ['status' => false, 'message' => 'API Request Failed: ' . $e->getMessage()];
    }
}


}
