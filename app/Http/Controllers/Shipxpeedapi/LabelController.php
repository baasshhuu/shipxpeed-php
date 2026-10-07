<?php

namespace App\Http\Controllers\Shipxpeedapi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\SellerList;
use App\Models\Warehouse;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class LabelController extends Controller
{
    /**
     * API method to download custom label with token authentication
     */
    public function apiDownloadCustomLabel(Request $request, $id)
    {
        try {
            // Get Authorization token from header
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Authorization token required'], 401);
            }

            // Find seller by token
            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
            }

            // Get seller ID from token
            $sellerId = $seller->id;

            // Find order for this seller
            $order = Order::where('seller_id', $sellerId)->find($id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Get seller's label settings
            $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();

            // Generate label HTML
            $labelHtml = $this->generateLabelHtml($order, $settings);

            // Generate PDF
            $pdf = Pdf::loadHTML($labelHtml);

            if ($settings && $settings->label_type == 'thermal') {
                $pdf->setPaper([0, 0, 288, 432], 'portrait'); // 4x6 inches in points
            } else {
                $pdf->setPaper('A4', 'portrait');
            }

            // Generate unique filename
            $filename = 'label_' . $sellerId . '_' . $order->id . '_' . time() . '.pdf';
            
            // Create directory if it doesn't exist - using relative path from document root
            $labelDir = $_SERVER['DOCUMENT_ROOT'] . '/labels';
            if (!file_exists($labelDir)) {
                mkdir($labelDir, 0755, true);
            }
            
            // Save PDF to public folder
            $pdfContent = $pdf->output();
            file_put_contents($labelDir . '/' . $filename, $pdfContent);
            
            // Generate URL using request host
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $fileUrl = $protocol . $host . '/labels/' . $filename;

            return response()->json([
                'success' => true,
                'message' => 'Label generated successfully',
                'data' => [
                    'seller_id' => $sellerId,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'pdf_url' => $fileUrl
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating label: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API method to get label data without generating PDF
     */
    public function apiGetLabelData(Request $request, $id)
    {
        try {
            // Get Authorization token from header
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Authorization token required'], 401);
            }

            // Find seller by token
            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
            }

            // Get seller ID from token
            $sellerId = $seller->id;

            // Find order for this seller
            $order = Order::where('seller_id', $sellerId)->find($id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Get seller's label settings
            $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();

            // Parse order data
            $orderItems = [];
            if (!empty($order->order_items)) {
                $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
                if (is_array($items)) $orderItems = $items;
            }

            $consignee = [];
            if (!empty($order->consignee)) {
                $cData = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
                if (is_array($cData)) $consignee = $cData;
            }

            $pickup = [];
            if (!empty($order->pickup)) {
                $pData = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
                if (is_array($pData)) $pickup = $pData;
            }

            return response()->json([
                'success' => true,
                'message' => 'Label data retrieved successfully',
                'data' => [
                    'seller_id' => $sellerId,
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'awb_number' => $order->awb_number,
                        'order_date' => $order->created_at ? $order->created_at->format('d M Y') : null,
                        'payment_type' => $order->payment_type,
                        'order_amount' => $order->order_amount,
                        'collectable_amount' => $order->collectable_amount,
                        'courier_id' => $order->courier_id,
                        'items' => $orderItems,
                        'consignee' => $consignee,
                        'pickup' => $pickup
                    ],
                    'settings' => $settings
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving label data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unified API method - decides between Tekipost/Amazon URL or Custom Label based on AWB
     */
    public function apiGetLabelByAwb(Request $request)
    {
        try {
            // Get Authorization token from header
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Authorization token required'], 401);
            }

            // Find seller by token
            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
            }

            // Get seller ID from token
            $sellerId = $seller->id;

            // Validate request
            $validator = Validator::make($request->all(), [
                'awb_number' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find order by AWB number for this seller
            $order = Order::where('seller_id', $sellerId)
                          ->where('awb_number', $request->awb_number)
                          ->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found with this AWB number'], 404);
            }

            // Check if it's Tekipost or Amazon courier
            if (in_array($order->courier_id, ['tekipost', 'amazon_0_5kg', 'amazon_2kg'])) {
                // Return Tekipost/Amazon style response
                return response()->json([
                    'success' => true,
                    'message' => 'label generated successfully',
                    // 'label_type' => 'url',
                    'data' => [
                        'seller_id' => $sellerId,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'awb_number' => $order->awb_number,
                        // 'courier_id' => $order->courier_id,
                        'courier' => $order->all_courier_name ?? '',
                        'label_url' => $order->smartship_tracking_url,
                    ]
                ]);
            }elseif(in_array($order->all_courier_name, ['Amazon 500gm', 'Amazon 1kg', 'Amazon 2kg'])){


                $apiUrl = 'https://app.parcelx.in/api/v1/label?awb=' . $order->awb_number . '&label_type=label';
$accessToken = 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl';

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => [
        'access-token: ' . $accessToken
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    return response()->json([
        'success' => false,
        'message' => 'ParcelX API Error: ' . $err
    ], 500);
}

$result = json_decode($response, true);

// 🟢 Validate new response format
if (isset($result['status']) && $result['status'] && isset($result['label_url'])) {
    return response()->json([
        'success' => true,
        'message' => 'Label generated successfully',
        'data' => [
            'seller_id' => $sellerId,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'awb_number' => $order->awb_number,
            'courier' => $order->all_courier_name ?? '',
            'label_url' => $result['label_url']  // 🔹 Updated key
        ]
    ]);
    
} else {
    return response()->json([
        'success' => false,
        'message' => 'API returned invalid response',
        'response' => $result
    ], 500);
}



        //     $apiUrl = 'https://app.parcelx.in/api/v1/label?awb=' . $order->awb_number . '&label_type=label';
        //     $accessToken = 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl'; // 🔹 Replace or store securely in .env

        //     $curl = curl_init();
        //     curl_setopt_array($curl, [
        //         CURLOPT_URL => $apiUrl,
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 30,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'GET',
        //         CURLOPT_HTTPHEADER => [
        //             'access-token: ' . $accessToken
        //         ],
        //     ]);

        //     $response = curl_exec($curl);
        //     $err = curl_error($curl);
        //     curl_close($curl);

        //     if ($err) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'ParcelX API Error: ' . $err
        //         ], 500);
        //     }

        //     $result = json_decode($response, true);
        //    dd($result);
        //     // Validate response
        //     if (isset($result['success']) && $result['success'] && isset($result['data']['label_url'])) {
        //         return response()->json([
        //             'success' => true,
        //             'message' => 'label generated successfully (ParcelX)',
        //             'data' => [
        //                 'seller_id' => $sellerId,
        //                 'order_id' => $order->id,
        //                 'order_number' => $order->order_number,
        //                 'awb_number' => $order->awb_number,
        //                 'courier' => $order->all_courier_name ?? '',
        //                 'label_url' => $result['data']['label_url']
        //             ]
        //         ]);
        //     } else {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'ParcelX API returned invalid response',
        //             'response' => $result
        //         ], 500);
        //     }
        




            } else {
                // Generate custom label for other couriers
                // Get seller's label settings
                $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();

                // Generate label HTML
                $labelHtml = $this->generateLabelHtml($order, $settings);

                // Generate PDF
                $pdf = Pdf::loadHTML($labelHtml);

                //dd($pdf->output());

                if ($settings && $settings->label_type == 'thermal') {
                    $pdf->setPaper([0, 0, 288, 432], 'portrait'); // 4x6 inches in points
                } else {
                    $pdf->setPaper('A4', 'portrait');
                }

                // Generate unique filename
                $filename = 'label_' . $sellerId . '_' . $order->id . '_' . time() . '.pdf';
                
                // Create directory if it doesn't exist - using relative path from document root
                $labelDir = $_SERVER['DOCUMENT_ROOT'] . '/labels';
                if (!file_exists($labelDir)) {
                    mkdir($labelDir, 0755, true);
                }
                
                // Save PDF to public folder
                $pdfContent = $pdf->output();
                file_put_contents($labelDir . '/' . $filename, $pdfContent);
                
                // Generate URL using request host
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $fileUrl = $protocol . $host . '/labels/' . $filename;

                return response()->json([
                    'success' => true,
                    'message' => 'label generated successfully',
                    // 'label_type' => 'pdf',
                    'data' => [
                        'seller_id' => $sellerId,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'awb_number' => $order->awb_number,
                        // 'courier_id' => $order->courier_id,
                        'courier' => $order->all_courier_name ?? '',

                        'label_url' => $fileUrl
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing label request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API method to get Tekipost label with token authentication
     */
    public function apiGetTekipostLabel(Request $request)
    {
        try {
            // Get Authorization token from header
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Authorization token required'], 401);
            }

            // Find seller by token
            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
            }

            // Get seller ID from token
            $sellerId = $seller->id;

            // Validate request
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find order for this seller
            $order = Order::where('seller_id', $sellerId)->find($request->order_id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            if ($order->courier_id !== 'tekipost') {
                return response()->json(['success' => false, 'message' => 'Order is not Tekipost'], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tekipost label data retrieved successfully',
                'data' => [
                    'seller_id' => $sellerId,
                    'order_id' => $order->id,
                    'courier' => $order->all_courier_name ?? '',
                    'label_url' => $order->smartship_tracking_url,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Tekipost label: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadCustomLabel($id)
    {
        try {
            $sellerId = auth()->guard('seller')->id();
            $order = Order::where('seller_id', $sellerId)->findOrFail($id);
            
            // Get seller's label settings
            $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();
            
            // Generate label HTML
            $labelHtml = $this->generateLabelHtml($order, $settings);
            
            // Generate PDF
            $pdf = Pdf::loadHTML($labelHtml);
            
            if ($settings && $settings->label_type == 'thermal') {
                $pdf->setPaper([0, 0, 288, 432], 'portrait'); // 4x6 inches in points
            } else {
                $pdf->setPaper('A4', 'portrait');
            }
            
            return $pdf->download("custom-label-{$order->order_number}.pdf");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error downloading label: ' . $e->getMessage());
        }
    }



    

// ...existing code...
private function generateLabelHtml($order, $settings = null)
{
    // Dummy fallback data
    $dummyData = [
        'order_number'       => $order->order_number ?? 'ORD-12345',
        'awb_number'         => $order->awb_number ?? 'AWB123456789',
        'order_date'         => $order->created_at ? $order->created_at->format('d M Y') : date('d M Y'),
        'payment_type'       => $order->payment_type ?? 'COD',
        'order_amount'       => $order->order_amount ?? 1450,
        'collectable_amount' => $order->collectable_amount ?? 1450,
    ];

    // Order items
    $orderItems = [];
    if (!empty($order->order_items)) {
        $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
        if (is_array($items)) $orderItems = $items;
    }
    if (empty($orderItems)) {
        $orderItems = [
            ['name' => 'Sample Product', 'sku' => 'SKU-001', 'quantity' => 1, 'price' => 1450]
        ];
    }

    // Consignee
    $consignee = [
        'name'     => 'John Doe',
        'phone'    => '9876543210',
        'address'  => '123 Main Street',
        'address_2'=> 'Apartment 4B',
        'city'     => 'Mumbai',
        'state'    => 'Maharashtra',
        'pincode'  => '400001'
    ];
    if (!empty($order->consignee)) {
        $cData = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
        if (is_array($cData)) $consignee = array_merge($consignee, $cData);
    }

    // Pickup
    $pickup = [
        'warehouse_name' => 'Main Warehouse',
        'name'           => 'Seller Name',
        'phone'          => '9123456780',
        'address'        => '456 Business Street',
        'address_2'      => 'Floor 2',
        'city'           => 'Delhi',
        'state'          => 'Delhi',
        'pincode'        => '110001'
    ];
    if (!empty($order->pickup)) {
        $pData = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
        if (is_array($pData)) $pickup = array_merge($pickup, $pData);
    }

    // Default settings
    if (!$settings) {
        $settings = (object) [
            'show_logo'                    => false,
            'logo_path'                    => null,
            'show_support_contact'         => false,
            'hide_prepaid_amount'          => false,
            'hide_customer_mobile'         => false,
            'hide_return_address_line_1'   => false,
            'hide_return_address_line_2'   => false,
            'hide_return_city_state_pincode'=> false,
            'hide_return_mobile_number'    => false,
            'hide_return_contact_name'     => false,
            'hide_sku'                     => false,
            'hide_product'                 => false,
            'hide_discount'                => false,
            'hide_qty'                     => false,
            'hide_amount'                  => false,
            'label_type'                   => 'standard',
            'label_size'                   => '8x11'
        ];
    }

    $html = '<div style="width:100%;max-width:700px;height:auto;margin:0 auto;font-family:Arial,sans-serif;font-size:9px;line-height:1.2;border:1px solid #ccc;padding:8px;background:#fff;box-sizing:border-box;page-break-inside:avoid;">';

    // Logo
    if ($settings->show_logo && $settings->logo_path) {
        $logoPath = public_path($settings->logo_path);
        if (file_exists($logoPath)) {
            $imageData = base64_encode(file_get_contents($logoPath));
            $mimeType  = mime_content_type($logoPath);
            $html .= '<div style="text-align:center;margin-bottom:8px;">
                        <img src="data:' . $mimeType . ';base64,' . $imageData . '" style="max-height:60px;max-width:200px;object-fit:contain;">
                      </div>';
        }
    }

    // Header
    $html .= '<div style="display:flex;justify-content:space-between;margin-bottom:6px;border-bottom:1px solid #eee;padding-bottom:4px;">
                <div style="font-weight:bold;font-size:12px;">SHIPPING LABEL</div>
                <div style="text-align:right;">
                    <div style="font-size:10px;font-weight:bold;">Order #' . $dummyData['order_number'] . '</div>
                    <div style="font-size:8px;">' . $dummyData['order_date'] . '</div>
                </div>
              </div>';

    // Addresses
    $html .= '<div style="display:flex;gap:4px;margin-bottom:6px;">';

    // From
    $html .= '<div style="flex:1;border:1px solid #e0e0e0;padding:4px;background:#f9f9f9;border-radius:3px;font-size:8px;">';
    $html .= '<div style="font-weight:bold;font-size:9px;margin-bottom:2px;">FROM (RETURN ADDRESS)</div>';
    if (!$settings->hide_return_contact_name)   $html .= '<div>' . $pickup['name'] . '</div>';
    if (!$settings->hide_return_address_line_1) $html .= '<div>' . $pickup['address'] . '</div>';
    if (!$settings->hide_return_address_line_2 && !empty($pickup['address_2'])) $html .= '<div>' . $pickup['address_2'] . '</div>';
    if (!$settings->hide_return_city_state_pincode) $html .= '<div>' . $pickup['city'] . ', ' . $pickup['state'] . ' - ' . $pickup['pincode'] . '</div>';
    if (!$settings->hide_return_mobile_number)  $html .= '<div>Ph: ' . $pickup['phone'] . '</div>';
    $html .= '</div>';

    // To
    $html .= '<div style="flex:1;border:1px solid #e0e0e0;padding:4px;background:#f9f9f9;border-radius:3px;font-size:8px;">';
    $html .= '<div style="font-weight:bold;font-size:9px;margin-bottom:2px;">SHIP TO</div>';
    $html .= '<div style="font-weight:bold;">' . $consignee['name'] . '</div>';
    if (!$settings->hide_customer_mobile) $html .= '<div>Ph: ' . $consignee['phone'] . '</div>';
    $html .= '<div>' . $consignee['address'] . '</div>';
    if (!empty($consignee['address_2'])) $html .= '<div>' . $consignee['address_2'] . '</div>';
    $html .= '<div>' . $consignee['city'] . ', ' . $consignee['state'] . ' - ' . $consignee['pincode'] . '</div>';
    $html .= '</div>';

    $html .= '</div>';

    // Items Table - Only show if at least one column is visible
    $showItemsTable = (!$settings->hide_product || !$settings->hide_sku || !$settings->hide_qty || !$settings->hide_amount);
    if ($showItemsTable) {
        $html .= '<div style="margin-bottom:6px;max-height:120px;overflow:hidden;">';
        $html .= '<div style="font-weight:bold;margin-bottom:3px;font-size:10px;">ORDER ITEMS</div>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:8px;">
                    <thead>
                        <tr style="background:#f5f5f5;">';
        if (!$settings->hide_product) $html .= '<th style="text-align:left;padding:3px;border-bottom:1px solid #ddd;font-size:8px;">Item</th>';
        if (!$settings->hide_qty)     $html .= '<th style="text-align:right;padding:3px;border-bottom:1px solid #ddd;font-size:8px;width:40px;">Qty</th>';
        if (!$settings->hide_amount)  $html .= '<th style="text-align:right;padding:3px;border-bottom:1px solid #ddd;font-size:8px;width:60px;">Price</th>';
        $html .= '</tr>
                    </thead>
                    <tbody>';

        $displayItems = array_slice($orderItems, 0, 5);

        foreach ($displayItems as $item) {
            $html .= '<tr>';
            if (!$settings->hide_product) {
                $html .= '<td style="padding:3px;border-bottom:1px solid #eee;vertical-align:top;">' . (strlen($item['name'] ?? 'Product') > 25 ? substr($item['name'] ?? 'Product', 0, 25) . '...' : ($item['name'] ?? 'Product')) . '</td>';
            }
            if (!$settings->hide_qty) {
                $html .= '<td style="padding:3px;text-align:right;vertical-align:top;">' . ($item['quantity'] ?? 1) . '</td>';
            }
            if (!$settings->hide_amount) {
                $html .= '<td style="padding:3px;text-align:right;vertical-align:top;">';
                if ($settings->hide_prepaid_amount && $dummyData['payment_type'] != 'COD') {
                    $html .= '<span style="color:#666;">Prepaid</span>';
                } else {
                    $html .= 'Rs ' . number_format(($item['price'] ?? $dummyData['order_amount']), 2);
                }
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        if (count($orderItems) > 5) {
            $colspan = 0;
            if (!$settings->hide_product) $colspan++;
            if (!$settings->hide_qty) $colspan++;
            if (!$settings->hide_amount) $colspan++;
            $html .= '<tr><td colspan="' . $colspan . '" style="padding:3px;text-align:center;font-style:italic;color:#666;">... and ' . (count($orderItems) - 5) . ' more items</td></tr>';
        }

        $html .= '</tbody>';
        // Total row only if amount is visible
        if (!$settings->hide_amount) {
            $colspan = 0;
            if (!$settings->hide_product) $colspan++;
            if (!$settings->hide_qty) $colspan++;
            $html .= '<tfoot>
                        <tr>
                            <td colspan="' . $colspan . '" style="text-align:right;padding:3px;font-weight:bold;font-size:8px;">Total:</td>
                            <td style="text-align:right;padding:3px;font-weight:bold;font-size:8px;">';
            if ($settings->hide_prepaid_amount && $dummyData['payment_type'] != 'COD') {
                $html .= '<span style="color:#666;">Prepaid</span>';
            } else {
                $html .= 'Rs ' . number_format($dummyData['order_amount'], 2);
            }
            $html .= '</td>
                        </tr>
                      </tfoot>';
        }
        $html .= '</table>';
        $html .= '</div>';
    }

    // Payment + Barcode
    $html .= '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-top:4px;border-top:1px solid #eee;padding-top:4px;">';

    // Payment - Only show if not hidden
    if (!$settings->hide_amount) {
        $html .= '<div style="flex:1;">
                    <div style="font-weight:bold;font-size:9px;">PAYMENT METHOD</div>
                    <div style="font-size:8px;">' . ucfirst($dummyData['payment_type']) . '</div>';
        if ($dummyData['payment_type'] == 'COD') {
            $html .= '<div style="margin-top:2px;font-weight:bold;font-size:8px;">COD: Rs ' . number_format($dummyData['collectable_amount'], 2) . '</div>';
        }
        $html .= '</div>';
    }

    // Barcode
    if (!empty($order->awb_number)) {
        $barcodeUrl = "https://bwipjs-api.metafloor.com/?bcid=code128&text=" . urlencode($order->awb_number) . "&scale=1&height=8&includetext";
        $barcodeImage = @file_get_contents($barcodeUrl);
        if ($barcodeImage !== false) {
            $barcodeBase64 = base64_encode($barcodeImage);
            $barcodeSrc    = "data:image/png;base64," . $barcodeBase64;
            $html .= '<div style="text-align:right;flex:1;">
                        <img src="' . $barcodeSrc . '" style="height:35px;"/>
                        <div style="font-size:7px;font-weight:bold;">' . $order->awb_number . '</div>
                      </div>';
        } else {
            $html .= '<div style="font-size:8px;color:#999;">[Barcode not available]</div>';
        }
    }

    $html .= '</div>';

    // Support
    if ($settings->show_support_contact) {
        $email = auth()->guard('seller')->user()->email ?? 'shipxpeed@gmail.com';
        $phone = auth()->guard('seller')->user()->phone_number ?? '9876543210';

        $html .= '<div style="text-align:center;margin-top:4px;font-size:7px;color:#666;border-top:1px solid #eee;padding-top:3px;">
                    For support: ' . htmlspecialchars($email) . ' | ' . htmlspecialchars($phone) . '
                  </div>';
    }

    $html .= '</div>'; //
        return $html;
}

    



    
public function getTekipostLabel(Request $request)
{
    $order = Order::find($request->order_id);

    if (!$order || $order->courier_id !== 'tekipost') {
        return response()->json(['error' => 'Order not found or not Tekipost.'], 404);
    }

    return response()->json([
        'courier' => $order->all_courier_name ?? '',
        'label_url' => $order->smartship_tracking_url,
    ]);
}




}