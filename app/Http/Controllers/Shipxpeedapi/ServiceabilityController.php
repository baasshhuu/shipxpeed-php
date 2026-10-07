<?php

namespace App\Http\Controllers\Shipxpeedapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\PriceSetting;
use App\Models\SellerList;
use App\Models\LogisticProvider;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\Warehouse;
use App\Models\ZonePriceSetting;
use App\Models\ActicvSleb;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ServiceabilityController extends Controller
{



    public function getAllServiceability(Request $request)
    {

        $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success'=>false,'message'=>'Authorization token required'], 401);
            }

            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success'=>false,'message'=>'Invalid or expired token'], 401);
            }
        $sellerid = $seller->id;


        // dd($request);
        // Validate request data
        $validator = Validator::make($request->all(), [
            'origin_pincode' => 'required|integer',
            'destination_pincode' => 'required|integer', 
            'payment_type' => 'required|in:cod,prepaid',
            'order_amount' => 'required|numeric',
            'package_weight' => 'required|numeric',
            'package_length' => 'nullable|numeric',
            'package_breadth' => 'nullable|numeric', 
            'package_height' => 'nullable|numeric',
            'pickup_state' => 'required',
            'destination_state' => 'required',

            // 'seller_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'responseCode' => 422
            ]);
        }

        // Prepare params for all provider calls
        $params = [
            'origin_pincode' => $request->origin_pincode,
            'destination_pincode' => $request->destination_pincode,
            'payment_type' => $request->payment_type,
            'order_amount' => $request->order_amount,
            'package_weight' => $request->package_weight,
            'package_length' => $request->package_length ?? 10,
            'package_breadth' => $request->package_breadth ?? 8,
            'package_height' => $request->package_height ?? 5,
            'pickup_state' => $request->pickup_state ?? 5,
            'destination_state' => $request->destination_state ?? 5,

            'seller_id' => $sellerid
        ];

        $allResults = [];

        // Get all active logistic providers
        $activeProviders = LogisticProvider::active()->get();

        // Call all provider methods and collect results
        try {
            foreach ($activeProviders as $provider) {
                try {
                    $providerResults = [];
                    
                    // Call specific provider service based on provider code/name
                    switch (strtolower($provider->code)) {
                        case 'boxd':
                            $providerResults = $this->callBoxdAPI($params);
                            break;
                            case 'shiprocket':
                            $providerResults = $this->callshiprocketAPI($params);
                            break;
                        case 'tekipost':
                            $providerResults = $this->callTekipostAPI($params);
                            break;
                         case 'parcelx':
                            $providerResults = $this->callParcelXAPI($params);
                            break;
                        case 'dtdc':
                            $providerResults = $this->callDtdcAPI($params);
                            break;
                        case 'delhivery':
                        case 'delhivery_surface':
                        case 'delhivery_b2c':
                            $providerResults = $this->callDelhiveryAPI($params);
                            break;
                        case 'delhivery_air':
                        case 'delhivery_express':
                        case 'delhivery_b2c_express':
                            $providerResults = $this->callDelhiveryAirAPI($params);
                            break;
                        case 'selloship':
                            $providerResults = $this->callSelloshipAPI($params);
                            break;
                        case 'shadowfax':
                            $providerResults = $this->callShadowfaxAPI($params);
                            break;
                        default:
                            continue 2; // Skip unknown providers
                    }

                    // Add provider info to each service result
                    foreach ($providerResults as $service) {
                        $allResults[] = array_merge($service, [
                            // 'courierId' => $provider->id,
                            // 'courierImage' => $provider->logo,
                            // 'courierType' => 1,
                            // 'courier_cat_id' => 1,
                            // 'courier_partner_id' => $provider->id,
                            // 'provider_logo' => $provider->logo,

                            'provider_name' => 'ShipXpeed',
                            // 'is_document_required' => false,
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('Provider service error: ' . $provider->code, [
                        'error' => $e->getMessage(),
                        'params' => $params
                    ]);
                    continue;
                }
            }

        } catch (\Exception $e) {
            \Log::error('Serviceability API Error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
        }

        return response()->json([
            'data' => $allResults,
            'success' => true,
            'message' => 'Successfully fetched serviceability from all providers!',
            'responseCode' => 200,
            'total_providers' => count($allResults)
        ]);
    }

    /**
     * Get Tekipost token for API authentication
     */

    
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
 
    /**
     * Call Boxd API (modified to work without order_id)
     */
    private function callBoxdAPI(array $params): array
    {    
        try {
            $url = 'https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_rate_time';

            $originPin = $params['origin_pincode'];
            $destinationPin = $params['destination_pincode'];
            $weight = $params['package_weight'];
            $payment = $params['payment_type'];
            $paymentMode = ($payment === 'cod') ? 'cod' : 'prepaid';
            $orderAmount = $params['order_amount'];
            $seller_id = $params['seller_id'];

            $requestPayload = [
                'from_postal_code' => $originPin,
                'from_country_code' => 'IN',
                'to_postal_code' => $destinationPin,
                'to_country_code' => 'IN',
                'weight' => $weight / 1000, // Convert grams to kilograms
                'length' => $params['package_length'],
                'height' => $params['package_height'],
                'width' => $params['package_breadth'],
                'parcel_type' => 'Parcel',
                'mode' => 'Domestic',
                'payment_mode' => 'prepaid'
            ];
            // dd($requestPayload);
 
            $response = Http::withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'application/json',
                'secretkey' => 'POVHFT',
                'customerid' => 'c1754533690129',
            ])->post($url, $requestPayload);
            
            // dd($response->json());
            if (!$response->successful()) {
                Log::error('Boxd API Error', ['response' => $response->body()]);
                return [];
            }

            $responseData = $response->json();
            // dd($responseData);
            if (!$responseData['status'] || empty($responseData['rate_list'])) {
                Log::error('Boxd API returned no rates', ['response' => $responseData]);
                return [];
            }

            // Filter to only include specific services
            $allowedProductIds = [
                "1750498184844", // BDS - Bluedart Air
                "1746709645240", // Dship - Bluedart Surface 0.5 kg
                "1753163038641",
            ];

            $filteredRates = collect($responseData['rate_list'])->filter(function ($rate) use ($allowedProductIds) {
                return in_array($rate['product_id'], $allowedProductIds);
            });
            // dd($filteredRates);
            if ($filteredRates->isEmpty()) {
                return [];
            }
            // echo 'cscscs';die;

            // Transform Boxd response to normalized format
            $rates = $filteredRates->map(function ($rate) use (
                $destinationPin,
                $orderAmount,
                $paymentMode,
                $seller_id,
                $weight
            ) {
                // Get specific pricing for each product ID
                $logisticProvider = 'Boxd_' . $rate['product_id'];
                $PriceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => $logisticProvider])->first();

                // Use product-specific pricing or fallback to default Boxd pricing
                if (!$PriceSetting) {
                    $PriceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'Boxd'])->first();
                }

                $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 70;
                $codChargePercent = $PriceSetting ? $PriceSetting->cod_charge_parsent : 1.9;
                $codChargeFixed = $PriceSetting ? $PriceSetting->cod_charge : 32;

                $codCharge = 0;
                if ($paymentMode === 'cod') {
                    $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercent / 100) : $codChargeFixed;
                }

                if ($PriceSetting && isset($PriceSetting->fixed_courier_price) && $PriceSetting->fixed_courier_price > 0) {
                    // Calculate weight multiplier based on 500g intervals
                    $weightInKg = $weight / 1000; // Convert grams to kg
                    $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
                    
                    $finalCharge = $PriceSetting->fixed_courier_price * $weightMultiplier;
                    $chargeWithSeller = 10 * $weightMultiplier;
                    $codCharge = 10 * $weightMultiplier;
                } else {
                    $baseCharge = $rate['total_charges'] * 1.10;
                    $chargeWithSeller = $baseCharge + ($baseCharge * $sellerPercentage / 100);
                    $chargeWithCod = $chargeWithSeller + $codCharge;
                    $finalCharge = $chargeWithCod + ($chargeWithCod * 18 / 100); // GST
                }
               
                return [
                    'serviceabilityId' => $destinationPin,
                    'courierName' => $rate['service_provider'],
                    'courierCharge' => round($finalCharge, 2),
                    'minWeight' => $weight,
                    'volWeight' => $weight,
                ];
            });

            // Add Ekart 2kg slab with base price of 76
            // $ekartPriceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'boxd_Ekart_2kg'])->first();
            
            // if ($ekartPriceSetting && $ekartPriceSetting->status == '1') {
            //     $ekartSellerPercentage = $ekartPriceSetting ? $ekartPriceSetting->shipping_charge : 70;
            //     $ekartCodChargePercent = $ekartPriceSetting ? $ekartPriceSetting->cod_charge_parsent : 1.9;
            //     $ekartCodChargeFixed = $ekartPriceSetting ? $ekartPriceSetting->cod_charge : 32;

            //     $ekartCodCharge = 0;
            //     if ($paymentMode === 'cod') {
            //         $ekartCodCharge = $orderAmount > 1400 ? ($orderAmount * $ekartCodChargePercent / 100) : $ekartCodChargeFixed;
            //     }

            //     if ($ekartPriceSetting && isset($ekartPriceSetting->fixed_courier_price) && $ekartPriceSetting->fixed_courier_price > 0) {
            //         $weightInKg = $weight / 1000;
            //         $weightMultiplier = max(1, ceil($weightInKg / 0.5));
                    
            //         $ekartFinalCharge = $ekartPriceSetting->fixed_courier_price * $weightMultiplier;
            //         $ekartChargeWithSeller = 10 * $weightMultiplier;
            //         $ekartCodCharge = 10 * $weightMultiplier;
            //     } else {
            //         $ekartBaseCharge = 76 * 1.10; // Ekart base price with 10% markup
            //         $ekartChargeWithSeller = $ekartBaseCharge + ($ekartBaseCharge * $ekartSellerPercentage / 100);
            //         $ekartChargeWithCod = $ekartChargeWithSeller + $ekartCodCharge;
            //         $ekartFinalCharge = $ekartChargeWithCod + ($ekartChargeWithCod * 18 / 100); // GST
            //     }

            //     $ekartRate = [
            //         'serviceabilityId' => $destinationPin,
            //         'courierName' => 'Ekart 2kg',
            //         'courierCharge' => round($ekartFinalCharge, 2),
            //         'minWeight' => $weight,
            //         'volWeight' => $weight,
            //     ];

            //     $rates->push($ekartRate);
            // }

            return $rates->toArray();

        } catch (\Exception $e) {
            Log::error('Boxd API Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get Serviceability for Shiprocket services
     */


public function callshiprocketAPI(array $params): array
{
    // dd($params);
    $weight = $params['package_weight'];
    $paymentType = $params['payment_type'];
    $orderAmount = $params['order_amount'];
    $seller_id = $params['seller_id'];
    $destinationstate = $params['destination_state'];
    $pickupstate = $params['pickup_state'];

    // Get only the active Shiprocket services for this seller
    // Include all possible case variations that might exist in database
    $shiprocketServices = [
        'shiprocket_Delhivery', 'Shiprocket_Delhivery', 'shiprocket_delhivery',
        'shiprocket_Xpressbee', 'Shiprocket_Xpressbee', 'shiprocket_xpressbee',
        'shiprocket_Bluedart', 'Shiprocket_Bluedart', 'shiprocket_bluedart'
    ];
    
    $activeShiprocketServicesRaw = ActicvSleb::where('seller_id', $seller_id)
        ->whereIn('LogisticProvider', $shiprocketServices)
        ->where('status', 1)
        ->pluck('LogisticProvider')
        ->toArray();
    
    // Convert to lowercase for case-insensitive comparison
    $activeShiprocketServices = array_map('strtolower', $activeShiprocketServicesRaw);
    
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
    // dd($zone);
    // Step 2: Weight & Payment Info
    $weight = (float)$weight; // grams
    $orderAmount = $orderAmount;
    $paymentType = strtolower($paymentType) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $seller_id;

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
    $serviceNames = ['Delhivery_Shiprocket 250gms', 'Xpressbee 250gms', 'Bluedart 2kg surface'];

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
                'serviceabilityId' => $destinationstate,
                'courierName'      => $serviceNames[$index],
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


    /**
     * Call Tekipost API (modified to work without order_id)
     */



public function callTekipostAPI(array $params): array
{
    // dd($params);
    $weight = $params['package_weight'];
    $paymentType = $params['payment_type'];
    $orderAmount = $params['order_amount'];
    $seller_id = $params['seller_id'];
    $destinationstate = $params['destination_state'];
    $pickupstate = $params['pickup_state'];

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
            'seller_id' => $seller_id,
            'status' => 1
        ])->whereIn('LogisticProvider', $tekipostServices)
          ->pluck('LogisticProvider')
          ->toArray();
        
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

    // Step 2: Weight & Payment Info
    $weight = (float)$weight; // grams
    $orderAmount = $orderAmount;
    $paymentType = strtolower($paymentType) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $seller_id;

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




    // private function callTekipostAPI(array $params): array
    // {
    //     try {
    //         $token = $this->getToken();
    //         if (!$token) {
    //             return [];
    //         }

    //         $originPin = $params['origin_pincode'];
    //         $destinationPin = $params['destination_pincode'];
    //         $weight = $params['package_weight'];
    //         $payment = $params['payment_type'];
    //         $orderAmount = $params['order_amount'];
    //         $seller_id = $params['seller_id'];

    //         // Prepare payload for Tekipost API
    //         $payload = [
    //             "paymentMode"      => $payment === 'cod' ? 0 : 1,
    //             "pickupPinCode"    => (int)$originPin,
    //             "deliveryPinCode"  => (int)$destinationPin,
    //             "apprWeight"       => (float)($weight / 1000),
    //             "b2c_length"       => (float)$params['package_length'],
    //             "b2c_breadth"      => (float)$params['package_breadth'],
    //             "b2c_height"       => (float)$params['package_height'],
    //             "total_Weight"     => (float)($weight / 1000),
    //             "declaredValue"    => (float)$orderAmount,
    //             "box_shipment"     => [
    //                 [
    //                     "no_of_box"       => 1,
    //                     "length"          => $params['package_length'],
    //                     "breadth"         => $params['package_breadth'],
    //                     "height"          => $params['package_height'],
    //                     "each_box_weight" => ($weight / 1000),
    //                 ]
    //             ]
    //         ];

    //         // Call Tekipost API
    //         $response = Http::withHeaders([
    //             'Authorization' => "Bearer {$token}",
    //             'Accept'        => 'application/json',
    //             'Content-Type'  => 'application/json',
    //         ])->post('https://app.tekipost.com/api-calculate-price', $payload);

    //         if (!$response->successful()) {
    //             return [];
    //         }

    //         $data = $response->json();
    //         if (!isset($data['success']) || !$data['success'] || empty($data['data']['rates']['list'])) {
    //             return [];
    //         }

    //         $skipLogistics = ['Blue Dart_0.5 KG', 'Delhivery_1 KG'];

    //         $rates = collect($data['data']['rates']['list'])
    //             ->reject(function ($service) use ($skipLogistics) {
    //                 return in_array($service['logistic'] ?? '', $skipLogistics);
    //             })
    //             ->values()
    //             ->toArray();

    //         return collect($rates)->map(function ($service) use ($orderAmount, $payment, $seller_id, $weight, $destinationPin) {

    //             // Get logistic name from API response
    //             $logisticName = $service['logistic'] ?? 'Tekipost';

    //             // Dynamically fetch seller-specific price setting for the logistic
    //             $PriceSetting = PriceSetting::where([
    //                 'seller_id'        => $seller_id,
    //                 'LogisticProvider' => $logisticName
    //             ])->first();

    //             $sellerPercentage = $PriceSetting->shipping_charge ?? 70;
    //             $codChargePercent = $PriceSetting->cod_charge_parsent ?? 1.9;
    //             $codChargeFixed   = $PriceSetting->cod_charge ?? 32;

    //             // Base freight
    //             $baseCost = $service['addittional_charges']['freight_charge'] ?? 0;
    //             $freight = $baseCost * 1.10; // Add 10% markup

    //             // Calculate freight + seller margin + COD + GST
    //             if ($PriceSetting && isset($PriceSetting->fixed_courier_price) && $PriceSetting->fixed_courier_price > 0) {
    //                 // Calculate weight multiplier based on 500g intervals
    //                 $weightInKg = $weight / 1000; // Convert grams to kg
    //                 $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
                    
    //                 // Use fixed price with weight multiplier
    //                 $freightWithGST = $PriceSetting->fixed_courier_price * $weightMultiplier;
    //                 $freightWithSeller = 10 * $weightMultiplier;
    //                 $codCharge = 10 * $weightMultiplier;
    //             } else {
    //                 $codCharge = 0;
    //                 if ($payment === 'cod') {
    //                     $codCharge = $orderAmount > 1400
    //                         ? ($orderAmount * $codChargePercent / 100)
    //                         : $codChargeFixed;
    //                 }
    //                 $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
    //                 $freightWithCod    = $freightWithSeller + $codCharge;
    //                 $freightWithGST    = $freightWithCod * 1.18;
    //             }

    //             return [
    //                 'serviceabilityId' => $destinationPin,
    //                 'courierName'      => $logisticName,
    //                 'courierCharge'    => round($freightWithGST, 2),
    //                 'minWeight'        => $weight,
    //                 'volWeight'        => $weight,
    //                 // 'provider'         => 'ShipXpeed',
    //                 'estimatedDays'    => $service['estimated_days'] ?? 'N/A',
    //             ];
    //         })->values()->toArray();

    //     } catch (\Exception $e) {
    //         Log::error('Tekipost API Exception', ['error' => $e->getMessage()]);
    //         return [];
    //     }
    // }





    /**
     * Get ParcelX Serviceability using ZonePriceSetting
     */
   
public function callParcelXAPI(array $params): array
{

    $weight = $params['package_weight'];
    $paymentType = $params['payment_type'];
    $orderAmount = $params['order_amount'];
    $seller_id = $params['seller_id'];
    $destinationstate = $params['destination_state'];
    $pickupstate = $params['pickup_state'];

    // Get only the active ParcelX services for this seller
    // Include all possible case variations that might exist in database
    $parcelxServices = [
        'parcel_x_Delhivery', 'Parcel_X_Delhivery', 
        'parcel_x_Amazon', 'Parcel_X_Amazon',
        'parcel_x_Amazon_1kg', 'parcel_x_Amazon_1KG', 'Parcel_X_Amazon_1kg', 'Parcel_X_Amazon_1KG',
        'parcel_x_Amazon_2kg', 'parcel_x_Amazon_2KG', 'Parcel_X_Amazon_2kg', 'Parcel_X_Amazon_2KG'
    ];
    
    $activeParcelxServicesRaw = ActicvSleb::where([
        'seller_id' => $seller_id,
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
    $orderAmount = $orderAmount;
    $paymentType = strtolower($paymentType) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $seller_id;

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
        ['serviceabilityId' => $destinationstate, 'courierName' => 'Deliveri 500gm', 'logisticProvider' => 'parcel_x_Delhivery', 'weightSlab' => 500],
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





    

    /**
     * Call DTDC API (modified to work without order_id)  
     */


    
public function callDtdcAPI(array $params): array
{
    // dd($params);
    $weight = $params['package_weight'];
    $paymentType = $params['payment_type'];
    $orderAmount = $params['order_amount'];
    $seller_id = $params['seller_id'];
    $destinationstate = $params['destination_state'];
    $pickupstate = $params['pickup_state'];

        // Get only the active DTDC services for this seller
        // Include all possible case variations that might exist in database
        $dtdcServices = [
            'DTDC_Surface_500gm', 'dtdc_surface_500gm', 'Dtdc_Surface_500gm',
            'DTDC_Surface_1kg', 'dtdc_surface_1kg', 'Dtdc_Surface_1kg', 'DTDC_Surface_1KG',
            'DTDC_Air', 'dtdc_air', 'Dtdc_Air', 'DTDC_AIR'
        ];
        
        $activeDtdcServicesRaw = ActicvSleb::where([
            'seller_id' => $seller_id,
            'status' => 1
        ])->whereIn('LogisticProvider', $dtdcServices)
          ->pluck('LogisticProvider')
          ->toArray();
        
        // Convert to lowercase for case-insensitive comparison
        $activeDtdcServices = array_map('strtolower', $activeDtdcServicesRaw);
        
        // Continue processing even if some services are inactive
        // We'll filter in the loop later
//  echo 'sxsx';die;
    // Pickup Info


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
    $weight = (float)$params['package_weight']; // grams
    $orderAmount = $params['order_amount'];
    $paymentType = strtolower($params['payment_type']) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $params['seller_id'];
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
                // Use variable COD price + 18% GST + COD charge if applicable
               

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


    // private function callDtdcAPI(array $params): array
    // {
    //     try {
    //         $originPin = $params['origin_pincode'];
    //         $destinationPin = $params['destination_pincode'];
    //         $weight = $params['package_weight'];
    //         $payment = $params['payment_type'];
    //         $orderAmount = $params['order_amount'];
    //         $seller_id = $params['seller_id'];

    //         // TODO: Implement pincode to state mapping
    //         $pickupstate = 'Delhi'; // Get from pincode lookup
    //         $destinationstate = 'Maharashtra'; // Get from pincode lookup

    //         // Zone Fetch (Case-Insensitive)
    //         $zoneData = DB::table('pincode_zones')
    //             ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
    //             ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
    //             ->first();

    //         if (!$zoneData) {
    //             return [];
    //         }

    //         $zone = strtoupper($zoneData->zone);

    //         // Fixed Zone Pricing (3 Slabs)
    //         $zonePricesSurface500 = ['A' => 22, 'B' => 25, 'C' => 28, 'D' => 32, 'E' => 36];
    //         $zonePricesSurface1kg = ['A' => 44, 'B' => 50, 'C' => 56, 'D' => 64, 'E' => 72];
    //         $zonePricesAir = ['A' => 42, 'B' => 43, 'C' => 87, 'D' => 90, 'E' => 100];

    //         $paymentType = $payment === 'cod' ? 'COD' : 'Pre-paid';

    //         $PriceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'DTDC'])->first();
    //         $sellerPercentage = $PriceSetting->shipping_charge ?? 30;
    //         $codChargePercent = $PriceSetting->cod_charge_parsent ?? 1.9;
    //         $codChargeFixed = $PriceSetting->cod_charge ?? 32;

    //         // Price Calculation Function
    //         $calculateCharge = function ($baseRate, $maxWeightGrams) use ($weight) {
    //             if ($weight <= $maxWeightGrams) {
    //                 return $baseRate;
    //             }
    //             $multiplier = ceil($weight / $maxWeightGrams);
    //             return $baseRate * $multiplier;
    //         };

    //         // Freight Charges for Each Slab
    //         $surface500Charge = $calculateCharge($zonePricesSurface500[$zone] ?? 0, 500);
    //         $surface1kgCharge  = $calculateCharge($zonePricesSurface1kg[$zone] ?? 0, 1000);
    //         $airCharge         = $calculateCharge($zonePricesAir[$zone] ?? 0, 500);

    //         // Common Function for COD/GST
    //         $applyCharges = function ($freight_charges) use ($sellerPercentage, $codChargePercent, $codChargeFixed, $paymentType, $orderAmount) {
    //             $freight = $freight_charges * 1.10;
    //             $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);

    //             $codCharge = 0;
    //             if ($paymentType === 'COD') {
    //                 $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercent / 100) : $codChargeFixed;
    //             }

    //             $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
    //             $freightWithGST = $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);

    //             return [
    //                 'courierCharge'  => round($freightWithGST, 2),
    //                 'freightCharges' => round($freightWithSeller, 2),
    //                 'codCharge'      => round($codCharge, 2),
    //             ];
    //         };

    //         // Build Final Response
    //         $slabs = [
    //             ['serviceabilityId' => $destinationPin, 'courierName' => 'DTDC Surface 500gm', 'charge' => $surface500Charge],
    //             ['serviceabilityId' => $destinationPin, 'courierName' => 'DTDC Surface 1kg',   'charge' => $surface1kgCharge],
    //             ['serviceabilityId' => $destinationPin, 'courierName' => 'DTDC Air',           'charge' => $airCharge],
    //         ];

    //         $results = [];
    //         foreach ($slabs as $slab) {
    //             $charges = $applyCharges($slab['charge']);
    //             $results[] = [
    //                 'serviceabilityId' => $slab['serviceabilityId'],
    //                 'courierName'      => $slab['courierName'],
    //                 'courierCharge'    => $charges['courierCharge'],
    //                 'zone'             => $zone,
    //                 'minWeight'        => $weight,
    //                 'volWeight'        => $weight,
    //                 'provider'         => 'DTDC',
    //             ];
    //         }

    //         return $results;

    //     } catch (\Exception $e) {
    //         Log::error('DTDC API Exception', ['error' => $e->getMessage()]);
    //         return [];
    //     }
    // }

    /**
     * Call Delhivery Surface API (modified to work without order_id)
     */


    public function callDelhiveryAPI(array $params): array
    {
        // dd($params);
    $weight = $params['package_weight'];
    $paymentType = $params['payment_type'];
    $orderAmount = $params['order_amount'];
    $seller_id = $params['seller_id'];
    $destinationstate = $params['destination_state'];
    $pickupstate = $params['pickup_state'];

        // Check if Delhivery zone pricing is configured for this seller
        $delhiveryZoneCheck = ActicvSleb::where([
            'seller_id' => $seller_id,
            'LogisticProvider' => 'Delhivery',
            'status' => 1
        ])->exists();
// dd($delhiveryZoneCheck);
        if (!$delhiveryZoneCheck) {
            return [];
        }


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
        $weight = (float)$params['package_weight']; // grams
        $orderAmount = $params['order_amount'];
        $paymentType = strtolower($params['payment_type']) === 'cod' ? 'COD' : 'Pre-paid';
        $seller_id = $params['seller_id'];

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
            'serviceabilityId' => $destinationstate,
            'courierName'      => 'Delhivery',
            'courierCharge'    => $charges['courierCharge'],
            'freightCharges'   => $charges['freightCharges'],
            'codCharge'        => $charges['codCharge'],
            'zone'             => $zone,
            'minWeight'        => $weight,
            'volWeight'        => $weight,
        ]];
    }


    // private function callDelhiveryAPI(array $params): array
    // {
    //     try {
    //         $token = "a6cd5bb955fddcb41757ec23ee92cf62b6650607";
    //         $url = 'https://track.delhivery.com/c/api/pin-codes/json/';

    //         $originPin = $params['origin_pincode'];
    //         $destinationPin = $params['destination_pincode'];
    //         $weight = $params['package_weight'];
    //         $payment = $params['payment_type'];
    //         $orderAmount = $params['order_amount'];
    //         $seller_id = $params['seller_id'];

    //         // Pincode Serviceability Check
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Token ' . $token,
    //             'Content-Type' => 'application/json',
    //         ])->get($url, [
    //             'filter_codes' => $destinationPin,
    //         ]);

    //         if (!$response->successful()) {
    //             Log::error('Delhivery B2C API Error', ['response' => $response->body()]);
    //             return [];
    //         }

    //         $json = $response->json();
    //         $codes = $json['delivery_codes'][0] ?? [];
    //         if (empty($codes)) {
    //             return [];
    //         }

    //         $paymentType = ($payment === 'cod') ? 'COD' : 'Pre-paid';

    //         $PriceSetting = PriceSetting::where(['seller_id' => $seller_id, 'LogisticProvider' => 'Delhivery'])->first();
    //         $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 30;
    //         $codChargePercent = $PriceSetting ? $PriceSetting->cod_charge_parsent : 1.9;
    //         $codChargeFixed = $PriceSetting ? $PriceSetting->cod_charge : 32;

    //         $rates = collect(['S' => 'Surface'])->flatMap(function ($modeName, $modeCode) use (
    //             $token,
    //             $originPin,
    //             $destinationPin,
    //             $weight,
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

    //             if ($PriceSetting && isset($PriceSetting->fixed_courier_price) && $PriceSetting->fixed_courier_price > 0) {
    //                 // Calculate weight multiplier based on 500g intervals
    //                 $weightInKg = $weight / 1000; // Convert grams to kg
    //                 $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
                    
    //                 // Use fixed price with weight multiplier
    //                 $freightWithGST = $PriceSetting->fixed_courier_price * $weightMultiplier;
    //                 $freightWithSeller = 10 * $weightMultiplier;
    //                 $codCharge = 10 * $weightMultiplier;
    //             } else {
    //                 $freight_charges = $data[0]['charge_DL'] ?? 0.0;
    //                 $freight = $freight_charges * 1.10;
    //                 $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
    //                 $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
    //                 $freightWithGST =  $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);
    //             }

    //             return [[
    //                 'serviceabilityId' => $destinationPin,
    //                 'courierName'      => 'Delhivery B2C (' . $modeName . ')',
    //                 'courierCharge'    => round($freightWithGST, 2),
    //                 'minWeight'        => $data[0]['charged_weight'],
    //                 'volWeight'        => $data[0]['charged_weight'],
    //                 'provider'         => 'Delhivery',
    //             ]];
    //         });

    //         return $rates->toArray();

    //     } catch (\Exception $e) {
    //         Log::error('Delhivery API Exception', ['error' => $e->getMessage()]);
    //         return [];
    //     }
    // }

    /**
     * Call Delhivery Air API (modified to work without order_id)
     */
   
   
    public function callDelhiveryAirAPI(array $params): array
    {
        // dd($params);
     $weight = $params['package_weight'];
    $paymentType = $params['payment_type'];
    $orderAmount = $params['order_amount'];
    $seller_id = $params['seller_id'];
    $destinationstate = $params['destination_state'];
    $pickupstate = $params['pickup_state'];

        // Check if Delhivery Air zone pricing is configured for this seller
        $delhiveryAirZoneCheck = ActicvSleb::where([
            'seller_id' => $seller_id,
            'LogisticProvider' => 'Delhivery_Air',
            'status' => 1
        ])->exists();

        if (!$delhiveryAirZoneCheck) {
            return [];
        }

        // Pickup Info


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
        $weight = (float)$params['package_weight']; // grams
        $orderAmount = $params['order_amount'];
        $paymentType = strtolower($params['payment_type']) === 'cod' ? 'COD' : 'Pre-paid';
        $seller_id = $params['seller_id'];

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
            'serviceabilityId' => $destinationstate,
            'courierName'      => 'Delhivery B2C (Express)',
            'courierCharge'    => $charges['courierCharge'],
            'freightCharges'   => $charges['freightCharges'],
            'codCharge'        => $charges['codCharge'],
            'zone'             => $zone,
            'minWeight'        => $weight,
            'volWeight'        => $weight,
        ]];
    }

   
   
    // private function callDelhiveryAirAPI(array $params): array
    // { 
    //     // echo 'ssxs';
    //     try {
    //         $token = "a6cd5bb955fddcb41757ec23ee92cf62b6650607";
    //         $url = 'https://track.delhivery.com/c/api/pin-codes/json/';

    //         $originPin = $params['origin_pincode'];
    //         $destinationPin = $params['destination_pincode'];
    //         $weight = $params['package_weight'];
    //         $payment = $params['payment_type'];
    //         $orderAmount = $params['order_amount'];
    //         $seller_id = $params['seller_id'];

    //         // Pincode Serviceability Check
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Token ' . $token,
    //             'Content-Type' => 'application/json',
    //         ])->get($url, [
    //             'filter_codes' => $destinationPin,
    //         ]);

    //         if (!$response->successful()) {
    //             Log::error('Delhivery B2C API Error', ['response' => $response->body()]);
    //             return [];
    //         }

    //         $json = $response->json();
    //         $codes = $json['delivery_codes'][0] ?? [];
    //         if (empty($codes)) {
    //             return [];
    //         }

    //         $paymentType = ($payment === 'cod') ? 'COD' : 'Pre-paid';

    //         $PriceSetting = PriceSetting::where(['seller_id' => $seller_id,'LogisticProvider'=> 'Delhivery Air'])->first();
    //         $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 30;
    //         $codChargePercent = $PriceSetting ? $PriceSetting->cod_charge_parsent : 1.9;
    //         $codChargeFixed = $PriceSetting ? $PriceSetting->cod_charge : 32;

    //         $rates = collect(['E' => 'Express'])->flatMap(function ($modeName, $modeCode) use (
    //             $token, $originPin, $destinationPin, $weight, $orderAmount, $paymentType, $sellerPercentage, $codChargePercent, $codChargeFixed, $PriceSetting
    //         ) {

    //             $resp = Http::withHeaders([
    //                 'Authorization' => 'Token ' . $token,
    //             ])->get("https://track.delhivery.com/api/kinko/v1/invoice/charges/.json?md={$modeCode}&ss=Delivered&d_pin={$destinationPin}&o_pin={$originPin}&cgm={$weight}&pt={$paymentType}");
    //         //    dd($resp->body());
    //             if (!$resp->successful()) {
    //                 Log::error("Delhivery Rate API Error for $modeName", ['response' => $resp->body()]);
    //                 return [];
    //             }

    //             $data = $resp->json();
    //             // dd($data);
    //             if (empty($data[0]['total_amount'])) return [];

    //             $codCharge = 0;
    //             if ($paymentType === 'COD') {
    //                 $codCharge = $orderAmount > 1400 ? ($orderAmount * $codChargePercent / 100) : $codChargeFixed;
    //             }

    //             if ($PriceSetting && isset($PriceSetting->fixed_courier_price) && $PriceSetting->fixed_courier_price > 0) {
    //                 // Calculate weight multiplier based on 500g intervals
    //                 $weightInKg = $weight / 1000; // Convert grams to kg
    //                 $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
                    
    //                 // Use fixed price with weight multiplier
    //                 $freightWithGST = $PriceSetting->fixed_courier_price * $weightMultiplier;
    //                 $freightWithSeller = 10 * $weightMultiplier;
    //                 $codCharge = 10 * $weightMultiplier;
    //             } else {
    //                 $freight_charges = $data[0]['charge_DL'] ?? 0.0;
    //                 $freight = $freight_charges * 1.10;
    //                 $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
    //                 $freightWithSellerCodCharge = $freightWithSeller + $codCharge;
    //                 $freightWithGST =  $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);
    //             }
                
    //             return [[
    //                 'serviceabilityId' => $destinationPin,
    //                 'courierName'      => 'Delhivery B2C (' . $modeName . ')',
    //                 'courierCharge'    => round($freightWithGST, 2),
    //                 'minWeight'        => $data[0]['charged_weight'],
    //                 'volWeight'        => $data[0]['charged_weight'],
    //                 'provider'         => 'Delhivery Air',
    //             ]];
    //         });

    //         return $rates->toArray();

    //     } catch (\Exception $e) {
    //         Log::error('Delhivery Air API Exception', ['error' => $e->getMessage()]);
    //         return [];
    //     }
    // }






    public function createShipment(Request $request)
    {
        try {
            // Authentication
            $token = $request->header('Authorization');
            // dd($token);
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Authorization token required'], 401);
            }

            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
            }

            if (!$seller || $seller->status != 1) {
                return response()->json(['success' => false, 'message' => 'Unauthorized or inactive seller'], 401);
            }
            $sellerid = $seller->id;

            // Validate request data
            $validator = Validator::make($request->all(), [
                'consignee.name' => 'required|string|max:255',
                'consignee.phone' => 'required|string|max:15',
                'consignee.email' => 'nullable|email|max:255',
                'consignee.address' => 'required|string|max:500',
                'consignee.address_2' => 'nullable|string|max:500',
                'consignee.pincode' => 'required|string|max:6',
                'consignee.city' => 'required|string|max:100',
                'consignee.state' => 'required|string|max:100',
                'order_number' => 'nullable|string|max:255',
                'payment_type' => 'required|in:cod,prepaid',
                'order_items' => 'required|array|min:1',
                'order_items.*.name' => 'required|string|max:255',
                'order_items.*.sku' => 'required|string|max:100',
                'order_items.*.qty' => 'required|integer|min:1',
                'order_items.*.price' => 'required|numeric|min:0',
                'collectable_amount' => 'required|numeric|min:0',
                'pickup.warehouse_name' => 'required|string|max:255',
                'pickup.name' => 'required|string|max:255',
                'pickup.address' => 'required|string|max:500',
                'pickup.address_2' => 'nullable|string|max:500',
                'pickup.pincode' => 'required|string|max:6',
                'pickup.city' => 'required|string|max:100',
                'pickup.state' => 'required|string|max:100',
                'pickup.phone' => 'required|string|max:15',
                'package_weight' => 'required|numeric|min:1',
                'package_length' => 'required|numeric|min:1',
                'package_breadth' => 'required|numeric|min:1',
                'package_height' => 'required|numeric|min:1',
                'courier_id' => 'nullable|string|in:delhivery_250gms,amazon_2kg,dtdc_air,dtdc_surface_500gm,delhivery_5kg,delhivery,bluedart_air_500gms,bluedart_surface_500gms,dtdc_surface_1kg,amazon_0_5kg,delhivery_air,delhivery_10kg,Delhivery_500gm,Amazon_500gm,xpressbee_250gms,Delhivery_ 250gms,bluedart_2kg_surface,Amazon_2kg,Amazon_1kg,sell_Ekart_2KG,shadowfax',
                'rto.warehouse_name' => 'nullable|string|max:255',
                'rto.name' => 'nullable|string|max:255',
                'rto.address' => 'nullable|string|max:500',
                'rto.pincode' => 'nullable|string|max:6',
                'rto.city' => 'nullable|string|max:100',
                'rto.state' => 'nullable|string|max:100',
                'rto.phone' => 'nullable|string|max:15',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'responseCode' => 422
                ], 422);
            }

            // Generate order number if not provided
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $orderNumber = 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1);
            
            // Calculate total order amount
            $totalAmount = 0;
            foreach ($request->order_items as $item) {
                $totalAmount += $item['price'] * $item['qty'];
            }

            // Get serviceability from all providers first
            $serviceabilityParams = [
                'origin_pincode' => $request->pickup['pincode'],
                'destination_pincode' => $request->consignee['pincode'],
                'payment_type' => $request->payment_type,
                'order_amount' => $request->collectable_amount,
                'package_weight' => $request->package_weight,
                'package_length' => $request->package_length,
                'package_breadth' => $request->package_breadth,
                'package_height' => $request->package_height,
                'seller_id' => $sellerid,
                'pickup_state' => $request->pickup['state'],
                'destination_state' => $request->consignee['state'],
            ];

            $allResults = [];
            $activeProviders = LogisticProvider::active()->get();

            // Call all provider methods and collect results
            try {
                foreach ($activeProviders as $provider) {
                    try {
                        $providerResults = [];
                        
                        switch (strtolower($provider->code)) {
                            case 'boxd':
                                $providerResults = $this->callBoxdAPI($serviceabilityParams);
                                // dd($providerResults);
                                break;
                            case 'tekipost':
                                $providerResults = $this->callTekipostAPI($serviceabilityParams);

                                break;

                              case 'parcelx':
                                $providerResults = $this->callParcelXAPI($serviceabilityParams);
                                break;

                                    case 'selloship':
                                $providerResults = $this->callSelloshipAPI($serviceabilityParams);
                                break;


                            case 'dtdc':
                                $providerResults = $this->callDtdcAPI($serviceabilityParams);
                                break;


                           case 'shadowfax':
                                $providerResults = $this->callShadowfaxAPI($serviceabilityParams);
                                break;

                            case 'delhivery':
                            case 'delhivery_surface':
                            case 'delhivery_b2c':
                                $providerResults = $this->callDelhiveryAPI($serviceabilityParams);

                                break;
                            case 'delhivery_air':
                            case 'delhivery_express':
                            case 'delhivery_b2c_express':
                                $providerResults = $this->callDelhiveryAirAPI($serviceabilityParams);
                                                    //    dd($providerResults);

                                break;
                            case 'shiprocket':
                                $providerResults = $this->callshiprocketAPI($serviceabilityParams);
                                break;
                            default:
                                continue;
                        }

                        foreach ($providerResults as $service) {
                            $allResults[] = array_merge($service, [
                                'provider_code' => $provider->code,
                                'provider_name' => $provider->name,
                                'is_active' => true,
                            ]);
                        }
                        // dd($allResults);
                        
                    } catch (\Exception $e) {
                        Log::error('Provider service error: ' . $provider->code, [
                            'error' => $e->getMessage(),
                            'params' => $serviceabilityParams
                        ]);
                        continue;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Serviceability API Error', [
                    'error' => $e->getMessage(),
                    'params' => $serviceabilityParams
                ]);
            }

            // Create order
            $order = new Order();
            
            // Basic order information
            $order->seller_id = $sellerid;
            $order->order_number = $orderNumber;
            $order->payment_type = $request->payment_type;
            $order->order_amount = $totalAmount;
            $order->collectable_amount = $request->collectable_amount;
            
            // Package details
            $order->package_weight = $request->package_weight;
            $order->package_length = $request->package_length;
            $order->package_breadth = $request->package_breadth;
            $order->package_height = $request->package_height;
            
            // Store JSON data
            $order->consignee = json_encode($request->consignee);
            $order->pickup = json_encode($request->pickup);
            $order->rto = json_encode($request->rto ?? $request->pickup);
            $order->order_items = json_encode($request->order_items);
            
            // Optional fields
            $order->courier_id = $request->courier_id ?? null;
            $order->shipping_charges = $request->shipping_charges ?? 0;
            $order->cod_charges = $request->cod_charges ?? 0;
            $order->discount = $request->discount ?? 0;
            
            // Store serviceability results
            // $order->serviceability_results = json_encode($allResults);
            
            // Generate API payloads for different providers
            $order->delhivery_b2c = json_encode($this->delhivery_b2c($request, $sellerid));
            $order->delhivery_b2c_air = json_encode($this->delhivery_b2c_air($request, $sellerid));
            
            $order->save();

            // If courier_id is provided, assign the order immediately using integrated logic
            $assignmentResult = null;
            $courierAssigned = false;
            
            if ($request->courier_id) {
                // Enhanced courier routing logic based on courier_id mappings
                $courierMapping = [
                    'delhivery_250gms' => 'Delhivery 250gms',
                    'amazon_2kg' => 'Amazon_2 KG',
                    'dtdc_air' => 'DTDC Air',
                    'dtdc_surface_500gm' => 'DTDC Surface 500gm',
                    'delhivery_5kg' => 'Delhivery_5kg',
                    'delhivery' => 'Delhivery B2C (Surface)',
                    'bluedart_air_500gms' => 'Bluedart Air 500gms',
                    'bluedart_surface_500gms' => 'Bluedart Surface 500gms',
                    'dtdc_surface_1kg' => 'DTDC Surface 1kg',
                    'amazon_0_5kg' => 'Amazon_0.5 KG',
                    'delhivery_air' => 'Delhivery Air',
                    'delhivery_10kg' => 'Delhivery_10kg',
                    'shadowfax' => 'Shadowfax',

                    'Amazon_500gm' => 'Amazon 500gm',
                    'Delhivery_500gm' => 'Delhivery 500gm',
                    'xpressbee_250gms' => 'Xpressbee 250gms',
                    'Delhivery250gms' => 'Delhivery_ 250gms',
                    'bluedart_2kg_surface' => 'Bluedart 2kg surface',
                    'Amazon_1kg' => 'Amazon 1kg',
                    'Amazon_2kg' => 'Amazon 2kg',
                    'sell_Ekart_2KG' => 'sell_Ekart_2KG'



                ];

                if (isset($courierMapping[$request->courier_id])) {
                    $providerName = $courierMapping[$request->courier_id];
                    
                    // Find courier charge from serviceability results based on courier_id mapping
                    $courierCharge = 0;
                    $courierNameMapping = [
                        'delhivery_250gms' => ['Delhivery 250gms', 'Delhivery_0.5kg', 'Delhivery 250gms'],
                        'bluedart_air_500gms' => ['Bluedart Air 500gms', 'BDS - Bluedart Air', 'Bluedart Air'],
                        'bluedart_surface_500gms' => ['Bluedart Surface 500gms', 'Bluedart 500 grams new', 'BlueDart Surface'],
                        'amazon_2kg' => ['Amazon_2 KG', 'Amazon 2kg', 'Amazon_2kg'],
                        'amazon_0_5kg' => ['Amazon_0.5 KG', 'Amazon 0.5kg', 'Amazon_0.5kg'],
                        'dtdc_air' => ['DTDC Air'],
                        'dtdc_surface_500gm' => ['DTDC Surface 500gm'],
                        'dtdc_surface_1kg' => ['DTDC Surface 1kg'],
                        'delhivery' => ['Delhivery B2C (Surface)', 'Delhivery B2C (Surface)'],
                        'delhivery_5kg' => ['Delhivery_5kg', 'Delhivery 5kg'],
                        'delhivery_10kg' => ['Delhivery_10kg', 'Delhivery 10kg'],
                        'delhivery_air' => ['Delhivery B2C (Express)', 'Delhivery Air', 'Delhivery Express'],
                        'shadowfax' => ['Shadowfax'],
                        'Delhivery_500gm' => ['Amazon 500gm'],
                        'Amazon_500gm' => ['Delhivery 500gm'],
                        'xpressbee_250gms' => ['Xpressbee 250gms'],
                        'Delhivery250gms' => ['Delhivery_ 250gms'],
                        'bluedart_2kg_surface' => ['Bluedart 2kg surface'],
                        'Amazon_1kg' => ['Amazon 1kg'],
                        'Amazon_2kg' => ['Amazon 2kg'],
                        'sell_Ekart_2KG' => ['sell_Ekart_2KG']


                    ];

                    $searchNames = $courierNameMapping[$request->courier_id] ?? [$providerName];
                    //  dd($allResults);
                    foreach ($allResults as $result) {
                        if (isset($result['courierName'])) {
                            foreach ($searchNames as $searchName) {
                                if (stripos($result['courierName'], $searchName) !== false || $result['courierName'] == $searchName) {
                                    $courierCharge = $result['courierCharge'];
                                    break 2; // Break both loops
                                }
                            }
                        }
                    }
                    
                    // Save courier charge to seller_amount_walate column
                    $finalCharge = $courierCharge > 0 ? $courierCharge : 85; // Fallback to 85 if not found
                    $order->seller_amount_walate = $finalCharge;
                    $order->save();
                    
                    Log::info('Courier charge saved for order', [
                        'order_id' => $order->id,
                        'courier_id' => $request->courier_id,
                        'provider_name' => $providerName,
                        'courier_charge' => $finalCharge,
                        'found_in_results' => $courierCharge > 0
                    ]);
                    
                    try {
                        // Enhanced courier routing logic based on courier_id patterns
                        if (in_array($request->courier_id, ['delhivery_250gms', 'bluedart_air_500gms', 'bluedart_surface_500gms'])) {
                            // Route to BoxD for delhivery_250gms and bluedart variants
                            $assignmentResult = $this->assignBoxdOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                            
                        } elseif (in_array($request->courier_id, ['amazon_2kg', 'amazon_0_5kg','delhivery_5kg','delhivery_10kg'])) {
                            // Route to Tekipost for amazon variants
                            $assignmentResult = $this->assignTekipostOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                            
                        }elseif (in_array($request->courier_id, ['sell_Ekart_2KG'])) {
                            // Route to Selloship for selloship variants
                            $assignmentResult = $this->assignSelloshipOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                            
                        } elseif (in_array($request->courier_id, ['Delhivery_500gm', 'Amazon_500gm','Amazon_1kg','Amazon_2kg'])) {
                            // Route to Tekipost for amazon variants
                            $assignmentResult = $this->assignparcelxOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                            
                        }elseif (in_array($request->courier_id, ['dtdc_air', 'dtdc_surface_500gm', 'dtdc_surface_1kg'])) {
                            // Route to DTDC for dtdc variants
                            $assignmentResult = $this->assignDTDCOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                            
                        } elseif (in_array($request->courier_id, ['delhivery'])) {
                            // Route to Delhivery Surface for regular delhivery variants
                            $orderPayload = $this->getDelhiveryPayload($order->id);
                            // dd($orderPayload);
                            $assignmentResult = $this->assignDelhiveryOrder($orderPayload, $sellerid);
                            
                        } elseif ($request->courier_id === 'delhivery_air') {
                            // Route to Delhivery Air for delhivery_air
                            $orderPayload = $this->getDelhiveryPayload($order->id);
                            // For air mode, we need to use air-specific payload
                            $consignee = json_decode($order->consignee, true);
                            $pickup = json_decode($order->pickup, true);
                            $orderItems = json_decode($order->order_items, true);

                            $requestData = [
                                'consignee' => $consignee,
                                'pickup' => $pickup,
                                'order_items' => $orderItems,
                                'payment_type' => $order->payment_type,
                                'collectable_amount' => $order->collectable_amount,
                                'package_weight' => $order->package_weight,
                                'package_length' => $order->package_length,
                                'package_breadth' => $order->package_breadth,
                                'package_height' => $order->package_height,
                                'unique_order_number' => $order->order_number
                            ];

                            $airRequest = new \Illuminate\Http\Request();
                            $airRequest->replace($requestData);
                            $orderPayload = $this->delhivery_b2c_air($airRequest, $order->seller_id);
                            
                            $assignmentResult = $this->assignDelhiveryOrder($orderPayload, $sellerid);
                        } elseif (in_array($request->courier_id, ['xpressbee_250gms', 'Delhivery250gms', 'bluedart_2kg_surface'])) {
                            // Route to Shiprocket for shiprocket variants
                            $assignmentResult = $this->assignShiprocketOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                        } elseif ($request->courier_id === 'shadowfax') {
                            // Route to Shadowfax for shadowfax
                            $assignmentResult = $this->assignshadowfaxOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                        } elseif ($request->courier_id === 'selloshipEkart2KG') {
                            // Route to Selloship for selloship variants
                            $assignmentResult = $this->assignSelloshipOrder([
                                'order_id' => $order->id,
                                'provider_name' => $providerName
                            ], $sellerid);
                        }

                        if ($assignmentResult && ($assignmentResult['status'] ?? false)) {
                            $courierAssigned = true;
                            // Update order with assignment details
                            $order->courier_id = $request->courier_id;
                            $order->all_courier_name = $providerName;
                            $order->shipping_status = 'assigned';
                            $order->shipping_date = now();
                            $order->save();
                        }

                    } catch (\Exception $e) {
                        Log::error('Integrated Order Assignment Error', [
                            'courier_id' => $request->courier_id,
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        $assignmentResult = [
                            'status' => false,
                            'message' => 'Assignment failed: ' . $e->getMessage()
                        ];
                    }
                } else {
                    $assignmentResult = [
                        'status' => false,
                        'message' => 'Invalid courier ID: ' . $request->courier_id
                    ];
                }
            }

            $responseData = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'seller_id' => $order->seller_id,
                'payment_type' => $order->payment_type,
                'order_amount' => $order->order_amount,
                'collectable_amount' => $order->collectable_amount,
                'package_weight' => $order->package_weight,
                'created_at' => $order->created_at->toDateTimeString(),
                'serviceability_results' => $allResults,
                'total_providers' => count($allResults),
                'courier_assigned' => $courierAssigned
            ];

            if ($assignmentResult) {
                $responseData['assignment_result'] = $assignmentResult;
                $responseData['courier_id'] = $request->courier_id ?? null;
                $responseData['courier_name'] = $courierMapping[$request->courier_id] ?? null;
            }

            $successMessage = 'Shipment created successfully';
            if ($courierAssigned) {
                $successMessage .= ' and assigned to ' . ($courierMapping[$request->courier_id] ?? $request->courier_id);
            } elseif ($request->courier_id && !$courierAssigned) {
                $successMessage .= ' but courier assignment failed';
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => $responseData,
                'responseCode' => 200
            ], 200);

        } catch (\Exception $e) {
            Log::error('Create Shipment API Error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
                'responseCode' => 500
            ], 500);
        }
    }



    /**
     * Get Delhivery payload for order
     */
    private function getDelhiveryPayload($orderId)
    {
        // dd($orderId);
        $order = Order::find($orderId);
        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Decode JSON data from order
        $consignee = json_decode($order->consignee, true);
        $pickup = json_decode($order->pickup, true);
        $orderItems = json_decode($order->order_items, true);

        // Create a request-like object from stored order data
        $requestData = [
            'consignee' => $consignee,
            'pickup' => $pickup,
            'order_items' => $orderItems,
            'payment_type' => $order->payment_type,
            'collectable_amount' => $order->collectable_amount,
            'package_weight' => $order->package_weight,
            'package_length' => $order->package_length,
            'package_breadth' => $order->package_breadth,
            'package_height' => $order->package_height,
            'unique_order_number' => $order->order_number
        ];

        // Create a proper request object
        $request = new \Illuminate\Http\Request();
        $request->replace($requestData);
        
        // Return the payload based on courier type (default to surface)
        return $this->delhivery_b2c($request, $order->seller_id);
    }

    protected function delhivery_b2c(Request $request, $sellerid = null)
    {
        // echo 'cscsc';die;
    //    dd($sellerid);
        $seller = Auth::guard('seller')->user();
        // dd($seller);
        $sellerid = $sellerid ?? $seller->id;
        // dd($sellerid);
        $gst = $seller->gst_no ?? "NA";
        
        // Use the actual order number from request instead of generating new one
        $orderNumber = $request['unique_order_number'] ?? 'SPX#' . time();

        $payload = [
            "shipments" => [
                [
                    "name" => $request['consignee']['name'],
                    "add" => $request['consignee']['address'] . (!empty($request['consignee']['address_2']) ? ', ' . $request['consignee']['address_2'] : ''),

                    "pin" => (int) $request['consignee']['pincode'],

                    "city" => $request['consignee']['city'],
                    "state" => $request['consignee']['state'],
                    "country" => "India",
                    "phone" => $request['consignee']['phone'],
                    "order" => $orderNumber,
                    "payment_mode" => $request['payment_type'], // Prepaid or COD
                    "cod_amount" => (string) $request['collectable_amount'], // If COD
                    "total_amount" => (string) $request['collectable_amount'],
                    "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
                    "hsn_code" => "6403", // You can pass per product if required
                    "quantity" => array_sum(array_column($request['order_items'], 'qty')),
                    "seller_gst_tin" => $gst, // Static or from your config
                    "seller_add" => $request['pickup']['address'],
                    "seller_name" => $request['pickup']['name'],
                    "seller_inv" => "yes", // or your invoice number
                    // "shipment_width" => $request['package_breadth'],
                    // "shipment_height" => $request['package_height'],
                    // "weight" => $request['package_weight'],
                                  "shipment_width"  => (string) $request['package_breadth'],
                    "shipment_height" => (string) $request['package_height'],
                    "weight"          => (string) $request['package_weight'],
                    "shipping_mode" => "Surface",
                    "address_type" => "home",
                    "waybill" => "",
                    "order_date" => now()->format('Y-m-d'),
                ]
            ],
            "pickup_location" => [
                "name" => $request['pickup']['warehouse_name'], // Must match registered warehouse name exactly
                "add" => $request['pickup']['address'],
                "city" => $request['pickup']['city'],
                "pin_code" => (int) $request['pickup']['pincode'],
                "country" => "India",
                "phone" => $request['pickup']['phone']
            ]
        ];
        //  dd($payload);

        return $payload;
    }





    protected function delhivery_b2c_air(Request $request, $sellerid = null)
    {

        $seller = Auth::guard('seller')->user();
        $sellerid = $sellerid ?? $seller->id;
        $gst = $seller->gst_no ?? "NA";
        
        // Use the actual order number from request instead of generating new one
        $orderNumber = $request['unique_order_number'] ?? 'SPX#' . time();

        $payload = [
            "shipments" => [
                [
                    "name" => $request['consignee']['name'],
                    // "add" => $request['consignee']['address'],
                    "add" => $request['consignee']['address'] . (!empty($request['consignee']['address_2']) ? ', ' . $request['consignee']['address_2'] : ''),

                    // "pin" => $request['consignee']['pincode'],
                    "pin" => (int) $request['consignee']['pincode'],

                    "city" => $request['consignee']['city'],
                    "state" => $request['consignee']['state'],
                    "country" => "India",
                    "phone" => $request['consignee']['phone'],
                    "order" => $orderNumber,
                    "payment_mode" => $request['payment_type'], // Prepaid or COD
                    "cod_amount" => (string) $request['collectable_amount'], // If COD
                    "total_amount" => (string) $request['collectable_amount'],
                    "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
                    "hsn_code" => "6403", // You can pass per product if required
                    "quantity" => array_sum(array_column($request['order_items'], 'qty')),
                    "seller_gst_tin" => $gst, // Static or from your config
                    "seller_add" => $request['pickup']['address'],
                    "seller_name" => $request['pickup']['name'],
                    "seller_inv" => "yes", // or your invoice number
                    // "shipment_width" => $request['package_breadth'],
                    // "shipment_height" => $request['package_height'],
                    // "weight" => $request['package_weight'],
                    "shipment_width"  => (string) $request['package_breadth'],
                    "shipment_height" => (string) $request['package_height'],
                    "weight"          => (string) $request['package_weight'],

                    "shipping_mode" => "Express",
                    "address_type" => "home",
                    "waybill" => "",
                    "order_date" => now()->format('Y-m-d'),
                ]
            ],
            "pickup_location" => [
                "name" => $request['pickup']['warehouse_name'], // Must match registered warehouse name exactly
                "add" => $request['pickup']['address'],
                "city" => $request['pickup']['city'],
                "pin_code" => (int) $request['pickup']['pincode'],
                "country" => "India",
                "phone" => $request['pickup']['phone']
            ]
        ];


        return $payload;
    }







    public function assignDelhiveryOrder($orderPayload, $sellerid = null)
    {
        //  dd($orderPayload, $sellerid);

        $orderPayload = json_decode(json_encode($orderPayload), true);
        // dd($orderPayload);
        $pickup = $orderPayload['pickup_location'];
        //  dd($orderPayload);
        $seller = Auth::guard('seller')->user();
        $sellerid = $sellerid ?? $seller->id;
        $order_number = $orderPayload['shipments'][0]['order'] ?? null;
        
        // Validate order number exists
        if (!$order_number) {
            return [
                'status' => false,
                'message' => 'Order number not found in payload'
            ];
        }
        
        $order = Order::where(['order_number' => $order_number, 'seller_id' => $sellerid])->first();
        
        // Validate order exists
        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found with number: ' . $order_number . ' for seller: ' . $sellerid
            ];
        }
        
        $seller_amount_walate = $order->seller_amount_walate ?? 0;
        $seller = SellerList::find($sellerid);
      

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



            $sellerRechargeAmount = Recharge::where('seller_id', $sellerid)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $sellerid)
                ->where('type', 'Debit')
                ->sum('amount');

            $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;

            if (
                $seller->negative_balance != 1 &&
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
            //    dd($responseData->json_decode());
            if (!$orderResponse->successful() || ($responseData['success'] ?? false) === false) {
                return [
                    'status' => false,
                    'message' => 'Order creation failed: ' . ($responseData['rmk'] ?? 'Unknown error')
                ];
            }

            // Extract waybill (AWB number) from successful response
            $waybill = null;
            if (isset($responseData['packages'][0]['waybill'])) {
                $waybill = $responseData['packages'][0]['waybill'];
            }

            // Update order with Delhivery details
            $order->courier_id = 'delhivery';
            $order->all_courier_name = 'delhivery_b2c';
            $order->awb_number = $waybill; // Save waybill to awb_number column
            $order->shipping_date = Carbon::now()->format('Y-m-d');
            $order->save();

            // Create wallet debit entry
            Recharge::create([
                'seller_id'   => $sellerid,
                'type'        => 'Debit',
                'amount'      => $seller_amount_walate,
                'status'      => 1,
                'description' => 'Order created'
            ]);

            // Return formatted success response
            return [
                'status' => true,
                'message' => 'Delhivery order created successfully',
                'awb_number' => $waybill,
                // 'waybill' => $waybill,
                'couriername' => 'Delhivery B2C',
                // 'upload_wbn' => $responseData['upload_wbn'] ?? null,
                // 'sort_code' => $responseData['packages'][0]['sort_code'] ?? null,
                // 'payment_mode' => $responseData['packages'][0]['payment'] ?? null,
                // 'full_response' => $responseData
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'API Request Failed: ' . $e->getMessage()
            ];
        }
    }








