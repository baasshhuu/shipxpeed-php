<?php 

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helper\Helper;
use App\Models\PriceSetting;
use App\Models\Order;
use  Illuminate\Support\Facades\Auth;
use App\Models\RateCard;
use App\Services\DelhiveryB2CService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ZonePriceSetting;
use App\Models\ActicvSleb;

class RateCardController extends Controller
{



    public function checkRate(Request $request)
    {
     
        $validated = $request->validate([
            'origin' => 'required',  
            'destination' => 'required',
            'payment_type' => 'required',
            'order_amount' => 'required|numeric',
            'weight' => 'required|numeric',
            'length' => 'required|numeric',
            'breadth' => 'required|numeric',
            'height' => 'required|numeric',
        ]);
         
        $pickupstate = $request->originState;
        $destinationstate = $request->destinationState;
        $paymentType = strtolower($request->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
        $order_amount = $request->order_amount;
        $weight = $request->weight;
        $sellerId = Auth::guard('seller')->id();

        // Initialize the enhanced data array
        $enhancedData = [];

// Shadowfax Integration Start
    $shadowfaxZoneCheck = ActicvSleb::where([
        'seller_id' => $sellerId,
        'LogisticProvider' => 'Shadowfax',
        'status' => 1
    ])->exists();

    if ($shadowfaxZoneCheck) {
        $zoneData = DB::table('pincode_zones')
            ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
            ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
            ->first();

        if ($zoneData) {
            $zone = strtoupper($zoneData->zone);

            // Step 2: Weight & Payment Info
            $weight = (float)$weight; // grams
            $orderAmount = $order_amount;
            $paymentType = strtolower($paymentType) === 'cod' ? 'COD' : 'Pre-paid';
            $seller_id = $sellerId;

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
            if ($charges !== null) {
                $enhancedData[] = [
                'name' => 'Shadowfax',
                'freight_charges' => round($charges['freightCharges'], 2),
                'cod_charges' => round($charges['codCharge'], 2),
                'total_charges' => round($charges['courierCharge'], 2),
                'min_weight' => $weight,
                'chargeable_weight' => $weight,
            ];
            }
        }
    }
// Shadowfax Integration End




// parcel_x Integration start
    $parcelxServices = [
        'parcel_x_Delhivery', 'Parcel_X_Delhivery', 
        'parcel_x_Amazon', 'Parcel_X_Amazon',
        'parcel_x_Amazon_1kg', 'parcel_x_Amazon_1KG', 'Parcel_X_Amazon_1kg', 'Parcel_X_Amazon_1KG',
        'parcel_x_Amazon_2kg', 'parcel_x_Amazon_2KG', 'Parcel_X_Amazon_2kg', 'Parcel_X_Amazon_2KG'
    ];

    $activeParcelxServicesRaw = ActicvSleb::where([
        'seller_id' => $sellerId,
        'status' => 1
    ])->whereIn('LogisticProvider', $parcelxServices)
      ->pluck('LogisticProvider')
      ->toArray();
    
    // Convert to lowercase for case-insensitive comparison
    $activeParcelxServices = array_map('strtolower', $activeParcelxServicesRaw);

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
    $weight = (float)$weight; // grams
    $orderAmount = $order_amount;
    $paymentType = strtolower($request->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $sellerId;

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
        ['serviceabilityId' => $destinationstate, 'courierName' => 'delhivery 500gm', 'logisticProvider' => 'parcel_x_Delhivery', 'weightSlab' => 500],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Amazon 500gm', 'logisticProvider' => 'parcel_x_Amazon', 'weightSlab' => 500],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Amazon 1kg', 'logisticProvider' => 'parcel_x_Amazon_1kg', 'weightSlab' => 1000],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Amazon 2kg', 'logisticProvider' => 'parcel_x_Amazon_2kg', 'weightSlab' => 2000],
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

        // Only add if pricing exists for this service
        if ($charges !== null) {
            $enhancedData[] = [
                'name'      => $slab['courierName'],
                'total_charges'    => $charges['courierCharge'],
                'freight_charges'   => $charges['freightCharges'],
                'cod_charges'        => $charges['codCharge'],
                'min_weight'        => $weight,
                'chargeable_weight'        => $weight,
            ];
        }
    }
    // dd($enhancedData);
    // return $enhancedData;
// parcel_x Integration end

// tekipost Integration Start

        $tekipostServices = [
            'tekipost_Delhivery_5kg', 'Tekipost_Delhivery_5kg', 'Tekipost_Delhivery_5KG',
            'tekipost_Delhivery_10kg', 'Tekipost_Delhivery_10kg', 'Tekipost_Delhivery_10KG', 
            'tekipost_Delhivery_1_KG', 'Tekipost_Delhivery_1_KG', 'tekipost_Delhivery_1_kg',
            'tekipost_Ekart_2_KG_Fixed', 'Tekipost_Ekart_2_KG_Fixed', 'tekipost_Ekart_2_kg_Fixed',
            'tekipost_Amazon_2_kg', 'Tekipost_Amazon_2_kg', 'Tekipost_Amazon_2_KG',
            'tekipost_Amazon_500_GM', 'Tekipost_Amazon_500_GM', 'tekipost_Amazon_500_gm'
        ];
// dd($tekipostServices);       
        $activeTekipostServicesRaw = ActicvSleb::where([
            'seller_id' => $sellerId,
            'status' => 1
        ])->whereIn('LogisticProvider', $tekipostServices)
          ->pluck('LogisticProvider')
          ->toArray();
        // dd($activeTekipostServicesRaw);
        // Convert to lowercase for case-insensitive comparison
        $activeTekipostServices = array_map('strtolower', $activeTekipostServicesRaw);
        
        // Continue processing even if some services are inactive
        // We'll filter in the loop later


    // 🧾 Step 1: Zone Fetch (Case-Insensitive)
    $zoneData = DB::table('pincode_zones')
        ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
        ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
        ->first();
        
    if (!$zoneData) {
        return [];
    }

    $zone = strtoupper($zoneData->zone);


    $weight = (float)$weight; // grams
    $orderAmount = $order_amount;
    $paymentType = strtolower($request->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $sellerId;

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
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Delhivery 5 KG', 'logisticProvider' => 'tekipost_Delhivery_5kg'],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Delhivery 10 KG', 'logisticProvider' => 'tekipost_Delhivery_10kg'],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Delhivery 1 KG', 'logisticProvider' => 'tekipost_Delhivery_1_KG'],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Ekart 2 KG Fixed', 'logisticProvider' => 'tekipost_Ekart_2_KG_Fixed'],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Amazon 2 KG', 'logisticProvider' => 'tekipost_Amazon_2_kg'],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Amazon 500 GM', 'logisticProvider' => 'tekipost_Amazon_500_GM'],
    ];

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
            $enhancedData[] = [
                'name'      => $slab['courierName'],
                'total_charges'    => $charges['courierCharge'],
                'freight_charges'   => $charges['freightCharges'],
                'cod_charges'        => $charges['codCharge'],
                'min_weight'        => $weight,
                'chargeable_weight'        => $weight,
            ];
        }
    }
    //   dd($enhancedData);
    // return $enhancedData;
