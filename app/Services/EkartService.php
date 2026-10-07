<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\PriceSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


use App\Models\ZonePriceSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ActicvSleb;
class EkartService implements CourierServiceInterface
{



    private $baseUrl = 'https://staging.ekartlogistics.com'; // staging token endpoint
private $merchantCode = 'SPX';
private $authBasic = 'c2hpcHhwZWVkc3B4OmR1bW15S2V5';

public function generateBearerToken()
{
    $cacheKey = 'ekart_bearer_token';

    // Return cached token when available
    if ($cached = Cache::get($cacheKey)) {
        return $cached;
    }

    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'HTTP_X_MERCHANT_CODE' => $this->merchantCode,
            'Authorization' => 'Basic ' . $this->authBasic,
        ])->post($this->baseUrl . '/auth/token');

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['Authorization'])) {
                $token = $data['Authorization'];

                // Try to parse JWT expiry and cache accordingly
                $ttl = 3000; // default fallback
                try {
                    if (strpos($token, 'Bearer ') === 0) {
                        $jwt = trim(substr($token, 7));
                        $parts = explode('.', $jwt);
                        if (count($parts) === 3) {
                            $payload = $parts[1];
                            $padding = 4 - (strlen($payload) % 4);
                            if ($padding < 4) {
                                $payload .= str_repeat('=', $padding);
                            }
                            $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
                            if (!empty($decoded['exp'])) {
                                $expiresAt = (int) $decoded['exp'];
                                $ttl = $expiresAt - time() - 60; // keep 1 minute buffer
                            }
                        }
                    }
                } catch (\Exception $ex) {
                    Log::warning('Ekart token parse warning', ['error' => $ex->getMessage()]);
                }

                if ($ttl <= 0) {
                    $ttl = 3000;
                }

                Cache::put($cacheKey, $token, $ttl);
                return $token;
            }

            Log::error('Ekart API Token Key Missing in Response', ['response' => $data]);
        } else {
            Log::error('Ekart API Token Generation Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Ekart API Token Generation Error', ['message' => $e->getMessage()]);
    }

    return null;
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
        $ekartZoneCheck = ActicvSleb::where([
            'seller_id' => $order->seller_id,
            'LogisticProvider' => 'Ekart500gm',
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
    // dd($paymentType);
// dd($order->payment_type);
    // 🧾 Step 3: Get Ekart 2KG Zone Pricing
    $ekartZonePricing = ZonePriceSetting::where([
        'seller_id' => $seller_id,
        'zone' => $zone,
        'LogisticProvider' => 'Ekart500gm',
        'status' => 1
    ])->first();
// dd($ekartZonePricing);
    if (!$ekartZonePricing) {
        // return [];
    $ekartZonePricing = ZonePriceSetting::where([
    'seller_id' => 14,
    'zone' => $zone,
    'LogisticProvider' => 'Ekart500gm',
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
    

    return [[
        'serviceabilityId' => $pincodeToCheck,
        'courierName'      => 'Ekart500gm',
        'courierCharge'    => $charges['courierCharge'],
        'freightCharges'   => $charges['freightCharges'],
        'codCharge'        => $charges['codCharge'],
        'zone'             => $zone,
        'zone_courier_name' => 'Ekart500gm',
        'minWeight'        => $weight,
        'volWeight'        => $weight,
    ]];
}















public function assignOrder($params)
{
    $token = $this->generateBearerToken();
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return ['status' => false, 'message' => 'Unauthorized or inactive seller.'];
    }

    if (!$token) {
        return ['status' => false, 'message' => 'Failed to generate authentication token.'];
    }

    $credit = Recharge::where('seller_id', $seller->id)->where('status', 1)->where('type', 'Credit')->sum('amount');
    $debit  = Recharge::where('seller_id', $seller->id)->where('type', 'Debit')->sum('amount');
    $walletBalance = $credit - $debit;

    $order = Order::find($params);
    if (!$order) {
        return ['status' => false, 'message' => 'Order not found.'];
    }

    if ($walletBalance < $order->seller_amount_walate) {
        return ['status' => false, 'message' => 'Insufficient wallet balance.'];
    }

    $productList = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;
    $product     = $productList[0];

    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
    $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;

    // === Step 1: Build Ekart v2 payload ===
    $payload = [
        "client_name" => "SPX",
        "goods_category" => "ESSENTIAL",
        "services" => [
            [
                "service_code" => "ECONOMY",
                "service_details" => [
                    [
                        "service_leg" => "FORWARD",
                        "service_data" => [
                            "service_types" => [
                                [
                                    "name" => "regional_handover",
                                    "value" => "true"
                                ],
                                [
                                    "name" => "delayed_dispatch",
                                    "value" => "false"
                                ]
                            ],
                            "vendor_name" => "Ekart",
                            "amount_to_collect" => $order->payment_type === 'cod' ? (string)$order->collectable_amount : "0",
                            "dispatch_date" => now()->format('Y-m-d H:i:s'),
                            "customer_promise_date" => null,
                            "delivery_type" => "SMALL",
                            "source" => [
                                "address" => [
                                    "first_name" => $pickup['name'] ?? $seller->name ?? "Seller",
                                    "address_line1" => $pickup['address_1'] ?? '',
                                    "address_line2" => $pickup['address_2'] ?? '',
                                    "pincode" => $pickup['pincode'] ?? $seller->pickup_pincode ?? '110001',
                                    "city" => $pickup['city'] ?? '',
                                    "state" => $pickup['state'] ?? '',
                                    "primary_contact_number" => $pickup['phone'] ?? $seller->phone ?? '9999999999',
                                    "email_id" => $pickup['email'] ?? $seller->email ?? 'seller@example.com'
                                ]
                            ],
                            "destination" => [
                                "address" => [
                                    "first_name" => $consignee['name'],
                                    "address_line1" => $consignee['address_1'] ?? $consignee['address_2'] ?? '',
                                    "address_line2" => $consignee['address_2'] ?? '',
                                    "pincode" => $consignee['pincode'],
                                    "city" => $consignee['city'] ?? '',
                                    "state" => $consignee['state'] ?? '',
                                    "primary_contact_number" => $consignee['phone'],
                                    "email_id" => $consignee['email'] ?? 'customer@example.com'
                                ]
                            ],
                            "return_location" => [
                                "address" => [
                                    "first_name" => $pickup['name'] ?? $seller->name ?? "Seller",
                                    "address_line1" => $pickup['address_1'] ?? '',
                                    "address_line2" => $pickup['address_2'] ?? '',
                                    "pincode" => $pickup['pincode'] ?? $seller->pickup_pincode ?? '110001',
                                    "city" => $pickup['city'] ?? '',
                                    "state" => $pickup['state'] ?? '',
                                    "primary_contact_number" => $pickup['phone'] ?? $seller->phone ?? '9999999999',
                                    "email_id" => $pickup['email'] ?? $seller->email ?? 'seller@example.com'
                                ]
                            ]
                        ],
                        "shipment" => [
                            "client_reference_id" => $order->order_number,
                            "tracking_id" => $order->order_number,
                            "shipment_value" => (float)$order->collectable_amount,
                            "shipment_dimensions" => [
                                "length" => [
                                    "value" => (float)($order->package_length ?? 15)
                                ],
                                "breadth" => [
                                    "value" => (float)($order->package_breadth ?? 10)
                                ],
                                "height" => [
                                    "value" => (float)($order->package_height ?? 10)
                                ],
                                "weight" => [
                                    "value" => (float)($order->package_weight ?? 0.4) / 1000 // convert grams to kg
                                ]
                            ],
                            "return_label_desc_1" => null,
                            "return_label_desc_2" => null,
                            "shipment_items" => [
                                [
                                    "product_id" => (string)$order->id,
                                    "category" => null,
                                    "product_title" => $product['name'],
                                    "quantity" => (int)$product['qty'],
                                    "cost" => [
                                        "total_sale_value" => (float)$order->collectable_amount,
                                        "total_tax_value" => 0.0,
                                        "tax_breakup" => [
                                            "cgst" => 0.0,
                                            "sgst" => 0.0,
                                            "igst" => 0.0
                                        ]
                                    ],
                                    "seller_details" => [
                                        "seller_reg_name" => $seller->name ?? "Seller",
                                        "gstin_id" => null
                                    ],
                                    "hsn" => $product['sku'] ?? "",
                                    "ern" => null,
                                    "discount" => null,
                                    "item_attributes" => [
                                        [
                                            "name" => "order_id",
                                            "value" => (string)$order->id
                                        ],
                                        [
                                            "name" => "invoice_id",
                                            "value" => "INV-" . $order->id
                                        ]
                                    ],
                                    "handling_attributes" => [
                                        [
                                            "name" => "isFragile",
                                            "value" => "false"
                                        ],
                                        [
                                            "name" => "isDangerous",
                                            "value" => "false"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    try {
        $response = Http::withHeaders([
            'Authorization' => $token, // generateBearerToken already returns 'Bearer xxxx'
            'HTTP_X_MERCHANT_CODE' => 'SPX',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/v2/shipments/create', $payload);

        $responseData = $response->json();

        // === Handle success ===
        if ($response->successful() && !empty($responseData['response']) && !empty($responseData['response'][0]['tracking_id'])) {
            $trackingId = $responseData['response'][0]['tracking_id'];
            
            $order->awb_number = $trackingId;
            $order->courier_id = 'ekart';
            $order->smartship_tracking_url = $responseData['response'][0]['shipment_payment_link'] ?? null;
            $order->save();

            Recharge::create([
                'seller_id' => $seller->id,
                'type'      => 'Debit',
                'amount'    => $order->seller_amount_walate,
                'status'    => 1,
            ]);

            return [
                'status'      => true,
                'message'     => 'Order assigned to Ekart successfully.',
                'couriername' => 'ekart',
                'awb_number'  => $trackingId,
                'request_id'  => $responseData['request_id'] ?? null,
            ];
        }

        Log::error('Ekart v2 API Assignment Failed', [
            'status' => $response->status(),
            'response' => $responseData
        ]);

        return [
            'status' => false,
            'message' => 'Ekart API error: ' . ($responseData['message'] ?? 'Unknown error'),
            'data' => $responseData
        ];
    } catch (\Exception $e) {
        Log::error('Ekart v2 API Request Exception', [
            'message' => $e->getMessage(),
            'order_id' => $order->id
        ]);
        
        return [
            'status' => false,
            'message' => 'API Request Failed: ' . $e->getMessage()
        ];
    }
}




public function cancelShipment(string $awb): array
{
    $token = $this->getToken(); // Get eKart token
    $seller = Auth::guard('seller')->user();

    $order = Order::where('awb_number', $awb)->first();
    if (!$order) {
        return ['status' => false, 'message' => 'Order not found.'];
    }

    // Only REVERSE orders are allowed
    if ($order->shipment_type !== 'REVERSE') {
        return ['status' => false, 'message' => 'Cancel RVP only allowed for REVERSE shipments.'];
    }

    $payload = [
        'merchant_reference_id' => $order->order_number,
        'tracking_id'           => $order->awb_number,
        'reason'                => 'Customer requested cancellation',
        'request_id'            => (string) Str::uuid(),
    ];

    $response = Http::withHeaders([
        'Authorization'           => "Bearer {$token}",
        'HTTP_X_MERCHANT_CODE'    => 'SPX', // Replace with your actual code
        'Content-Type'            => 'application/json',
        'Accept'                  => 'application/json',
    ])->put('https://api.ekartlogistics.com/v3/shipments/cancel_rvp', $payload);

    $responseData = $response->json();
    \Log::info('Ekart Cancel RVP Response:', $responseData);

    if (!$response->successful()) {
        return [
            'status'  => false,
            'message' => 'Ekart Cancel RVP API error',
            'code'    => $response->status(),
            'data'    => $responseData,
        ];
    }

    // Update local DB
    $order->order_status = 'cancelled';
    $order->save();

    Recharge::create([
        'seller_id' => $seller->id,
        'type'      => 'Credit',
        'amount'    => $order->seller_amount_walate,
        'status'    => 1,
    ]);

    return [
        'status'      => true,
        'message'     => 'Reverse shipment cancelled successfully on Ekart.',
        'request_id'  => $payload['request_id'],
        'awb_number'  => $order->awb_number,
    ];
}



public function fetchNdrData($params)
{
    try {
        $client = new \GuzzleHttp\Client();
        // $token = $this->fetchAuthToken();

        $response = $client->post('https://shipment.xpressbees.com/api/ndr', [
            'verify'  => false,
            'headers' => [
                'Authorization' => 'Bearer ' . 'asasxaxs',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => [], // ✅ Use this instead of 'body' => ''
        ]);

        $data = json_decode($response->getBody(), true);
        // dd($data);

        if (!empty($data['status']) && $data['status'] === true) {
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




}