public function registerHubboxd(array $hubDetails, $sellerid = null)
{
    // Authenticated Seller ID
    $seller = Auth::guard('seller')->user();
    $sellerId = $sellerid ?? $seller->id;
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
        "full_name" => $hubDetails['hub_name'] ?? 'TEST VENDOR',
        "pincode"   => $hubDetails['pincode'] ?? '400001',
        "address"   => $hubDetails['address1'] ?? 'mumbai',
        "email"     => $hubDetails['email'] ?? 'mumbai@gmail.com',
        "mobile"    => $hubDetails['hub_phone'] ?? '9887875670'
    ];

    $response = Http::withHeaders([
        'Access-Control-Allow-Origin' => '*',
        'Content-Type'                => 'application/json',
        'secretkey'                   => 'POVHFT',
        'customerid'                  => 'c1754533690129',
    ])->post($url, $payload);

    if (!$response->successful()) {
        Log::error('Boxd Warehouse API Error', ['response' => $response->body()]);
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


    Log::error('Boxd Warehouse API - No address_id returned', ['response' => $responseData]);

    // Fallback address_id if API failed or no address_id in response
    return 'RANDOM-FALLBACK-ID-001';
}

    /**
     * Call Selloship API (modified to work without order_id)
     */

    
public function callSelloshipAPI(array $params): array
{
    // dd($params);
     $weight = $params['package_weight'];
    $paymentType = $params['payment_type'];
    $orderAmount = $params['order_amount'];
    $seller_id = $params['seller_id'];
    $destinationstate = $params['destination_state'];
    $pickupstate = $params['pickup_state'];

        // Check if Ekart 2KG zone pricing exists for this seller
        // If no data exists, skip this service
        $ekartZoneCheck = ActicvSleb::where([
            'seller_id' => $seller_id,
            'LogisticProvider' => 'Ekart2KG_selloship',
            'status' => 1
        ])->exists();

        if (!$ekartZoneCheck) {
            return [];
        }


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
    $weight = (float)$params['package_weight']; // grams
    $orderAmount = $params['order_amount'];
    $paymentType = strtolower($params['payment_type']) === 'cod' ? 'COD' : 'Pre-paid';
    $seller_id = $params['seller_id'];
    // dd($paymentType);
// dd($order->payment_type);
    // 🧾 Step 3: Get Ekart 2KG Zone Pricing
    $ekartZonePricing = ZonePriceSetting::where([
        'seller_id' => $seller_id,
        'zone' => $zone,
        'LogisticProvider' => 'Ekart2KG_selloship',
        'status' => 1
    ])->first();
// dd($ekartZonePricing);
    if (!$ekartZonePricing) {
        // return [];
    $ekartZonePricing = ZonePriceSetting::where([
    'seller_id' => 14,
    'zone' => $zone,
    'LogisticProvider' => 'Ekart2KG_selloship',
    'status' => 1
     ])->first();
    }

    // 🧾 Step 4: Calculate Ekart 2KG charges based on ZonePriceSetting
    $calculateEkartCharge = function () use ($ekartZonePricing, $weight, $paymentType, $orderAmount) {
        
        if ($paymentType === 'COD') {
            // echo 'cscsc';die;
            // COD Order Logic
            if ($ekartZonePricing->cod_fix_price > 0.00) {
                // Use fixed COD price - NO GST, NO other charges
                $totalPrice = $ekartZonePricing->cod_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable COD price + 18% GST
                $basePrice = $ekartZonePricing->cod_price;
                $prepaid_price = $ekartZonePricing->prepaid_price;
                $basePriceprepaid_price = $basePrice + $prepaid_price;
                $totalWithGST = $basePriceprepaid_price + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        } else {
            // Prepaid Order Logic
            if ($ekartZonePricing->prepaid_fix_price > 0.00) {
                // Use fixed Prepaid price - NO GST, NO other charges
                $totalPrice = $ekartZonePricing->prepaid_fix_price;
                return [
                    'courierCharge'  => round($totalPrice, 2),
                    'freightCharges' => round($totalPrice, 2),
                    'codCharge'      => 0,
                ];
            } else {
                // Use variable Prepaid price + 18% GST
                $basePrice = $ekartZonePricing->prepaid_price;
                $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                return [
                    'courierCharge'  => round($totalWithGST, 2),
                    'freightCharges' => round($basePrice, 2),
                    'codCharge'      => 0,
                ];
            }
        }
    };

    // 🧮 Step 5: Calculate final charges for Ekart 2KG
    $charges = $calculateEkartCharge();
    
    // 🧾 Step 6: Return single Ekart 2KG result
    // dd([
    //     'serviceabilityId' => $pincodeToCheck,
    //     'courierName'      => 'Ekart2KG',
    //     'courierCharge'    => $charges['courierCharge'],
    //     'freightCharges'   => $charges['freightCharges'],
    //     'codCharge'        => $charges['codCharge'],
    //     'zone'             => $zone,
    //     'minWeight'        => $weight,
    //     'volWeight'        => $weight,
    // ]);
    return [[
        'serviceabilityId' => $destinationstate,
        'courierName'      => 'NEW Ekart2KG',
        'courierCharge'    => $charges['courierCharge'],
        'freightCharges'   => $charges['freightCharges'],
        'codCharge'        => $charges['codCharge'],
        'zone'             => $zone,
        'minWeight'        => $weight,
        'volWeight'        => $weight,
    ]];
}

    /**
     * Call Shadowfax API (modified to work without order_id)
     */
    public function callShadowfaxAPI(array $params): array
    {
        // dd($params);
        $weight = $params['package_weight'];
        $paymentType = $params['payment_type'];
        $orderAmount = $params['order_amount'];
        $seller_id = $params['seller_id'];
        $destinationstate = $params['destination_state'];
        $pickupstate = $params['pickup_state'];

        // Check if Shadowfax zone pricing is configured for this seller
        $shadowfaxZoneCheck = ActicvSleb::where([
            'seller_id' => $seller_id,
            'LogisticProvider' => 'Shadowfax',
            'status' => 1
        ])->exists();
        //  echo 'uhhkui';die;
        if (!$shadowfaxZoneCheck) {
            return [];
        }

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
        $weight = (float)$weight; // grams
        $orderAmount = $orderAmount;
        $paymentType = strtolower($paymentType) === 'cod' ? 'COD' : 'Pre-paid';
        $seller_id = $seller_id;

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
            'serviceabilityId' => $destinationstate,
            'courierName'      => 'Shadowfax',
            'courierCharge'    => $charges['courierCharge'],
            'freightCharges'   => $charges['freightCharges'],
            'codCharge'        => $charges['codCharge'],
            'zone'             => $zone,
            'minWeight'        => $weight,
            'volWeight'        => $weight,
        ]];
    }




    // private function callSelloshipAPI(array $params): array
    // {
    //     // dd($params);
    //     try {
    //         $originPin = $params['origin_pincode'];
    //         $destinationPin = $params['destination_pincode'];
    //         $weight = (float)$params['package_weight']; // grams
    //         $payment = $params['payment_type'];
    //         $orderAmount = $params['order_amount'];
    //         $seller_id = $params['seller_id'];
            
    //         $paymentType = strtolower($payment) === 'cod' ? 'COD' : 'Pre-paid';

    //         // Get pickup and destination states from request parameters
    //         $pickupstate = $params['pickup_state'] ?? 'Delhi';
    //         $destinationstate = $params['destination_state'] ?? 'Maharashtra';

    //         // Check if Ekart 2KG zone pricing exists for this seller
    //         $ekartZoneCheck = ZonePriceSetting::where([
    //             'seller_id' => $seller_id,
    //             'LogisticProvider' => 'Ekart2KG_selloship',
    //             'status' => 1
    //         ])->exists();

    //         if (!$ekartZoneCheck) {
    //             return [];
    //         }
    //         // Zone Fetch (Case-Insensitive)
    //         $zoneData = DB::table('pincode_zones')
    //             ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupstate)])
    //             ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationstate)])
    //             ->first();

    //         if (!$zoneData) {
    //             return [];
    //         }

    //         $zone = strtoupper($zoneData->zone);
    //         //  dd()
    //         // Get Ekart 2KG Zone Pricing
    //         $ekartZonePricing = ZonePriceSetting::where([
    //             'seller_id' => $seller_id,
    //             'zone' => $zone,
    //             'LogisticProvider' => 'Ekart2KG_selloship',
    //             'status' => 1
    //         ])->first();
    // //    dd($ekartZonePricing);
    //         if (!$ekartZonePricing) {
    //             return [];
    //         }
    //     //  echo 'csdcsc';die;

    //         // Calculate Ekart 2KG charges based on ZonePriceSetting
    //         $calculateEkartCharge = function () use ($ekartZonePricing, $weight, $paymentType, $orderAmount) {
                
    //             if ($paymentType === 'COD') {
    //                 // COD Order Logic
    //                 if ($ekartZonePricing->cod_fix_price > 0) {
    //                     // Use fixed COD price - NO GST, NO other charges
    //                     $totalPrice = $ekartZonePricing->cod_fix_price;
    //                     return [
    //                         'courierCharge'  => round($totalPrice, 2),
    //                         'freightCharges' => round($totalPrice, 2),
    //                         'codCharge'      => 0,
    //                     ];
    //                 } else {
    //                     // Use variable COD price + 18% GST
    //                     $basePrice = $ekartZonePricing->cod_price;
    //                     $totalWithGST = $basePrice + ($basePrice * 18 / 100);
    //                     return [
    //                         'courierCharge'  => round($totalWithGST, 2),
    //                         'freightCharges' => round($basePrice, 2),
    //                         'codCharge'      => 0,
    //                     ];
    //                 }
    //             } else {
    //                 // Prepaid Order Logic
    //                 if ($ekartZonePricing->prepaid_fix_price > 0) {
    //                     // Use fixed Prepaid price - NO GST, NO other charges
    //                     $totalPrice = $ekartZonePricing->prepaid_fix_price;
    //                     return [
    //                         'courierCharge'  => round($totalPrice, 2),
    //                         'freightCharges' => round($totalPrice, 2),
    //                         'codCharge'      => 0,
    //                     ];
    //                 } else {
    //                     // Use variable Prepaid price + 18% GST
    //                     $basePrice = $ekartZonePricing->prepaid_price;
    //                     $totalWithGST = $basePrice + ($basePrice * 18 / 100);
    //                     return [
    //                         'courierCharge'  => round($totalWithGST, 2),
    //                         'freightCharges' => round($basePrice, 2),
    //                         'codCharge'      => 0,
    //                     ];
    //                 }
    //             }
    //         };

    //         // Calculate final charges for Ekart 2KG
    //         $charges = $calculateEkartCharge();
    //         // dd([
    //         //     'serviceabilityId' => $destinationPin,
    //         //     'courierName'      => 'selloshipEkart2KG',
    //         //     'courierCharge'    => $charges['courierCharge'],
    //         //     'freightCharges'   => $charges['freightCharges'],
    //         //     'codCharge'        => $charges['codCharge'],
    //         //     'zone'             => $zone,
    //         //     'minWeight'        => $weight,
    //         //     'volWeight'        => $weight,
    //         // ]);
    //         // Return single Ekart 2KG result
    //         return [[
    //             'serviceabilityId' => $destinationPin,
    //             'courierName'      => 'Ekart 2KG',
    //             'courierCharge'    => $charges['courierCharge'],
    //             'freightCharges'   => $charges['freightCharges'],
    //             'codCharge'        => $charges['codCharge'],
    //             'zone'             => $zone,
    //             'minWeight'        => $weight,
    //             'volWeight'        => $weight,
    //         ]];

    //     } catch (\Exception $e) {
    //         Log::error('Selloship API Exception', ['error' => $e->getMessage()]);
    //         return [];
    //     }
    // }





    public function assignBoxdOrder($params, $sellerid = null)
    {

        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'];
        // dd($provider_name);
        if ($provider_name == 'Bluedart Surface 500gms') {
            $productId = "1746709645240";
            $carrierId = "1738577045";
            $courierId = "67a30c0bc0f32f279b8b5c88";
            $logistic_name = 'Bluedart Surface 500gms';
        } elseif ($provider_name == 'Bluedart Air 500gms') {
            $productId = "1750498184844";
            $carrierId = "1750343798";
            $courierId = "678b3ac540f9b7f91a8b4c3f";
            $logistic_name = 'Bluedart Air 500gms';
        } elseif ($provider_name == 'Delhivery Air' || $provider_name == 'Delhivery 250gms') {
            // Both use the same working configuration
            $productId = "1753163038641";
            $carrierId = "1656013556";
            $courierId = "1456367975";
            $logistic_name = 'Delhivery 250gms';
        } else {
            return ['status' => false, 'message' => 'Invalid provider name.'];
        }


        // $seller = Auth::guard('seller')->user();
        $sellerid = $sellerid;
        $seller = SellerList::find($sellerid);

        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
        }

        // Wallet balance check
        $credit = Recharge::where('seller_id', $sellerid)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerid)->where('type', 'Debit')->sum('amount');
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
        $addressId = $this->registerHubboxd([
            "hub_name" => $pickup['warehouse_name'],
            "pincode" => $pickup['pincode'],
            "address1" => $pickup['address'],
            "hub_phone" => $pickup['phone'],
            "email" => $pickup['email'] ?? 'warehouse@example.com'
        ], $sellerid);
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

        $codAmount = ($order->payment_type === 'cod') ? $order->collectable_amount : 0;
        $paymentMode = ($order->payment_type === 'cod') ? 'cod' : 'prepaid';
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
                    'seller_id' => $sellerid,
                    'type' => 'Debit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order created',

                ]);

                return [
                    'status' => true,
                    'message' => 'Order successfully assigned to Boxd.',
                    'couriername' => $logistic_name,
                    'awb_number' => $responseData['awb_number'] ?? null,
                    // 'label_url' => $responseData['label'] ?? null, // Changed from label_url to label
                    // 'route_code' => $responseData['route_code'] ?? null,
                    // 'tracking_status' => $responseData['tracking_status'] ?? null,
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



    public function assignTekipostOrder($params, $sellerid = null)
    {
        // dd($params);
        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'];

        $token = $this->getToken();
        // $seller = Auth::guard('seller')->user();
        $sellerid = $sellerid;
        $seller = SellerList::find($sellerid);
        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
        }

        // Wallet balance check
        $credit = Recharge::where('seller_id', $sellerid)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerid)->where('type', 'Debit')->sum('amount');
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
            "address2" => $pickup['address'] ?? '',
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

        $codAmount = ($order->payment_type === 'cod') ? $order->collectable_amount : 0;

        if ($order->payment_type === 'cod') {
            $order_type = "1"; // Set to 0 if not COD
        } else {
            $order_type = "0"; // Set to 1 if not COD
        }

        if ($provider_name === 'Delhivery_1 KG') {
            $logistic_id = 38; // Set to 38 for Delhivery_1 KG
            $logistic_name = 'Delhivery_1 KG';

        } else if ($provider_name === 'Delhivery_10kg') {
            $logistic_id = 24; // Set to 24 for Delhivery_10kg
            $logistic_name = 'Delhivery_10kg';
        } else if ($provider_name === 'Delhivery_5kg') {
            $logistic_id = 23; // Set to 23 for Delhivery_5kg
            $logistic_name = 'Delhivery_5kg';
        } else if ($provider_name === 'Blue Dart_0.5 KG') {
            $logistic_id = 16; // Set to 16 for Blue Dart_0.5 KG
            $logistic_name = 'Blue Dart_0.5 KG';
        } else if ($provider_name === 'Amazon_2 KG') {
            $logistic_id = 13; // Set to 13 for Amazon_2 KG
            $logistic_name = 'Amazon_2 KG';
        } else if ($provider_name === 'Amazon_0.5 KG') {
            $logistic_id = 11; // Set to 11 for Amazon_0.5 KG
            $logistic_name = 'Amazon_0.5 KG';
        } else {
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
            "receiver_address" => $consignee['address'],
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
                    'seller_id' => $sellerid,
                    'type' => 'Debit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order created'
                ]);
                return [
                    'status' => true,
                    'message' => 'Order successfully assigned to Shipxpeed.',
                    'couriername' => $provider_name,
                    'awb_number' => $responseData['tracking_number'] ?? null,
                    // 'label_url' => $order->smartship_tracking_url,
                    // 'freight_charges' => $responseData['freight_charges'] ?? null,
                ];
            }

            // If not successful
            return [
                'status' => false,
                'message' => $responseData['message'] ?? 'Shipxped API error.',
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
            // "phone" => $pickup['phone'],
            "phone" => (int) $pickup['phone'],

            "pincode" => $pickup['pincode']
        ];
        // dd($warehousePayload);

        $url = "https://app.parcelx.in/api/v3/create_warehouse";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
        ])->post($url, $warehousePayload);

        $responseData = $response->json();
        //  dd($responseData);
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

