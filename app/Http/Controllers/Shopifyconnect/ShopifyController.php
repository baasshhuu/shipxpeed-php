<?php

namespace App\Http\Controllers\Shopifyconnect;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\Recharge;
use App\Models\SellerAgreement;
use App\Models\SellerBankDetail;
use Illuminate\Support\Facades\Auth;
use App\Models\{Order, Buyer, OrderItem, Warehouse, OrderPackageDetail, SellerList, ShopifyConnection};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use App\Helper\Helper;
use App\Models\LogisticProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Imports\OrdersImport;
use App\Http\Controllers\selleradmin\ShipmentController;


class ShopifyController extends Controller
{

    public function channelList()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
        }

        $warehouses = [];
        if ($seller) {
            $warehouses = Warehouse::where('seller_id', $seller->id)->get();
        }
        // dd($warehouses);

        $allSellers = SellerList::active()->get();
         $channels = ShopifyConnection::where('seller_id', $seller->id)->get();


        return view('shopify.index', [
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'warehouses' => $warehouses,
            'allSellers' => $allSellers,
            'couriers' => 'vicky', // Pass to view
            'channels' => $channels,
        ]);
    }




    
    public function channelAdd()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
        }

        $warehouses = [];
        if ($seller) {
            $warehouses = Warehouse::where('seller_id', $seller->id)->get();
        }
        // dd($warehouses);

        $allSellers = SellerList::active()->get();



        return view('shopify.add-channel', [
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'warehouses' => $warehouses,
            'allSellers' => $allSellers,
            'couriers' => 'vicky', // Pass to view
        ]);
    }


    
    public function shopifyIntegration()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
        }

        $warehouses = [];
        if ($seller) {
            $warehouses = Warehouse::where('seller_id', $seller->id)->get();
        }

        $allSellers = SellerList::active()->get();

        return view('shopify.shopify-integration', [
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'warehouses' => $warehouses,
            'allSellers' => $allSellers,
            'couriers' => 'vicky', // Pass to view
        ]);
    }

    
    public function shopifyConnect(Request $request)
    {
        try {
            // dd($request);
            $validatedData = $request->validate([
                'store_url' => 'nullable|url',
                'api_key' => 'nullable|string',
                'admin_api_token' => 'nullable|string',
                'host_name' => 'nullable|string',
                'sync_start_date' => 'nullable|date',
            ]);

            $seller = Auth::guard('seller')->user();
            // dd($seller);
            ShopifyConnection::create([
                'seller_id' => $seller->id,
                'store_url' => $validatedData['store_url'],
                // 'api_key' => $validatedData['api_key'],
                // 'admin_api_token' => $validatedData['admin_api_token'],
                // 'host_name' => $validatedData['host_name'],
                'sync_start_date' => $validatedData['sync_start_date'],
                'status' => 'connected'
            ]);

            return redirect()->back()->with('success', 'Shopify integration successful! Your store has been connected.');
            
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Shopify Integration Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to connect Shopify store. Please try again.');
        }
    }

    /**
     * Sync orders from Shopify to local database
     */
    public function syncOrders(Request $request)
    {
        // echo 'sdsdx';die;
        try {
            $seller = Auth::guard('seller')->user();
            
            // Get seller's Shopify connection
            $shopifyConnection = ShopifyConnection::where('seller_id', $seller->id)
                ->where('status', 'connected')
                ->first();

            if (!$shopifyConnection) {
                return redirect()->back()->with('error', 'No active Shopify connection found. Please connect your store first.');
            }

            // Sync orders from Shopify
            $result = $this->fetchAndSaveShopifyOrders($shopifyConnection);
            // dd($result);
            if ($result['success']) {
                return redirect()->back()->with('success', 
                    "Orders synced successfully! {$result['new_orders']} new orders imported, {$result['updated_orders']} existing orders updated."
                );
            } else {
                return redirect()->back()->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Shopify Order Sync Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to sync orders. Please try again.');
        }
    }

    /**
     * Fetch orders from Shopify API and save to database
     */
    // private function fetchAndSaveShopifyOrders($shopifyConnection)
    // {
    //     try {
    //         $storeUrl = rtrim($shopifyConnection->store_url, '/');
    //         $accessToken = $shopifyConnection->admin_api_token;
            
    //         // Prepare API URL with parameters
    //         $apiUrl = $storeUrl . '/admin/api/2023-10/orders.json';
    //         $params = [
    //             'limit' => 250, // Maximum allowed by Shopify
    //             'status' => 'any',
    //             'created_at_min' => $shopifyConnection->sync_start_date->format('Y-m-d\TH:i:s\Z'),
    //         ];

    //         $response = Http::withHeaders([
    //             'X-Shopify-Access-Token' => $accessToken,
    //             'Content-Type' => 'application/json',
    //         ])->get($apiUrl, $params);
    //         dd(json_decode($response->body()));
    //         if (!$response->successful()) {
    //             return [
    //                 'success' => false,
    //                 'message' => 'Failed to fetch orders from Shopify API. Status: ' . $response->status()
    //             ];
    //         }

    //         $ordersData = $response->json();
    //         // dd($ordersData['orders'][1]['customer']);
    //         $orders = $ordersData['orders'] ?? [];
    //         // dd($orders);
    //         $newOrders = 0;
    //         $updatedOrders = 0;

    //         foreach ($orders as $shopifyOrder) {
    //             // dd($shopifyOrder);
    //             $result = $this->createOrderFromShopifyData($shopifyOrder, $shopifyConnection);
    //             if ($result['action'] === 'created') {
    //                 $newOrders++;
    //             } elseif ($result['action'] === 'updated') {
    //                 $updatedOrders++;
    //             }
    //         }

    //         // Update sync information
    //         $shopifyConnection->update([
    //             'last_sync_at' => Carbon::now(),
    //             'orders_synced_count' => ($shopifyConnection->orders_synced_count ?? 0) + $newOrders,
    //         ]);

    //         return [
    //             'success' => true,
    //             'new_orders' => $newOrders,
    //             'updated_orders' => $updatedOrders,
    //         ];

    //     } catch (\Exception $e) {
    //         Log::error('Shopify API Error: ' . $e->getMessage());
    //         return [
    //             'success' => false,
    //             'message' => 'Error fetching orders: ' . $e->getMessage()
    //         ];
    //     }
    // }

private function fetchAndSaveShopifyOrders($shopifyConnection, $endDate = null)
{
    try {
        $storeUrl = rtrim($shopifyConnection->store_url, '/');
        $accessToken = $shopifyConnection->admin_api_token;

        $apiUrl = $storeUrl . '/admin/api/2023-10/orders.json';

        // ✅ Start Date (from DB, UTC)
        $startDateUTC = Carbon::parse($shopifyConnection->sync_start_date)
            ->setTimezone('UTC')
            ->format('Y-m-d\TH:i:s\Z');

        // ✅ End Date (user-defined or current UTC)
        $endDateUTC = $endDate
            ? Carbon::parse($endDate)->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')
            : Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');

        // 🕒 For debug/log: Local IST display
        $startDateIST = Carbon::parse($shopifyConnection->sync_start_date)
            ->setTimezone('Asia/Kolkata')
            ->format('Y-m-d h:i:s A');
        $endDateIST = $endDate
            ? Carbon::parse($endDate)->setTimezone('Asia/Kolkata')->format('Y-m-d h:i:s A')
            : Carbon::now('Asia/Kolkata')->format('Y-m-d h:i:s A');

        // 🧾 Debug Info (you can remove dd() later)
        // dd([
        //     'API Start Date (UTC)' => $startDateUTC,
        //     'API End Date (UTC)' => $endDateUTC,
        //     'Local Start Date (IST)' => $startDateIST,
        //     'Local End Date (IST)' => $endDateIST,
        // ]);

        $orders = [];
        $nextPage = null;

        do {
            // $params = [
            //     'limit' => 250,
            //     'status' => 'any',
            //     'financial_status' => 'pending', // ✅ only pending orders
            //     'created_at_min' => $startDateUTC, // ✅ start date in UTC
            //     'created_at_max' => $endDateUTC,   // ✅ end date in UTC
            // ];
            $params = [
            'limit' => 250,
            'status' => 'any',
            'financial_status' => 'any', 
            'fulfillment_status' => 'unfulfilled',
            'created_at_min' => $startDateUTC,
            'created_at_max' => $endDateUTC,
        ];

            if ($nextPage) {
                $params['page_info'] = $nextPage;
            }

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->get($apiUrl, $params);
//  dd(json_decode($response->body()));
             if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch orders from Shopify API. Status: ' . $response->status(),
                ];
            }

            $data = $response->json();
            $fetchedOrders = $data['orders'] ?? [];
            $orders = array_merge($orders, $fetchedOrders);

            // ✅ Handle pagination
            $linkHeader = $response->header('Link');
            if ($linkHeader && preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches)) {
                $nextUrl = $matches[1];
                parse_str(parse_url($nextUrl, PHP_URL_QUERY), $query);
                $nextPage = $query['page_info'] ?? null;
            } else {
                $nextPage = null;
            }

        } while ($nextPage);

        // ✅ Save or update orders
        $newOrders = 0;
        $updatedOrders = 0;

        foreach ($orders as $shopifyOrder) {
            $result = $this->createOrderFromShopifyData($shopifyOrder, $shopifyConnection);
            if ($result['action'] === 'created') {
                $newOrders++;
            } elseif ($result['action'] === 'updated') {
                $updatedOrders++;
            }
        }

        // ✅ Update sync info
        $shopifyConnection->update([
            'last_sync_at' => Carbon::now(),
            'orders_synced_count' => ($shopifyConnection->orders_synced_count ?? 0) + $newOrders,
        ]);

        return [
            'success' => true,
            'new_orders' => $newOrders,
            'updated_orders' => $updatedOrders,
            'total_fetched' => count($orders),
            'from_date_utc' => $startDateUTC,
            'to_date_utc' => $endDateUTC,
            'from_date_ist' => $startDateIST,
            'to_date_ist' => $endDateIST,
        ];

    } catch (\Exception $e) {
        Log::error('Shopify API Error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error fetching orders: ' . $e->getMessage(),
        ];
    }
}