// tekipost Integration End

// DTDC Integration Start

        $dtdcServices = [
            'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
            'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg', 'DTDC_Surface_1KG',
            'DTDC_Air', 'dtdc_air', 'Dtdc_Air', 'DTDC_AIR'
        ];
        
        $activeDtdcServicesRaw = ActicvSleb::where([
            'seller_id' => $sellerId,
            'status' => 1
        ])->whereIn('LogisticProvider', $dtdcServices)
          ->pluck('LogisticProvider')
          ->toArray();
        
        // Convert to lowercase for case-insensitive comparison
        $activeDtdcServices = array_map('strtolower', $activeDtdcServicesRaw);
        


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


    $weight = (float)$weight; // grams
    $orderAmount = $order_amount;
    $paymentType = strtolower($request->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $sellerId;
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
        ['serviceabilityId' => $destinationstate, 'courierName' => 'DTDC Surface 500gm', 'logisticProvider' => 'DTDC_Surface_500gm'],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'DTDC Surface 1kg', 'logisticProvider' => 'DTDC_Surface_1kg'],
        ['serviceabilityId' => $destinationstate, 'courierName' => 'DTDC Air', 'logisticProvider' => 'DTDC_Air'],
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
            $enhancedData[] = [
                'name'      => $slab['courierName'],
                'total_charges'    => $charges['courierCharge'],
                'freight_charges'   => $charges['freightCharges'],
                'cod_charges'        => $charges['codCharge'],
                'min_weight'        => $weight,
                'chargeable_weight'        => $weight,
            ];
        }
    }
