<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helper\Helper;
use App\Models\Order;
use App\Models\LogisticProvider;
use App\Services\XpressBeesService;
use App\Models\SellerList;


use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\SellerAgreement;
use App\Models\SellerBankDetail;
use App\Models\{Buyer, OrderItem, Warehouse, OrderPackageDetail};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Imports\OrdersImport;

class ShipmentController extends Controller
{
    const API_BASE_URL = 'https://shipment.xpressbees.com/';
    const API_ENDPOINT = '/api/shipments2';
    const TOKEN_CACHE_KEY = 'xpressbees_api_token';
    const TOKEN_CACHE_TTL = 3600;




public function orderedit($id)
{
    $seller = Auth::guard('seller')->user();
    
    // Get the order with proper authorization check
    $order = Order::where('id', $id)
                ->where('seller_id', $seller->id)
                ->firstOrFail();

    // Calculate seller's balance
    $totalAmount = 0;
    if ($seller && $seller->status == 1) {
        $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
            ->where('type', 'Debit')
            ->sum('amount');

        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
    }

    // Get warehouses
    $warehouses = Warehouse::where('seller_id', $seller->id)->get();
    $allSellers = SellerList::active()->get();

    // Decode JSON fields
    // $order->consignee = json_decode($order->consignee, true);
    // $order->pickup = json_decode($order->pickup, true);
    // $order->rto = json_decode($order->rto, true);
    // $order->order_items = json_decode($order->order_items, true);
// Decode JSON fields safely
$order->consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
$order->pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
$order->rto = is_string($order->rto) ? json_decode($order->rto, true) : $order->rto;
$order->order_items = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;

    return view('sellerdashboard.order.edit', [
        'seller' => $seller,
        'totalAmount' => $totalAmount,
        'warehouses' => $warehouses,
        'allSellers' => $allSellers,
        'order' => $order,
        'couriers' => 'vicky',
    ]);
}

public function orderupdate(Request $request, $id)
{
    try {
        // dd($request);
        $seller = Auth::guard('seller')->user();
        
        // Get the order with proper authorization check
        $order = Order::where('id', $id)
                    ->where('seller_id', $seller->id)
                    ->firstOrFail();

        $validated = $this->validateRequest($request);

        $requestData = $this->prepareRequestDataupdate($request);
        $shadowfax_json = $this->shadowfaxprepareRequestData($request);
        $DelhiveryServiceb2b = $this->DelhiveryServiceb2b($request);
        $delhivery_b2c = $this->delhivery_b2cupdate($request);
        $delhivery_b2c_air = $this->delhivery_b2c_airupdate($request);

        // Update order fields
        $order->delhivery_b2c_air = json_encode($delhivery_b2c_air);
        $order->delhivery_b2c = json_encode($delhivery_b2c);
        $order->DelhiveryServiceb2b = json_encode($DelhiveryServiceb2b);
        $order->shadowfax_json = json_encode($shadowfax_json);
        $order->encode_data = json_encode($requestData);
        $order->courier_id = $request->courier_id;
        $order->payment_type = $request->payment_type;
        $order->order_amount = $request->collectable_amount ?? 0;
        $order->shipping_charges = $request->shipping_charges ?? 0;
        $order->cod_charges = $request->cod_charges ?? 0;
        $order->discount = $request->discount ?? 0;
        $order->collectable_amount = $request->collectable_amount ?? 0;
        $order->package_type = $request->package_type;
        $order->package_weight = $request->package_weight;
        $order->package_length = $request->package_length;
        $order->package_breadth = $request->package_breadth;
        $order->package_height = $request->package_height;
        $order->consignee = json_encode($request->consignee);
        $order->pickup = json_encode($request->pickup);
        $order->rto = json_encode($request->rto);
        $order->order_items = json_encode($request->order_items);
        
        $order->save();

        return redirect()->route('seller.order')->with('success', 'Order updated successfully.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error updating order: ' . $e->getMessage());
    }
}





public function createShipmentweb(Request $request)
{
    try {
        $validated = $this->validateRequest($request);

        // Use seller_id from request instead of Auth
        // $request->seller_id
        $sellerId = str_replace('#SX', '', $request->seller_id);

        $seller = \App\Models\SellerList::find($sellerId);
        if (!$seller || $seller->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive seller.'
            ], 403);
        }

        $requestData         = $this->prepareRequestData($request);
        $shadowfax_json      = $this->shadowfaxprepareRequestData($request);
        $DelhiveryServiceb2b = $this->DelhiveryServiceb2b($request);
        $delhivery_b2c       = $this->delhivery_b2c($request);
        $delhivery_b2c_air   = $this->delhivery_b2c_air($request);

        $order = new \App\Models\Order();

        $order->delhivery_b2c_air   = json_encode($delhivery_b2c_air);
        $order->delhivery_b2c       = json_encode($delhivery_b2c);
        $order->DelhiveryServiceb2b = json_encode($DelhiveryServiceb2b);
        $order->shadowfax_json      = json_encode($shadowfax_json);
        $order->encode_data         = json_encode($requestData);
        // $order->order_number        = $request->order_number;
        $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
       $order->order_number = $lastOrder ? ($lastOrder->id + 1) : 1;

        $order->seller_id           = $seller->id;
        $order->courier_id          = $request->courier_id;
        $order->payment_type        = $request->payment_type;
        $order->order_amount        = $request->collectable_amount ?? 0;
        $order->shipping_charges    = $request->shipping_charges ?? 0;
        $order->cod_charges         = $request->cod_charges ?? 0;
        $order->discount            = $request->discount ?? 0;
        $order->collectable_amount  = $request->collectable_amount ?? 0;
        $order->package_type        = $request->package_type;
        $order->package_weight      = $request->package_weight;
        $order->package_length      = $request->package_length;
        $order->package_breadth     = $request->package_breadth;
        $order->package_height      = $request->package_height;
        $order->consignee           = $request->consignee;
        $order->pickup              = $request->pickup;
        $order->rto                 = $request->rto;
        $order->order_items         = $request->order_items;
        $order->save();

        // Send or log response (optional)
        // \Log::info('Order created', ['order_id' => $order->id]);