public function assignparcelxOrder($params, $sellerid = null)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'ParcelX';

    // Get Seller
    $seller = $sellerid ? SellerList::find($sellerid) : Auth::guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    // Wallet balance check
    $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
    $debit = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
    $walletBalance = $credit - $debit;

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

    // Decode JSON fields
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    // 🏠 Create warehouse (or reuse existing) 
    $warehouseResult = $this->createWarehouse($pickup, $seller->id);
    if (!$warehouseResult['status']) {
        return ['status' => false, 'message' => 'Failed to create warehouse: ' . $warehouseResult['message']];
    }
    $warehouseId = $warehouseResult['warehouse_id'];

    // 📦 Build Product Details
    $products = [];
    foreach ($orderItems as $item) {
        $products[] = [
            "product_sku" => $item['sku'] ?? $order->order_number,
            "product_name" => $item['name'] ?? 'Product',
            "product_value" => (string)($item['price'] ?? $order->collectable_amount ?? 0),
            "product_hsnsac" => $item['hsn'] ?? "",
            "product_taxper" => (int)($item['tax_rate'] ?? 0),
            "product_category" => $item['category'] ?? "General",
            "product_quantity" => (string)($item['qty'] ?? 1),
            "product_description" => $item['description'] ?? ""
        ];
    }

    // 🚚 Courier mapping
    $courier_code = match ($provider_name) {
        'Deliveri 500gm' => 'PXDEL01',
        'Amazon 500gm' => 'PXA01',
        default => 'PXA01'
    };
    $express_type = "surface";

    // 🧾 ParcelX Payload
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
        "cod_amount" => $order->payment_type == 'cod' ? (string)$order->collectable_amount : "0",
        "tax_amount" => "0",
        "mps" => "0",
        "courier_type" => 1,
        "courier_code" => $courier_code,
        "products" => $products,
        "address_type" => "Home",
        "payment_mode" => $order->payment_type == 'cod' ? 'Cod' : 'Prepaid',
        "order_amount" => (string)($order->collectable_amount ?? 0),
        "extra_charges" => "0",
        // "shipment_width" => [(string)($order->package_breadth ?? 1)],
        // "shipment_height" => [(string)($order->package_height ?? 1)],
        // "shipment_length" => [(string)($order->package_length ?? 1)],
        // "shipment_weight" => [(string)(($order->package_weight ?? 500) / 1000)],
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
        
    // dd($responseData);
        if (!$response->successful() || empty($responseData)) {
            return ['status' => false, 'message' => 'ParcelX API error', 'data' => $responseData];
        }

        if (isset($responseData['status']) && $responseData['status'] === true) {
            $awb = $responseData['data']['awb_number'] ?? null;
            if ($awb) {
                $order->awb_number = $awb;
                $order->courier_id = 'parcelx';
                $order->all_courier_name = $provider_name;
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
                    'message' => 'Order successfully assigned.',
                    'couriername' => $provider_name,
                    'awb_number' => $awb,

                ];
            }
        }

        return ['status' => false, 'message' => 'ParcelX Order creation failed', 'data' => $responseData];
    } catch (\Exception $e) {
        return ['status' => false, 'message' => 'API Request Failed: ' . $e->getMessage()];
    }
}

    
    // public function assignparcelxOrder($params, $sellerid = null)
    // {
    //     // dd($params);
    //     $order_id = $params['order_id'];
    //     $provider_name = $params['provider_name'];

    //     $token = $this->getToken();
    //     // $seller = Auth::guard('seller')->user();
    //     $sellerid = $sellerid;
    //     $seller = SellerList::find($sellerid);
    //     if (!$seller || $seller->status != 1) {
    //         return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    //     }

    //     // Wallet balance check
    //     $credit = Recharge::where('seller_id', $sellerid)->where('status', 1)->where('type', 'Credit')->sum('amount');
    //     $debit = Recharge::where('seller_id', $sellerid)->where('type', 'Debit')->sum('amount');
    //     $walletBalance = $credit - $debit;

    //     $order = Order::where('id', $order_id)->first();
    //     if (!$order) {
    //         return ['status' => false, 'message' => 'Order not found.'];
    //     }


    //     if (
    //         $seller->negative_balance != '1' &&
    //         ($walletBalance < $order->seller_amount_walate || $walletBalance < 150)
    //     ) {
    //         return [
    //             'status'  => false,
    //             'message'  => 'Insufficient wallet balance. Please recharge your wallet.',
    //         ];
    //     }


    //     // Decode order item & consignee
    //     $orderItems = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;
    //     $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    //     $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

    //     // Register warehouse and get sender address ID
    //     $sender_address_id = $this->registerHub([
    //         "hub_name" => $pickup['warehouse_name'],
    //         "pincode" => $pickup['pincode'],
    //         "city" => $pickup['city'],
    //         "state" => $pickup['state'],
    //         "address1" => $pickup['address'],
    //         "address2" => $pickup['address'] ?? '',
    //         "hub_phone" => $pickup['phone'],
    //         "contact_person_name" => $pickup['contact_person_name'] ?? 'Admin'
    //     ]);
    //     // dd($sender_address_id);

    //     if (!$sender_address_id) {
    //         return ['status' => false, 'message' => 'Failed to register warehouse.'];
    //     }
    //     // dd($sender_address_id);

    //     $productDetails = [];
    //     foreach ($orderItems as $item) {
    //         $productDetails[] = [
    //             "sku_number" => (int) ($item['sku'] ?? 0),
    //             "product_name" => $item['name'],
    //             "product_quantity" => (int) ($item['qty'] ?? 0),
    //             "product_value" => (int) ($item['price'] ?? 100)
    //         ];
    //     }

    //     $codAmount = ($order->payment_type === 'cod') ? $order->collectable_amount : 0;

    //     if ($order->payment_type === 'cod') {
    //         $order_type = "1"; // Set to 0 if not COD
    //     } else {
    //         $order_type = "0"; // Set to 1 if not COD
    //     }

    //     if ($provider_name === 'Delhivery_1 KG') {
    //         $logistic_id = 38; // Set to 38 for Delhivery_1 KG
    //         $logistic_name = 'Delhivery_1 KG';

    //     } else if ($provider_name === 'Delhivery_10kg') {
    //         $logistic_id = 24; // Set to 24 for Delhivery_10kg
    //         $logistic_name = 'Delhivery_10kg';
    //     } else if ($provider_name === 'Delhivery_5kg') {
    //         $logistic_id = 23; // Set to 23 for Delhivery_5kg
    //         $logistic_name = 'Delhivery_5kg';
    //     } else if ($provider_name === 'Blue Dart_0.5 KG') {
    //         $logistic_id = 16; // Set to 16 for Blue Dart_0.5 KG
    //         $logistic_name = 'Blue Dart_0.5 KG';
    //     } else if ($provider_name === 'Amazon_2 KG') {
    //         $logistic_id = 13; // Set to 13 for Amazon_2 KG
    //         $logistic_name = 'Amazon_2 KG';
    //     } else if ($provider_name === 'Amazon_0.5 KG') {
    //         $logistic_id = 11; // Set to 11 for Amazon_0.5 KG
    //         $logistic_name = 'Amazon_0.5 KG';
    //     } else {
    //         $logistic_name = 'Delhivery_1 KG';
    //         $logistic_id = 38; // Default to 38 if no specific provider
    //     }
    //     // dd($logistic_id);
    //     // Final payload matching the cURL structure exactly
    //     $payload = [
    //         // "isorder" => 1,
    //         "logistic_id" => $logistic_id, //pass this when specific logistic to be assign else dont pass any variables in payload
    //         "consignee_name" => $consignee['name'],
    //         "mobile_no" => (int) $consignee['phone'],
    //         "alternate_mobile_no" => (int) $consignee['phone'],
    //         "email_id" => $consignee['email'] ?? "customer@example.com",
    //         "receiver_address" => $consignee['address'],
    //         "receiver_pincode" => (int) $consignee['pincode'],
    //         "receiver_city" => $consignee['city'],
    //         "receiver_state" => strtolower($consignee['state']), // Ensure lowercase to match cURL example
    //         "receiver_landmark" => $consignee['landmark'] ?? '',
    //         "customer_order_no" => $order->order_number,
    //         "order_type" => $order_type,
    //         "product_quantity" => array_sum(array_column($orderItems, 'qty')),
    //         "cod_amount" => $codAmount,
    //         "physical_weight" => (float) ($order->package_weight / 1000), // Convert grams to kg
    //         "product_length" => (float) $order->package_length,
    //         "product_width" => (float) $order->package_breadth,
    //         "product_height" => (float) $order->package_height,
    //         "hsn_number" => $orderItems[0]['sku'] ?? 'HSN001',
    //         "order_value" => (float) $order->collectable_amount,
    //         "productdetatis" => $productDetails, // Changed from "productdetails" to "productdetatis"
    //         "sender_address_id" => $sender_address_id,
    //         "return_address_same_as_pickup_address" => 1,
    //         "return_consignee_name" => $pickup['contact_person_name'] ?? 'Return Admin',
    //         "return_mobile_no" => (int) $pickup['phone'],
    //         "return_alternate_mobile_no" => (int) $pickup['phone'],
    //         "return_address" => $pickup['address'],
    //         "return_pincode" => (int) $pickup['pincode'],
    //         "return_city" => strtolower($pickup['city']), // Ensure lowercase
    //         "return_state" => strtolower($pickup['state']), // Ensure lowercase
    //         "return_landmark" => $pickup['landmark'] ?? ''
    //     ];

    //     // dd($payload);
    //     try {
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $token,
    //             'Content-Type' => 'application/json',
    //             'Accept' => 'application/json',
    //         ])->post('https://app.tekipost.com/api-b2c-single-order', $payload);



    //         $responseData = $response->json();
    //         //    dd($responseData);
    //         // Check for successful response using 'status'
    //         if (isset($responseData['status']) && $responseData['status'] == 1) {
    //             // Save the tracking number as AWB number
    //             $order->awb_number = $responseData['tracking_number'] ?? null;
    //             $order->courier_id = 'tekipost';
    //             $order->all_courier_name = $logistic_name;

    //             $order->smartship_tracking_url = $responseData['label_url'] ?? null;
    //             // $order->smartship_courier_id = $provider_name ?? null;
    //             $order->shipping_date = Carbon::now()->format('Y-m-d');

    //             $order->save();
    //             // Wallet debit
    //             Recharge::create([
    //                 'seller_id' => $sellerid,
    //                 'type' => 'Debit',
    //                 'amount' => $order->seller_amount_walate,
    //                 'status' => 1,
    //                 'description' => 'Order created'
    //             ]);
    //             return [
    //                 'status' => true,
    //                 'message' => 'Order successfully assigned to Shipxpeed.',
    //                 'couriername' => $provider_name,
    //                 'awb_number' => $responseData['tracking_number'] ?? null,
    //                 // 'label_url' => $order->smartship_tracking_url,
    //                 // 'freight_charges' => $responseData['freight_charges'] ?? null,
    //             ];
    //         }

    //         // If not successful
    //         return [
    //             'status' => false,
    //             'message' => $responseData['message'] ?? 'Shipxped API error.',
    //             'data' => $responseData,
    //         ];



    //         return [
    //             'status' => false,
    //             'message' => 'Tekipost API error: ' . ($responseData['message'] ?? 'Unknown error'),
    //             'data' => $responseData,
    //         ];
    //     } catch (\Exception $e) {
    //         return [
    //             'status' => false,
    //             'message' => 'API Request Failed: ' . $e->getMessage(),
    //         ];
    //     }
    // }