// private function fetchAndSaveShopifyOrders($shopifyConnection, $endDate = null)
// {
//     try {
//         $storeUrl = rtrim($shopifyConnection->store_url, '/');
//         $accessToken = $shopifyConnection->admin_api_token;
//         $apiUrl = $storeUrl . '/admin/api/2023-10/orders.json';

//         // ✅ Start Date (from DB, UTC)
//         $startDateUTC = Carbon::parse($shopifyConnection->sync_start_date)
//             ->setTimezone('UTC')
//             ->format('Y-m-d\TH:i:s\Z');

//         // ✅ End Date (user-defined or current UTC)
//         $endDateUTC = $endDate
//             ? Carbon::parse($endDate)->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')
//             : Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');

//         // 🕒 For debug/log (IST display)
//         $startDateIST = Carbon::parse($shopifyConnection->sync_start_date)
//             ->setTimezone('Asia/Kolkata')
//             ->format('Y-m-d h:i:s A');
//         $endDateIST = $endDate
//             ? Carbon::parse($endDate)->setTimezone('Asia/Kolkata')->format('Y-m-d h:i:s A')
//             : Carbon::now('Asia/Kolkata')->format('Y-m-d h:i:s A');

//         Log::info("🔄 Shopify Fetch Started: From {$startDateIST} to {$endDateIST}");

