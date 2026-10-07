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
use App\Models\Order;
use App\Models\ZonePriceSetting;

class RatecardController extends Controller
{


    

    private function getTokentekipost()
    {
        try {

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->asForm()
                ->post('https://app.tekipost.com/api-login', [
                    "email" => "Bashu@shipxpeed.com", 
                    "password" => "Shipxpeed@7722"
                ]);
            if ($response->successful()) {
                $data = $response->json();
                $token = $data['data']['token'] ?? null;
                echo "DEBUG: Token extracted: " . ($token ? 'YES' : 'NO') . "\n";
                return $token;
            } else {
                echo "DEBUG: Tekipost token API failed! Response: " . $response->body() . "\n";
                return null;
            }
        } catch (\Exception $e) {
            echo "DEBUG: Tekipost token Exception: " . $e->getMessage() . "\n";
            return null;
        }
    }


    public function checkRate(Request $request)
    {
    //    dd($request);
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['success'=>false,'message'=>'Authorization token required'], 401);
        }

        $seller = SellerList::where('api_token', $token)->first();
        if (!$seller) {
            return response()->json(['success'=>false,'message'=>'Invalid or expired token'], 401);
        }
        $sellerId = $seller->id;


        // ===== Validation using Validator::make =====
        $validator = Validator::make($request->all(), [
            'origin'            => 'required|numeric|digits:6',
            'destination'       => 'required|numeric|digits:6',
            'originState'       => 'required|string|max:100',
            'destinationState'  => 'required|string|max:100',
            'payment_type'      => 'required|in:cod,prepaid',
            'order_amount'      => 'required|numeric',
            'weight'            => 'required|numeric',
            'length'            => 'required|numeric',
            'breadth'           => 'required|numeric',
            'height'            => 'required|numeric',
        ], [
            'origin.required' => 'Origin pin code is required',
            'destination.required' => 'Destination pin code is required',
            'originState.required' => 'Origin state is required',
            'destinationState.required' => 'Destination state is required',
            'payment_type.required' => 'Payment type is required',
            'payment_type.in' => 'Payment type must be cod or prepaid',
            'order_amount.required' => 'Order amount is required',
            'weight.required' => 'Weight is required',
            'length.required' => 'Length is required',
            'breadth.required' => 'Breadth is required',
            'height.required' => 'Height is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $orderAmount = $request->order_amount;
        $paymentType = $request->payment_type;
        $originPin = $request->origin;
        $destinationPin = $request->destination;
        $weight = $request->weight;

        $enhancedData = [];

        // ==================== Shadowfax ====================
        $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
        $sellerPercentage = $PriceSetting ? $PriceSetting->shipping_charge : 70;

        $codCharge = 0;
        if ($paymentType == 'cod') {
            $codCharge = $orderAmount > 1400 ? ($orderAmount * 1.9 / 100) : 28;
        }

        $slabs = ceil($weight / 500);
        $shadowfaxBase = 45;
        $shadowfaxFreight = $shadowfaxBase * $slabs;

        $shadowfaxWithSeller = $shadowfaxFreight + ($shadowfaxFreight * $sellerPercentage / 100);
        $shadowfaxWithSellercodCharge = $shadowfaxWithSeller + $codCharge;
        $shadowfaxWithGST =  $shadowfaxWithSellercodCharge + ($shadowfaxWithSellercodCharge * 18 / 100);
        $shadowfaxTotal = $shadowfaxWithGST;

        $enhancedData[] = [
            'name' => 'Shadowfax',
            // 'freight_charges' => round($shadowfaxWithSeller, 2),
            // 'cod_charges' => round($codCharge, 2),
            'total_charges' => round($shadowfaxTotal, 2),
            // 'min_weight' => 500,
            'chargeable_weight' => $weight,
        ];

        // ==================== DTDC ====================
        try {
            $PriceDTDC = PriceSetting::where(['seller_id' => $sellerId, 'LogisticProvider' => 'DTDC'])->first();
            $sellerPercentageDTDC = $PriceDTDC ? $PriceDTDC->shipping_charge : 30;
            $codPercent = $PriceDTDC ? $PriceDTDC->cod_charge_parsent : 1.9;
            $codFixed = $PriceDTDC ? $PriceDTDC->cod_charge : 32;

            $codChargeDTDC = 0;
            $pickupState = $request->originState;
            $destinationState = $request->destinationState;

            if ($paymentType === 'cod') {
                $codChargeDTDC = $orderAmount > 1400 ? ($orderAmount * $codPercent) / 100 : $codFixed;
            }

            $zoneData = DB::table('pincode_zones')
                ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupState)])
                ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationState)])
                ->first();

            $zone = $zoneData ? strtoupper($zoneData->zone) : 'A';

            $zoneRates = [
                'Surface500' => ['A'=>22,'B'=>25,'C'=>28,'D'=>32,'E'=>36],
                'Surface1kg' => ['A'=>44,'B'=>50,'C'=>56,'D'=>64,'E'=>72],
                'Air'        => ['A'=>42,'B'=>43,'C'=>87,'D'=>90,'E'=>100],
            ];

            $services = [
                ['name'=>'DTDC Surface 500gm','baseRate'=>$zoneRates['Surface500'][$zone] ?? 0,'maxWeight'=>500],
                ['name'=>'DTDC Surface 1kg','baseRate'=>$zoneRates['Surface1kg'][$zone] ?? 0,'maxWeight'=>1000],
                ['name'=>'DTDC Air','baseRate'=>$zoneRates['Air'][$zone] ?? 0,'maxWeight'=>500],
            ];

            foreach ($services as $svc) {
                $weightMultiplier = max(1, ceil($weight / $svc['maxWeight']));
                if ($PriceDTDC && isset($PriceDTDC->fixed_courier_price) && $PriceDTDC->fixed_courier_price > 0) {
                    $totalDTDC = $PriceDTDC->fixed_courier_price * $weightMultiplier;
                    $freightWithSeller = 10 * $weightMultiplier;
                } else {
                    $baseCharge = $svc['baseRate'] * $weightMultiplier * 1.10;
                    $freightWithSeller = $baseCharge + ($baseCharge * $sellerPercentageDTDC / 100);
                    $freightWithSellerCodCharge = $freightWithSeller + $codChargeDTDC;
                    $totalDTDC = $freightWithSellerCodCharge + ($freightWithSellerCodCharge * 18 / 100);
                }

                $enhancedData[] = [
                    'name' => $svc['name'],
                    // 'freight_charges' => round($freightWithSeller, 2),
                    // 'cod_charges' => round($codChargeDTDC, 2),
                    'total_charges' => round($totalDTDC, 2),
                    // 'min_weight' => $weight,
                    'chargeable_weight' => $weight,
                ];
            }

        } catch (\Exception $e) {
            // Optionally log errors
        }
        // ==================== DTDC ====================

        // ==================== Tekipost ====================

        try {
            $tekipostToken = $this->getTokentekipost();

            if ($tekipostToken) {
                $tekipostResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tekipostToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://app.tekipost.com/api-calculate-price', [
                    'paymentMode' => $paymentType === 'cod' ? 0 : 1,
                    'pickupPinCode' => $originPin,
                    'deliveryPinCode' => $destinationPin,
                    'apprWeight' => $weight / 1000,
                    'b2c_length' => $request->length,
                    'b2c_breadth' => $request->breadth,
                    'b2c_height' => $request->height,
                    'total_Weight' => $weight / 1000,
                    'declaredValue' => $orderAmount,
                    'box_shipment' => [
                        [
                            'no_of_box' => 1,
                            'length' => $request->length,
                            'breadth' => $request->breadth,
                            'height' => $request->height,
                            'each_box_weight' => $weight,
                        ]
                    ],
                ]);

                if ($tekipostResponse->successful()) {
                    $tekipostData = $tekipostResponse->json();
                    $rateList = $tekipostData['data']['rates']['list'] ?? [];
                    //    dd($rateList);
                    foreach ($rateList as $carrier) {
                        $carrierName = $carrier['logistic'] ?? '';

                        if (in_array($carrierName, ['Delhivery', 'Delhivery-SS', 'Delhivery-VPoint', 'Blue Dart_0.5 KG', 'Delhivery_1 KG'])) {
                            continue;
                        }

                        // 🔁 Dynamic Price Setting Per Carrier
                        $PriceSetting = PriceSetting::where([
                            'seller_id' => $sellerId,
                            'LogisticProvider' => $carrierName,
                        ])->first();

                        $sellerPercentage = $PriceSetting->shipping_charge ?? 70;
                        $codChargePercent = $PriceSetting->cod_charge_parsent ?? 1.9;
                        $codChargeFixed = $PriceSetting->cod_charge ?? 32;

                        $codCharge = 0;
                        if ($paymentType === 'cod') {
                            $codCharge = $orderAmount > 1400
                                ? ($orderAmount * $codChargePercent) / 100
                                : $codChargeFixed;
                        }

                        $baseCost = $carrier['addittional_charges']['freight_charge'] ?? 0;
                        $freight = $baseCost * 1.10; // Add 10% markup

                        $freightWithSeller = $freight + ($freight * $sellerPercentage / 100);
                        $freightWithCod = $freightWithSeller + $codCharge;
                        $freightWithGST = $freightWithCod + ($freightWithCod * 0.18);

                        $enhancedData[] = [
                            'name' => $carrierName ?: 'Tekipost Carrier',
                            // 'freight_charges' => round($freightWithSeller, 2),
                            // 'cod_charges' => round($codCharge, 2),
                            'total_charges' => round($freightWithGST, 2),
                            // 'min_weight' => 500,
                            'chargeable_weight' => $weight,
                        ];
                    }
                } else {

                }
            }
        } catch (\Exception $e) {
            // Log::error('Tekipost API Exception', ['message' => $e->getMessage()]);
        }
        // ==================== Tekipost ====================



        // === Delhivery B2C Express ===
        try {

            $PriceExpress = PriceSetting::where(['seller_id' => $sellerId, 'LogisticProvider' => 'Delhivery Air'])->first();
            $sellerPercentageExpresshipping_charge = $PriceExpress ? $PriceExpress->shipping_charge : 70;
            $Express_COD_Charge_parsent = $PriceExpress ? $PriceExpress->cod_charge_parsent : 1.9; // e.g. 1.9%
            $ExpressCOD_Charge_amount = $PriceExpress ? $PriceExpress->cod_charge : 32; // default ₹28

            $codChargeExpress = 0;

            if ($paymentType === 'cod') {
                if ($orderAmount > 1400) {
                    $codChargeExpress = ($orderAmount * $Express_COD_Charge_parsent) / 100;
                } else {
                    $codChargeExpress = $ExpressCOD_Charge_amount;
                }
            }

            if ($paymentType === 'cod') {
                $payment = 'COD';
            } else {
                $payment = 'Pre-paid';
            }

            $delhiveryToken = "a6cd5bb955fddcb41757ec23ee92cf62b6650607";

            $delhiveryModes = ['E' => 'Express'];

            foreach ($delhiveryModes as $md => $serviceName) {


                $resp = Http::withHeaders([
                    'Authorization' => 'Token ' . $delhiveryToken,
                ])->get("https://track.delhivery.com/api/kinko/v1/invoice/charges/.json?md={$md}&ss=Delivered&d_pin={$destinationPin}&o_pin={$originPin}&cgm={$weight}&pt={$payment}");


                //   dd($resp->json());
                if ($resp->successful()) {
                    $delhiveryData = $resp->json();
                    //  dd($delhiveryData);
                    // $baseCharge = $delhiveryData[0]['gross_amount'] ?? 0;

                    if ($PriceSetting && isset($PriceSetting->fixed_courier_price) && $PriceSetting->fixed_courier_price > 0) {
                        // Calculate weight multiplier based on 500g intervals
                        $weightInKg = $weight; // Convert grams to kg
                        $weightMultiplier = max(1, ceil($weightInKg / 0.5)); // Every 500g (0.5kg) interval, minimum 1
                        $delhiveryTotal = $PriceSetting->fixed_courier_price * $weightMultiplier;
                        $delhiveryWithSeller = 10 * $weightMultiplier;
                    } else {

                        $baseCharge = ($delhiveryData[0]['gross_amount'] ?? 0) * 1.10;
                        $delhiveryWithSeller = $baseCharge + ($baseCharge * $sellerPercentageExpresshipping_charge / 100);
                        $delhiveryWithSellerCodCharge = $delhiveryWithSeller + $codChargeExpress;
                        $delhiveryWithGST = $delhiveryWithSellerCodCharge + ($delhiveryWithSellerCodCharge * 18 / 100);
                        $delhiveryTotal = $delhiveryWithGST;
                    }
                    $enhancedData[] = [
                        'name' => "Delhivery B2C ($serviceName)",
                        // 'freight_charges' => round($delhiveryWithSeller, 2),
                        // 'cod_charges' => round($codChargeExpress, 2),
                        'total_charges' => round($delhiveryTotal, 2),
                        // 'min_weight' => $weight,
                        'chargeable_weight' => $weight,
                    ];
                } else {
                    // Log::error("Delhivery $serviceName Rate API Error", ['response' => $resp->body()]);
                }
            }
        } catch (\Exception $e) {
            // Log::error("Delhivery API Exception", ['message' => $e->getMessage()]);
        }
        // === Delhivery B2C Express ===

        // === Delhivery B2C Surface ===
        try {

            $PriceSurface = PriceSetting::where(['seller_id' => $sellerId, 'LogisticProvider' => 'Delhivery'])->first();
            $sellerPercentageSurfaceshipping_charge = $PriceSurface ? $PriceSurface->shipping_charge : 70;
            $Surface_COD_Charge_parsent = $PriceSurface ? $PriceSurface->cod_charge_parsent : 1.9; // e.g. 1.9%
            $SurfaceCOD_Charge_amount = $PriceSurface ? $PriceSurface->cod_charge : 32; // default ₹28

            $codChargeSurface = 0;

            if ($paymentType === 'cod') {
                if ($orderAmount > 1400) {
                    $codChargeSurface = ($orderAmount * $Surface_COD_Charge_parsent) / 100;
                } else {
                    $codChargeSurface = $SurfaceCOD_Charge_amount;
                }
            }

            if ($paymentType === 'cod') {
                $payment = 'COD';
            } else {
                $payment = 'Pre-paid';
            }


            $delhiveryToken = "a6cd5bb955fddcb41757ec23ee92cf62b6650607";

            $delhiveryModes = ['S' => 'Surface'];

            foreach ($delhiveryModes as $md => $serviceName) {


                $resp = Http::withHeaders([
                    'Authorization' => 'Token ' . $delhiveryToken,
                ])->get("https://track.delhivery.com/api/kinko/v1/invoice/charges/.json?md={$md}&ss=Delivered&d_pin={$destinationPin}&o_pin={$originPin}&cgm={$weight}&pt={$payment}");

                if ($resp->successful()) {
                    $delhiveryData = $resp->json();
                    //  dd($delhiveryData);
                    // $baseCharge = $delhiveryData[0]['gross_amount'] ?? 0;
                    $baseCharge = ($delhiveryData[0]['charge_DL'] ?? 0) * 1.10;

                    $delhiveryWithSeller = $baseCharge + ($baseCharge * $sellerPercentageSurfaceshipping_charge / 100);
                    $delhiveryWithSellerCodCharge = $delhiveryWithSeller + $codChargeSurface;
                    $delhiveryWithGST = $delhiveryWithSellerCodCharge + ($delhiveryWithSellerCodCharge * 18 / 100);
                    $delhiveryTotal = $delhiveryWithGST;

                    $enhancedData[] = [
                        'name' => "Delhivery B2C ($serviceName)",
                        // 'freight_charges' => round($delhiveryWithSeller, 2),
                        // 'cod_charges' => round($codChargeSurface, 2),
                        'total_charges' => round($delhiveryTotal, 2),
                        // 'min_weight' => $weight,
                        'chargeable_weight' => $weight,
                    ];
                } else {
                    // Log::error("Delhivery $serviceName Rate API Error", ['response' => $resp->body()]);
                }
            }
        } catch (\Exception $e) {
            // Log::error("Delhivery API Exception", ['message' => $e->getMessage()]);
        }
        // === Delhivery B2C Surface ===

        // === Boxd ===
        try {
            $url = 'https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_rate_time';
            $paymentMode = ($paymentType === 'cod') ? 'cod' : 'prepaid';
            $requestPayload = [
                'from_postal_code' => $originPin,
                'from_country_code' => 'IN',
                'to_postal_code' => $destinationPin,
                'to_country_code' => 'IN',
                'weight' => $weight / 1000, // Convert grams to kilograms
                'length' => $request->length,
                'height' => $request->height,
                'width' => $request->breadth,
                'parcel_type' => 'Parcel',
                'mode' => 'Domestic',
                'payment_mode' => 'prepaid'
            ];
            $response = Http::withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'application/json',
                'secretkey' => 'POVHFT',
                'customerid' => 'c1754533690129',
            ])->post($url, $requestPayload);
            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['status'] && !empty($responseData['rate_list'])) {
                    $allowedProductIds = [
                        '1750498184844', // BDS - Bluedart Air
                        '1746709645240', // Dship - Bluedart Surface 0.5 kg
                    ];
                    if ($weight < 250) {
                        $allowedProductIds[] = '1753163038641'; // Delhivery Surface
                    }
                    $filteredRates = collect($responseData['rate_list'])->filter(function ($rate) use ($allowedProductIds) {
                        return in_array($rate['product_id'], $allowedProductIds);
                    });
                    foreach ($filteredRates as $rate) {
                        $logisticProvider = 'Boxd_' . $rate['product_id'];
                        $PriceSettingBoxd = PriceSetting::where(['seller_id' => $sellerId, 'LogisticProvider' => $logisticProvider])->first();
                        if (!$PriceSettingBoxd) {
                            $PriceSettingBoxd = PriceSetting::where(['seller_id' => $sellerId, 'LogisticProvider' => 'Boxd'])->first();
                        }

                        $sellerPercentageBoxd = $PriceSettingBoxd ? $PriceSettingBoxd->shipping_charge : 70;
                        $codChargePercentBoxd = $PriceSettingBoxd ? $PriceSettingBoxd->cod_charge_parsent : 1.9;
                        $codChargeFixedBoxd = $PriceSettingBoxd ? $PriceSettingBoxd->cod_charge : 32;

                        $codChargeBoxd = 0;
                        if ($paymentType === 'cod') {
                            $codChargeBoxd = $orderAmount > 1400 ? ($orderAmount * $codChargePercentBoxd / 100) : $codChargeFixedBoxd;
                        }

                        $baseCharge = $rate['total_charges'] * 1.10;

                        $chargeWithSeller = $baseCharge + ($baseCharge * $sellerPercentageBoxd / 100);
                        $chargeWithCod = $chargeWithSeller + $codChargeBoxd;
                        $finalCharge = $chargeWithCod + ($chargeWithCod * 18 / 100); // GST

                        $enhancedData[] = [
                            'name' => $rate['service_provider'],
                            // 'freight_charges' => round($chargeWithSeller, 2),
                            // 'cod_charges' => round($codChargeBoxd, 2),
                            'total_charges' => round($finalCharge, 2),
                            // 'min_weight' => $weight,
                            'chargeable_weight' => $weight,
                            // 'product_id' => $rate['product_id'],
                            // 'carrier_id' => $rate['carrier_id'] ?? '',
                            // 'zone_name' => $rate['zone_name'] ?? ''
                        ];
                    }
                } else {
                    echo "DEBUG: Boxd API response status false or empty rate_list\n";
                }
            } else {
                echo "DEBUG: Boxd API call failed - Status: " . $response->status() . "\n";
            }
        } catch (\Exception $e) {

        }

        // ==================== Ekart2KG Zone Based Pricing ====================
        try {
            // Check if Ekart 2KG zone pricing exists for this seller
            $ekartZoneCheck = ZonePriceSetting::where([
                'seller_id' => $sellerId,
                'LogisticProvider' => 'Ekart2KG_selloship',
                'status' => 1
            ])->exists();

            if ($ekartZoneCheck) {
                $pickupState = $request->originState;
                $destinationState = $request->destinationState;

                // Zone Fetch (Case-Insensitive)
                $zoneData = DB::table('pincode_zones')
                    ->whereRaw('LOWER(pickup_state) = ?', [strtolower($pickupState)])
                    ->whereRaw('LOWER(deliver_state) = ?', [strtolower($destinationState)])
                    ->first();

                if ($zoneData) {
                    $zone = strtoupper($zoneData->zone);

                    // Get Ekart 2KG Zone Pricing
                    $ekartZonePricing = ZonePriceSetting::where([
                        'seller_id' => $sellerId,
                        'zone' => $zone,
                        'LogisticProvider' => 'Ekart2KG_selloship',
                        'status' => 1
                    ])->first();

                    if ($ekartZonePricing) {
                        $ekartPaymentType = strtolower($paymentType) === 'cod' ? 'cod' : 'prepaid';

                        // Calculate Ekart 2KG charges based on ZonePriceSetting
                        if ($ekartPaymentType === 'cod') {
                            // COD Order Logic
                            if ($ekartZonePricing->cod_fix_price > 0) {
                                // Use fixed COD price - NO GST, NO other charges
                                $totalPrice = $ekartZonePricing->cod_fix_price;
                                $ekartTotal = $totalPrice;
                            } else {
                                // Use variable COD price + 18% GST
                                $basePrice = $ekartZonePricing->cod_price;
                                $ekartTotal = $basePrice + ($basePrice * 18 / 100);
                            }
                        } else {
                            // Prepaid Order Logic
                            if ($ekartZonePricing->prepaid_fix_price > 0) {
                                // Use fixed Prepaid price - NO GST, NO other charges
                                $totalPrice = $ekartZonePricing->prepaid_fix_price;
                                $ekartTotal = $totalPrice;
                            } else {
                                // Use variable Prepaid price + 18% GST
                                $basePrice = $ekartZonePricing->prepaid_price;
                                $ekartTotal = $basePrice + ($basePrice * 18 / 100);
                            }
                        }

                        $enhancedData[] = [
                            'name' => 'Ekart2KG',
                            'total_charges' => round($ekartTotal, 2),
                            'chargeable_weight' => $weight,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error if needed
        }
        // ==================== Ekart2KG Zone Based Pricing ====================

        // ==================== Tekipost / Delhivery / Boxd ====================
        // Keep all your existing logic here as-is

        return response()->json([
            'success' => true,
            'message' => 'Rate fetched successfully',
            'data' => $enhancedData,
        ]);
    }

    public function getServiceability(array $params): array
    {
        // dd($params);
        $order = Order::findOrFail($params['order_id']);
        $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
        $pincodeToCheck = $consignee['pincode'];
        $destinationstate = $consignee['state'];

            // Check if Ekart 2KG zone pricing exists for this seller
            // If no data exists, skip this service
            $ekartZoneCheck = ZonePriceSetting::where([
                'seller_id' => $order->seller_id,
                'LogisticProvider' => 'Ekart2KG_selloship',
                'status' => 1
            ])->exists();

            if (!$ekartZoneCheck) {
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
    // dd($order->payment_type);
        // 🧾 Step 3: Get Ekart 2KG Zone Pricing
        $ekartZonePricing = ZonePriceSetting::where([
            'seller_id' => $seller_id,
            'zone' => $zone,
            'LogisticProvider' => 'Ekart2KG_selloship',
            'status' => 1
        ])->first();

        if (!$ekartZonePricing) {
            return [];
        }

        // 🧾 Step 4: Calculate Ekart 2KG charges based on ZonePriceSetting
        $calculateEkartCharge = function () use ($ekartZonePricing, $weight, $paymentType, $orderAmount) {
            
            if ($paymentType === 'cod') {
                // COD Order Logic
                if ($ekartZonePricing->cod_fix_price > 0) {
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
                    $totalWithGST = $basePrice + ($basePrice * 18 / 100);
                    return [
                        'courierCharge'  => round($totalWithGST, 2),
                        'freightCharges' => round($basePrice, 2),
                        'codCharge'      => 0,
                    ];
                }
            } else {
                // Prepaid Order Logic
                if ($ekartZonePricing->prepaid_fix_price > 0) {
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
            'serviceabilityId' => $pincodeToCheck,
            'courierName'      => 'selloshipEkart2KG',
            'courierCharge'    => $charges['courierCharge'],
            'freightCharges'   => $charges['freightCharges'],
            'codCharge'        => $charges['codCharge'],
            'zone'             => $zone,
            'minWeight'        => $weight,
            'volWeight'        => $weight,
        ]];
    }


}



