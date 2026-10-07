<?php

namespace App\Http\Controllers\Shipxpeedapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\PriceSetting;
use App\Models\SellerList;
use App\Models\Order;
use App\Models\Recharge;

class CancelshipmentController extends Controller
{
    /**
     * Unified API to cancel shipment by AWB number
     * Automatically detects courier service and cancels accordingly
     */
    public function cancelByAwb(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'awb' => 'required|string|min:5|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $awb = $request->awb;

        try {
            // Find the order by AWB number
            $order = \App\Models\Order::where('awb_number', $awb)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found with AWB: ' . $awb,
                    'awb' => $awb
                ], 404);
            }

            // Get seller_id from order table
            $sellerId = $order->seller_id;

            // Check if order is already cancelled
            if ($order->order_status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already cancelled',
                    'awb' => $awb,
                    'current_status' => $order->order_status
                ], 400);
            }

            // Get courier service from courier_id
            $courier = $this->detectCourierService($order->courier_id);

            if (!$courier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to detect courier service for this AWB',
                    'awb' => $awb,
                    'courier_id' => $order->all_courier_name
                ], 400);
            }

            // Cancel shipment using appropriate courier service
            $result = $this->cancelShipmentByCourier($courier, $awb, $order, $sellerId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Shipment cancelled successfully',
                    'awb' => $awb,
                    // 'courier' => $courier,
                    'data' => $result['data'] ?? []
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to cancel shipment',
                    'awb' => $awb,
                    // 'courier' => $courier,
                    'error_details' => $result['error_details'] ?? []
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Cancel AWB Error: ' . $e->getMessage(), [
                'awb' => $awb,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'awb' => $awb
            ], 500);
        }
    }

    /**
     * Detect courier service based on courier_id
     */
    private function detectCourierService($courierId)
    {
        // Map courier_id to courier service names
        $courierMappings = [
            'delhivery' => 'delhivery_b2c',
            'delhivery_air' => 'delhivery_b2c',
            'delhivery_b2c' => 'delhivery_b2c',
            'delhivery_250gms' => 'boxd',
            'delhivery_5kg' => 'tekipost',
            'delhivery_10kg' => 'tekipost',
            'tekipost' => 'tekipost',
            'amazon_2kg' => 'tekipost',
            'amazon_0_5kg' => 'tekipost',
            'dtdc' => 'dtdc',
            'dtdc_air' => 'dtdc',
            'dtdc_surface_500gm' => 'dtdc',
            'dtdc_surface_1kg' => 'dtdc',
            'boxd' => 'boxd',
            'bluedart_surface_500gms' => 'boxd',
            'bluedart_air_500gms' => 'boxd',
            'smartship' => 'smartship',
            'parcelx' => 'parcelx',
            'parcelx' => 'parcelx',
            'shiprocket' => 'shiprocket',
            'selloship' => 'selloship',
            'sell_Ekart_2KG' => 'selloship'


        ];

        return $courierMappings[$courierId] ?? null;
    }

    /**
     * Cancel shipment using appropriate courier service
     */
    private function cancelShipmentByCourier($courier, $awb, $order, $sellerId)
    {
        switch ($courier) {
            case 'delhivery_b2c':
                return $this->cancelDelhiveryShipment($awb, $order, $sellerId);

                            case 'selloship':
                return $this->cancelSelloshipShipment($awb, $order, $sellerId);

            case 'shiprocket':
                return $this->cancelShiprocketShipment($awb, $order, $sellerId);
            
            case 'tekipost':
                return $this->cancelTekipostShipment($awb, $order, $sellerId);

                case 'parcelx':
                return $this->cancelparcelxShipment($awb, $order, $sellerId);
            
            case 'dtdc':
                return $this->cancelDtdcShipment($awb, $order, $sellerId);
            
            case 'boxd':
                return $this->cancelBoxdShipment($awb, $order, $sellerId);
            
            case 'smartship':
                return $this->cancelSmartshipShipment($awb, $order, $sellerId);
            
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported courier service: ' . $awb
                ];
        }
    }







    /**
     * Cancel parcelx shipment
     */
  private function cancelparcelxShipment($awb, $order, $sellerId)
{
    try {
        // ParcelX API URL for cancellation
        $url = "https://app.parcelx.in/api/v3/order/cancel_order";

        // Prepare Payload
        $payload = [
            "awb" => $awb
        ];

        // API Request to ParcelX
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'access-token'  => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
        ])->post($url, $payload);

        $responseData = $response->json();
        //    dd($responseData);
        // ✅ Success case (ParcelX uses 'status' key)
        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {

            // Update Order
            $order->order_status = 'cancelled';
            $order->save();

            // Refund to wallet
            Recharge::create([
                'seller_id'   => $sellerId,
                'type'        => 'Credit',
                'amount'      => $order->seller_amount_walate,
                'status'      => 1,
                'description' => 'Order cancelled - AWB: ' . $awb,
            ]);

            return [
                'success' => true,
                'message' => 'shipment cancelled successfully.',
                'awb_number' => $awb,
                'data' => $responseData,
            ];
        }

        // ❌ Failure case
        return [
            'success' => false,
            'message' => $responseData['message'] ?? 'ParcelX cancellation failed.',
            'error_details' => $responseData,
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'ParcelX API error: ' . $e->getMessage(),
        ];
    }
}





    /**
     * Cancel Selloship shipment
     */
    private function cancelSelloshipShipment($awb, $order, $sellerId)
    {
        try {
            // NEW Waybill Cancellation API URL (update base URL)
            $url = "https://selloship.com/api/lock_actvs/channels/cancel";

            // Your API Token
            $apiToken = $this->fetchAuthToken();

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found for AWB: ' . $awb,
                ];
            }

            // New payload
            $payload = [
                "waybill" => $awb
            ];

            // Make POST request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $apiToken,
            ])->post($url, $payload);

            $responseData = $response->json();

            // Check for SUCCESS
            if ($response->successful() && ($responseData['status'] ?? '') === 'SUCCESS') {

                // Update order
                $order->order_status = 'cancelled';
                $order->save();

                // Refund seller
                Recharge::create([
                    'seller_id'   => $sellerId,
                    'type'        => 'Credit',
                    'amount'      => $order->seller_amount_walate,
                    'status'      => 1,
                    'description' => 'Order cancelled',
                ]);

                return [
                    'success' => true,
                    'message' => $responseData['errorMessage'] ?? 'Selloship shipment cancelled successfully.',
                    'data' => $responseData,
                ];
            }

            // Failure scenario
            return [
                'success' => false,
                'message' => $responseData['errorMessage'] ?? 'Failed to cancel Selloship shipment.',
                'error_details' => $responseData,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Selloship API Request Failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel Delhivery shipment
     */

    
    private function cancelDelhiveryShipment($awb, $order, $sellerId)
    {
        try {
            $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';

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
                    'seller_id'   => $sellerId,
                    'type'        => 'Credit',
                    'amount'      => $order->seller_amount_walate,
                    'status'      => 1,
                    'description' => 'Delhivery Order cancelled',
                ]);

                return [
                    'success' => true,
                    'message' => 'Delhivery shipment cancelled successfully',
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['error'] ?? 'Delhivery cancellation failed',
                'error_details' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Delhivery API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel Shiprocket shipment
     */
    private function cancelShiprocketShipment($awb, $order, $sellerId)
    {
        try {
            // Get Shiprocket auth token
            $authResponse = $this->shiprocketLogin();
            if (!isset($authResponse['token'])) {
                return [
                    'success' => false,
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
        //  dd($response->json());
            if ($response->successful()) {
                $responseData = $response->json();
                      
                // Check if the cancellation was successful based on the message
                if ($response->status() == 200 && 
                    isset($responseData['message']) && 
                    $responseData['message'] === "Shipment(s) have been cancelled") {
                    $order->order_status = 'cancelled';
                    $order->save();

                    // Credit back to seller wallet
                    Recharge::create([
                        'seller_id' => $sellerId,
                        'type' => 'Credit',
                        'amount' => $order->seller_amount_walate,
                        'status' => 1,
                        'description' => 'Shiprocket Order cancelled',
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Shipment cancelled successfully',
                        'data' => $responseData
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => $responseData['message'] ?? 'Shiprocket cancellation failed',
                        'error_details' => $responseData
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'API request failed',
                'error_details' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel Tekipost shipment
     */
    private function cancelTekipostShipment($awb, $order, $sellerId)
    {
        try {
            $token = $this->getTekipostToken();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type'  => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://app.tekipost.com/api-delete-order', [
                "awb_no" => $awb
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                $order->order_status = 'cancelled';
                $order->save();

                Recharge::create([
                    'seller_id' => $sellerId,
                    'type'      => 'Credit',
                    'amount'    => $order->seller_amount_walate,
                    'status'    => 1,
                    'description' => 'Tekipost Order cancelled',
                ]);

                return [
                    'success' => true,
                    'message' => $responseData['message'] ?? 'Tekipost order deleted successfully',
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Tekipost cancellation failed',
                'error_details' => $responseData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Tekipost API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel DTDC shipment
     */
    private function cancelDtdcShipment($awb, $order, $sellerId)
    {
        try {
            $url = "http://pxapi.dtdc.in/api/customer/integration/consignment/cancel";
            $apiKey = "a74cd3ae095ad603dc6d506fb30dcf";
            $customerCode = "GL11173";

            $payload = [
                "AWBNo" => [$awb],
                "customerCode" => $customerCode
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ])->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful() && ($responseData['status'] ?? '') === 'OK' && ($responseData['success'] ?? false)) {
                $consignment = $responseData['successConsignments'][0] ?? null;

                if ($consignment['success'] ?? false) {
                    $order->order_status = 'cancelled';
                    $order->save();

                    Recharge::create([
                        'seller_id'   => $sellerId,
                        'type'        => 'Credit',
                        'amount'      => $order->seller_amount_walate,
                        'status'      => 1,
                        'description' => 'DTDC Order cancelled',
                    ]);

                    return [
                        'success' => true,
                        'message' => 'DTDC shipment cancelled successfully',
                        'data' => $responseData
                    ];
                }
            }

            return [
                'success' => false,
                'message' => $responseData['error'] ?? 'DTDC cancellation failed',
                'error_details' => $responseData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'DTDC API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel Boxd shipment
     */
    private function cancelBoxdShipment($awb, $order, $sellerId)
    {
        try {

            $response = Http::withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'secretkey' => 'POVHFT',
                'customerid' => 'c1754533690129',
            ])->asForm()->post('https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_cancel', [
                'awb_number' => $awb,
                'cancel_reason' => 'Order cancellation requested by seller'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['status']) && $responseData['status'] == true) {
                    $order->order_status = 'cancelled';
                    $order->save();

                    Recharge::create([
                        'seller_id' => $sellerId,
                        'type' => 'Credit',
                        'amount' => $order->seller_amount_walate,
                        'status' => 1,
                        'description' => 'Boxd Order cancelled',
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Boxd shipment cancelled successfully',
                        'data' => $responseData
                    ];
                }

                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Boxd cancellation failed',
                    'error_details' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => 'Boxd API request failed',
                'error_details' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Boxd API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel SmartShip shipment
     */
    private function cancelSmartshipShipment($awb, $order, $sellerId)
    {
        try {

            // Use the service container to get SmartShip service
            $serviceKey = "courier.smartship";
            if (!app()->bound($serviceKey)) {
                return [
                    'success' => false,
                    'message' => 'SmartShip service not available'
                ];
            }

            $result = app($serviceKey)->cancelShipment($awb);

            if ($result && isset($result['status']) && $result['status'] === 1) {
                $order->order_status = 'cancelled';
                $order->save();

                Recharge::create([
                    'seller_id' => $sellerId,
                    'type' => 'Credit',
                    'amount' => $order->seller_amount_walate,
                    'status' => 1,
                    'description' => 'SmartShip Order cancelled',
                ]);

                return [
                    'success' => true,
                    'message' => 'SmartShip shipment cancelled successfully',
                    'data' => $result
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'SmartShip cancellation failed',
                'error_details' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SmartShip API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get Tekipost API token
     */




public static function getTekipostToken(): ?string
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
     * Get Selloship API authentication token
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


    
       public function cancelAwb(Request $request)
    {
        // dd($request);
        $data = $request->validate([
            'courier' => 'required|string',
            'awb'     => 'required|string',
        ]);

    // Handle Delhivery courier variations
    if (in_array($data['courier'], ['delhivery', 'delhivery_air', 'delhivery_b2c'])) {
        $data['courier'] = 'delhivery_b2c';
    }
        if (in_array($data['courier'], ['tekipost', 'amazon_2kg', 'delhivery_5kg','amazon_0_5kg','delhivery_10kg'])) {
        $data['courier'] = 'tekipost';
        }
        if (in_array($data['courier'], ['dtdc', 'dtdc_air', 'dtdc_surface_500gm','dtdc_surface_1kg'])) {
                $data['courier'] = 'dtdc';
            }

        if (in_array($data['courier'], ['boxd', 'delhivery_250gms', 'bluedart_surface_500gms','bluedart_air_500gms'])) {
                $data['courier'] = 'boxd';
            }

    $serviceKey = "courier.{$data['courier']}";

    if (! app()->bound($serviceKey)) {
        return redirect()->back()->with('error', 'Unknown courier');
    }
 
    $result = app($serviceKey)->cancelShipment($data['awb']);
    //   return $result; // For debugging, you can remove this later

  if ($request->courier === 'smartship') {
        if ($result && isset($result['status']) && $result['status'] === 1) {
            return redirect()->route('seller.courier.Assigned')->with('success', 'SmartShip courier cancelled successfully! AWB: ' . $data['awb']);
        }

        return redirect()->route('seller.courier.Assigned')->with('error', 'SmartShip cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
    }



if ($request->courier === 'boxd') {
    if ($result && isset($result['status']) && $result['status'] === true) {
        return redirect()->route('seller.courier.Assigned')
            ->with('success', 'Boxd courier cancelled successfully! Response Code: ' . ($result['responseCode'] ?? 'N/A'));
    }

    return redirect()->route('seller.courier.Assigned')
        ->with('error', 'Boxd cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
}



if ($request->courier === 'tekipost') {
    if ($result && isset($result['success']) && $result['success'] === true) {
        return redirect()->route('seller.courier.Assigned')
            ->with('success', 'Tekipost courier cancelled successfully! Order ID: ' . ($result['order_id'] ?? 'N/A'));
    }

    return redirect()->route('seller.courier.Assigned')
        ->with('error', 'Tekipost cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
}



  if ($request->courier === 'smartship') {
        if ($result && isset($result['status']) && $result['status'] === 1) {
            return redirect()->route('seller.courier.Assigned')->with('success', 'SmartShip courier cancelled successfully! AWB: ' . $data['awb']);
        }

        return redirect()->route('seller.courier.Assigned')->with('error', 'SmartShip cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
    }


    if ($result && (
        (isset($result['status']) && $result['status'] === true) || 
        (isset($result['responseCode']) && $result['responseCode'] == 200)
        )){

        $order = \App\Models\Order::where('awb_number', $data['awb'])->first();
        if ($order) {
            $order->order_status = 'cancelled';
            $order->save();
        }
        return redirect()->route('seller.courier.Assigned')->with('success', 'Courier cancelled successfully! AWB: ' . $data['awb']);
    }

    return redirect()->route('seller.courier.Assigned')->with('error', 'Courier cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
}


//     public function cancelShipment($awb)
//     {
//         // dd($awb);
//         $seller = Auth::guard('seller')->user();
//         $order = Order::where('awb_number', $awb)->first();

//         if (!$order) {
//             return [
//                 'status' => false,
//                 'message' => 'Order not found with AWB: ' . $awb,
//             ];
//         }

//         $response = Http::withHeaders([
//             'Access-Control-Allow-Origin' => '*',
//             'Content-Type' => 'application/x-www-form-urlencoded',
//             'secretkey' => 'POVHFT',
//             'customerid' => 'c1754533690129',
//         ])->asForm()->post('https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_cancel', [
//             'awb_number' => $awb,
//             'cancel_reason' => 'Order cancellation requested by seller'
//         ]);
//         // dd($response->json());

//         if ($response->successful()) {
//             $responseData = $response->json();

//             // Check if the cancellation was successful in the response
//             if (isset($responseData['status']) && $responseData['status'] == true) {
//                 $order->order_status = 'cancelled';
//                 $order->save();

//                 // Credit back to seller wallet
//                 Recharge::create([
//                     'seller_id' => $seller->id,
//                     'type' => 'Credit',
//                     'amount' => $order->seller_amount_walate,
//                     'status' => 1,
//                 ]);

//                 return [
//                     'status' => true,
//                     'message' => 'Shipment cancelled successfully',
//                     'data' => $responseData,
//                     'responseCode' => $response->status()
//                 ];
//             } else {
//                 return [
//                     'status' => false,
//                     'message' => $responseData['message'] ?? 'Cancellation failed',
//                     'data' => $responseData,
//                     'responseCode' => $response->status()
//                 ];
//             }
//         }

//         return [
//             'status' => false,
//             'message' => 'API request failed',
//             'responseCode' => $response->status(),
//             'error' => $response->body()
//         ];
//     }


    
//     public function cancelShipment(string $awb): array
//     {
//         $token = $this->getToken();
//         $order = Order::where('awb_number', $awb)->first();

//         if (!$order) {
//             return [
//                 'error' => 'Order not found',
//                 'status' => 404,
//                 'message' => 'Order with AWB number not found',
//             ];
//         }

//         $url = 'https://app.tekipost.com/api-delete-order';

//         $payload = [
//             "awb_no" => $awb
//         ];

//         $response = Http::withHeaders([
//             'Authorization' => "Bearer {$token}",
//             'Content-Type'  => 'application/json',
//             'Accept' => 'application/json',
//         ])->post($url, $payload);

//         $responseData = $response->json();
//     //   dd($responseData);
//         if (
//             !$response->successful() ||
//             !isset($responseData['success']) ||
//             $responseData['success'] !== true
//         ) {
//             return [
//                 'error'   => 'Tekipost Delete API error',
//                 'status'  => $response->status(),
//                 'message' => $responseData['message'] ?? 'Unknown error',
//             ];
//         }

//         // Update local DB
//         $seller = Auth::guard('seller')->user();
//         $order->order_status = 'cancelled';
//         $order->save();

//         Recharge::create([
//             'seller_id' => $seller->id,
//             'type'      => 'Credit',
//             'amount'    => $order->seller_amount_walate,
//             'status'    => 1,
//             'description' => 'Order cancelled',

//         ]);

//         return [
//             'success' => true,
//             'message' => $responseData['message'] ?? 'Order deleted successfully.',
//             'order_id' => $order->order_number,
//         ];
//     }

    
// public function cancelShipment($awb)
// {
//     try {
//         // DTDC Production API URL
//         $url = "http://pxapi.dtdc.in/api/customer/integration/consignment/cancel";

//         // DTDC API credentials (replace with actual)
//         $apiKey = "a74cd3ae095ad603dc6d506fb30dcf";
//         $customerCode = "GL11173"; // Your DTDC customer code

//         $seller = Auth::guard('seller')->user();
//         $order = Order::where('awb_number', $awb)->first();

//         if (!$order) {
//             return [
//                 'status' => false,
//                 'message' => 'Order not found for AWB: ' . $awb,
//                 'responseCode' => 404
//             ];
//         }

//         // Prepare request payload for a single AWB
//         $payload = [
//             "AWBNo" => [$awb],
//             "customerCode" => $customerCode
//         ];

//         // Send POST request
//         $response = Http::withHeaders([
//             'Content-Type' => 'application/json',
//             'api-key' => $apiKey,
//         ])->post($url, $payload);

//         $responseData = $response->json();
//             // dd($responseData);
//         // Check if cancellation succeeded
//         if ($response->successful() && ($responseData['status'] ?? '') === 'OK' && ($responseData['success'] ?? false)) {
//             $consignment = $responseData['successConsignments'][0] ?? null;

//             if ($consignment['success'] ?? false) {
//                 // Update order
//                 $order->order_status = 'cancelled';
//                 $order->save();

//                 // Refund seller
//                 Recharge::create([
//                     'seller_id'   => $seller->id,
//                     'type'        => 'Credit',
//                     'amount'      => $order->seller_amount_walate,
//                     'status'      => 1,
//                     'description' => 'DTDC Order cancelled',
//                 ]);

//                 return [
//                     'status' => true,
//                     'message' => 'DTDC shipment cancelled successfully.',
//                     'awb_number' => $awb,
//                     'response' => $responseData,
//                     'responseCode' => $response->status(),
//                 ];
//             }
//         }

//         // If cancellation failed
//         return [
//             'status' => false,
//             'message' => $responseData['error'] ?? 'Failed to cancel shipment.',
//             'response' => $responseData,
//             'responseCode' => $response->status(),
//         ];

//     } catch (\Exception $e) {
//         return [
//             'status' => false,
//             'message' => 'API Request Failed: ' . $e->getMessage(),
//         ];
//     }
// }




//     public function cancelShipment($awb)
//     {
//         $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607'; // <-- Replace with actual token

//         $seller = Auth::guard('seller')->user();
//         $order = Order::where('awb_number', $awb)->first();

//         $response = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//             'Content-Type'  => 'application/json',
//         ])->post('https://track.delhivery.com/api/p/edit', [
//             'waybill'      => $awb,
//             'cancellation' => 'true',
//         ]);

//         if ($response->successful()) {

//             $order->order_status = 'cancelled';
//             $order->save();

//             Recharge::create([
//                 'seller_id'   => $seller->id,
//                 'type'        => 'Credit',
//                 'amount'      => $order->seller_amount_walate,
//                 'status'      => 1,
//                 'description' => 'Order cancelled',
//             ]);

//             return [
//                 'status' => true,
//                 'message' => 'Order cancelled successfully',
//                 'data' => $response->json(),
//                 'responseCode' => $response->status()
//             ];
//         }

//         return [
//             'status' => false,
//             'message' => $response->json()['error'] ?? 'Unknown error',
//             'responseCode' => $response->status(),
//         ];
//     }



}