//         $orders = [];
//         $nextPage = null;

//         do {
//             $params = [
//                 'limit' => 250,
//                 'status' => 'any',
//                 'financial_status' => 'pending', // ✅ Only pending orders
//                 'created_at_min' => $startDateUTC,
//                 'created_at_max' => $endDateUTC,
//             ];

//             if ($nextPage) {
//                 $params['page_info'] = $nextPage;
//             }

//             $response = Http::withHeaders([
//                 'X-Shopify-Access-Token' => $accessToken,
//                 'Content-Type' => 'application/json',
//             ])->get($apiUrl, $params);

//             if (!$response->successful()) {
//                 return [
//                     'success' => false,
//                     'message' => 'Failed to fetch orders from Shopify API. Status: ' . $response->status(),
//                 ];
//             }

//             $data = $response->json();
//             $fetchedOrders = $data['orders'] ?? [];
//             $orders = array_merge($orders, $fetchedOrders);

//             // ✅ Handle pagination
//             $linkHeader = $response->header('Link');
//             if ($linkHeader && preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches)) {
//                 $nextUrl = $matches[1];
//                 parse_str(parse_url($nextUrl, PHP_URL_QUERY), $query);
//                 $nextPage = $query['page_info'] ?? null;
//             } else {
//                 $nextPage = null;
//             }

//         } while ($nextPage);