        // Optional: send this response to some webhook URL
        // Http::post('https://webhook-url.com', ['order_id' => $order->id, 'status' => 'created']);

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully',
            'order_id' => $order->id
        ]);

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return response()->json([
            'success' => false,
            'message' => 'API request failed',
            // 'error' => $e->getMessage()
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong',
            // 'error' => $e->getMessage()
        ], 500);
    }
}




   public function createShipmentbulk(Request $request)
{
    //  dd($request);
    try {
        $seller = Auth::guard('seller')->user();
        if (!$seller || $seller->status != 1) {
            return redirect()->back()->with('error', 'Unauthorized or inactive seller.');
        }

        // $requestData = $this->prepareRequestData($request);
        // $shadowfax_json = $this->shadowfaxprepareRequestData($request);
        // $DelhiveryServiceb2b = $this->DelhiveryServiceb2b($request);
        $delhivery_b2c = $this->delhivery_b2c($request);
        $delhivery_b2c_air = $this->delhivery_b2c_air($request);

        $order = new \App\Models\Order();

        $order->delhivery_b2c_air        = json_encode($delhivery_b2c_air);
        $order->delhivery_b2c        = json_encode($delhivery_b2c);
        // $order->DelhiveryServiceb2b        = json_encode($DelhiveryServiceb2b);
        // $order->shadowfax_json        = json_encode($shadowfax_json);
        // $order->encode_data        = json_encode($requestData);
        $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
        $order->order_number = 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1);
        $order->seller_id           = $seller->id;
        $order->customer_order_id          = $request->order_number;

        $order->courier_id          = $request->courier_id;
        $order->payment_type        = $request->payment_type;
        $order->order_amount        = $request->collectable_amount ?? 0;
        $order->shipping_charges    = $request->shipping_charges ?? 0;
        $order->cod_charges         = $request->cod_charges ?? 0;
        $order->discount            = $request->discount ?? 0;
        $order->collectable_amount  = $request->collectable_amount ?? 0;
        $order->package_type        = $request->package_type;
        $order->package_weight      = $request->package_weight;
        $order->package_length      = $request->package_length;
        $order->package_breadth     = $request->package_breadth;
        $order->package_height      = $request->package_height;
        $order->consignee           = $request->consignee;
        $order->pickup              = $request->pickup;
        $order->rto                 = $request->rto;
        $order->order_items         = $request->order_items;
        $order->save();

        // return redirect()->back()->with('success', 'Shipment created successfully');

    return redirect()->back()->with('success', 'Shipment created successfully. Order ID: ' . $order->id);

        // return redirect()->back()->with('success', 'Shipment created successfully. AWB: ' . ($order->awb_number ?? 'N/A'));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        // dd($e->getMessage());
        return redirect()->back()->with('error', 'API request failed: ' . $e->getMessage());
    } catch (\Exception $e) {
        // dd($e->getMessage());
        return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}
 

   public function createShipmentshopify(Request $request)
{
    // dd($request);
    try {
        // Enhanced validation for Shopify orders
        // $validated = $this->validateShopifyRequest($request);
// echo 'xasxas';die;
        $seller = Auth::guard('seller')->user();
        // dd($seller);
        if (!$seller || $seller->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or inactive seller.'
            ], 403);
        }
        // echo "Seller ID: " . $seller->id; // Debug line to check seller ID

        // Check if order already exists (prevent duplicates)
        $existingOrder = \App\Models\Order::where('shopify_order_number', $request->order_number)
            ->where('seller_id', $seller->id)
            ->first();

        if ($existingOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Order already exists with this order number.',
                'order_id' => $existingOrder->id
            ], 409);
        }

        // Prepare courier data
        $requestData = $this->prepareShopifyRequestData($request);
        $shadowfax_json = $this->shadowfaxprepareRequestData($request);
        $DelhiveryServiceb2b = $this->DelhiveryServiceb2b($request);
        $delhivery_b2c = $this->delhivery_b2c_shopify($request);
        $delhivery_b2c_air = $this->delhivery_b2c_air_shopify($request);

        // Create new order
        $order = new \App\Models\Order();

        $order->delhivery_b2c_air = json_encode($delhivery_b2c_air);
        $order->delhivery_b2c = json_encode($delhivery_b2c);
        $order->DelhiveryServiceb2b = json_encode($DelhiveryServiceb2b);
        $order->shadowfax_json = json_encode($shadowfax_json);
        $order->encode_data = json_encode($requestData);
        
        // Use the order number from Shopify
            $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
            $order->order_number = 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1);
        // $order->order_number = $request->order_number;
        $order->seller_id = $seller->id;
        $order->courier_id = $request->courier_id ?? '';
        $order->payment_type = $request->payment_type;
        $order->order_amount = $request->order_amount ?? 0;
        $order->shipping_charges = $request->shipping_charges ?? 0;
        $order->cod_charges = $request->cod_charges ?? 0;
        $order->discount = $request->discount ?? 0;
        $order->collectable_amount = $request->collectable_amount ?? 0;
        $order->package_type = $request->package_type ?? 'box';
        $order->package_weight = $request->package_weight ?? 500;
        $order->package_length = $request->package_length ?? 20;
        $order->package_breadth = $request->package_breadth ?? 15;
        $order->package_height = $request->package_height ?? 10;
        $order->consignee = $request->consignee;
        $order->pickup = $request->pickup;
        $order->rto = $request->rto;
        $order->order_items = $request->order_items;
        $order->shopify_order_id = $request->shopify_order_id;
        $order->shopify_order_number = $request->order_number;
        $order->channel = $request->channel ?? 'Shopify';
        $order->channel_order_id = $request->channel_order_id ?? null;
        $order->channel_order_date = $request->channel_order_date ?? null;

        // Add Shopify specific fields
        // $order->additional_info = json_encode([
        //     'source' => 'shopify',
        //     'shopify_order_id' => $request->shopify_order_id ?? null,
        //     'shopify_order_name' => $request->shopify_order_name ?? null,
        //     'financial_status' => $request->financial_status ?? null,
        //     'fulfillment_status' => $request->fulfillment_status ?? null,
        //     'created_at' => $request->shopify_created_at ?? now(),
        // ]);

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Shopify order created successfully',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'seller_id' => $seller->id,
                'payment_type' => $order->payment_type,
                'order_amount' => $order->order_amount,
                'status' => 'pending'
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        dd($e->errors());
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        dd($e->getMessage());
         
        Log::error('Shopify Order Creation Error: ' . $e->getMessage(), [
            'request' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to create Shopify order',
            'error' => $e->getMessage()
        ], 500);
    }
}

 

   public function createShipment(Request $request)
{
    //  dd($request);
    // Product Name
$productName = trim($request->input('order_items.0.name'));

if (empty($productName)) {
    return redirect()->back()->with('error', 'Product Name is required.');
}

if (!preg_match('/^[a-zA-Z0-9\s\-_.()&\/]+$/', $productName)) {
    return redirect()->back()->with('error', 'Product Name contains invalid special characters.');
}

// Unit Price
$unitPrice = (float) $request->input('order_items.0.price');

if ($unitPrice <= 0) {
    return redirect()->back()->with('error', 'Unit Price must be greater than 0.');
}

// Collectable Amount
$collectable = (float) $request->collectable_amount;

if ($collectable <= 0) {
    return redirect()->back()->with('error', 'Collectable Amount must be greater than 0.');
}

// Match
if (round($unitPrice, 2) != round($collectable, 2)) {
    return redirect()->back()->with('error', 'Collectable Amount must be equal to Unit Price.');
}
$regex = '/^[a-zA-Z0-9\s,\-_.()\/#&]+$/';

$fields = [
    $request->input('consignee.name'),
    $request->input('consignee.address'),
    $request->input('consignee.address_2'),
    $request->input('consignee.city'),
    $request->input('consignee.state'),
    $request->input('order_items.0.name'),
    $request->input('order_items.0.sku'),
];

foreach ($fields as $field) {
    if (!empty($field) && !preg_match($regex, $field)) {
        return redirect()->back()->with('error', 'Special characters are not allowed.');
    }
}
    try {

       // dd($request);
        $validated = $this->validateRequest($request);

        $seller = Auth::guard('seller')->user();
        if (!$seller || $seller->status != 1) {
            return redirect()->back()->with('error', 'Unauthorized or inactive seller.');
        }

        $requestData = $this->prepareRequestData($request);
        $shadowfax_json = $this->shadowfaxprepareRequestData($request);
        $DelhiveryServiceb2b = $this->DelhiveryServiceb2b($request);
        $delhivery_b2c = $this->delhivery_b2c($request);
        $delhivery_b2c_air = $this->delhivery_b2c_air($request);
        $order = new \App\Models\Order();

        $order->delhivery_b2c_air        = json_encode($delhivery_b2c_air);
        $order->delhivery_b2c        = json_encode($delhivery_b2c);
        $order->DelhiveryServiceb2b        = json_encode($DelhiveryServiceb2b);
        $order->shadowfax_json        = json_encode($shadowfax_json);
        $order->encode_data        = json_encode($requestData);
        // $order->order_number        = $request->order_number;
        $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
        $order->order_number = 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1);
        if($request->reverse == 'reverse'){
        $order->reverse          = $request->reverse;
        }else{
        $order->reverse          = 'no';
        }
        $order->seller_id           = $seller->id;
        // $order->awb_number          = $responseData['data']['awb_number'] ?? null;
        $order->customer_order_id          = $request->order_number;

        $order->courier_id          = $request->courier_id;
        $order->payment_type        = $request->payment_type;
        $order->order_amount        = $request->collectable_amount ?? 0;
        $order->shipping_charges    = $request->shipping_charges ?? 0;
        $order->cod_charges         = $request->cod_charges ?? 0;
        $order->discount            = $request->discount ?? 0;
        $order->collectable_amount  = $request->collectable_amount ?? 0;
        $order->package_type        = $request->package_type;
        $order->package_weight      = $request->package_weight;
        $order->package_length      = $request->package_length;
        $order->package_breadth     = $request->package_breadth;
        $order->package_height      = $request->package_height;
        $order->consignee           = $request->consignee;
        $order->pickup              = $request->pickup;
        $order->rto                 = $request->rto;
        $order->order_items         = $request->order_items;
        $order->save();

        // return redirect()->back()->with('success', 'Shipment created successfully');

    return redirect()->back()->with('success', 'Shipment created successfully. Order ID: ' . $order->id);


        // return redirect()->back()->with('success', 'Shipment created successfully. AWB: ' . ($order->awb_number ?? 'N/A'));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        // dd($e->getMessage());
        return redirect()->back()->with('error', 'API request failed: ' . $e->getMessage());
    } catch (\Exception $e) {
        // dd($e->getMessage());
        return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}



    protected function validateRequest(Request $request)
    {
        return $request->validate([
            'order_number' => 'required|string',
            'courier_id' => 'nullable|string',
            'unique_order_number' => 'required|in:yes,no',
            'payment_type' => 'required|in:cod,prepaid',
            'order_amount' => 'nullable|numeric',
            'shipping_charges' => 'nullable|numeric',
            'cod_charges' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'collectable_amount' => 'nullable|numeric',
            'package_weight' => 'required|numeric',
            'package_length' => 'required|numeric',
            'package_breadth' => 'required|numeric',
            'package_height' => 'required|numeric',
            'package_type' => 'nullable|string',
            'request_auto_pickup' => 'required|in:yes,no',
            'is_rto_different' => 'required|in:yes,no',

            'consignee.name' => 'required|string',
            'consignee.address' => 'required|string',
            'consignee.address_2' => 'nullable|string',
            'consignee.city' => 'required|string',
            'consignee.state' => 'required|string',
            'consignee.pincode' => 'required|string',
            'consignee.phone' => 'required|string',

            'pickup.warehouse_name' => 'nullable|string',
            'pickup.name' => 'required|string',
            'pickup.address' => 'required|string',
            'pickup.address_2' => 'nullable|string',
            'pickup.city' => 'required|string',
            'pickup.state' => 'required|string',
            'pickup.pincode' => 'required|string',
            'pickup.phone' => 'required|string',

            'rto.warehouse_name' => 'required_if:is_rto_different,yes|nullable|string',
            'rto.name' => 'required_if:is_rto_different,yes|nullable|string',
            'rto.address' => 'required_if:is_rto_different,yes|nullable|string',
            'rto.city' => 'required_if:is_rto_different,yes|nullable|string',
            'rto.state' => 'required_if:is_rto_different,yes|nullable|string',
            'rto.pincode' => 'required_if:is_rto_different,yes|nullable|string',
            'rto.phone' => 'required_if:is_rto_different,yes|nullable|string',
            'order_items' => 'required|array',
            'order_items.*.name' => 'required|string',
            'order_items.*.qty' => 'required|integer',
            'order_items.*.price' => 'required|numeric',
            'order_items.*.sku' => 'nullable|string',
        ]);
    }

    /**
     * Validate Shopify order request
     */
    protected function validateShopifyRequest(Request $request)
    {
        return $request->validate([
            'order_number' => 'required|string',
            'payment_type' => 'required|in:cod,prepaid',
            'order_amount' => 'nullable|numeric',
            'shipping_charges' => 'nullable|numeric',
            'cod_charges' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'collectable_amount' => 'nullable|numeric',
            'package_weight' => 'nullable|numeric',
            'package_length' => 'nullable|numeric',
            'package_breadth' => 'nullable|numeric',
            'package_height' => 'nullable|numeric',
            'package_type' => 'nullable|string',

            'consignee' => 'required|array',
            'consignee.name' => 'required|string',
            'consignee.address' => 'required|string',
            'consignee.city' => 'required|string',
            'consignee.state' => 'required|string',
            'consignee.pincode' => 'required|string',
            'consignee.phone' => 'required|string',

            'pickup' => 'required|array',
            'pickup.name' => 'required|string',
            'pickup.address' => 'required|string',
            'pickup.city' => 'required|string',
            'pickup.state' => 'required|string',
            'pickup.pincode' => 'required|string',
            'pickup.phone' => 'required|string',

            'order_items' => 'required|array',
            'order_items.*.name' => 'required|string',
            'order_items.*.qty' => 'required|integer',
            'order_items.*.price' => 'required|numeric',

            // Shopify specific fields
            'shopify_order_id' => 'nullable|string',
            'shopify_order_name' => 'nullable|string',
            'financial_status' => 'nullable|string',
            'fulfillment_status' => 'nullable|string',
        ]);
    }

    /**
     * Prepare request data specifically for Shopify orders
     */
    protected function prepareShopifyRequestData(Request $request)
    {
        $collectableAmount = $request->input('payment_type') === 'cod'
            ? (float)$request->input('collectable_amount')
            : 0.0;

        return [
            'order_number' => $request->input('order_number'),
            'payment_type' => $request->input('payment_type'),
            'order_amount' => (float)$request->input('order_amount', 0),
            'shipping_charges' => (float)$request->input('shipping_charges', 0),
            'cod_charges' => (float)$request->input('cod_charges', 0),
            'discount' => (float)$request->input('discount', 0),
            'package_weight' => (float)$request->input('package_weight', 500),
            'package_length' => (float)$request->input('package_length', 20),
            'package_breadth' => (float)$request->input('package_breadth', 15),
            'package_height' => (float)$request->input('package_height', 10),
            'collectable_amount' => $collectableAmount,
            'source' => 'shopify',

            'consignee' => $request->input('consignee'),
            'pickup' => $request->input('pickup'),
            'rto' => $request->input('rto', $request->input('pickup')), // Use pickup as RTO if not provided
            'order_items' => $request->input('order_items'),
        ];
    }

    /**
     * Delhivery B2C method for Shopify orders
     */
    protected function delhivery_b2c_shopify(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $gst = $seller->gst_no ?? "";

        return [
            "shipments" => [
                [
                    "name" => $request->input('consignee.name'),
                    "add" => $request->input('consignee.address'),
                    "pin" => (int) $request->input('consignee.pincode'),
                    "city" => $request->input('consignee.city'),
                    "state" => $request->input('consignee.state'),
                    "country" => "India",
                    "phone" => $request->input('consignee.phone'),
                    "order" => $request->input('order_number'),
                    "payment_mode" => $request->input('payment_type'),
                    "cod_amount" => (string) $request->input('collectable_amount', 0),
                    "total_amount" => (string) $request->input('order_amount', 0),
                    "products_desc" => implode(', ', array_column($request->input('order_items'), 'name')),
                    "hsn_code" => "6403",
                    "quantity" => array_sum(array_column($request->input('order_items'), 'qty')),
                    "seller_gst_tin" => $gst,
                    "seller_add" => $request->input('pickup.address'),
                    "seller_name" => $request->input('pickup.name'),
                    "seller_inv" => $request->input('order_number'),
                    "shipment_width" => $request->input('package_breadth', 15),
                    "shipment_height" => $request->input('package_height', 10),
                    "weight" => $request->input('package_weight', 500),
                    "shipping_mode" => "Surface",
                    "address_type" => "home",
                    "waybill" => "",
                    "order_date" => now()->format('Y-m-d'),
                ]
            ],
            "pickup_location" => [
                "name" => $request->input('pickup.warehouse_name', $request->input('pickup.name')),
                "add" => $request->input('pickup.address'),
                "city" => $request->input('pickup.city'),
                "pin_code" => (int) $request->input('pickup.pincode'),
                "country" => "India",
                "phone" => $request->input('pickup.phone')
            ]
        ];
    }

    /**
     * Delhivery B2C Air method for Shopify orders
     */
    protected function delhivery_b2c_air_shopify(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $gst = $seller->gst_no ?? "";

        return [
            "shipments" => [
                [
                    "name" => $request->input('consignee.name'),
                    "add" => $request->input('consignee.address'),
                    "pin" => (int) $request->input('consignee.pincode'),
                    "city" => $request->input('consignee.city'),
                    "state" => $request->input('consignee.state'),
                    "country" => "India",
                    "phone" => $request->input('consignee.phone'),
                    "order" => $request->input('order_number'),
                    "payment_mode" => $request->input('payment_type'),
                    "cod_amount" => (string) $request->input('collectable_amount', 0),
                    "total_amount" => (string) $request->input('order_amount', 0),
                    "products_desc" => implode(', ', array_column($request->input('order_items'), 'name')),
                    "hsn_code" => "6403",
                    "quantity" => array_sum(array_column($request->input('order_items'), 'qty')),
                    "seller_gst_tin" => $gst,
                    "seller_add" => $request->input('pickup.address'),
                    "seller_name" => $request->input('pickup.name'),
                    "seller_inv" => $request->input('order_number'),
                    "shipment_width" => $request->input('package_breadth', 15),
                    "shipment_height" => $request->input('package_height', 10),
                    "weight" => $request->input('package_weight', 500),
                    "shipping_mode" => "Express",
                    "address_type" => "home",
                    "waybill" => "",
                    "order_date" => now()->format('Y-m-d'),
                ]
            ],
            "pickup_location" => [
                "name" => $request->input('pickup.warehouse_name', $request->input('pickup.name')),
                "add" => $request->input('pickup.address'),
                "city" => $request->input('pickup.city'),
                "pin_code" => (int) $request->input('pickup.pincode'),
                "country" => "India",
                "phone" => $request->input('pickup.phone')
            ]
        ];
    }

    protected function getAuthenticatedClient()
    {
        return new Client([
            'base_uri' => self::API_BASE_URL,
            'verify' => false,
            'timeout' => 150,
            'connect_timeout' => 150,
            'headers' => [
                'User-Agent'    => 'Mozilla/5.0 (compatible; Laravel Guzzle Client)',
                'Authorization' => 'Bearer ' . $this->getApiToken(),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'curl' => [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ],

        ]);
    }

    protected function shouldVerifySSL()
    {
        return config('app.env') === 'production'
            ? storage_path('certs/cacert.pem') // Ensure this file exists
            : false; // Disable SSL verification in local/dev
    }

    protected function getApiToken()
    {
        $token = Cache::get(self::TOKEN_CACHE_KEY);

        if (!$token || $this->isTokenExpired($token)) {
            $token = $this->refreshApiToken();
            Cache::put(self::TOKEN_CACHE_KEY, $token, self::TOKEN_CACHE_TTL);
        }

        return $token;
    }

    protected function isTokenExpired($token)
    {
        try {
            $payload = json_decode(base64_decode(explode('.', $token)[1]));
            return $payload->exp < time();
        } catch (\Exception $e) {
            return true;
        }
    }

    protected function refreshApiToken()
    {
        return env('XPRESSBEES_API_TOKEN', 'your-default-hardcoded-token');
    }




      
    protected function delhivery_b2c_airupdate(Request $request)
{

        $seller = Auth::guard('seller')->user();
       $gst = $seller->gst_no ?? "";
  $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();

$payload = [
    "shipments" => [
        [
            "name" => $request['consignee']['name'],
            // "add" => $request['consignee']['address'],
             "add" => $request['consignee']['address'] . ', ' . $request['consignee']['address_2'],

            // "pin" => $request['consignee']['pincode'],
            "pin" => (int) $request['consignee']['pincode'],

            "city" => $request['consignee']['city'],
            "state" => $request['consignee']['state'],
            "country" => "India",
            "phone" => $request['consignee']['phone'],
            "order" => $request->input('order_number'),
            "payment_mode" => $request['payment_type'], // Prepaid or COD
            "cod_amount" => (string) $request['collectable_amount'], // If COD
            "total_amount" => (string) $request['collectable_amount'],
            "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
            "hsn_code" => "6403", // You can pass per product if required
            "quantity" => array_sum(array_column($request['order_items'], 'qty')),
            "seller_gst_tin" => $gst, // Static or from your config
            "seller_add" => $request['pickup']['address'],
            "seller_name" => $request['pickup']['name'],
            "seller_inv" => $request['unique_order_number'], // or your invoice number
            "shipment_width" => $request['package_breadth'],
            "shipment_height" => $request['package_height'],
            "weight" => $request['package_weight'],
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
        "pin_code" =>(int) $request['pickup']['pincode'],
        "country" => "India",
        "phone" => $request['pickup']['phone']
    ]
];


    return $payload;
}



    
    protected function delhivery_b2c_air(Request $request)
{

        $seller = Auth::guard('seller')->user();
       $gst = $seller->gst_no ?? "";
  $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();

$payload = [
    "shipments" => [
        [
            "name" => $request['consignee']['name'],
            // "add" => $request['consignee']['address'],
             "add" => $request['consignee']['address'] . ', ' . $request['consignee']['address_2'],

            // "pin" => $request['consignee']['pincode'],
            "pin" => (int) $request['consignee']['pincode'],

            "city" => $request['consignee']['city'],
            "state" => $request['consignee']['state'],
            "country" => "India",
            "phone" => $request['consignee']['phone'],
            "order" => 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1),
            "payment_mode" => $request['payment_type'], // Prepaid or COD
            "cod_amount" => (string) $request['collectable_amount'], // If COD
            "total_amount" => (string) $request['collectable_amount'],
            "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
            "hsn_code" => "6403", // You can pass per product if required
            "quantity" => array_sum(array_column($request['order_items'], 'qty')),
            "seller_gst_tin" => $gst, // Static or from your config
            "seller_add" => $request['pickup']['address'],
            "seller_name" => $request['pickup']['name'],
            "seller_inv" => $request['unique_order_number'], // or your invoice number
            "shipment_width" => $request['package_breadth'],
            "shipment_height" => $request['package_height'],
            "weight" => $request['package_weight'],
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
        "pin_code" =>(int) $request['pickup']['pincode'],
        "country" => "India",
        "phone" => $request['pickup']['phone']
    ]
];


    return $payload;
}



    
    protected function delhivery_b2cupdate(Request $request)
{

       $seller = Auth::guard('seller')->user();
       $gst = $seller->gst_no ?? "";
  $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
    // $order->order_number = '#' . ($lastOrder ? ($lastOrder->id + 1) : 1);

$payload = [
    "shipments" => [
        [
            "name" => $request['consignee']['name'],
            "add" => $request['consignee']['address'] . ', ' . $request['consignee']['address_2'],

            // "add" => $request['consignee']['address'],
            // "pin" => $request['consignee']['pincode'],
            "pin" => (int) $request['consignee']['pincode'],

            "city" => $request['consignee']['city'],
            "state" => $request['consignee']['state'],
            "country" => "India",
            "phone" => $request['consignee']['phone'],
            "order" => $request->input('order_number'),
            "payment_mode" => $request['payment_type'], // Prepaid or COD
            "cod_amount" => (string) $request['collectable_amount'], // If COD
            "total_amount" => (string) $request['collectable_amount'],
            "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
            "hsn_code" => "6403", // You can pass per product if required
            "quantity" => array_sum(array_column($request['order_items'], 'qty')),
            "seller_gst_tin" => $gst, // Static or from your config
            "seller_add" => $request['pickup']['address'],
            "seller_name" => $request['pickup']['name'],
            "seller_inv" => $request['unique_order_number'], // or your invoice number
            "shipment_width" => $request['package_breadth'],
            "shipment_height" => $request['package_height'],
            "weight" => $request['package_weight'],
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


    return $payload;
}


    
    protected function delhivery_b2c(Request $request)
{

       $seller = Auth::guard('seller')->user();
       $gst = $seller->gst_no ?? "";
  $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
    // $order->order_number = '#' . ($lastOrder ? ($lastOrder->id + 1) : 1);

$payload = [
    "shipments" => [
        [
            "name" => $request['consignee']['name'],
            "add" => $request['consignee']['address'] . ', ' . $request['consignee']['address_2'],

            // "add" => $request['consignee']['address'],
            // "pin" => $request['consignee']['pincode'],
            "pin" => (int) $request['consignee']['pincode'],

            "city" => $request['consignee']['city'],
            "state" => $request['consignee']['state'],
            "country" => "India",
            "phone" => $request['consignee']['phone'],
            "order" => 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1),
            "payment_mode" => $request['payment_type'], // Prepaid or COD
            "cod_amount" => (string) $request['collectable_amount'], // If COD
            "total_amount" => (string) $request['collectable_amount'],
            "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
            "hsn_code" => "6403", // You can pass per product if required
            "quantity" => array_sum(array_column($request['order_items'], 'qty')),
            "seller_gst_tin" => $gst, // Static or from your config
            "seller_add" => $request['pickup']['address'],
            "seller_name" => $request['pickup']['name'],
            "seller_inv" => $request['unique_order_number'], // or your invoice number
            "shipment_width" => $request['package_breadth'],
            "shipment_height" => $request['package_height'],
            "weight" => $request['package_weight'],
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


    return $payload;
}



    protected function DelhiveryServiceb2b(Request $request)
{
    // dd($request);
    $weightInGrams = $request->input('package_weight', 0.0);
    // $weightInGrams = $weightInKg * 1000;
//    dd((float)$weightInGrams);
    $payload = [
        'lrn' => '',
        'pickup_location_name' => $request->input('pickup.address'),
        'payment_mode' => $request->input('payment_type'),
        'cod_amount' => (int)$request->input('order_amount', 0),
        'weight' => (float)$weightInGrams,

        'dropoff_location' => [
            'consignee_name' => $request->input('consignee.name'),
            'address' => $request->input('consignee.address'),
            'city' => $request->input('consignee.city'),
            'state' => $request->input('consignee.state'),
            'zip' => $request->input('consignee.pincode'),
            'phone' => $request->input('consignee.phone'),
            'email' => '',
        ],

        'rov_insurance' => true,

        'invoices' => [
            [
                'ewaybill' => '',
                'inv_num' => $request->input('order_number'),
                'inv_amt' => (int)$request->input('order_amount'),
                'inv_qr_code' => '',
            ]
        ],

        'shipment_details' => [
            [
                'order_id' => 'test010101',
                'box_count' => 1,
                'description' => 'Test description',
                'weight' => 100,
                'waybills'=> array(),
            ]
        ],

        'doc_data' => [
            [
                'doc_type' => 'INVOICE_COPY',
                'doc_meta' => [
                    'invoice_num' => ['1/2/2025']
                ]
            ]
        ],

        'doc_file' => '',

        'fm_pickup' => false,
        'freight_mode' => 'fop',

        'billing_address' => [
            'name' =>  $request->input('consignee.name'),
            'company' => $request->input('consignee.name'),
            'consignor' => $request->input('consignee.name'),
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'pin' => '123456',
            'phone' => '9876543210',
            'pan_number' => 'ABCDE1234F',
            'gst_number' => '22ABCDE1234F1Z5',
        ],
    ];

    return $payload;
}




protected function shadowfaxprepareRequestData(Request $request)
{
    // dd($request);
    $seller = Auth::guard('seller')->user();

    $paymentType = $request->input('payment_type');

    if ($paymentType === 'prepaid') {
        $paymentMode = 'Prepaid';
        $datts =  0;
    } elseif ($paymentType === 'cod') {
        $paymentMode = 'COD';
       $datts =  $request->input('collectable_amount');

    } else {
        $paymentMode = null; // or some default
    }

    return [
        'order_type' => "marketplace",

        'order_details' => [
            'client_order_id' => $request->input('order_number'),
            'actual_weight' => (float) $request->input('package_weight', 0.0),
            'volumetric_weight' => ((float) $request->input('package_weight', 0.0)) / 5000,
            'product_value' => (int)ltrim($request->input('order_number'), '#'),
            'payment_mode' => $paymentMode,
            'cod_amount' => $datts,
            'promised_delivery_date' => $request->input('order_details.promised_delivery_date') ?? null,
            'total_amount' => $request->input('collectable_amount'),
            'eway_bill' => $request->input('order_details.eway_bill') ?? null,
            'gstin_number' => 701417354627,
            'order_service' => $request->input('order_details.order_service') ?? null,
        ],


        'customer_details' => [
            'name' => $request->input('consignee.name'),
            'contact' => $request->input('consignee.phone'),
            'address_line_1' => $request->input('consignee.address'),
            'address_line_2' => $request->input('consignee.address_2', ''),
            'city' => $request->input('consignee.city'),
            'state' => $request->input('consignee.state'),
            'pincode' => $request->input('consignee.pincode'),
            'alternate_contact' => $request->input('customer_details.alternate_contact') ?? null,
            'latitude' => $request->input('customer_details.latitude') ?? null,
            'longitude' => $request->input('customer_details.longitude') ?? null,
        ],



        'pickup_details' => [
            'name' => $request->input('pickup.name'),
            'contact' => $request->input('pickup.phone'),
            'address_line_1' => $request->input('pickup.address'),
            'address_line_2' => $request->input('pickup.address_2', ''),
            'city' => $request->input('pickup.city'),
            'state' => $request->input('pickup.state'),
            'pincode' => $request->input('pickup.pincode'),
            'latitude' => $request->input('pickup_details.latitude') ?? null,
            'longitude' => $request->input('pickup_details.longitude') ?? null,
            'unique_code' => $request->input('pickup_details.unique_code') ?? null,
        ],



        'rts_details' => [
            'name' => $request->input('rto.name'),
            'contact' => $request->input('rto.phone'),
            'address_line_1' => $request->input('rto.address'),
            'address_line_2' => $request->input('rto.address') ?? null,
            'city' => $request->input('rto.city'),
            'state' => $request->input('rto.state'),
            'pincode' => $request->input('rto.pincode'),
            'email' => $request->input('rts_details.email') ?? null,
            'latitude' => $request->input('rts_details.latitude') ?? null,
            'longitude' => $request->input('rts_details.longitude') ?? null,
            'unique_code' => $request->input('RTS_' . uniqid()) ?? null,
        ],



        $collectableAmount = $request->input('collectable_amount'),

'product_details' => collect($request->input('order_items'))->map(function ($product) use ($collectableAmount) {
    return [
        'hsn_code' => $product['hsn_code'] ?? null,
        'invoice_no' => $product['invoice_no'] ?? null,
        'sku_name' => $product['name'] ?? null, // NOTE: fixed this line
        'sku_id' => $product['sku'] ?? null,
        'category' => $product['category'] ?? null,
        'price' => $collectableAmount, 
        'seller_details' => [
            'seller_name' => $product['seller_name'] ?? null,
            // 'seller_address' => $product['seller_address'] ?? null,
            'seller_state' => $product['seller_state'] ?? null,
            'gstin_number' => $product['gstin_number'] ?? null,
        ],
        'taxes' => [
            'cgst' => $product['taxes']['cgst'] ?? null,
            'sgst' => $product['taxes']['sgst'] ?? null,
            'igst' => $product['taxes']['igst'] ?? null,
            'total_tax' => $product['taxes']['total_tax'] ?? null,
        ],
        'additional_details' => [
            'requires_extra_care' => $product['additional_details']['requires_extra_care'] ?? null,
            'type_extra_care' => $product['additional_details']['type_extra_care'] ?? null,
            'quantity' => $product['additional_details']['quantity'] ?? null,
        ],
    ];
})->toArray(),


    ];
}






protected function prepareRequestDataupdate(Request $request)
{
    // dd($request);
    $collectableAmount = $request->input('payment_type') === 'cod'
        ? (float)$request->input('collectable_amount')
        : 0.0;
  $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
    // $order->order_number = '#' . ($lastOrder ? ($lastOrder->id + 1) : 1);

    $requestData = [
        'order_number' => $request->input('order_number'),
        'unique_order_number' => $request->input('unique_order_number'),
        'shipping_charges' => (float)$request->input('shipping_charges') ?: 0.0,
        'discount' => (float)$request->input('discount') ?: 0.0,
        'cod_charges' => (float)$request->input('cod_charges'),
        'payment_type' => $request->input('payment_type'),
        'order_amount' => (float)$request->input('collectable_amount'),
        'package_weight' => (float)$request->input('package_weight'),
        'package_length' => (float)$request->input('package_length'),
        'package_breadth' => (float)$request->input('package_breadth'),
        'package_height' => (float)$request->input('package_height'),
        'request_auto_pickup' => $request->input('request_auto_pickup'),
        'courier_id' => $request->input('courier_id', ''),
        'collectable_amount' => $collectableAmount,

        'consignee' => [
            'name' => $request->input('consignee.name'),
            'address' => $request->input('consignee.address'),
            'address_2' => $request->input('consignee.address_2', ''),
            'city' => $request->input('consignee.city'),
            'state' => $request->input('consignee.state'),
            'pincode' => $request->input('consignee.pincode'),
            'phone' => $request->input('consignee.phone'),
        ],

        'pickup' => [
            'warehouse_name' => $request->input('pickup.name', ''),
            'name' => $request->input('pickup.name'),
            'address' => $request->input('pickup.address'),
            'address_2' => $request->input('pickup.address_2', ''),
            'city' => $request->input('pickup.city'),
            'state' => $request->input('pickup.state'),
            'pincode' => $request->input('pickup.pincode'),
            'phone' => $request->input('pickup.phone'),
        ],

        'is_rto_different' => $request->input('is_rto_different'),

        'order_items' => array_map(function ($item) use ($request) {
            return [
                'name' => $item['name'],
                'qty' => (int)$item['qty'],
                'sku' => $item['sku'] ?? '',
                'price' => $request->input('payment_type') === 'cod'
                    ? 0.0
                    : (float)($item['price'] ?? 0.0),
            ];
        }, $request->input('order_items', [])),


     
    ];

    if ($request->input('is_rto_different') === 'yes') {
        $requestData['rto'] = [
            'warehouse_name' => $request->input('rto.warehouse_name'),
            'name' => $request->input('rto.name'),
            'address' => $request->input('rto.address'),
            'city' => $request->input('rto.city'),
            'state' => $request->input('rto.state'),
            'pincode' => $request->input('rto.pincode'),
            'phone' => $request->input('rto.phone'),
        ];
    }

    return $requestData;
}




protected function prepareRequestData(Request $request)
{
    // dd($request);
    $collectableAmount = $request->input('payment_type') === 'cod'
        ? (float)$request->input('collectable_amount')
        : 0.0;
  $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
    // $order->order_number = '#' . ($lastOrder ? ($lastOrder->id + 1) : 1);

    $requestData = [
        'order_number' => 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1),
        'unique_order_number' => $request->input('unique_order_number'),
        'shipping_charges' => (float)$request->input('shipping_charges') ?: 0.0,
        'discount' => (float)$request->input('discount') ?: 0.0,
        'cod_charges' => (float)$request->input('cod_charges'),
        'payment_type' => $request->input('payment_type'),
        'order_amount' => (float)$request->input('collectable_amount'),
        'package_weight' => (float)$request->input('package_weight'),
        'package_length' => (float)$request->input('package_length'),
        'package_breadth' => (float)$request->input('package_breadth'),
        'package_height' => (float)$request->input('package_height'),
        'request_auto_pickup' => $request->input('request_auto_pickup'),
        'courier_id' => $request->input('courier_id', ''),
        'collectable_amount' => $collectableAmount,

        'consignee' => [
            'name' => $request->input('consignee.name'),
            'address' => $request->input('consignee.address'),
            'address_2' => $request->input('consignee.address_2', ''),
            'city' => $request->input('consignee.city'),
            'state' => $request->input('consignee.state'),
            'pincode' => $request->input('consignee.pincode'),
            'phone' => $request->input('consignee.phone'),
        ],

        'pickup' => [
            'warehouse_name' => $request->input('pickup.name', ''),
            'name' => $request->input('pickup.name'),
            'address' => $request->input('pickup.address'),
            'address_2' => $request->input('pickup.address_2', ''),
            'city' => $request->input('pickup.city'),
            'state' => $request->input('pickup.state'),
            'pincode' => $request->input('pickup.pincode'),
            'phone' => $request->input('pickup.phone'),
        ],

        'is_rto_different' => $request->input('is_rto_different'),

        'order_items' => array_map(function ($item) use ($request) {
            return [
                'name' => $item['name'],
                'qty' => (int)$item['qty'],
                'sku' => $item['sku'] ?? '',
                'price' => $request->input('payment_type') === 'cod'
                    ? 0.0
                    : (float)($item['price'] ?? 0.0),
            ];
        }, $request->input('order_items', [])),


     
    ];

    if ($request->input('is_rto_different') === 'yes') {
        $requestData['rto'] = [
            'warehouse_name' => $request->input('rto.warehouse_name'),
            'name' => $request->input('rto.name'),
            'address' => $request->input('rto.address'),
            'city' => $request->input('rto.city'),
            'state' => $request->input('rto.state'),
            'pincode' => $request->input('rto.pincode'),
            'phone' => $request->input('rto.phone'),
        ];
    }

    return $requestData;
}



    protected function handleSuccessResponse($response)
    {
        $responseBody = json_decode($response->getBody(), true);

        Log::info('XpressBees API Success', [
            'response' => $responseBody
        ]);

        if (isset($responseBody['status']) && $responseBody['status'] === true && isset($responseBody['data']['awb_number'])) {
            return view('sellerdashboard.order.add', [
                'shipment' => $responseBody['data'],
                'message' => 'Shipment created successfully',
            ]);
        }

        return back()->withErrors(['error' => 'Invalid response from XpressBees']);
    }

    protected function handleError(\Exception $e)
    {
        Log::error('XpressBees API Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => request()->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create shipment',
            'error' => $e->getMessage()
        ], 500);
    }





    public function assignCourier(Request $request)
    {
        //    dd($request);
        $orderId = $request->input('order_id');
        $courierId = $request->input('courier_id');
        $courierCharge = $request->input('courier_charge');
        $freightCharges = $request->input('freight_charges');
        $codCharges = $request->input('cod_charges');
        $sedofexcourier_charge = $request->input('sedofexcourier_charge');
        $serviceability_id = $request->input('serviceability_id');
        $provider_name = $request->input('provider_name');


        $logisticProvider = LogisticProvider::where('id', $courierId)->first();
        $key = 'courier.' . $logisticProvider->code;

        // CRM courier assign branch (new Courier & Rate Manager couriers)
        if (is_string($serviceability_id) && strpos($serviceability_id, 'crm-') === 0) {
            $order = Order::where('id', $orderId)->first();
            $crmAccountId = (int) explode('-', $serviceability_id)[1];
            $pickupArr = is_string($order->pickup) ? json_decode($order->pickup, true) : (array) $order->pickup;
            $consigneeArr = is_string($order->consignee) ? json_decode($order->consignee, true) : (array) $order->consignee;
            $itemsArr = is_string($order->order_items) ? json_decode($order->order_items, true) : (array) $order->order_items;
            if (!is_array($itemsArr)) { $itemsArr = []; }
            $isCod = strtolower($order->payment_type) === 'cod';
            $codAmt = $isCod ? (float) ($order->collectable_amount ?? $order->order_amount ?? 0) : 0;
            $warehouseName = app(\App\Services\WarehouseRegistrationService::class)->ensureWarehouse($order, $crmAccountId, 'delhivery_zapdeal');
            $params = [
                'shipments' => [[
                    'name' => $consigneeArr['name'] ?? '',
                    'add' => trim(($consigneeArr['address'] ?? '') . ', ' . ($consigneeArr['address_2'] ?? ''), ', '),
                    'pin' => (int) ($consigneeArr['pincode'] ?? 0),
                    'city' => $consigneeArr['city'] ?? '',
                    'state' => $consigneeArr['state'] ?? '',
                    'country' => 'India',
                    'phone' => $consigneeArr['phone'] ?? '',
                    'order' => $order->order_number,
                    'payment_mode' => $isCod ? 'COD' : 'Prepaid',
                    'cod_amount' => $codAmt,
                    'total_amount' => (float) ($order->order_amount ?? $codAmt),
                    'products_desc' => count($itemsArr) ? implode(', ', array_map(fn($i) => $i['name'] ?? 'Item', $itemsArr)) : 'Order',
                    'quantity' => count($itemsArr) ? array_sum(array_map(fn($i) => (int) ($i['qty'] ?? 1), $itemsArr)) : 1,
                    'weight' => (float) ($order->package_weight ?? 100),
                    'shipment_length' => (float) ($order->package_length ?? 10),
                    'shipment_width' => (float) ($order->package_breadth ?? 10),
                    'shipment_height' => (float) ($order->package_height ?? 10),
                ]],
                'pickup_location' => [
                    'name' => $warehouseName,
                    'phone' => $pickupArr['phone'] ?? '',
                    'add' => $pickupArr['address'] ?? '',
                    'city' => $pickupArr['city'] ?? '',
                    'pin_code' => (string) ($pickupArr['pincode'] ?? ''),
                    'country' => 'India',
                ],
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
        } else if ($courierId == '2') {

           $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
               ];

            $order = Order::where('id', $orderId)->first();
            // $params = json_decode($order->shadowfax_json);
            // $paymentType = $order->payment_type;

            // $order->shadowfax_json = json_encode($params);
            $order->seller_amount_walate = $courierCharge;
            $order->save();
        } else if ($courierId == '1') {
            $order = Order::where('id', $orderId)->first();
            $params = json_decode($order->encode_data);
            //  dd($params);
            $params->cod_charges = $codCharges;
            $params->courier_id = $serviceability_id;

            $order->encode_data = json_encode($params);
            $order->seller_amount_walate = $courierCharge;
            $order->save();
        } else if ($courierId == '4') {
            $order = Order::where('id', $orderId)->first();
            $params = json_decode($order->delhivery_b2c);
            $order->seller_amount_walate = $courierCharge;
            $order->save();
   
        } else if ($courierId == '5') {
            $order = Order::where('id', $orderId)->first();
            $params = json_decode($order->delhivery_b2c_air);
            $order->seller_amount_walate = $courierCharge;
            $order->save();

        } else if ($courierId == '6') {
            $order = Order::where('id', $orderId)->first();
            //  $params = $orderId;
            $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
        
        }else if ($courierId == '8') {
            $order = Order::where('id', $orderId)->first();
            $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
 
        }else if ($courierId == '9') {
            $order = Order::where('id', $orderId)->first();
            $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
 
        }else if ($courierId == '10') {
            $order = Order::where('id', $orderId)->first();
            $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
 
        }else if ($courierId == '11') {
            $order = Order::where('id', $orderId)->first();
            $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
 
        }else if ($courierId == '13') {
            $order = Order::where('id', $orderId)->first();
            $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
 
        }else if ($courierId == '28') {
            $order = Order::where('id', $orderId)->first();
            $params = [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
            $order->seller_amount_walate = $courierCharge;
            $order->save();
 
        }
        else {


            // $order = Order::where('id', $orderId)->first();
            // $params = json_decode($order->DelhiveryServiceb2b);  

        }

        //   dd($key);
        // dd($params);
        // EKART_CRM_BRANCH: if this CRM courier is Ekart, use the Ekart service + payload
            if (is_string($serviceability_id) && strpos($serviceability_id, 'crm-') === 0) {
                $__acctId = (int) explode('-', $serviceability_id)[1];
                $__code   = (string) \Illuminate\Support\Facades\DB::table('courier_accounts')->where('id', $__acctId)->value('code');
                if (stripos($__code, 'ekart') !== false) {
                    $__o  = \App\Models\Order::find($orderId);
                    $__p  = is_string($__o->pickup) ? json_decode($__o->pickup, true) : (array) $__o->pickup;
                    $__c  = is_string($__o->consignee) ? json_decode($__o->consignee, true) : (array) $__o->consignee;
                    $__it = is_string($__o->order_items) ? json_decode($__o->order_items, true) : (array) $__o->order_items;
                    if (!is_array($__it)) { $__it = []; }
                    $__cod   = strtoupper(trim((string) $__o->payment_type)) === 'COD';
                    $__codAmt= $__cod ? (float) ($__o->collectable_amount ?? $__o->order_amount ?? 0) : 0;
                    $__total = (float) ($__o->order_amount ?: $__codAmt ?: 100);
                // EKART_MIN_AMOUNT: Ekart rejects total_amount < 1 (VALIDATOR_SCHEMA)
                $__total = max(2, $__total);
                $__tax = max(1, round($__total * 18 / 118, 2));
                    $__taxbl = max(1, round($__total - $__tax, 2));
                    $__ph    = (int) preg_replace('/\D/', '', substr((string) ($__c['phone'] ?? '9999999999'), -10));
                    $__alt   = (string) ($__p['phone'] ?? '8888888888');
                    if ($__alt === (string) $__ph || $__alt === '') { $__alt = '8888888888'; }
                    // EKART_WH_ALIAS: register this pickup with Ekart once, then reuse the stored alias
                    $__alias = app(\App\Services\WarehouseRegistrationService::class)
                        ->ensureWarehouse($__o, $__acctId, $__code);

                    // EKART_SELLER_GST: real seller GST when available, dummy fallback otherwise
                    $__sellerRow = \Illuminate\Support\Facades\DB::table('seller_lists')->where('id', $__o->seller_id)->first();
                    $__gst = trim((string) ($__sellerRow->gst_no ?? ''));
                    if ($__gst === '' || strlen($__gst) < 15) {
                        $__gst = '09AAACH7409R1ZZ'; // fallback for sellers without GST
                    }
                    $__sellerName = trim((string) ($__sellerRow->gst_verified_name ?? '')) ?: (trim((string) ($__sellerRow->name ?? '')) ?: 'Shipxpeed Logistics LLP');

                    $key = 'courier.ekart_b2c_elite';
                    $params = [
                        'order_number'   => (string) $__o->order_number,
                        'invoice_number' => 'INV-' . $__o->id,
                        'invoice_date'   => date('Y-m-d'),
                        'seller_name'    => $__sellerName,
                        'seller_address' => (string) ($__p['address'] ?? ''),
                        'seller_gst_tin' => $__gst,
                        'consignee_gst_amount' => 0,
                        'consignee_name' => (string) ($__c['name'] ?? ''),
                        'consignee_alternate_phone' => $__alt,
                        'payment_mode'   => $__cod ? 'COD' : 'Prepaid',
                        'category_of_goods' => 'General',
                        'products_desc'  => count($__it) ? implode(', ', array_map(fn($i) => $i['name'] ?? 'Item', $__it)) : 'Order',
                        'total_amount'   => $__total,
                        'tax_value'      => $__tax,
                        'taxable_amount' => $__taxbl,
                        'commodity_value'=> (string) $__taxbl,
                        'cod_amount'     => $__cod ? $__codAmt : 0,
                        'return_reason'  => '',
                        'quantity'       => count($__it) ? array_sum(array_map(fn($i) => (int) ($i['qty'] ?? 1), $__it)) : 1,
                        'weight'         => (int) ($__o->package_weight ?: 500),
                        'length'         => (int) ($__o->package_length ?: 10),
                        'width'          => (int) ($__o->package_breadth ?: 10),
                        'height'         => (int) ($__o->package_height ?: 10),
                        'drop_location'  => [
                            'name'    => (string) ($__c['name'] ?? ''),
                            'address' => trim(($__c['address'] ?? '') . ' ' . ($__c['address_2'] ?? '')),
                            'city'    => (string) ($__c['city'] ?? ''),
                            'state'   => (string) ($__c['state'] ?? ''),
                            'country' => 'India',
                            'phone'   => $__ph,
                            'pin'     => (int) ($__c['pincode'] ?? 0),
                        ],
                        'pickup_location' => ['name' => $__alias],
                        'return_location' => ['name' => $__alias],
                    ];
                }
            }

            // GENERIC_CRM_ORDERID: non-Ekart CRM couriers (e.g. Sharkship) build their own
            // payload inside the service and only need the order id passed through.
            if (is_string($serviceability_id) && strpos($serviceability_id, "crm-") === 0) {
                if (empty($params["order_id"])) {
                    $params["order_id"] = $orderId;
                }
                // CRM_COURIER_ID: services like Sharkship resolve the courier account from
                // $order->courier_id, so persist the CRM account id on the order before assigning.
                $crmOrder = Order::find($orderId);
                if ($crmOrder && (int) $crmOrder->courier_id !== $crmAccountId) {
                    $crmOrder->courier_id = $crmAccountId;
                    $crmOrder->save();
                }
            }
        $response = app($key)->assignOrder($params);
        // dd($response); 
        Log::info($response);

        // EKART_CRM_SAVE: Ekart returns top-level awb/awb_number (no couriername/data wrapper).
        if (is_string($serviceability_id) && strpos($serviceability_id, 'crm-') === 0 && !empty($response['success']) && !empty($response['awb_number'])) {
            $order = $order ?? Order::find($orderId);
            if ($order) {
                $order->awb_number = $response['awb_number'];
                $order->courier_id = $courierId;
                $order->courier_name = $provider_name ?: ($response['vendor'] ?? 'Ekart');
                $order->shipping_status = 'Assigned';
                $order->save();
                // SHOPIFY_FULFILL: push tracking to Shopify (safe no-op for non-Shopify orders)
                $this->pushShopifyFulfillment($order, $order->awb_number, $order->courier_name);
            }
            return redirect()->route('seller.order')->with('success', 'Courier assigned successfully! AWB: ' . $response['awb_number']);
        }
        // //   return $response;



if (
    !empty($response['status']) &&
    $response['status'] === true &&
    !empty($response['couriername']) &&
    $response['couriername'] === 'shiprocket' &&
    !empty($response['awb_number'])
) {
// 
    return redirect()
        ->route('seller.order')
        ->with('success', 'Order successfully assigned! AWB: ' . $response['awb_number']);
}


if (
    !empty($response['status']) &&
    $response['status'] === true &&
    !empty($response['couriername']) &&
    $response['couriername'] === 'shadowfax'
) {

        return redirect()
        ->route('seller.order')
        ->with('success', 'Order successfully assigned ! AWB: ' . ($response['awb_number'] ?? 'N/A'));


}



if (
    !empty($response['status']) &&
    $response['status'] === true &&
    !empty($response['couriername']) &&
    $response['couriername'] === 'parcelx'
) {
    // Return structured response instead of redirect

    return redirect()
        ->route('seller.order')
        ->with('success', 'Order successfully assigned ! AWB: ' . ($response['awb_number'] ?? 'N/A'));


}

if (
    !empty($response['status']) &&
    $response['status'] == true &&
    !empty($response['couriername']) &&
    strtolower($response['couriername']) == 'sharkship'
) {

    return redirect()
        ->route('seller.order')
        ->with(
            'success',
            'Courier assigned successfully! AWB: ' .
            ($response['awb_number'] ?? 'N/A')
        );

}


if (
    !empty($response['status']) &&
    $response['status'] === true &&
    !empty($response['couriername']) &&
    $response['couriername'] === 'dtdc'
) {
    return redirect()
        ->route('seller.order')
        ->with('success', 'Order successfully assigned to DTDC! AWB: ' . ($response['awb_number'] ?? 'N/A'));
}

        if (
            !empty($response['status']) &&
            $response['status'] === true &&
            !empty($response['couriername']) &&
            $response['couriername'] === 'boxd'
        ) {
            // echo 'xasxavicky';die;
            return redirect()
                ->route('seller.order')
                ->with('success', 'Courier assigned successfully! AWB: ' . ($response['awb_number'] ?? 'N/A'));
        }

        if (
            isset($response['status']) && $response['status'] == true
            && isset($response['couriername']) && $response['couriername'] == 'tekipost'
        ) {
            return redirect()->route('seller.order')->with('success', 'Courier assigned successfully! AWB: ' . $response['awb_number']);
        }


        if (
            isset($response['status']) && $response['status'] == 1
            && isset($response['couriername']) && $response['couriername'] == 'smart'
        ) {
            return redirect()->route('seller.order')->with('success', 'Courier assigned successfully! AWB: ' . $response['awb_number']);
        }

        // Check if new style response (with 'packages')
        if (
            isset($response['success']) && $response['success'] === true &&
            isset($response['packages'][0]['waybill'])
        ) {
            $awb = $response['packages'][0]['waybill'];
            $order->awb_number = $awb;
            $order->courier_id = "delhivery_b2c";

            $order->save();
            // SHOPIFY_FULFILL: push tracking to Shopify (safe no-op for non-Shopify orders)
            $this->pushShopifyFulfillment($order, $awb, $order->courier_name ?? 'Delhivery');

            return redirect()->route('seller.order')->with('success', 'Courier assigned successfully! AWB: ' . $awb);
        }


        if (
            $response &&
            (
                (isset($response['status']) && $response['status'] == true) ||
                (isset($response['message']) && $response['message'] === "Success")
            )
        ) {
            $data = $response['data']['awb_number'] ?? null;
            $order_id = $response['data']['order_id'] ?? null;
            $shipment_id = $response['data']['shipment_id'] ?? null;
            $courier_id = $response['data']['courier_id'] ?? null;
            $courier_name = $response['data']['courier_name'] ?? null;
            $status = $response['data']['status'] ?? null;
            $additional_info = $response['data']['additional_info'] ?? null;
            $payment_type = $response['data']['payment_type'] ?? null;
            $fwd_destination_code = $response['data']['fwd_destination_code'] ?? null;
            $label = $response['data']['label'] ?? null;
            $manifest = $response['data']['manifest'] ?? null;

            if ($data) {
                $order->awb_number = $data;
                $order->courier_order_id = $order_id;
                $order->shipment_id = $shipment_id;
                $order->co_courier_id = $courier_id;
                $order->courier_name = $courier_name;
                $order->status = $status;
                $order->additional_info = $additional_info;
                $order->co_payment_type = $payment_type;
                $order->fwd_destination_code = $fwd_destination_code;
                $order->label = $label;
                $order->manifest = $manifest;
                $order->save();
                // SHOPIFY_FULFILL: push tracking to Shopify (safe no-op for non-Shopify orders)
                $this->pushShopifyFulfillment($order, $data, $courier_name);

                return redirect()->route('seller.order')->with('success', 'Courier assigned successfully! AWB: ' . $data);
            } else {
                return redirect()->route('seller.order')->with('error', 'Failed to assign courier. Please try again later.');
            }
        }

        // If none matched
        return redirect()->route('seller.order')->with('error', 'Failed to assign courier. Please try again later.');
    }





































    public function assignCourier_bulk(Request $request)
    {
        // Handle bulk order assignment
        $orderIds = $request->input('order_ids', []);
        $courierId = $request->input('courier_id');
        $courierCharge = $request->input('courier_charge');
        $freightCharges = $request->input('freight_charges');
        $codCharges = $request->input('cod_charges');
        $serviceability_id = $request->input('serviceability_id');
        $provider_name = $request->input('provider_name');

        // Handle single order for backward compatibility
        if (!$orderIds && $request->input('order_id')) {
            $orderIds = [$request->input('order_id')];
        }

        if (empty($orderIds)) {
            return redirect()->route('seller.order')->with('error', 'No orders provided for bulk assignment.');
        }

        $logisticProvider = LogisticProvider::where('id', $courierId)->first();
        if (!$logisticProvider) {
            return redirect()->route('seller.order')->with('error', 'Invalid courier provider.');
        }

        $key = 'courier.' . $logisticProvider->code;
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        // Process each order
        foreach ($orderIds as $orderId) {
            try {
                $order = Order::where('id', $orderId)->first();
                if (!$order) {
                    $results[] = "Order ID {$orderId}: Not found";
                    $failureCount++;
                    continue;
                }

                // Prepare parameters based on courier type
                $params = $this->prepareBulkCourierParams($courierId, $orderId, $provider_name, $codCharges, $serviceability_id);
                
                // Update seller wallet amount
                $order->seller_amount_walate = $courierCharge;
                $order->save();

                // Call the appropriate courier service
                $response = app($key)->assignOrderbulk($params);

                // Process response
                $result = $this->processBulkCourierResponse($response, $order, $orderId);
                
                if ($result['success']) {
                    $successCount++;
                    $results[] = "Order ID {$orderId}: Successfully assigned - AWB: {$result['awb']}";
                } else {
                    $failureCount++;
                    $results[] = "Order ID {$orderId}: Failed - {$result['message']}";
                }

            } catch (\Exception $e) {
                $failureCount++;
                $results[] = "Order ID {$orderId}: Exception - " . $e->getMessage();
                Log::error("Bulk courier assignment error for order {$orderId}: " . $e->getMessage());
            }
        }

        // Return response with proper flash messages
        if ($successCount > 0 && $failureCount == 0) {
            return redirect()->route('seller.order')->with('success', "All {$successCount} orders successfully assigned to courier!");
        } elseif ($successCount > 0 && $failureCount > 0) {
            $successDetails = implode(', ', array_filter($results, function($result) {
                return strpos($result, 'Successfully assigned') !== false;
            }));
            return redirect()->route('seller.order')->with('success', "Bulk assignment completed: {$successCount} successful, {$failureCount} failed. " . substr($successDetails, 0, 200));
        } else {
            $errorDetails = implode(', ', array_slice($results, 0, 3));
            return redirect()->route('seller.order')->with('error', "Bulk assignment failed: {$failureCount} orders failed. " . substr($errorDetails, 0, 200));
        }
    }

    private function prepareBulkCourierParams($courierId, $orderId, $provider_name, $codCharges, $serviceability_id)
    {
        $order = Order::where('id', $orderId)->first();
        
        if ($courierId == '2') {
            return [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
        } else if ($courierId == '1') {
            $params = json_decode($order->encode_data);
            $params->cod_charges = $codCharges;
            $params->courier_id = $serviceability_id;
            $order->encode_data = json_encode($params);
            $order->save();
            return $params;
        } else if ($courierId == '4') {
            return json_decode($order->delhivery_b2c);
        } else if ($courierId == '5') {
            return json_decode($order->delhivery_b2c_air);
        } else if (in_array($courierId, ['6', '8', '9', '10', '11', '13', '28'])) {
            return [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
        } else {
            return [
                'order_id' => $orderId,
                'provider_name' => $provider_name
            ];
        }
    }

    private function processBulkCourierResponse($response, $order, $orderId)
    {
        // Shiprocket response
        if (!empty($response['status']) && $response['status'] === true && 
            !empty($response['couriername']) && $response['couriername'] === 'shiprocket' && 
            !empty($response['awb_number'])) {
            return ['success' => true, 'awb' => $response['awb_number']];
        }

        // Shadowfax response
        if (!empty($response['status']) && $response['status'] === true && 
            !empty($response['couriername']) && $response['couriername'] === 'shadowfax') {
            return ['success' => true, 'awb' => $response['awb_number'] ?? 'N/A'];
        }

        // Parcelx response
        if (!empty($response['status']) && $response['status'] === true && 
            !empty($response['couriername']) && $response['couriername'] === 'parcelx') {
            return ['success' => true, 'awb' => $response['awb_number'] ?? 'N/A'];
        }

        // DTDC response
        if (!empty($response['status']) && $response['status'] === true && 
            !empty($response['couriername']) && $response['couriername'] === 'dtdc') {
            return ['success' => true, 'awb' => $response['awb_number'] ?? 'N/A'];
        }

        // Boxd response
        if (!empty($response['status']) && $response['status'] === true && 
            !empty($response['couriername']) && $response['couriername'] === 'boxd') {
            return ['success' => true, 'awb' => $response['awb_number'] ?? 'N/A'];
        }

        // Tekipost response
        if (isset($response['status']) && $response['status'] == true && 
            isset($response['couriername']) && $response['couriername'] == 'tekipost') {
            return ['success' => true, 'awb' => $response['awb_number']];
        }

        // Smart response
        if (isset($response['status']) && $response['status'] == 1 && 
            isset($response['couriername']) && $response['couriername'] == 'smart') {
            return ['success' => true, 'awb' => $response['awb_number']];
        }

        // Delhivery B2C response (with packages)
        if (isset($response['success']) && $response['success'] === true && 
            isset($response['packages'][0]['waybill'])) {
            $awb = $response['packages'][0]['waybill'];
            $order->awb_number = $awb;
            $order->courier_id = "delhivery_b2c";
            $order->save();
            return ['success' => true, 'awb' => $awb];
        }

        // XpressBees style response
        if ($response && 
            ((isset($response['status']) && $response['status'] == true) || 
             (isset($response['message']) && $response['message'] === "Success"))) {
            
            $awb = $response['data']['awb_number'] ?? null;
            if ($awb) {
                $order->awb_number = $awb;
                $order->courier_order_id = $response['data']['order_id'] ?? null;
                $order->shipment_id = $response['data']['shipment_id'] ?? null;
                $order->co_courier_id = $response['data']['courier_id'] ?? null;
                $order->courier_name = $response['data']['courier_name'] ?? null;
                $order->status = $response['data']['status'] ?? null;
                $order->save();
                return ['success' => true, 'awb' => $awb];
            }
        }

        return ['success' => false, 'message' => 'Invalid response or failed assignment'];
    }










public function assignCourier_bulkold(Request $request)
{
    $orderIds = $request->input('order_ids', []);
    $courierId = $request->input('courier_id');
    $courierCharge = $request->input('courier_charge');
    $freightCharges = $request->input('freight_charges');
    $codCharges = $request->input('cod_charges');
    $serviceability_id = $request->input('serviceability_id');
    $provider_name = $request->input('provider_name');

    $logisticProvider = LogisticProvider::find($courierId);
    $key = 'courier.' . $logisticProvider->code;

    $success = [];
    $failures = [];

    foreach ($orderIds as $orderId) {
        try {
            $order = Order::find($orderId);
            if (!$order) {
                $failures[] = "Order ID {$orderId} not found.";
                continue;
            }
        if ($order) {
           $order->seller_amount_walate = $courierCharge;
            $order->save();              
               
            }

            // Set courier-specific payload
            if ($courierId == '2') {
                $params = json_decode($order->shadowfax_json);
            } elseif ($courierId == '1') {
                $params = json_decode($order->encode_data);
                $params->cod_charges = $codCharges;
                $params->courier_id = $serviceability_id;
                $order->encode_data = json_encode($params);
            } elseif ($courierId == '4') {
                $params = json_decode($order->delhivery_b2c);
            } elseif ($courierId == '5') {
                $params = json_decode($order->delhivery_b2c_air);
            } elseif ($courierId == '6') {
                $params = [
                    'order_id'      => $orderId,
                    'provider_name' => $provider_name
                ];
            } else {
                $params = json_decode($order->DelhiveryServiceb2b);
            }

            // Assign courier via service binding
            $response = app($key)->assignOrder_bulk($params);
            //  return $response;

            if (
                isset($response['success']) && $response['success'] === true &&
                isset($response['packages'][0]['waybill'])
            ) {
                $awb = $response['packages'][0]['waybill'];
                $order->awb_number = $awb;
                $order->courier_id = "delhivery_b2c";
                $order->seller_amount_walate = $courierCharge;
                $order->save();
                // SHOPIFY_FULFILL: push tracking to Shopify (safe no-op for non-Shopify orders)
                $this->pushShopifyFulfillment($order, $awb, $order->courier_name);
                $success[] = "Order ID {$orderId} → AWB: {$awb}";
                continue;
            }

            if (
                isset($response['status']) && $response['status'] == 1 &&
                isset($response['couriername']) && $response['couriername'] == 'smart'
            ) {
                $order->awb_number = $response['awb_number'];
                $order->courier_id = $courierId;
                $order->seller_amount_walate = $courierCharge;
                $order->save();
                // SHOPIFY_FULFILL: push tracking to Shopify (safe no-op for non-Shopify orders)
                $this->pushShopifyFulfillment($order, $response['awb_number'], $order->courier_name);
                $success[] = "Order ID {$orderId} → AWB: {$response['awb_number']}";
                continue;
            }

            // Fallback response
            if (
                isset($response['status']) && $response['status'] == true ||
                isset($response['message']) && $response['message'] === "Success"
            ) {
                $order->awb_number = $response['data']['awb_number'] ?? null;
                $order->courier_order_id = $response['data']['order_id'] ?? null;
                $order->shipment_id = $response['data']['shipment_id'] ?? null;
                $order->co_courier_id = $response['data']['courier_id'] ?? null;
                $order->courier_name = $response['data']['courier_name'] ?? null;
                $order->status = $response['data']['status'] ?? null;
                $order->additional_info = $response['data']['additional_info'] ?? null;
                $order->co_payment_type = $response['data']['payment_type'] ?? null;
                $order->fwd_destination_code = $response['data']['fwd_destination_code'] ?? null;
                $order->label = $response['data']['label'] ?? null;
                $order->manifest = $response['data']['manifest'] ?? null;
                $order->seller_amount_walate = $courierCharge;
                $order->save();
                // SHOPIFY_FULFILL: push tracking to Shopify (safe no-op for non-Shopify orders)
                $this->pushShopifyFulfillment($order, $order->awb_number, $order->courier_name);

                $success[] = "Order ID {$orderId} → AWB: {$order->awb_number}";
                continue;
            }

            $failures[] = "Order ID {$orderId} failed: No valid response.";
        } catch (\Exception $e) {
            $failures[] = "Order ID {$orderId} exception: " . $e->getMessage();
        }
    }

    return redirect()->route('seller.order')->with([
        'success' => implode(', ', $success),
        'error' => implode(', ', $failures),
    ]);
}













    /**
     * SHOPIFY_FULFILL: after a courier is assigned locally, push the fulfillment
     * (tracking number + carrier) to Shopify so the order flips from
     * "Unfulfilled" to "Fulfilled" there too. Silent no-op for non-Shopify orders
     * or on any API failure so it never breaks the courier-assignment flow.
     */
    private function pushShopifyFulfillment($order, $trackingNumber, $courierName)
    {
        try {
            if (!$order || $order->channel !== 'Shopify' || empty($order->shopify_order_id)) {
                return;
            }
            $conn = \App\Models\ShopifyConnection::where('seller_id', $order->seller_id)
                ->where('status', 1)
                ->first();
            if (!$conn || empty($conn->access_token)) {
                return;
            }
            $base = 'https://' . $conn->shop_domain . '/admin/api/2025-07';
            $headers = ['X-Shopify-Access-Token' => $conn->access_token, 'Content-Type' => 'application/json'];

            $foResp = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->get($base . '/orders/' . $order->shopify_order_id . '/fulfillment_orders.json');
            if (!$foResp->successful()) {
                \Illuminate\Support\Facades\Log::warning('SHOPIFY_FULFILL: fulfillment_orders fetch failed', ['order_id' => $order->id, 'body' => $foResp->body()]);
                return;
            }
            $fulfillmentOrders = $foResp->json('fulfillment_orders') ?? [];
            $openFo = null;
            foreach ($fulfillmentOrders as $fo) {
                if (($fo['status'] ?? '') === 'open' || ($fo['status'] ?? '') === 'in_progress') {
                    $openFo = $fo;
                    break;
                }
            }
            if (!$openFo) {
                return;
            }

            $payload = [
                'fulfillment' => [
                    'line_items_by_fulfillment_order' => [
                        ['fulfillment_order_id' => $openFo['id']],
                    ],
                    'tracking_info' => [
                        'number' => (string) $trackingNumber,
                        'company' => (string) $courierName,
                    ],
                    'notify_customer' => true,
                ],
            ];

            $fResp = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->post($base . '/fulfillments.json', $payload);

            if (!$fResp->successful()) {
                \Illuminate\Support\Facades\Log::warning('SHOPIFY_FULFILL: fulfillment create failed', ['order_id' => $order->id, 'body' => $fResp->body()]);
            } else {
                \Illuminate\Support\Facades\Log::info('SHOPIFY_FULFILL: success', ['order_id' => $order->id]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SHOPIFY_FULFILL: exception', ['order_id' => $order->id ?? null, 'message' => $e->getMessage()]);
        }
    }



}