// dd($enhancedData);
    // return $enhancedData;

// DTDC Integration End


// shiprocket Integration Start

    $shiprocketServices = [
        'shiprocket_Delhivery', 'Shiprocket_Delhivery', 'shiprocket_delhivery',
        'shiprocket_Xpressbee', 'Shiprocket_Xpressbee', 'shiprocket_xpressbee',
        'shiprocket_Bluedart', 'Shiprocket_Bluedart', 'shiprocket_bluedart'
    ];
    
    $activeShiprocketServicesRaw = ActicvSleb::where('seller_id', $sellerId)
        ->whereIn('LogisticProvider', $shiprocketServices)
        ->where('status', 1)
        ->pluck('LogisticProvider')
        ->toArray();
    
    // Convert to lowercase for case-insensitive comparison
    $activeShiprocketServices = array_map('strtolower', $activeShiprocketServicesRaw);
    
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
   $weight = (float)$weight; // grams
    $orderAmount = $order_amount;
    $paymentType = strtolower($request->payment_type) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $sellerId;

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
    $shiprocketServicesList = ['shiprocket_Delhivery', 'shiprocket_Xpressbee', 'shiprocket_Bluedart'];
    $serviceNames = ['Delhivery 250gms', 'Xpressbee 250gms', 'Bluedart 2kg surface'];

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
            $enhancedData[] = [
                'name'      => $serviceNames[$index],
                'total_charges'    => $charges['courierCharge'],
                'freight_charges'   => $charges['freightCharges'],
                'cod_charges'        => $charges['codCharge'],
                'min_weight'        => $weight,
                'chargeable_weight'        => $weight,
            ];
        }
    }


    //  dd($enhancedData);
    // return $enhancedData;


// shiproclet Integration End

// selloship Integration Start

        $ekartZoneCheck = ActicvSleb::where([
            'seller_id' => $sellerId,
            'LogisticProvider' => 'Ekart2KG_selloship',
            'status' => 1
        ])->exists();

        if ($ekartZoneCheck) {
            $zoneData = DB::table('pincode_zones')
                ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
                ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
                ->first();

            if ($zoneData) {
                // ...existing selloship calculation code...
            }
        }
// selloship Services End



// DelhiveryB2CServices start

        $delhiveryZoneCheck = ActicvSleb::where([
            'seller_id' => $sellerId,
            'LogisticProvider' => 'Delhivery',
            'status' => 1
        ])->exists();

        if ($delhiveryZoneCheck) {
            $zoneData = DB::table('pincode_zones')
                ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
                ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
                ->first();

            if ($zoneData) {
                // ...existing Delhivery calculation code...
            }
        }
// DelhiveryB2CServices End

// DelhiveryB2C expres Services start

        $delhiveryAirZoneCheck = ActicvSleb::where([
            'seller_id' => $sellerId,
            'LogisticProvider' => 'Delhivery_Air',
            'status' => 1
        ])->exists();

        if ($delhiveryAirZoneCheck) {
            $zoneData = DB::table('pincode_zones')
                ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
                ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
                ->first();

            if ($zoneData) {
                // ...existing Delhivery Air calculation code...
            }
        }
// DelhiveryB2C expres Services End

        // Remove all dd($enhancedData); statements except the final one
        echo "DEBUG: Final Summary - Total carriers found: " . count($enhancedData) . "\n";
        echo "DEBUG: Working APIs: Shadowfax ✅, Delhivery ✅, Boxd Logistics ✅, Parcelx Services ✅\n";
        echo "DEBUG: Disabled APIs: Tekipost ❌ (server timeout)\n";
        foreach ($enhancedData as $carrier) {
            echo "DEBUG: Carrier: " . $carrier['name'] . " - ₹" . $carrier['total_charges'] . "\n";
        }

        return redirect()->back()->with([
            'rate_data' => $enhancedData,
            'success' => 'Rate fetched successfully!',
        ]);
    }












}





