//         $newOrders = 0;
//         $updatedOrders = 0;

//         foreach ($orders as $shopifyOrder) {
//             // ✅ Step 1: Save or update locally
//             $result = $this->createOrderFromShopifyData($shopifyOrder, $shopifyConnection);

//             // ✅ Step 2: If pending, mark as paid in Shopify
//             if ($shopifyOrder['financial_status'] === 'pending') {
//                 $this->markShopifyOrderAsPaid($shopifyOrder['id'], $shopifyConnection, $shopifyOrder['total_price']);
//             }

//             if ($result['action'] === 'created') {
//                 $newOrders++;
//             } elseif ($result['action'] === 'updated') {
//                 $updatedOrders++;
//             }
//         }

//         // ✅ Update sync info
//         $shopifyConnection->update([
//             'last_sync_at' => Carbon::now(),
//             'orders_synced_count' => ($shopifyConnection->orders_synced_count ?? 0) + $newOrders,
//         ]);

//         Log::info("✅ Shopify Sync Complete: {$newOrders} new, {$updatedOrders} updated, total " . count($orders));

//         return [
//             'success' => true,
//             'new_orders' => $newOrders,
//             'updated_orders' => $updatedOrders,
//             'total_fetched' => count($orders),
//             'from_date_utc' => $startDateUTC,
//             'to_date_utc' => $endDateUTC,
//             'from_date_ist' => $startDateIST,
//             'to_date_ist' => $endDateIST,
//         ];

//     } catch (\Exception $e) {
//         Log::error('❌ Shopify API Error: ' . $e->getMessage());
//         return [
//             'success' => false,
//             'message' => 'Error fetching orders: ' . $e->getMessage(),
//         ];
//     }
// }




// private function markShopifyOrderAsPaid($orderId, $shopifyConnection, $amount = '0.00')
// {
//     try {
//         $storeUrl = rtrim($shopifyConnection->store_url, '/');
//         $accessToken = $shopifyConnection->admin_api_token;

//         // ✅ Shopify API endpoint for creating a transaction
//         $apiUrl = $storeUrl . "/admin/api/2023-10/orders/{$orderId}/transactions.json";

//         // ✅ Shopify transaction payload (marks order as paid)
//         $payload = [
//             'transaction' => [
//                 'kind' => 'sale',        // creates a sale-type transaction
//                 'status' => 'success',   // marks as successful
//                 'amount' => $amount,     // actual order total amount
//             ]
//         ];

//         $response = Http::withHeaders([
//             'X-Shopify-Access-Token' => $accessToken,
//             'Content-Type' => 'application/json',
//         ])->post($apiUrl, $payload);

//         if ($response->successful()) {
//             Log::info("💰 Order ID {$orderId} marked as PAID on Shopify (₹{$amount}).");
//         } else {
//             Log::warning("⚠️ Failed to mark order {$orderId} as paid. Response: " . $response->status());
//         }