public function assignDTDCOrder($params, $sellerid = null)
{
    $order_id = $params['order_id'];
    $provider_name = $params['provider_name'] ?? 'DTDC';
    //    dd($provider_name);
    $sellerid = $sellerid;
     $seller = SellerList::find($sellerid);
    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    // Wallet balance check
    $credit = Recharge::where('seller_id', $sellerid)->where('status', 1)->where('type', 'Credit')->sum('amount');
    $debit = Recharge::where('seller_id', $sellerid)->where('type', 'Debit')->sum('amount');
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
                    "address_line_2" => $pickup['address'],
                    "pincode" => (string)$pickup['pincode'],
                    "city" => $pickup['city'],
                    "state" => $pickup['state'] ?? 'Delhi',
                ],

                "destination_details" => [
                    "name" => $consignee['name'],
                    "phone" => $consignee['phone'],
                    "alternate_phone" => $consignee['phone'],
                    "address_line_1" => $consignee['address'],
                    "address_line_2" => $consignee['address'],
                    "pincode" => (string)$consignee['pincode'],
                    "city" => $consignee['city'],
                    "state" => $consignee['state']
                ],

                "return_details" => [
                    "address_line_1" => $pickup['address'],
                    "address_line_2" => $pickup['address'],
                    "city_name" => $pickup['city'],
                    "name" => $pickup['name'],
                    "phone" => $pickup['phone'],
                    "pincode" => (string)$pickup['pincode'],
                    "state_name" => $pickup['state'] ?? 'Delhi',
                    "email" => $pickup['email'] ?? 'test@gmail.com',
                    "alternate_phone" => $pickup['phone']
                ],

                "customer_reference_number" => $order->order_number,
                "cod_collection_mode" => $order->payment_type == 'cod' ? 'CASH' : '',
                "cod_amount" => $order->payment_type == 'cod' ? $order->collectable_amount : 0,
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
                'seller_id' => $sellerid,
                'type' => 'Debit',
                'amount' => $order->seller_amount_walate,
                'status' => 1,
                'description' => 'Order created'
            ]);

            return [
            'status' => true,
            'message' => 'Order successfully assigned to DTDC.',
            'couriername' => $provider_name,
            'awb_number' => $responseData['data'][0]['reference_number'] ?? null,
            // 'label_url' => $responseData['data'][0]['barCodeData'] ?? null, // DTDC may return base64 label or empty
            // 'route_code' => $responseData['data'][0]['courier_partner'] ?? null,
            // 'tracking_status' => $responseData['data'][0]['success'] ? 'Created' : 'Failed',
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

    /**
     * Shiprocket Login to get auth token
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

    /**
     * Create Shiprocket warehouse/pickup location
     */
    public function createShiprocketWarehouse($pickup, $sellerId)
    {
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

            if ($existingWarehouse) {
                // Return existing warehouse pickup location name
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

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->post('https://apiv2.shiprocket.in/v1/external/settings/company/addpickup', $pickupPayload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
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

    /**
     * Assign order to Shiprocket
     */
    public function assignShiprocketOrder($params, $sellerid = null)
    {
        // dd($sellerid);
        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'] ?? 'Xpressbee 250gms';
        
        // Get seller based on sellerid
        $seller = SellerList::find($sellerid);

        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
        }
        // Wallet balance check
        $credit = Recharge::where('seller_id', $sellerid)->where('status', 1)->where('type', 'Credit')->sum('amount');
        $debit = Recharge::where('seller_id', $sellerid)->where('type', 'Debit')->sum('amount');
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

        // Get Shiprocket auth token
        $authResponse = $this->shiprocketLogin();
        if (!isset($authResponse['token'])) {
            return ['status' => false, 'message' => 'Failed to authenticate with Shiprocket'];
        }
        $token = $authResponse['token'];

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
// echo 'cscsc';die;
        try {
            // STEP 0: Create Pickup Location/Warehouse in Shiprocket using createShiprocketWarehouse function
            $warehouseResult = $this->createShiprocketWarehouse($pickup, $sellerid);
            // dd($warehouseResult);
            if (!$warehouseResult['status']) {
                return [
                    'status' => false,
                    'message' => 'Failed to create/get warehouse: ' . $warehouseResult['message']
                ];
            }
            
            $pickupLocationName = $warehouseResult['pickup_location'];

            // STEP 1: Create Order in Shiprocket
            $shiprocketPayload = [
                "order_id" => $order->order_number,
                "order_date" => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s'),
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
                "billing_email" => $consignee['email'] ?? 'customer@example.com',
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
                "payment_method" => ucfirst(strtolower($order->payment_type ?? 'prepaid')),
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

            $createOrderResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->post('https://apiv2.shiprocket.in/v1/external/orders/create/adhoc', $shiprocketPayload);

            $orderResponseData = $createOrderResponse->json();

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

            if ($awbResponse->successful() && 
                isset($awbResponseData['awb_assign_status']) && 
                $awbResponseData['awb_assign_status'] == 1 &&
                isset($awbResponseData['response']['data']['awb_code'])) {

                // Successfully got AWB - extract from nested structure
                $responseData = $awbResponseData['response']['data'];
                $awb_number = $responseData['awb_code'];
                
                // Update order with all Shiprocket details
                $order->courier_id = 'shiprocket';
                $order->all_courier_name = $provider_name;
                $order->awb_number = $awb_number;
                $order->shipping_date = Carbon::now()->format('Y-m-d');
                $order->save();

                // Debit wallet
                Recharge::create([
                    'seller_id' => $sellerid,
                    'type' => 'Debit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order created'
                ]);

                return [
                    'status' => true,
                    'message' => 'Order successfully created and AWB assigned',
                    'couriername' => $provider_name,
                    'awb_number' => $awb_number,
                ];
            } else {
                // AWB assignment failed, but order was created
                $order->courier_id = 'shiprocket';
                $order->all_courier_name = $provider_name;
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
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'API Request Failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get Shiprocket courier ID by provider name
     */
    private function getCourierIdByProvider($provider_name)
    {
        // Map provider names to Shiprocket courier IDs
        $courierMapping = [
            'Xpressbee 250gms' => 751,  // Xpressbees Surface
            'Delhivery_Shiprocket 250gms' => 724,  // Delhivery Surface
            'Bluedart 2kg surface' => 603,  // BlueDart Surface
        ];

        return $courierMapping[$provider_name] ?? 751; // Default to Xpressbees
    }


    /**
     * Fetch authentication token for Selloship API
     */
    protected function fetchAuthToken()
    {
        $resp = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://selloship.com/api/lock_actvs/channels/authToken', [
            'username' => 'Bashu@shipxpeed.com',
            'password' => 'Selloship@123',
        ]);

        $json = $resp->json();
        if (!$resp->successful() || empty($json['token'])) {
            throw new \RuntimeException("Selloship login failed: {$resp->body()}");
        }

        return $json['token'];
    }

    /**
     * Assign order to Selloship
     */
    public function assignSelloshipOrder($params, $sellerid = null)
    {
        // dd($params);
        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'] ?? 'selloshipEkart2KG';
        
        try {
            $api_token = $this->fetchAuthToken();
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Failed to get API token: ' . $e->getMessage()];
        }

        $seller = $sellerid ? SellerList::find($sellerid) : Auth::guard('seller')->user();

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

        // Prepare items array for the API
        $items = [];
        if ($orderItems && is_array($orderItems)) {
            foreach ($orderItems as $item) {
                $items[] = [
                    "name" => $item['name'] ?? $order->product_name ?? 'Product',
                    "skuCode" => $item['sku'] ?? $item['skuCode'] ?? '',
                    "quantity" => (int)($item['qty'] ?? 1),
                    "itemPrice" => (float)($item['price'] ?? $item['itemPrice'] ?? 0),
                    "description" => $item['description'] ?? 'Product description'
                ];
            }
        } else {
            // Default item if no items found
            $items[] = [
                "name" => $order->product_name ?? 'Product',
                "skuCode" => "",
                "quantity" => 1,
                "itemPrice" => (float)$order->collectable_amount,
                "description" => "Product description"
            ];
        }

        // Prepare the waybill API payload
        $waybillPayload = [
            "Shipment" => [
                "items" => $items,
                "height" => (string)($order->package_height ?? 10),
                "length" => (string)($order->package_length ?? 10),
                "weight" => (string)max(0.5, ($order->package_weight ?? 500) / 1000), // Convert to kg
                "breadth" => (string)($order->package_breadth ?? 10),
                "orderCode" => $order->order_number,
                "orderDate" => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
                "channelCode" => "ONLINE",
                "channelName" => $seller->business_name ?? "Store",
                "invoiceCode" => $order->order_number,
                "numberOfBoxes" => 1,
                "fullFilllmentTat" => Carbon::now()->addDays(3)->format('Y-m-d H:i:s')
            ],
            "paymentMode" => strtoupper($order->payment_type) === 'COD' ? 'COD' : 'PREPAID',
            "serviceType" => "Standard",
            "totalAmount" => (string)$order->collectable_amount,
            "currencyCode" => "INR",
            "collectableAmount" => strtoupper($order->payment_type) === 'COD' ? (string)$order->collectable_amount : "0",
            "returnShipmentFlag" => false,
            "pickupAddressDetails" => [
                "city" => $pickup['city'],
                "name" => $pickup['name'],
                "phone" => $pickup['phone'],
                "state" => $pickup['state'],
                "country" => "India",
                "pincode" => (string)$pickup['pincode'],
                "address1" => $pickup['address']
            ],
            "returnAddressDetails" => [
                "city" => $pickup['city'],
                "name" => $pickup['name'],
                "phone" => $pickup['phone'],
                "state" => $pickup['state'],
                "country" => "India",
                "pincode" => (string)$pickup['pincode'],
                "address1" => $pickup['address']
            ],
            "deliveryAddressDetails" => [
                "city" => $consignee['city'],
                "name" => $consignee['name'],
                "phone" => $consignee['phone'],
                "state" => $consignee['state'],
                "country" => "India",
                "pincode" => (string)$consignee['pincode'],
                "address1" => $consignee['address']
            ]
        ];

        try {
            $url = "https://selloship.com/api/lock_actvs/channels/waybill";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $api_token,
            ])->post($url, $waybillPayload);
          
            $responseData = $response->json();
            //  dd($responseData);
            if (!$response->successful() || ($responseData['status'] ?? '') !== 'SUCCESS') {
                return [
                    'status' => false, 
                    'message' => 'Selloship API order creation failed', 
                    'data' => $responseData
                ];
            }

            if ($responseData['status'] === 'SUCCESS' && !empty($responseData['waybill'])) {
                $awb = $responseData['waybill'];

                // Update order
                $order->courier_id = 'selloship';
                $order->all_courier_name = $provider_name;
                $order->awb_number = $awb;
                $order->shipping_date = now()->format('Y-m-d');
                $order->save();

                // Deduct from wallet
                Recharge::create([
                    'seller_id' => $seller->id,
                    'type' => 'Debit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'Order created'
                ]);

                return [
                    'status' => true,
                    'message' => 'Order successfully assigned to ' . $provider_name,
                    'couriername' => strtolower($provider_name),
                    'awb_number' => $awb,
                    // 'label_url' => $responseData['shippingLabel'] ?? null,
                    // 'route_code' => $responseData['routingCode'] ?? null,
                    // 'tracking_status' => 'Created',
                    // 'courier_name' => $responseData['courierName'] ?? $provider_name
                ];
            }

            return [
                'status' => false,
                'message' => 'Invalid response from Selloship API',
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Selloship API assignment failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'API Request Failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Assign order to Shadowfax
     */
    public function assignshadowfaxOrder($params, $sellerid = null)
    {
        // dd($sellerid);
        $order_id = $params['order_id'];
        $provider_name = $params['provider_name'] ?? 'Shadowfax';
        $token = "fec1949bfc737bd52df914d18673e27b67a7f92d";

        $seller = $sellerid ? SellerList::find($sellerid) : Auth::guard('seller')->user();
        if (!$seller || $seller->status != 1) {
            return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
        }

        // 🧾 Wallet Balance Calculation
        $credit = Recharge::where('seller_id', $sellerid ?? $seller->id)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');
        $debit = Recharge::where('seller_id', $sellerid ?? $seller->id)
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
                    'seller_id'   => $sellerid ?? $seller->id,
                    'type'        => 'Debit',
                    'amount'      => $order->seller_amount_walate,
                    'status'      => 1,
                    'description' => 'Order created'
                ]);

                // return [
                //     'status'          => true,
                //     'message'         => 'Order successfully assigned to Shadowfax.',
                //     'couriername'     => 'shadowfax',
                //     'awb_number'      => $awb,
                //     'tracking_status' => 'Created',
                //     'api_response'    => $responseData,
                // ];

                          return [
                    'status' => true,
                    'message' => 'Order successfully assigned to Shadowfax',
                    'couriername' => 'shadowfax',
                    'awb_number' => $awb,

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

}