//     } catch (\Exception $e) {
//         Log::error("❌ Error marking Shopify order {$orderId} as paid: " . $e->getMessage());
//     }
// }

    /**
     * Create or update order from Shopify order data
     */
    private function createOrderFromShopifyData($shopifyOrder, $shopifyConnection)
    {
        // dd($shopifyOrder, $shopifyConnection);
        try {
            // Check if order already exists
            $existingOrder = Order::where('shopify_order_number', $shopifyOrder['order_number'])
                ->where('seller_id', $shopifyConnection->seller_id)
                ->first();

            if ($existingOrder) {
                // Update existing order if needed
                // $orderData = $this->mapShopifyOrderToLocal($shopifyOrder, $shopifyConnection);
                // NOTE: Updating existing orders is disabled for now per request.
                // If you want to enable updates later, uncomment the line below.
                // $existingOrder->update($orderData);
                return ['action' => 'exists', 'order' => $existingOrder];
            } else {
                // Prepare request data for ShipmentController
                // echo 'cscscs';die;
                $requestData = $this->prepareShopifyDataForShipment($shopifyOrder, $shopifyConnection);
                // dd($requestData);
                // Create a mock request object
                $mockRequest = new \Illuminate\Http\Request();
                $mockRequest->merge($requestData);
                
                // Set the authenticated seller
                Auth::guard('seller')->setUser(\App\Models\SellerList::find($shopifyConnection->seller_id));
                
                // Create shipment controller instance and call createShipmentshopify
                $shipmentController = new ShipmentController();
                $response = $shipmentController->createShipmentshopify($mockRequest);
                
                // Parse the response
                $responseData = json_decode($response->getContent(), true);
                // dd($responseData);
                if ($responseData['success']) {
                    // Get the created order
                    $newOrder = Order::find($responseData['order_id']);
                    return ['action' => 'created', 'order' => $newOrder];
                } else {
                    Log::error('Shipment creation failed', ['response' => $responseData]);
                    return ['action' => 'failed', 'error' => $responseData['message']];
                }
            }

        } catch (\Exception $e) {
            Log::error('Order Creation Error: ' . $e->getMessage(), ['shopify_order' => $shopifyOrder]);
            return ['action' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Prepare Shopify data for ShipmentController format
     */
    private function prepareShopifyDataForShipment($shopifyOrder, $shopifyConnection)
    {
    // Get shipping address
    $shippingAddress = $shopifyOrder['shipping_address'] ?? [];
    $billingAddress = $shopifyOrder['billing_address'] ?? $shippingAddress;
    $customer = $shopifyOrder['customer'] ?? [];
    $customerDefault = $customer['default_address'] ?? [];
            // dd($customerDefault);
        // Extract address details from note_attributes
        $noteAttributes = $shopifyOrder['note_attributes'] ?? [];
        $addressData = [];
        foreach ($noteAttributes as $attribute) {
            $addressData[$attribute['name']] = $attribute['value'];
        }

        // Prepare consignee data
        // Prefer note attributes, then customer.default_address, then shipping_address
        $consignee = [
            'name' => $addressData['Full name'] ?? (($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'email' => $shopifyOrder['email'] ?? ($customer['email'] ?? ''),
            'phone' => $addressData['Phone number'] ?? ($shippingAddress['phone'] ?? ($customer['phone'] ?? '')),
            // Shopify address fields are address1/address2 in API payload
            'address' => $addressData['Address'] ?? ($customerDefault['address1'] ?? $shippingAddress['address1'] ?? ''),
            'address_2' => $addressData['Landmark'] ?? ($customerDefault['address2'] ?? $shippingAddress['address2'] ?? ''),
            'city' => $addressData['City'] ?? ($customerDefault['city'] ?? $shippingAddress['city'] ?? ''),
            'state' => $addressData['State'] ?? ($customerDefault['province'] ?? $shippingAddress['province'] ?? ''),
            'country' => $customerDefault['country'] ?? $shippingAddress['country'] ?? '',
            'pincode' => $addressData['zip_code'] ?? ($customerDefault['zip'] ?? $shippingAddress['zip'] ?? ''),
        ];
        // Get seller details for pickup and RTO addresses
        $seller = \App\Models\SellerList::find($shopifyConnection->seller_id);
        $sellerAddress = \App\Models\SellerAddress::where('seller_id', $seller->id)->first();

        // Prepare pickup data (seller's address)
        $pickup = [
            'name' => $seller->company_name ?? $seller->name ?? '',
            'email' => $seller->email ?? '',
            'phone' => $seller->mobile ?? '',
            'address' => $sellerAddress->address ?? '',
            'address2' => $sellerAddress->address2 ?? '',
            'city' => $sellerAddress->city ?? '',
            'state' => $sellerAddress->state ?? '',
            'country' => 'India',
            'pincode' => $sellerAddress->pincode ?? '',
        ];

        // RTO address (same as pickup for most cases)
        $rto = $pickup;

        // Prepare order items
        $orderItems = [];
        foreach ($shopifyOrder['line_items'] as $item) {
            // dd($item);
            $orderItems[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'name' => $item['name'],
                'sku' => $item['sku'] ?? '',
                'qty' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['quantity'] * $item['price'],
                'weight' => $item['grams'] ?? 0,
            ];
        }

        // Calculate amounts
        $orderAmount = floatval($shopifyOrder['total_price']);
        $shippingCharges = floatval($shopifyOrder['shipping_lines'][0]['price'] ?? 0);
        $discount = floatval($shopifyOrder['total_discounts']);

        // Determine payment type based on payment gateway
        $paymentGateways = $shopifyOrder['payment_gateway_names'] ?? [];
        $isCOD = false;
        foreach ($paymentGateways as $gateway) {
            if (stripos($gateway, 'cod') !== false || stripos($gateway, 'cash on delivery') !== false) {
                $isCOD = true;
                break;
            }
        }

        $paymentType = $isCOD ? 'cod' : 'prepaid';
        $collectableAmount = $isCOD ? $orderAmount : 0;

        return [
            'order_number' => $shopifyOrder['order_number'],
            'courier_id' => '', // Will be set later when assigning courier
            'payment_type' => $paymentType,
            'order_amount' => $orderAmount,
            'shipping_charges' => $shippingCharges,
            'cod_charges' => $isCOD ? 10 : 0, // Default COD charges
            'discount' => $discount,
            'collectable_amount' => $collectableAmount,
            'package_type' => 'box',
            'package_weight' => $this->calculateTotalWeight($orderItems),
            'package_length' => 20, // Default dimensions
            'package_breadth' => 15,
            'package_height' => 10,
            'consignee' => $consignee, // Pass as array, not JSON
            'pickup' => $pickup,       // Pass as array, not JSON
            'rto' => $rto,             // Pass as array, not JSON
            'order_items' => $orderItems, // Pass as array, not JSON
            
            // Shopify specific fields
            'shopify_order_id' => $shopifyOrder['id'],
            'shopify_order_name' => $shopifyOrder['name'],
            'financial_status' => $shopifyOrder['financial_status'],
            'fulfillment_status' => $shopifyOrder['fulfillment_status'],
            'shopify_created_at' => $shopifyOrder['created_at'],
        ];
    }

    /**
     * Map Shopify order data to local order format
     */


    private function mapShopifyOrderToLocal($shopifyOrder, $shopifyConnection)
    {
        // Get shipping address
        $shippingAddress = $shopifyOrder['shipping_address'] ?? [];
        $billingAddress = $shopifyOrder['billing_address'] ?? $shippingAddress;

        // Extract address details from note_attributes
        $noteAttributes = $shopifyOrder['note_attributes'] ?? [];
        $addressData = [];
        foreach ($noteAttributes as $attribute) {
            $addressData[$attribute['name']] = $attribute['value'];
        }

        // Prefer customer.default_address, then shipping_address
        $customer = $shopifyOrder['customer'] ?? [];
        $customerDefault = $customer['default_address'] ?? [];

        // Prepare consignee data (use same keys & fallbacks as prepareShopifyDataForShipment)
        $consignee = [
            'name' => $addressData['Full name'] ?? (($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'email' => $shopifyOrder['email'] ?? ($customer['email'] ?? ''),
            'phone' => $addressData['Phone number'] ?? ($shippingAddress['phone'] ?? ($customer['phone'] ?? '')),
            'address' => $addressData['Address'] ?? ($customerDefault['address1'] ?? $shippingAddress['address1'] ?? ''),
            'address_2' => $addressData['Landmark'] ?? ($customerDefault['address2'] ?? $shippingAddress['address2'] ?? ''),
            'city' => $addressData['City'] ?? ($customerDefault['city'] ?? $shippingAddress['city'] ?? ''),
            'state' => $addressData['State'] ?? ($customerDefault['province'] ?? $shippingAddress['province'] ?? ''),
            'country' => $customerDefault['country'] ?? $shippingAddress['country'] ?? '',
            'pincode' => $addressData['zip_code'] ?? ($customerDefault['zip'] ?? $shippingAddress['zip'] ?? ''),
        ];

        // Prepare order items
        $orderItems = [];
        foreach ($shopifyOrder['line_items'] as $item) {
            $orderItems[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'name' => $item['name'],
                'sku' => $item['sku'] ?? '',
                'qty' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['quantity'] * $item['price'],
                'weight' => $item['grams'] ?? 0,
            ];
        }

        // Calculate amounts
        $orderAmount = floatval($shopifyOrder['total_price']);
        $shippingCharges = floatval($shopifyOrder['shipping_lines'][0]['price'] ?? 0);
        $discount = floatval($shopifyOrder['total_discounts']);

        // Determine payment type based on payment gateway
        $paymentGateways = $shopifyOrder['payment_gateway_names'] ?? [];
        $isCOD = false;
        foreach ($paymentGateways as $gateway) {
            if (stripos($gateway, 'cod') !== false || stripos($gateway, 'cash on delivery') !== false) {
                $isCOD = true;
                break;
            }
        }

        return [
            'order_number' => $shopifyOrder['order_number'],
            'seller_id' => $shopifyConnection->seller_id,
            'payment_type' => $isCOD ? 'COD' : 'Prepaid',
            'order_amount' => $orderAmount,
            'shipping_charges' => $shippingCharges,
            'discount' => $discount,
            'collectable_amount' => $isCOD ? $orderAmount : 0,
            'package_weight' => $this->calculateTotalWeight($orderItems),
            'package_length' => 20, // Default dimensions - you can adjust these
            'package_breadth' => 15,
            'package_height' => 10,
            'consignee' => json_encode($consignee),
            'order_items' => json_encode($orderItems),
            'status' => 'pending',
            'additional_info' => json_encode([
                'shopify_order_id' => $shopifyOrder['id'],
                'shopify_order_name' => $shopifyOrder['name'],
                'shopify_created_at' => $shopifyOrder['created_at'],
                'shopify_updated_at' => $shopifyOrder['updated_at'],
                'financial_status' => $shopifyOrder['financial_status'],
                'fulfillment_status' => $shopifyOrder['fulfillment_status'],
                'confirmation_number' => $shopifyOrder['confirmation_number'] ?? '',
                'currency' => $shopifyOrder['currency'] ?? 'INR',
                'note_attributes' => $shopifyOrder['note_attributes'] ?? [],
                'payment_gateway_names' => $shopifyOrder['payment_gateway_names'] ?? [],
            ]),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * Calculate total weight of order items
     */
    private function calculateTotalWeight($orderItems)
    {
        $totalWeight = 0;
        foreach ($orderItems as $item) {
            $totalWeight += ($item['weight'] * $item['qty']);
        }
        return $totalWeight > 0 ? $totalWeight : 500; // Default 500g if no weight
    }

    /**
     * Handle Shopify webhook for new orders
     */
    public function handleOrderWebhook(Request $request)
    {
        try {
            // Verify webhook (you should implement proper webhook verification)
            $shopifyOrder = $request->all();
            
            if (!isset($shopifyOrder['id'])) {
                return response()->json(['error' => 'Invalid order data'], 400);
            }

            // Find the seller's Shopify connection based on shop domain
            $shopDomain = $request->header('X-Shopify-Shop-Domain');
            $shopifyConnection = ShopifyConnection::where('host_name', $shopDomain)
                ->where('status', 'connected')
                ->first();

            if (!$shopifyConnection) {
                Log::warning('Webhook received for unknown shop: ' . $shopDomain);
                return response()->json(['error' => 'Shop not found'], 404);
            }

            // Create order from webhook data
            $result = $this->createOrderFromShopifyData($shopifyOrder, $shopifyConnection);
            
            Log::info('Webhook order processed', [
                'action' => $result['action'],
                'order_number' => $shopifyOrder['order_number'] ?? 'N/A'
            ]);

            return response()->json(['status' => 'success', 'action' => $result['action']]);

        } catch (\Exception $e) {
            Log::error('Webhook Error: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Get list of connected Shopify stores for a seller
     */
    public function getConnectedStores()
    {
        $seller = Auth::guard('seller')->user();
        
        $connections = ShopifyConnection::where('seller_id', $seller->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'connections' => $connections->map(function($connection) {
                return [
                    'id' => $connection->id,
                    'store_url' => $connection->store_url,
                    'host_name' => $connection->host_name,
                    'status' => $connection->status,
                    'last_sync_at' => $connection->last_sync_at?->format('Y-m-d H:i:s'),
                    'orders_synced_count' => $connection->orders_synced_count ?? 0,
                    'created_at' => $connection->created_at->format('Y-m-d H:i:s'),
                ];
            })
        ]);
    }

    /**
     * Disconnect a Shopify store
     */
    public function disconnectStore(Request $request, $connectionId)
    {
        try {
            $seller = Auth::guard('seller')->user();
            
            $connection = ShopifyConnection::where('id', $connectionId)
                ->where('seller_id', $seller->id)
                ->first();

            if (!$connection) {
                return redirect()->back()->with('error', 'Connection not found.');
            }

            $connection->update(['status' => 'disconnected']);

            return redirect()->back()->with('success', 'Shopify store disconnected successfully.');

        } catch (\Exception $e) {
            Log::error('Disconnect Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to disconnect store.');
        }
    }




}