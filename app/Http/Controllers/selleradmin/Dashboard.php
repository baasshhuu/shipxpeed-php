<?php

namespace App\Http\Controllers\selleradmin;

use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\Recharge;
use App\Models\SellerAgreement;
use App\Models\SellerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Order, Buyer, OrderItem, Warehouse, OrderPackageDetail, SellerList};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

class Dashboard extends Controller
{






    public function clone(Request $request)
    {
        $id = $request->input('order_id');
        $item = Order::findOrFail($id);

        // Record clone
        $newItem = $item->replicate(); // duplicate data
        // Set specific fields to null
        $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
        $newItem->order_number = 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1);
        $newItem->awb_number = null;
        $newItem->rto_amount = '0';
        $newItem->all_courier_name = null;
        $newItem->smarship_courier_id = null;
        $newItem->courier_name = null;
        $newItem->status = null;
        $newItem->label = null;
        $newItem->order_status = null;
        $newItem->shipping_status = null;
        $newItem->shipper_hub_id = null;
        $newItem->payment_status = 'Progress';
        $newItem->delivered_date = null;
        $newItem->shipping_date = null;
        $newItem->label_pdf = null;
        $newItem->cancelled_amount = null;
        $newItem->order_cancelled_amount = null;

        $newItem->courier_id = null;
        // Update timestamps
        $newItem->created_at = now();
        $newItem->updated_at = now();
        
        // Save the cloned item
        $newItem->save();

        return back()->with('success', 'Record cloned successfully');
    }


    public function downloadLabel($id)
{
    $order = Order::findOrFail($id);

    $labelData = [
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'awb_number' => $order->awb_number,
    ];

    return view('lebal', compact('labelData'));
}

    // Custom Label Generation Methods
    public function generateCustomLabel($id)
    {
        try {
            $sellerId = auth()->guard('seller')->id();
            $order = Order::where('seller_id', $sellerId)->findOrFail($id);
            
            // Get seller's label settings
            $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();
            
            // Generate label HTML with dummy data
            $labelHtml = $this->generateLabelHtml($order, $settings);
            
            return response()->json([
                'success' => true,
                'labelHtml' => $labelHtml
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating label: ' . $e->getMessage()
            ]);
        }
    }

    public function generateBulkCustomLabels(Request $request)
    {
        try {
            $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'integer'
            ]);

            $sellerId = auth()->guard('seller')->id();
            $orders = Order::where('seller_id', $sellerId)
                          ->whereIn('id', $request->order_ids)
                          ->get();
            
            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid orders found.'
                ]);
            }

            // Get seller's label settings
            $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();
            
            // Generate bulk label HTML
            $bulkLabelHtml = '';
            foreach ($orders as $index => $order) {
                if ($index > 0) {
                    $bulkLabelHtml .= '<div class="page-break"></div>';
                }
                $bulkLabelHtml .= $this->generateLabelHtml($order, $settings);
            }
            
            return response()->json([
                'success' => true,
                'labelHtml' => $bulkLabelHtml
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating bulk labels: ' . $e->getMessage()
            ]);
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

    public function downloadBulkCustomLabels(Request $request)
    {
        try {
            $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'integer'
            ]);

            $sellerId = auth()->guard('seller')->id();
            $orders = Order::where('seller_id', $sellerId)
                          ->whereIn('id', $request->order_ids)
                          ->get();
            
            if ($orders->isEmpty()) {
                return back()->with('error', 'No valid orders found.');
            }

            // Get seller's label settings
            $settings = \App\Models\LabelSetting::where('seller_id', $sellerId)->first();
            
            // Generate bulk label HTML - ALL labels on single page
            $bulkLabelHtml = '<div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; padding: 8px;">';
            
            foreach ($orders as $index => $order) {
                $labelHtml = $this->generateLabelHtml($order, $settings);
                
                // Modify individual label to be smaller and fit more per page (3 per row)
                $labelHtml = str_replace(
                    'style="max-width: 100%; margin: 0 auto;', 
                    'style="max-width: 30%; margin: 0; flex: 0 0 30%;', 
                    $labelHtml
                );
                
                // Make labels more compact
                $labelHtml = str_replace('padding: 15px', 'padding: 8px', $labelHtml);
                $labelHtml = str_replace('font-size: 12px', 'font-size: 9px', $labelHtml);
                $labelHtml = str_replace('line-height: 1.4', 'line-height: 1.2', $labelHtml);
                
                $bulkLabelHtml .= $labelHtml;
            }
            
            $bulkLabelHtml .= '</div>';
            
            // Generate PDF
            $pdf = Pdf::loadHTML($bulkLabelHtml);
            
            if ($settings && $settings->label_type == 'thermal') {
                $pdf->setPaper([0, 0, 288, 432], 'portrait'); // 4x6 inches in points
            } else {
                $pdf->setPaper('A4', 'portrait');
            }
            
            return $pdf->download("bulk-custom-labels-" . date('Y-m-d-H-i-s') . ".pdf");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error downloading bulk labels: ' . $e->getMessage());
        }
    }











    private function generateLabelHtml($order, $settings = null)
{
    // Dummy fallback data
    $dummyData = [
        'order_number'       => $order->customer_order_id ?? $order->order_number,
        'awb_number'         => $order->awb_number ?? '3268598659',
        'order_date'         => $order->created_at ? $order->created_at->format('d-M-Y') : date('d-M-Y'),
        'payment_type'       => $order->payment_type ?? 'COD',
        'order_amount'       => $order->order_amount ?? 100,
        'collectable_amount' => $order->collectable_amount ?? 1000,
    ];

    // Order items
    $orderItems = [];
    if (!empty($order->order_items)) {
        $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
        if (is_array($items)) $orderItems = $items;
    }
    if (empty($orderItems)) {
        $orderItems = [
            ['name' => 'product 1', 'sku' => '1', 'quantity' => 1, 'price' => 1]
        ];
    }

    // Consignee
    $consignee = [
        'name'     => 'Customer Name',
        'phone'    => '7357169546',
        'address'  => 'jaipur raj.',
        'address_2'=> 'Near Bus Stand',
        'city'     => 'East delhi',
        'state'    => 'Delhi',
        'pincode'  => '110092'
    ];
    if (!empty($order->consignee)) {
        $cData = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
        if (is_array($cData)) $consignee = array_merge($consignee, $cData);
    }

    // Pickup
    $pickup = [
        'warehouse_name' => 'delhi',
        'name'           => 'vicky',
        'phone'          => '7357169546',
        'address'        => 'delhi',
        'address_2'      => 'delhi',
        'city'           => 'East delhi',
        'state'          => 'Delhi',
        'pincode'        => '110092'
    ];
    if (!empty($order->pickup)) {
        $pData = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
        if (is_array($pData)) $pickup = array_merge($pickup, $pData);
    }

    // Default settings if none provided
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

    // Start HTML with exact invoice layout matching the image
    $html = '<div style="width:100%;max-width:580px;height:auto;margin:0 auto;font-family:Arial,sans-serif;font-size:11px;border:2px solid #000;background:#fff;page-break-inside:avoid;">';

    // Header section with warehouse name + logo, and courier info
    $html .= '<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #000;padding:8px 12px;min-height:50px;">';
    
    // Left side - Warehouse name + Logo together
    $html .= '<div style="display:flex;align-items:center;">';
    
    // Show Shipxpeed logo first (default or custom)
    if ($settings->show_logo && !empty($settings->logo_path)) {
        $logoPath = public_path($settings->logo_path);
        if (file_exists($logoPath)) {
            $imageData = base64_encode(file_get_contents($logoPath));
            $mimeType = mime_content_type($logoPath);
            $html .= '<img src="data:' . $mimeType . ';base64,' . $imageData . '" style="max-height:50px;max-width:100px;margin-right:10px;object-fit:contain;">';
        }
    } else {
        // Show default Shipxpeed logo icon
        $html .= '<div style="font-size:14px;margin-right:10px;color:#666;">📦 SHIPXPEED</div>';
    }
    
    // $html .= '<div style="font-weight:bold;font-size:14px;">' . htmlspecialchars($pickup['name']) . '</div>';
    $html .= '</div>';
    
    // Right side - Courier info
                    // <div style="font-size:12px;font-weight:bold;">Shipxpeed</div>

    $html .= '<div style="text-align:right;">
                <div style="font-size:10px;">Courier: ' . htmlspecialchars($order->all_courier_name ?? 'Courier Name') . '</div>
              </div>';
    
    $html .= '</div>';

    // Barcode section
    if (!empty($dummyData['awb_number'])) {
        $barcodeUrl = "https://bwipjs-api.metafloor.com/?bcid=code128&text=" . urlencode($dummyData['awb_number']) . "&scale=2&height=10&includetext";
        $barcodeImage = @file_get_contents($barcodeUrl);
        if ($barcodeImage !== false) {
            $barcodeBase64 = base64_encode($barcodeImage);
            $barcodeSrc = "data:image/png;base64," . $barcodeBase64;
            $html .= '<div style="text-align:center;padding:6px;border-bottom:1px solid #000;">
                        <img src="' . $barcodeSrc . '" style="height:40px;"/>
                      </div>';
        } else {
            // Fallback barcode display
            $html .= '<div style="text-align:center;padding:6px;border-bottom:1px solid #000;">
                        <div style="font-family:monospace;font-size:20px;letter-spacing:1px;">||||||||||||||||||||||||</div>
                        <div style="font-size:10px;font-weight:bold;margin-top:2px;">' . htmlspecialchars($dummyData['awb_number']) . '</div>
                      </div>';
        }
    }

    // Delivery information section
    $html .= '<div style="padding:6px 8px;border-bottom:1px solid #000;">
                <div style="font-weight:bold;margin-bottom:3px;font-size:11px;">Deliver To:</div>
                <div style="font-weight:bold;font-size:12px;">' . htmlspecialchars($consignee['name']) . '</div>';
    
    if (!$settings->hide_customer_mobile) {
        $html .= '<div style="margin-bottom:1px;font-size:10px;">Phone: ' . htmlspecialchars($consignee['phone']) . '</div>';
    }
    
    $html .= '<div style="font-size:10px;">' . htmlspecialchars($consignee['address']) . '</div>';
    
    if (!empty($consignee['address_2'])) {
        $html .= '<div style="font-size:10px;">' . htmlspecialchars($consignee['address_2']) . '</div>';
    }
    
    $html .= '<div style="font-size:10px;">' . htmlspecialchars($consignee['city']) . ', ' . htmlspecialchars($consignee['state']) . '</div>
              <div style="font-size:10px;"><strong>Pin - ' . htmlspecialchars($consignee['pincode']) . '</strong></div>
              </div>';

    // Order details section in two columns (removed weight and dimensions)
    $html .= '<div style="display:flex;border-bottom:1px solid #000;">
                <div style="flex:1;padding:6px 8px;border-right:1px solid #000;">
<div style="font-size:10px;margin-bottom:2px;">
<strong>Seller:</strong> ' . htmlspecialchars(auth()->guard('seller')->user()->name ?? '') . '
</div>
                    <div style="font-size:10px;margin-bottom:2px;"><strong>Order Id:</strong> ' . htmlspecialchars($dummyData['order_number'] ?? '29593') . '</div>
                     <div style="font-size:10px;margin-bottom:2px;"><strong>Ref./Invoice#:</strong> ' . htmlspecialchars($dummyData['order_number'] ?? '#8581') . '</div>
                    <div style="font-size:10px;margin-bottom:2px;"><strong>Date:</strong> ' . $dummyData['order_date'] . '</div>';
    
    if (!$settings->hide_amount) {
        $html .= '<div style="font-size:10px;margin-bottom:2px;"><strong>Invoice Value:</strong> Rs. ' . number_format($dummyData['order_amount'], 2) . '</div>';
    }
    
    $html .= '</div>
              <div style="flex:1;padding:6px 8px;">
                    <div style="font-size:10px;margin-bottom:2px;"><strong>Payment:</strong> ' . strtoupper($dummyData['payment_type']) . '</div>';
    
    if ($dummyData['payment_type'] == 'COD' && !$settings->hide_amount) {
        $html .= '<div style="font-size:10px;"><strong>COD Amount:</strong> Rs. ' . number_format($dummyData['collectable_amount'], 2) . '</div>';
    }
    
    $html .= '</div>
              </div>';

    // Product table (only show if product info is not hidden)
    if (!$settings->hide_product) {
        $html .= '<table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid #000;">
                            <th style="text-align:left;padding:4px 6px;border-right:1px solid #000;font-size:9px;font-weight:bold;">Product Name</th>';
        
        if (!$settings->hide_sku) {
            $html .= '<th style="text-align:center;padding:4px 6px;border-right:1px solid #000;font-size:9px;font-weight:bold;width:50px;">SKU</th>';
        }
        
        if (!$settings->hide_qty) {
            $html .= '<th style="text-align:center;padding:4px 6px;border-right:1px solid #000;font-size:9px;font-weight:bold;width:35px;">Qty</th>';
        }
        
        if (!$settings->hide_amount) {
            $html .= '<th style="text-align:right;padding:4px 6px;font-size:9px;font-weight:bold;width:60px;">Price</th>';
        }
        
        $html .= '</tr>
                    </thead>
                    <tbody>';

        // Product rows
        $displayItems = array_slice($orderItems, 0, 3);
        $totalQty = 0;
        foreach ($displayItems as $item) {
            $qty = $item['qty'] ?? 1;
            $totalQty += $qty;
            $html .= '<tr style="border-bottom:1px solid #000;">
                        <td style="padding:4px 6px;border-right:1px solid #000;font-size:9px;">' . htmlspecialchars($item['name'] ?? 'product 1') . '</td>';
            
            if (!$settings->hide_sku) {
                $html .= '<td style="padding:4px 6px;text-align:center;border-right:1px solid #000;font-size:9px;">' . htmlspecialchars($item['sku'] ?? '1') . '</td>';
            }
            
            if (!$settings->hide_qty) {
                $html .= '<td style="padding:4px 6px;text-align:center;border-right:1px solid #000;font-size:9px;">' . $qty . '</td>';
            }
            
            if (!$settings->hide_amount) {
                if ($settings->hide_prepaid_amount && $dummyData['payment_type'] != 'COD') {
                    $html .= '<td style="padding:4px 6px;text-align:right;font-size:9px;">Prepaid</td>';
                } else {
                    $html .= '<td style="padding:4px 6px;text-align:right;font-size:9px;">' . number_format(($item['price'] ?? 1), 2) . '</td>';
                }
            }
            
            $html .= '</tr>';
        }

        // Total row
        if (!$settings->hide_amount) {
            $html .= '<tr style="font-weight:bold;border-bottom:1px solid #000;">
                        <td colspan="2" style="padding:4px 6px;text-align:right;border-right:1px solid #000;font-size:9px;">Total</td>';
            
            if (!$settings->hide_qty) {
                $html .= '<td style="padding:4px 6px;text-align:center;border-right:1px solid #000;font-size:9px;">' . $totalQty . '</td>';
            }
            
            if ($settings->hide_prepaid_amount && $dummyData['payment_type'] != 'COD') {
                $html .= '<td style="padding:4px 6px;text-align:right;font-size:9px;">Prepaid</td>';
            } else {
                $html .= '<td style="padding:4px 6px;text-align:right;font-size:9px;">Rs.' . number_format($dummyData['collectable_amount'], 2) . '</td>';
            }
            
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
    }

    // Footer with support contact (if enabled)
    if ($settings->show_support_contact) {
        $email = auth()->guard('seller')->user()->email ?? 'support@shipxpeed.com';
        $phone = auth()->guard('seller')->user()->phone_number ?? '9876543210';
        
        $html .= '<div style="text-align:center;padding:4px;font-size:8px;color:#666;border-top:1px solid #000;">
                    Support: ' . htmlspecialchars($email) . ' | ' . htmlspecialchars($phone) . '
                  </div>';
    }

    $html .= '</div>';
    
    return $html;
}






// // ...existing code...
// private function generateLabelHtml($order, $settings = null)
// {
//     // Dummy fallback data
//     $dummyData = [
//         'order_number'       => $order->customer_order_id ?? $order->order_number,
//         'awb_number'         => $order->awb_number ?? 'AWB123456789',
//         'order_date'         => $order->created_at ? $order->created_at->format('d M Y') : date('d M Y'),
//         'payment_type'       => $order->payment_type ?? 'COD',
//         'order_amount'       => $order->order_amount ?? 1450,
//         'collectable_amount' => $order->collectable_amount ?? 1450,
//     ];

//     // Order items
//     $orderItems = [];
//     if (!empty($order->order_items)) {
//         $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
//         if (is_array($items)) $orderItems = $items;
//     }
//     if (empty($orderItems)) {
//         $orderItems = [
//             ['name' => 'Sample Product', 'sku' => 'SKU-001', 'quantity' => 1, 'price' => 1450]
//         ];
//     }

//     // Consignee
//     $consignee = [
//         'name'     => 'John Doe',
//         'phone'    => '9876543210',
//         'address'  => '123 Main Street',
//         'address_2'=> 'Apartment 4B',
//         'city'     => 'Mumbai',
//         'state'    => 'Maharashtra',
//         'pincode'  => '400001'
//     ];
//     if (!empty($order->consignee)) {
//         $cData = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
//         if (is_array($cData)) $consignee = array_merge($consignee, $cData);
//     }

//     // Pickup
//     $pickup = [
//         'warehouse_name' => 'Main Warehouse',
//         'name'           => 'Seller Name',
//         'phone'          => '9123456780',
//         'address'        => '456 Business Street',
//         'address_2'      => 'Floor 2',
//         'city'           => 'Delhi',
//         'state'          => 'Delhi',
//         'pincode'        => '110001'
//     ];
//     if (!empty($order->pickup)) {
//         $pData = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
//         if (is_array($pData)) $pickup = array_merge($pickup, $pData);
//     }

//     // Default settings
//     if (!$settings) {
//         $settings = (object) [
//             'show_logo'                    => false,
//             'logo_path'                    => null,
//             'show_support_contact'         => false,
//             'hide_prepaid_amount'          => false,
//             'hide_customer_mobile'         => false,
//             'hide_return_address_line_1'   => false,
//             'hide_return_address_line_2'   => false,
//             'hide_return_city_state_pincode'=> false,
//             'hide_return_mobile_number'    => false,
//             'hide_return_contact_name'     => false,
//             'hide_sku'                     => false,
//             'hide_product'                 => false,
//             'hide_discount'                => false,
//             'hide_qty'                     => false,
//             'hide_amount'                  => false,
//             'label_type'                   => 'standard',
//             'label_size'                   => '8x11'
//         ];
//     }

//     $html = '<div style="width:100%;max-width:700px;height:auto;margin:0 auto;font-family:Arial,sans-serif;font-size:9px;line-height:1.2;border:1px solid #ccc;padding:8px;background:#fff;box-sizing:border-box;page-break-inside:avoid;">';

//     // Logo
//     if ($settings->show_logo && $settings->logo_path) {
//         $logoPath = public_path($settings->logo_path);
//         if (file_exists($logoPath)) {
//             $imageData = base64_encode(file_get_contents($logoPath));
//             $mimeType  = mime_content_type($logoPath);
//             $html .= '<div style="text-align:center;margin-bottom:8px;">
//                         <img src="data:' . $mimeType . ';base64,' . $imageData . '" style="max-height:60px;max-width:200px;object-fit:contain;">
//                       </div>';
//         }
//     }

//     // Header
//     $html .= '<div style="display:flex;justify-content:space-between;margin-bottom:6px;border-bottom:1px solid #eee;padding-bottom:4px;">
//                 <div style="font-weight:bold;font-size:12px;">SHIPPING LABEL</div>
//                 <div style="text-align:right;">
//                     <div style="font-size:10px;font-weight:bold;">Order #' . $dummyData['order_number'] . '</div>
//                     <div style="font-size:8px;">' . $dummyData['order_date'] . '</div>
//                                         <div style="font-size:8px;">Courier: ' . htmlspecialchars($order->all_courier_name ?? '') . '</div>

//                 </div>
//               </div>';

//     // Addresses
//     $html .= '<div style="display:flex;gap:4px;margin-bottom:6px;">';

//     // From
//     $html .= '<div style="flex:1;border:1px solid #e0e0e0;padding:4px;background:#f9f9f9;border-radius:3px;font-size:8px;">';
//     $html .= '<div style="font-weight:bold;font-size:9px;margin-bottom:2px;">FROM (RETURN ADDRESS)</div>';
//     if (!$settings->hide_return_contact_name)   $html .= '<div>' . $pickup['name'] . '</div>';
//     if (!$settings->hide_return_address_line_1) $html .= '<div>' . $pickup['address'] . '</div>';
//     if (!$settings->hide_return_address_line_2 && !empty($pickup['address_2'])) $html .= '<div>' . $pickup['address_2'] . '</div>';
//     if (!$settings->hide_return_city_state_pincode) $html .= '<div>' . $pickup['city'] . ', ' . $pickup['state'] . ' - ' . $pickup['pincode'] . '</div>';
//     if (!$settings->hide_return_mobile_number)  $html .= '<div>Ph: ' . $pickup['phone'] . '</div>';
//     $html .= '</div>';

//     // To
//     $html .= '<div style="flex:1;border:1px solid #e0e0e0;padding:4px;background:#f9f9f9;border-radius:3px;font-size:8px;">';
//     $html .= '<div style="font-weight:bold;font-size:9px;margin-bottom:2px;">SHIP TO</div>';
//     $html .= '<div style="font-weight:bold;">' . $consignee['name'] . '</div>';
//     if (!$settings->hide_customer_mobile) $html .= '<div>Ph: ' . $consignee['phone'] . '</div>';
//     $html .= '<div>' . $consignee['address'] . '</div>';
//     if (!empty($consignee['address_2'])) $html .= '<div>' . $consignee['address_2'] . '</div>';
//     $html .= '<div>' . $consignee['city'] . ', ' . $consignee['state'] . ' - ' . $consignee['pincode'] . '</div>';
//     $html .= '</div>';

//     $html .= '</div>';

//     // Items Table - Only show if at least one column is visible
//     $showItemsTable = (!$settings->hide_product || !$settings->hide_sku || !$settings->hide_qty || !$settings->hide_amount);
//     if ($showItemsTable) {
//         $html .= '<div style="margin-bottom:6px;max-height:120px;overflow:hidden;">';
//         $html .= '<div style="font-weight:bold;margin-bottom:3px;font-size:10px;">ORDER ITEMS</div>';
//         $html .= '<table style="width:100%;border-collapse:collapse;font-size:8px;">
//                     <thead>
//                         <tr style="background:#f5f5f5;">';
//         if (!$settings->hide_product) $html .= '<th style="text-align:left;padding:3px;border-bottom:1px solid #ddd;font-size:8px;">Item</th>';
//         if (!$settings->hide_qty)     $html .= '<th style="text-align:right;padding:3px;border-bottom:1px solid #ddd;font-size:8px;width:40px;">Qty</th>';
//         if (!$settings->hide_amount)  $html .= '<th style="text-align:right;padding:3px;border-bottom:1px solid #ddd;font-size:8px;width:60px;">Price</th>';
//         $html .= '</tr>
//                     </thead>
//                     <tbody>';

//         $displayItems = array_slice($orderItems, 0, 5);

//         foreach ($displayItems as $item) {
//             $html .= '<tr>';
//             if (!$settings->hide_product) {
//                 $html .= '<td style="padding:3px;border-bottom:1px solid #eee;vertical-align:top;">' . (strlen($item['name'] ?? 'Product') > 25 ? substr($item['name'] ?? 'Product', 0, 25) . '...' : ($item['name'] ?? 'Product')) . '</td>';
//             }
//             if (!$settings->hide_qty) {
//                 $html .= '<td style="padding:3px;text-align:right;vertical-align:top;">' . ($item['qty'] ?? 1) . '</td>';
//             }
//             if (!$settings->hide_amount) {
//                 $html .= '<td style="padding:3px;text-align:right;vertical-align:top;">';
//                 if ($settings->hide_prepaid_amount && $dummyData['payment_type'] != 'COD') {
//                     $html .= '<span style="color:#666;">Prepaid</span>';
//                 } else {
//                     $html .= 'Rs ' . number_format(($item['price'] ?? $dummyData['order_amount']), 2);
//                 }
//                 $html .= '</td>';
//             }
//             $html .= '</tr>';
//         }

//         if (count($orderItems) > 5) {
//             $colspan = 0;
//             if (!$settings->hide_product) $colspan++;
//             if (!$settings->hide_qty) $colspan++;
//             if (!$settings->hide_amount) $colspan++;
//             $html .= '<tr><td colspan="' . $colspan . '" style="padding:3px;text-align:center;font-style:italic;color:#666;">... and ' . (count($orderItems) - 5) . ' more items</td></tr>';
//         }

//         $html .= '</tbody>';
//         // Total row only if amount is visible
//         if (!$settings->hide_amount) {
//             $colspan = 0;
//             if (!$settings->hide_product) $colspan++;
//             if (!$settings->hide_qty) $colspan++;
//             $html .= '<tfoot>
//                         <tr>
//                             <td colspan="' . $colspan . '" style="text-align:right;padding:3px;font-weight:bold;font-size:8px;">Total:</td>
//                             <td style="text-align:right;padding:3px;font-weight:bold;font-size:8px;">';
//             if ($settings->hide_prepaid_amount && $dummyData['payment_type'] != 'COD') {
//                 $html .= '<span style="color:#666;">Prepaid</span>';
//             } else {
//                 $html .= 'Rs ' . number_format($dummyData['order_amount'], 2);
//             }
//             $html .= '</td>
//                         </tr>
//                       </tfoot>';
//         }
//         $html .= '</table>';
//         $html .= '</div>';
//     }

//     // Payment + Barcode
//     $html .= '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-top:4px;border-top:1px solid #eee;padding-top:4px;">';

//     // Payment - Only show if not hidden
//     if (!$settings->hide_amount) {
//         $html .= '<div style="flex:1;">
//                     <div style="font-weight:bold;font-size:9px;">PAYMENT METHOD</div>
//                     <div style="font-size:8px;">' . ucfirst($dummyData['payment_type']) . '</div>';
//         if ($dummyData['payment_type'] == 'COD') {
//             $html .= '<div style="margin-top:2px;font-weight:bold;font-size:8px;">COD: Rs ' . number_format($dummyData['collectable_amount'], 2) . '</div>';
//         }
//         $html .= '</div>';
//     }

//     // Barcode
//     if (!empty($order->awb_number)) {
//         $barcodeUrl = "https://bwipjs-api.metafloor.com/?bcid=code128&text=" . urlencode($order->awb_number) . "&scale=1&height=8&includetext";
//         $barcodeImage = @file_get_contents($barcodeUrl);
//         if ($barcodeImage !== false) {
//             $barcodeBase64 = base64_encode($barcodeImage);
//             $barcodeSrc    = "data:image/png;base64," . $barcodeBase64;
//             $html .= '<div style="text-align:right;flex:1;">
//                         <img src="' . $barcodeSrc . '" style="height:35px;"/>
//                         <div style="font-size:7px;font-weight:bold;">' . $order->awb_number . '</div>
//                       </div>';
//         } else {
//             $html .= '<div style="font-size:8px;color:#999;">[Barcode not available]</div>';
//         }
//     }

//     $html .= '</div>';

//     // Support
//     if ($settings->show_support_contact) {
//         $email = auth()->guard('seller')->user()->email ?? 'shipxpeed@gmail.com';
//         $phone = auth()->guard('seller')->user()->phone_number ?? '9876543210';

//         $html .= '<div style="text-align:center;margin-top:4px;font-size:7px;color:#666;border-top:1px solid #eee;padding-top:3px;">
//                     For support: ' . htmlspecialchars($email) . ' | ' . htmlspecialchars($phone) . '
//                   </div>';
//     }

//     $html .= '</div>'; //
//         return $html;
// }

    


  










public function downloadExcelTemplate()
{
    $headers = [
        'Order Number', 'Payment Type', 'Collectable Amount',
        'Product Name', 'SKU', 'Qty', 'Price',
        'Consignee Name', 'Phone', 'Email', 'Address', 'Address 2', 'Pincode', 'City', 'State',
        'Pickup Warehouse Name', 'Pickup Name', 'Pickup Address', 'Pickup Address 2', 'Pickup Pincode', 'Pickup City', 'Pickup State', 'Pickup Phone',
        'Weight', 'Length', 'Breadth', 'Height'
    ];

    $rows = [
        [
            '#8581', 'cod', 100,
            'product 1', '1', '1', '1',
            'Customer Name', '7357169546', 'customer@example.com', 'jaipur raj.', 'Near Bus Stand', '110092', 'East delhi', 'Delhi',
            'delhi', 'vicky', 'delhi', 'delhi', '110092', 'East delhi', 'Delhi', '7357169546',
            '100', '10', '10', '10'
        ]
    ];

    $callback = function () use ($headers, $rows) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $headers);
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    };

    return Response::stream($callback, 200, [
        "Content-Type" => "text/csv",
        "Content-Disposition" => "attachment; filename=order-template.csv"
    ]);
}





public function exportOrdersExcel(Request $request)
{
    $seller = Auth::guard('seller')->user();
    
    if (!$seller || $seller->status != 1) {
        return back()->with('error', 'Unauthorized access.');
    }

    // Start with base query
    $query = Order::where('seller_id', $seller->id)
        ->whereNull('awb_number');
    
    // Apply date filters if provided
    if ($request->has('start_date') && $request->start_date) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }
    
    if ($request->has('end_date') && $request->end_date) {
        $query->whereDate('created_at', '<=', $request->end_date);
    }
    
    $orders = $query->latest()->get();

    $headers = [
        'Order Number',
        'AWB Number', 
        'Payment Type',
        'Order Amount',
        'Collectable Amount',
        'Product Name',
        'Quantity',
        'Customer Name',
        'Customer Phone',
        'Customer Address',
        'Pincode',
        'City',
        'State',
        'Weight (kg)',
        'Warehouse',
        'Created Date',
        'Status'
    ];

    $callback = function () use ($headers, $orders) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $headers);
        
        foreach ($orders as $order) {
            $pickup = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
            $consignee = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
            $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
            
            // Get product names and quantities
            $productNames = [];
            $totalQty = 0;
            if ($items) {
                foreach ($items as $item) {
                    $productNames[] = $item['name'] ?? 'N/A';
                    $totalQty += (int)($item['qty'] ?? $item['quantity'] ?? 1);
                }
            }
            
            $row = [
                $order->seller_id == 433 ? $order->customer_order_id : $order->order_number,
                $order->awb_number ?? 'Not Assigned',
                strtoupper($order->payment_type ?? 'N/A'),
                $order->order_amount ?? 0,
                $order->collectable_amount ?? 0,
                implode(', ', $productNames),
                $totalQty,
                $consignee['name'] ?? 'N/A',
                $consignee['phone'] ?? 'N/A',
                ($consignee['address'] ?? 'N/A') . ' ' . ($consignee['address2'] ?? ''),
                $consignee['pincode'] ?? 'N/A',
                $consignee['city'] ?? 'N/A',
                $consignee['state'] ?? 'N/A',
                $order->package_weight ?? 0,
                $pickup['warehouse_name'] ?? 'Not Assigned',
                $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
                $order->shipping_status ?? 'Pending'
            ];
            
            fputcsv($file, $row);
        }
        fclose($file);
    };

    $filename = 'orders-export-' . date('Y-m-d-H-i-s') . '.csv';
    
    return Response::stream($callback, 200, [
        "Content-Type" => "text/csv",
        "Content-Disposition" => "attachment; filename={$filename}"
    ]);
}

public function exportAssignedOrdersExcel(Request $request)
{
    $seller = Auth::guard('seller')->user();
    
    if (!$seller || $seller->status != 1) {
        return back()->with('error', 'Unauthorized access.');
    }

    // Build the same query as index_Assigned function
    $query = Order::where('seller_id', $seller->id)
        ->whereNotNull('awb_number')
        ->where(function ($query) {
            $query->whereNull('order_status')
                ->orWhere('order_status', '');
        })
        ->where(function ($query) {
            $query->whereNull('shipping_status')
                ->orWhereIn('shipping_status', ['Assigned', 'Pending Pickup', 'Manifested', 'Not Picked','courier Assigned','assigned']);
        });

    // Apply date filters if provided
    if ($request->has('date_from') && $request->date_from) {
        $query->whereDate('shipping_date', '>=', $request->date_from);
    }
    
    if ($request->has('date_to') && $request->date_to) {
        $query->whereDate('shipping_date', '<=', $request->date_to);
    }

    // Single date filter
    if ($request->has('filter_date') && $request->filter_date) {
        $query->whereDate('shipping_date', $request->filter_date);
    }
    
    $orders = $query->latest()->orderBy('order_number', 'desc')->get();

    $headers = [
        'Order Number',
        'AWB Number',
        'Courier',
        'Shipping Status',
        'Order Status', 
        'Payment Type',
        'Order Amount',
        'Collectable Amount',
        'Product Name',
        'Quantity',
        'Customer Name',
        'Customer Phone',
        'Customer Address',
        'Pincode',
        'City',
        'State',
        'Weight (kg)',
        'Warehouse',
        'Shipping Date',
        'Created Date'
    ];

    $callback = function () use ($headers, $orders) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $headers);
        
        foreach ($orders as $order) {
            $pickup = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
            $consignee = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
            $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
            
            // Get product names and quantities
            $productNames = [];
            $totalQty = 0;
            if ($items) {
                foreach ($items as $item) {
                    $productNames[] = $item['name'] ?? 'N/A';
                    $totalQty += (int)($item['qty'] ?? $item['quantity'] ?? 1);
                }
            }
            
            $row = [
                $order->seller_id == 433 ? $order->customer_order_id : $order->order_number,
                $order->awb_number ?? 'Not Assigned',
                $order->courier_name ?? 'N/A',
                $order->shipping_status ?? 'Pending',
                $order->order_status ?? 'N/A',
                strtoupper($order->payment_type ?? 'N/A'),
                $order->order_amount ?? 0,
                $order->collectable_amount ?? 0,
                implode(', ', $productNames),
                $totalQty,
                $consignee['name'] ?? 'N/A',
                $consignee['phone'] ?? 'N/A',
                ($consignee['address'] ?? 'N/A') . ' ' . ($consignee['address2'] ?? ''),
                $consignee['pincode'] ?? 'N/A',
                $consignee['city'] ?? 'N/A',
                $consignee['state'] ?? 'N/A',
                $order->package_weight ?? 0,
                $pickup['warehouse_name'] ?? 'Not Assigned',
                $order->shipping_date ? $order->shipping_date : '',
                $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : ''
            ];
            
            fputcsv($file, $row);
        }
        fclose($file);
    };

    $filename = 'assigned-orders-export-' . date('Y-m-d-H-i-s') . '.csv';
    
    return Response::stream($callback, 200, [
        "Content-Type" => "text/csv",
        "Content-Disposition" => "attachment; filename={$filename}"
    ]);
}

public function importOrders(Request $request)
{
    // dd($request);
    $request->validate([
        'excel_file' => 'required|file',
    ]);
// dd($request);
    try {
        Excel::import(new OrdersImport, $request->file('excel_file'));
        return back()->with('success', 'Orders uploaded successfully.');
    } catch (\Exception $e) {
        return back()->with('error', 'Error importing: ' . $e->getMessage());
    }
}

public function exportCancelledOrdersExcel(Request $request)
{
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return redirect()->route('seller.login')->with('error', 'Please login to access this feature.');
    }

    $query = Order::where('seller_id', $seller->id)
                  ->where('order_status', 'Cancelled')
                  ->latest();

    // Apply date filters if provided
    if ($request->filled('from_date') && $request->filled('to_date')) {
        $fromDate = \Carbon\Carbon::parse($request->from_date)->startOfDay();
        $toDate = \Carbon\Carbon::parse($request->to_date)->endOfDay();
        $query->whereBetween('created_at', [$fromDate, $toDate]);
    }

    $orders = $query->get();

    // Prepare data for Excel
    $data = [];
    $data[] = [
        'AWB Number',
        'Order Number'
    ];

    foreach ($orders as $order) {
        $data[] = [
            $order->awb_number ?? '',
            $order->order_number ?? ''
        ];
    }

    // Create Excel file
    $filename = 'cancelled_orders_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
        public $data;
        
        public function __construct($data) {
            $this->data = collect($data);
        }
        
        public function collection() {
            return $this->data->skip(1); // Skip header row
        }
        
        public function headings(): array {
            return $this->data->first(); // Return first row as headings
        }
    }, $filename);
}

public function exportInTransitOrdersExcel(Request $request)
{
    $seller = Auth::guard('seller')->user();

    if (!$seller || $seller->status != 1) {
        return redirect()->route('seller.login')->with('error', 'Please login to access this feature.');
    }

    $query = Order::where('seller_id', $seller->id)
                  ->where('shipping_status', 'transit')
                  ->latest();

    // Apply date filters if provided
    if ($request->filled('from_date') && $request->filled('to_date')) {
        $fromDate = \Carbon\Carbon::parse($request->from_date)->startOfDay();
        $toDate = \Carbon\Carbon::parse($request->to_date)->endOfDay();
        $query->whereBetween('created_at', [$fromDate, $toDate]);
    }

    $orders = $query->get();

    // Prepare data for Excel
    $data = [];
    $data[] = [
        'AWB Number',
        'Order Number'
    ];

    foreach ($orders as $order) {
        $data[] = [
            $order->awb_number ?? '',
            $order->order_number ?? ''
        ];
    }

    // Create Excel file
    $filename = 'in_transit_orders_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
        public $data;
        
        public function __construct($data) {
            $this->data = collect($data);
        }
        
        public function collection() {
            return $this->data->skip(1); // Skip header row
        }
        
        public function headings(): array {
            return $this->data->first(); // Return first row as headings
        }
    }, $filename);
}




public function getTekipostLabel(Request $request)
{
    $order = Order::find($request->order_id);

    if (!$order || $order->courier_id !== 'tekipost') {
        return response()->json(['error' => 'Order not found or not Tekipost.'], 404);
    }
// echo 'cscsc';die;
    return response()->json([
        // 'courier' => $couriername,
        'courier' => $order->smarship_courier_id ?? 'Tekipost',
        // 'processed' => $order->status ?? 'Pending',
        'label_url' => $order->smartship_tracking_url,
    ]);
    
}




public function getParcelxLabel(Request $request)
{
    $order = Order::find($request->order_id);

    if (!$order || $order->courier_id !== 'parcelx') {
        return response()->json(['error' => 'Order not found or not Parcelx.'], 404);
    }

    // PARCELX API DETAILS
    $apiUrl = "https://app.parcelx.in/api/v1/label?awb=" . $order->awb_number . "&label_type=label";

    $accessToken = "MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl"; // <-- yaha apna token dale

    // CALL PARCELX API
    $response = Http::withHeaders([
        "access-token" => $accessToken,
    ])->get($apiUrl);

    // Check if API failed
    if ($response->failed()) {
        return response()->json([
            'status' => false,
            'message' => 'Parcelx API error',
            'parcelx_response' => $response->json()
        ], 500);
    }

    $parcelxData = $response->json();
    // dd($parcelxData);
    // FINAL RESPONSE (same as Parcelx format)
    return response()->json([
        "status" => true,
        "courier" => $order->all_courier_name ?? 'shipxpeed',
        "label_url" => $parcelxData['label_url'] ?? null, // Parcelx label URL
    ]);
}



// public function getParcelxLabel(Request $request)
// {
//     $order = Order::find($request->order_id);

//     if (!$order || $order->courier_id !== 'parcelx') {
//         return response()->json(['error' => 'Order not found or not Parcelx.'], 404);
//     }
// // echo 'cscsc';die;
//     return response()->json([
//         // 'courier' => $couriername,
//         'courier' => $order->smarship_courier_id ?? 'Parcelx',
//         // 'processed' => $order->status ?? 'Pending',
//         'label_url' => $order->smartship_tracking_url,
//     ]);
    
// }



public function getSmartshipLabel(Request $request)
{
    $order = Order::find($request->order_id);

    if (!$order || $order->courier_id !== 'smartship') {
        return response()->json(['error' => 'Order not found or not Smartship.'], 404);
    }

    return response()->json([
        // 'courier' => $couriername,
                'courier' => $order->smarship_courier_id ?? 'Smartship',

        // 'processed' => $order->status ?? 'Pending',
        'label_url' => $order->smartship_tracking_url,
    ]);
    
}


    public function getCourierInfo_delhivery_b2c(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['error' => 'Order not found']);
        }

        try {
            $awb = $order->awb_number;

            Log::info('Fetching Delhivery B2C Label for AWB:', ['awb' => $awb]);

            $response = Http::withHeaders([
                'Authorization' => 'Token a6cd5bb955fddcb41757ec23ee92cf62b6650607',
                'Content-Type' => 'application/json',
            ])->get("https://track.delhivery.com/api/p/packing_slip", [
                        'wbns' => $awb,
                        'pdf' => 'true', // <- Set to true to get PDF label base64
                    ]);

            Log::info('Delhivery API response:', ['body' => $response->body()]);

            if ($response->failed()) {
                return response()->json(['error' => 'Failed to fetch from Delhivery']);
            }

            $data = $response->json();

            if (!isset($data['packages_found']) || $data['packages_found'] == 0) {
                return response()->json(['error' => 'No packages found for this AWB']);
            }

            $pdfBase64 = $data['packages'][0]['pdf_download_link'] ?? null;
            // dd($pdfBase64);
            if (!$pdfBase64) {
                return response()->json(['error' => 'Label PDF not found in API response']);
            }

            return response()->json([
                'courier' => 'Delhivery',
                'processed' => 1,
                'pdf_base64' => $pdfBase64,
            ]);

        } catch (\Exception $e) {
            Log::error('Delhivery API Exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()]); // Return real error for debugging
        }
    }

 public function acceptAgreement(Request $request)
{
    $validated = $request->validate([
        'clientName' => 'required|string',
        'clientAddress' => 'required|string',
        'clientPAN' => 'nullable',
    ]);

    /** @var SellerList $seller */
    $seller = auth('seller')->user();

    if (!$seller) {
        return redirect()->back()->with('error', 'Seller not authenticated.');
    }

    $pdf = PDF::loadView('seller.agreement-pdf', [
        'seller' => $seller,
        'clientName' => $request->clientName,
        'clientAddress' => $request->clientAddress,
        'clientPAN' => $request->clientPAN,
        'currentDate' => now()->format('d/m/Y'),
    ]);

    $path = 'agreements/agreement' . $seller->id . '.pdf';
    Storage::put('public/' . $path, $pdf->output());

    \Log::info('Updating SellerList', ['seller_id' => $seller->id]);

    $affected = SellerList::where('id', $seller->id)->update([
        'agreement_accepted' => 1,

    ]);

    \Log::info('SellerList updated rows count:', ['count' => $affected]);

    SellerAgreement::updateOrCreate(
        ['seller_id' => $seller->id],
        [
            'agreement_accepted_at' => now(),
            'client_name' => $request->clientName,
            'client_address' => $request->clientAddress,
            'client_pan' => $request->clientPAN,
            'pdf_path' => $path
        ]
    );

      SellerAddress::updateOrCreate(
            ['seller_id' => $seller->id],
            [
                // 'state_id' => $request->state_id,
                // 'city_id' => $request->city_id,
                // 'country' => $request->country,
                // 'pincode' => $request->pincode,
                'address_line' => $request->clientAddress,
            ]
        );

    return redirect()->back()->with('success', 'Agreement accepted successfully!');
}





    public function getCourierInfo_shadowfax(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['error' => 'Order not found']);
        }

        $awb = $order->awb_number;

        $response = Http::withHeaders([
            'Authorization' => 'Token fec1949bfc737bd52df914d18673e27b67a7f92d', // replace with actual token
            'Content-Type' => 'application/json',
        ])->post('https://dale.shadowfax.in/api/client/generate_label/', [
                    'awb_number' => $awb,
                    'file_type' => 'pdf',
                ]);

        //  dd($response->json());
        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch from Shadowfax']);
        }

        $responseData = $response->json();
        // dd($responseData);
        // dd($responseData['data']['label_url']);
        return response()->json([
            'courier' => 'Shadowfax',
            'processed' => 1,
            'label_url' => $responseData['data']['label_url'] ?? null,
            'message' => $responseData['message'] ?? 'Success',
        ]);
    }


    public function getCourierInfo(Request $request)
    {
        $orderId = $request->input('order_id');
        //  dd($orderId);
        // Fetch order
        $order = Order::find($orderId);


        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $courier = $order->courier_name ?? 'Not assigned';
        ;
        $manifest_url = $order->manifest ?? 'Not assigned';
        ;
        $label_url = $order->label ?? 'Not assigned';
        ;
        //    dd($courier, $manifest_url, $label_url);
        // You can customize what data you want to return
        return response()->json([
            'courier' => $courier, // example
            'processed' => 1,
            'manifest_url' => $manifest_url,
            'label_url' => $label_url
        ]);
    }


public function getPrepaidCodData()
{
    $cod = Order::where('payment_type', 'cod')->count();
    $prepaid = Order::where('payment_type', 'prepaid')->count();

    return response()->json([
        'cod' => $cod,
        'prepaid' => $prepaid
    ]);
}


public function getDashboardData(Request $request)
{
    // dd($request);
    $sellerId = Auth::guard('seller')->id();
    // dd($sellerId);
    // echo 'cscsc';die;
    $days = $request->days ?? 30;
    //  dd($days);
    $startDate = now()->subDays($days - 1)->startOfDay();

    // Orders per day
    $orders = Order::where('created_at', '>=', $startDate)
         ->where('seller_id', $sellerId)
        ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    $labels = [];
    $data = [];

    for ($i = 0; $i < $days; $i++) {
        $date = now()->subDays($days - $i - 1)->format('Y-m-d');
        $labels[] = date('d M', strtotime($date));

        $found = $orders->firstWhere('date', $date);
        $data[] = $found ? $found->total : 0;
    }

    // COD / Prepaid
    $cod = Order::where('seller_id', $sellerId)->where('payment_type', 'cod')->count();
    $prepaid = Order::where('seller_id', $sellerId)->where('payment_type', 'prepaid')->count();

    return response()->json([
        'labels' => $labels,
        'orders' => $data,
        'cod' => $cod,
        'prepaid' => $prepaid
    ]);
}


public function getRevenueDashboardData(Request $request)
{
    try {
        // dd($request);

        $sellerId = Auth::guard('seller')->id();
//  dd($sellerId);

        $range = $request->range ?? 'month';
        $labels = [];
        $data = [];

        // 🔹 Revenue (Month)
        if ($range == 'month') {
            for ($i=1;$i<=30;$i+=4) {

                $date = now()->startOfMonth()->addDays($i-1);

                $labels[] = $date->format('d');

                $data[] = Order::where('seller_id', $sellerId) // ✅ filter
                    ->whereDate('created_at', $date)
                    ->sum('collectable_amount') ?? 0;
            }
        }

        // 🔹 Zones (seller wise)
        $zoneData = Order::where('seller_id', $sellerId)
            ->select('zone')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('zone')
            ->pluck('total','zone');

        $zones = [
            'A' => $zoneData['A'] ?? 24,
            'B' => $zoneData['B'] ?? 30,
            'C' => $zoneData['C'] ?? 50,
            'D' => $zoneData['D'] ?? 12,
        ];

        // 🔹 Total Revenue (seller wise)
        $totalRevenue = Order::where('seller_id', $sellerId)
            ->sum('collectable_amount');

        // 🔹 Courier (seller wise)
        $couriers = Order::where('seller_id', $sellerId)
            ->select('all_courier_name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('all_courier_name')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(function ($c) {
                return [
                    'name' => $c->all_courier_name ?? 'N/A',
                    'percent' => rand(90, 99)
                ];
            });
//  dd($couriers);
        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'zones' => $zones,
            'couriers' => $couriers,
            'totalRevenue' => $totalRevenue
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


// public function getRevenueDashboardData(Request $request)
// {
//     try {

//         $range = $request->range ?? 'month';

//         $labels = [];
//         $data = [];

//         if ($range == 'month') {
//             for ($i=1;$i<=30;$i+=4) {
//                 $date = now()->startOfMonth()->addDays($i-1);

//                 $labels[] = $date->format('d');

//                 $data[] = Order::whereDate('created_at',$date)
//                     ->sum('collectable_amount') ?? 0;
//             }
//         }

//         // Zones (safe)
//         $zoneData = Order::select('zone')
//             ->selectRaw('COUNT(*) as total')
//             ->groupBy('zone')
//             ->pluck('total','zone');

//         $zones = [
//             'A' => $zoneData['A'] ?? 15,
//             'B' => $zoneData['B'] ?? 29,
//             'C' => $zoneData['C'] ?? 30,
//             'D' => $zoneData['D'] ?? 25,
//         ];

//         $totalRevenue = Order::sum('total_amount');
//         // Courier safe
//         $couriers = Order::select('courier_name')
//             ->selectRaw('COUNT(*) as total')
//             ->groupBy('courier_name')
//             ->limit(3)
//             ->get();

//         $couriers = $couriers->map(function ($c) {
//             return [
//                 'name' => $c->courier_name ?? 'N/A',
//                 'percent' => rand(90, 99)
//             ];
//         });

//         return response()->json([
//             'labels' => $labels,
//             'data' => $data,
//             'zones' => $zones,
//             'couriers' => $couriers
//         ]);

//     } catch (\Exception $e) {
//         dd($e->getMessage());
//         return response()->json([
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }


// public function getRevenueDashboardData(Request $request)
// {
//     $range = $request->range ?? 'month';

//     // 🔹 Revenue
//     if ($range == 'year') {
//         $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

//         $data = [];
//         for ($i=1;$i<=12;$i++) {
//             $sum = Order::whereMonth('created_at',$i)
//                 ->whereYear('created_at', now()->year)
//                 ->sum('total_amount');

//             $data[] = $sum;
//         }

//     } elseif ($range == 'week') {

//         $labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
//         $data = [];

//         for ($i=0;$i<7;$i++) {
//             $date = now()->startOfWeek()->addDays($i);
//             $labels[$i] = $date->format('D');

//             $data[] = Order::whereDate('created_at',$date)
//                 ->sum('total_amount');
//         }

//     } else {
//         // month
//         $labels = [];
//         $data = [];

//         for ($i=1;$i<=30;$i+=4) {
//             $date = now()->startOfMonth()->addDays($i-1);

//             $labels[] = $date->format('d');

//             $data[] = Order::whereDate('created_at',$date)
//                 ->sum('total_amount');
//         }
//     }

//     // 🔹 Zone Stats (REAL)


//     $zones = [
//         'A' => Order::where('zone','A')->count(),
//         'B' => Order::where('zone','B')->count(),
//         'C' => Order::where('zone','C')->count(),
//         'D' => Order::where('zone','D')->count(),
//     ];

//     // 🔹 Courier Performance
//     $couriers = Order::select('courier_name')
//         ->selectRaw('COUNT(*) as total')
//         ->groupBy('courier_name')
//         ->orderByDesc('total')
//         ->limit(3)
//         ->get()
//         ->map(function ($c) {
//             return [
//                 'name' => $c->courier_name,
//                 'percent' => rand(90, 99) // optional logic
//             ];
//         });

//     return response()->json([
//         'labels' => $labels,
//         'data' => $data,
//         'zones' => $zones,
//         'couriers' => $couriers
//     ]);
// }

    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        // dd($sellerId);
        $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
            ->latest()
            ->take(5)
            ->get();


        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }

        // Get latest orders
        $latestOrders = Order::where('seller_id', $sellerId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Date ranges for comparison
        $now = Carbon::now();
        $last30Days = $now->copy()->subDays(30);
        $previous30Days = $now->copy()->subDays(60);

        // Current counts (last 30 days)
        $orderCount = Order::where('seller_id', $seller->id)
            ->whereNull('awb_number')
            ->where('created_at', '>=', $last30Days)
            ->count();
        
        // $Assigned = Order::where('seller_id', $seller->id)
        //     ->where('shipping_status', 'Assigned','courier Assigned')
        //     ->whereNotNull('awb_number')
        //     ->whereNull('order_status')
        //     ->whereNull('shipping_status')

        //     ->where('created_at', '>=', $last30Days)
        //     ->count();
        $Assigned = Order::where('seller_id', $seller->id)
    ->where(function ($q) {
        $q->whereIn('shipping_status', ['Assigned', 'courier Assigned'])
          ->orWhereNull('shipping_status');
    })
    ->whereNotNull('awb_number')
    ->whereNull('order_status')
    ->where('created_at', '>=', $last30Days)
    ->count();


        $Allorder = Order::where('seller_id', $sellerId)
            ->where('created_at', '>=', $last30Days)
            ->count();

        $Cancelled = Order::where(['seller_id' => $sellerId, 'order_status' => 'cancelled'])
            ->where('created_at', '>=', $last30Days)
            ->count();

        // Previous counts (30-60 days ago)
        $previousOrderCount = Order::where('seller_id', $seller->id)
            ->whereNull('awb_number')
            ->whereBetween('created_at', [$previous30Days, $last30Days])
            ->count();
        
        $previousAssigned = Order::where('seller_id', $seller->id)
            ->whereNotNull('awb_number')
            ->whereNull('order_status')
            ->whereBetween('created_at', [$previous30Days, $last30Days])
            ->count();

        $previousAllorder = Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [$previous30Days, $last30Days])
            ->count();

        $previousCancelled = Order::where(['seller_id' => $sellerId, 'order_status' => 'cancelled'])
            ->whereBetween('created_at', [$previous30Days, $last30Days])
            ->count();

        // Calculate percentage changes
        $orderCountChange = $previousOrderCount > 0 ? 
            round((($orderCount - $previousOrderCount) / $previousOrderCount) * 100, 1) : 
            ($orderCount > 0 ? 100 : 0);
        
        $assignedChange = $previousAssigned > 0 ? 
            round((($Assigned - $previousAssigned) / $previousAssigned) * 100, 1) : 
            ($Assigned > 0 ? 100 : 0);
        
        $allorderChange = $previousAllorder > 0 ? 
            round((($Allorder - $previousAllorder) / $previousAllorder) * 100, 1) : 
            ($Allorder > 0 ? 100 : 0);
        
        $cancelledChange = $previousCancelled > 0 ? 
            round((($Cancelled - $previousCancelled) / $previousCancelled) * 100, 1) : 
            ($Cancelled > 0 ? 100 : 0);

        // Calculate analytics metrics based on order data
        $totalVisitors = $Allorder * 12.5; // Overall visitors based on orders
        $avgOrderTime = $Allorder > 0 ? round(($Allorder * 15.5) / $Allorder, 2) : 15.48; // Average duration
        $pagesPerVisit = $Allorder > 0 ? round(245 + ($Allorder / 10), 2) : 245.65; // Pages per visit
        
        // Previous period analytics for percentage calculation
        $previousVisitors = $previousAllorder * 12.5;
        $previousAvgTime = $previousAllorder > 0 ? round(($previousAllorder * 14.2) / $previousAllorder, 2) : 14.20;
        $previousPagesPerVisit = $previousAllorder > 0 ? round(235 + ($previousAllorder / 10), 2) : 235.00;
        
        // Analytics percentage changes
        $visitorsChange = $previousVisitors > 0 ? 
            round((($totalVisitors - $previousVisitors) / $previousVisitors) * 100, 1) : 
            ($totalVisitors > 0 ? 100 : 0);
            
        $durationChange = $previousAvgTime > 0 ? 
            round((($avgOrderTime - $previousAvgTime) / $previousAvgTime) * 100, 1) : 
            ($avgOrderTime > 0 ? 100 : 0);
            
        $pagesChange = $previousPagesPerVisit > 0 ? 
            round((($pagesPerVisit - $previousPagesPerVisit) / $previousPagesPerVisit) * 100, 1) : 
            ($pagesPerVisit > 0 ? 100 : 0);

        // Sales breakdown based on orders (mock data for different channels)
        $currentWeekOrders = Order::where('seller_id', $sellerId)
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();
        
        $previousWeekOrders = Order::where('seller_id', $sellerId)
            ->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->count();

        // Sales channel data (simulated based on order patterns)
        $directSales = $currentWeekOrders * 0.45; // 45% direct
        $affiliateSales = $currentWeekOrders * 0.25; // 25% affiliate  
        $emailSales = $currentWeekOrders * 0.15; // 15% email
        $otherSales = $currentWeekOrders * 0.15; // 15% other

        // Previous week for comparison
        $prevDirectSales = $previousWeekOrders * 0.40; // Slightly lower percentages for previous week
        $prevAffiliateSales = $previousWeekOrders * 0.20;
        $prevEmailSales = $previousWeekOrders * 0.13;
        $prevOtherSales = $previousWeekOrders * 0.14;

        // Sales changes
        $directChange = $prevDirectSales > 0 ? 
            round((($directSales - $prevDirectSales) / $prevDirectSales) * 100, 1) : 
            ($directSales > 0 ? 100 : 0);
            
        $affiliateChange = $prevAffiliateSales > 0 ? 
            round((($affiliateSales - $prevAffiliateSales) / $prevAffiliateSales) * 100, 1) : 
            ($affiliateSales > 0 ? 100 : 0);
            
        $emailChange = $prevEmailSales > 0 ? 
            round((($emailSales - $prevEmailSales) / $prevEmailSales) * 100, 1) : 
            ($emailSales > 0 ? 100 : 0);
            
        $otherChange = $prevOtherSales > 0 ? 
            round((($otherSales - $prevOtherSales) / $prevOtherSales) * 100, 1) : 
            ($otherSales > 0 ? 100 : 0);

        // Social media metrics (based on order performance)
        $facebookFollowers = $Allorder * 251;
        $twitterTweets = $Allorder * 22;
        $youtubeSubscribers = $Allorder * 38;

        // Previous period social metrics
        $prevFacebookFollowers = $previousAllorder * 240;
        $prevTwitterTweets = $previousAllorder * 20;
        $prevYoutubeSubscribers = $previousAllorder * 35;

        // Social media growth percentages
        $facebookGrowth = $prevFacebookFollowers > 0 ? 
            round((($facebookFollowers - $prevFacebookFollowers) / $prevFacebookFollowers) * 100, 1) : 
            ($facebookFollowers > 0 ? 100 : 0);
            
        $twitterGrowth = $prevTwitterTweets > 0 ? 
            round((($twitterTweets - $prevTwitterTweets) / $prevTwitterTweets) * 100, 1) : 
            ($twitterTweets > 0 ? 100 : 0);
            
        $youtubeGrowth = $prevYoutubeSubscribers > 0 ? 
            round((($youtubeSubscribers - $prevYoutubeSubscribers) / $prevYoutubeSubscribers) * 100, 1) : 
            ($youtubeSubscribers > 0 ? 100 : 0);

        // Shipping Status Counts (current period - last 30 days)
        $inTransitCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['In Transit', 'In-Transit', 'transit', 'shipped'])
            ->where('created_at', '>=', $last30Days)
            ->count();

        $outForDeliveryCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['Out for Delivery', 'out for delivery', 'Out For Delivery'])
            ->where('created_at', '>=', $last30Days)
            ->count();

            
        $Allorderrto = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['rto', 'rto delivered'])
            ->where('created_at', '>=', $last30Days)
            ->count();

        $deliveredCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['Delivered', 'delivered', 'DELIVERED'])
            ->where('created_at', '>=', $last30Days)
            ->count();
            
        $ndrdeliveredCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['ndr', 'ndr', 'ndr'])
            ->where('created_at', '>=', $last30Days)
            ->count();

                    $transitdeliveredCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['transit', 'transit', 'transit'])
            ->where('created_at', '>=', $last30Days)
            ->count();
                               $OutforDelivery  = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['out for delivery', 'out for delivery', 'out for delivery'])
            ->where('created_at', '>=', $last30Days)
            ->count();

        // Previous period shipping status counts (30-60 days ago)
        $prevInTransitCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['In Transit', 'In-Transit', 'in_transit', 'shipped'])
            ->whereBetween('created_at', [$previous30Days, $last30Days])
            ->count();

        $prevOutForDeliveryCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['Out for Delivery', 'out_for_delivery', 'Out For Delivery'])
            ->whereBetween('created_at', [$previous30Days, $last30Days])
            ->count();

        $prevDeliveredCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['Delivered', 'delivered', 'DELIVERED'])
            ->whereBetween('created_at', [$previous30Days, $last30Days])
            ->count();

        // Calculate shipping status growth percentages
        $inTransitGrowth = $prevInTransitCount > 0 ? 
            round((($inTransitCount - $prevInTransitCount) / $prevInTransitCount) * 100, 1) : 
            ($inTransitCount > 0 ? 100 : 0);

        $outForDeliveryGrowth = $prevOutForDeliveryCount > 0 ? 
            round((($outForDeliveryCount - $prevOutForDeliveryCount) / $prevOutForDeliveryCount) * 100, 1) : 
            ($outForDeliveryCount > 0 ? 100 : 0);

        $deliveredGrowth = $prevDeliveredCount > 0 ? 
            round((($deliveredCount - $prevDeliveredCount) / $prevDeliveredCount) * 100, 1) : 
            ($deliveredCount > 0 ? 100 : 0);

        // Get RTO orders for the seller
        $rtoOrders = Order::where('seller_id', $sellerId)
            // ->where('shipping_status', 'rto')
            ->whereIn('shipping_status', ['rto', 'rto delivered','rto-it','rto-dispatched','rto-pending','rto delivered','rto_ofd','rts'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total RTO amount
        $totalRtoAmount = $rtoOrders->sum('seller_amount_walate');

        // Get Courier Partner Sales Distribution based on courier_id and seller_amount_walate
        $courierSales = Order::where('seller_id', $sellerId)
            ->whereNotNull('courier_id')
            ->whereNotNull('seller_amount_walate')
            ->where('seller_amount_walate', '>', 0)
            ->selectRaw('courier_id, COUNT(*) as order_count, SUM(seller_amount_walate) as total_amount')
            ->groupBy('courier_id')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Previous week data for comparison
        $previousWeekCourierSales = Order::where('seller_id', $sellerId)
            ->whereNotNull('courier_id')
            ->whereNotNull('seller_amount_walate')
            ->where('seller_amount_walate', '>', 0)
            ->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->selectRaw('courier_id, COUNT(*) as order_count, SUM(seller_amount_walate) as total_amount')
            ->groupBy('courier_id')
            ->get()
            ->keyBy('courier_id');

        // Prepare Sales Distribution data for charts
        $salesLabels = [];
        $salesValues = [];
        $salesTrends = [];

        if ($courierSales->count() > 0) {
            foreach ($courierSales->take(5) as $courier) { // Show top 5 courier partners
                $originalCourierName = $courier->courier_id ?: 'Unknown';
                
                // Map courier names for display
                $courierNameMapping = [
                    'tekipost' => 'Delhivery & Amazon',
                    'smartship' => 'Bluedart-Surface/DTDC',
                    'xpressbees' => 'Xpressbees',
                    'delhivery_b2c' => 'Delhivery B2C',
                    'shadowfax' => 'Shadowfax',
                    'boxd' => 'Delhivery',
                    'parcelx' => 'Delhivery',
                    'shiprocket' => 'Delhivery',

                ];
                
                $courierName = $courierNameMapping[$originalCourierName] ?? ucfirst($originalCourierName);
                $amount = $courier->total_amount;
                $previousAmount = $previousWeekCourierSales->get($courier->courier_id)->total_amount ?? 0;
                
                // Calculate percentage change
                $trend = $previousAmount > 0 ? 
                    round((($amount - $previousAmount) / $previousAmount) * 100, 1) : 
                    ($amount > 0 ? 100 : 0);

                $salesLabels[] = $courierName;
                $salesValues[] = round($amount);
                $salesTrends[] = $trend;
            }
        } else {
            // Fallback data when no courier data available
            $salesLabels = ['No Data Available'];
            $salesValues = [0];
            $salesTrends = [0];
        }

        // Prepare Traffic Overview data (last 7 days)
        $trafficLabels = [];
        $newVisitorsData = [];
        $returningVisitorsData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $trafficLabels[] = $date->format('M d');
            
            // Get orders for each day
            $dayOrders = Order::where('seller_id', $sellerId)
                ->whereDate('created_at', $date)
                ->count();
            
            // Simulate new vs returning customers
            $newVisitorsData[] = round($dayOrders * 0.6); // 60% new
            $returningVisitorsData[] = round($dayOrders * 0.4); // 40% returning
        }

            // return view('maintenance', compact('transactions', 'seller', 'totalAmount', 'latestOrders', 'orderCount', 'Assigned', 'Allorder', 'Cancelled'));



            return view('sellerdashboard.dashboard', compact(
            'transactions', 
            'seller', 
            'totalAmount', 
            'latestOrders', 
            'orderCount', 
            'Assigned', 
            'Allorder', 
            'Cancelled',
            'orderCountChange',
            'assignedChange',
            'allorderChange',
            'cancelledChange',
            'totalVisitors',
            'avgOrderTime',
            'pagesPerVisit',
            'visitorsChange',
            'durationChange',
            'pagesChange',
            'directSales',
            'affiliateSales',
            'emailSales',
            'otherSales',
            'directChange',
            'affiliateChange',
            'emailChange',
            'otherChange',
            'facebookFollowers',
            'twitterTweets',
            'youtubeSubscribers',
            'facebookGrowth',
            'twitterGrowth',
            'youtubeGrowth',
            'inTransitCount',
            'outForDeliveryCount',
            'deliveredCount',
            'inTransitGrowth',
            'outForDeliveryGrowth',
            'deliveredGrowth',
            'rtoOrders',
            'totalRtoAmount',
            'salesLabels',
            'salesValues',
            'salesTrends',
            'trafficLabels',
            'newVisitorsData',
            'returningVisitorsData',
            'sellerId',
            'transitdeliveredCount',
            'deliveredCount',
            'ndrdeliveredCount','Allorderrto'
        ));
    }

    public function downloadRtoExcel()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        if (!$seller) {
            return redirect()->back()->with('error', 'Seller not authenticated.');
        }

        // Get RTO orders for the seller
        $rtoOrders = Order::where('seller_id', $sellerId)
            ->where('shipping_status', 'rto')
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Order Number',
            'AWB Number', 
            'Customer Name',
            'Customer Phone',
            'RTO Amount Deducted',
            'Order Amount',
            'Created Date',
            'RTO Date',
            'Courier Name'
        ];

        $callback = function () use ($headers, $rtoOrders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($rtoOrders as $order) {
                $consignee = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
                $customerName = isset($consignee['name']) ? $consignee['name'] : 'N/A';
                $customerPhone = isset($consignee['phone']) ? $consignee['phone'] : 'N/A';
                
                fputcsv($file, [
                    $order->order_number ?? 'N/A',
                    $order->awb_number ?? 'N/A',
                    $customerName,
                    $customerPhone,
                    '₹' . number_format($order->seller_amount_walate ?? 0, 2),
                    '₹' . number_format($order->order_amount ?? 0, 2),
                    $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $order->updated_at ? $order->updated_at->format('Y-m-d H:i:s') : 'N/A',
                    $order->courier_name ?? 'N/A'
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=rto_orders_' . date('Y-m-d_H-i-s') . '.csv'
        ]);
    }



    public function orderstatus()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where('seller_id', $sellerId)
            ->latest()
            ->get();
        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.order.orderstatus', compact('transactions', 'seller', 'totalAmount'));
    }
    public function storeWarehouse(Request $request)
    {
        $validated = $request->validate([
            'address_title' => 'required',
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'pincode' => 'required',
            'address_line1' => 'required',
            'is_default' => 'sometimes',
        ]);

        $seller = Auth::guard('seller')->user();


        if ($request->input('is_default', false)) {
            Warehouse::where('seller_id', $seller->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create([
            'seller_id' => $seller->id,
            'address_title' => $validated['address_title'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'email' => $validated['email'],
            'pincode' => $validated['pincode'],
            'address_line1' => $validated['address_line1'],
            'address_line2' => $request->input('address_line2'),
            'is_default' => $request->input('is_default', false),

        ]);

        return redirect()->route('seller.orderadd')->with('success', 'Warehouse added successfully');
    }
    public function updateAddress(Request $request)
    {
        $request->validate([
            'state_id' => 'required|integer',
            'city_id' => 'required|integer',
            'country' => 'required|string',
            'pincode' => 'required|string',
            'address_line' => 'required|string',
        ]);

        $seller = Auth::guard('seller')->user();

        SellerAddress::updateOrCreate(
            ['seller_id' => $seller->id],
            [
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'country' => $request->country,
                'pincode' => $request->pincode,
                'address_line' => $request->address_line,
            ]
        );

        return redirect()->back()->with('success', 'Address updated successfully.');
    }
    public function updateBank(Request $request)
    {

        $request->validate([
            'account_holder_name' => 'required',
            'bank_name' => 'required',
            'account_number' => 'required',
            'ifsc_code' => 'required',
            'account_type' => 'required',
        ]);

        $seller = Auth::guard('seller')->user();

        SellerBankDetail::updateOrCreate(
            ['seller_id' => $seller->id],
            [
                'account_holder_name' => $request->account_holder_name,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'ifsc_code' => $request->ifsc_code,
                'account_type' => $request->account_type,
            ]
        );

        return redirect()->back()->with('success', 'Bank Details updated successfully.');
    }



    public function orderadd()
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



        return view('sellerdashboard.order.add', [
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'warehouses' => $warehouses,
            'allSellers' => $allSellers,
            'couriers' => 'vicky', // Pass to view
        ]);
    }

public function order()
{
    $seller = Auth::guard('seller')->user();
   
    $totalAmount = 0;
    $rateCard = null;
    $commonPdf = null;
    $orders = collect(); // default empty collection
    $Warehouse = collect(); // Initialize as empty collection

    if ($seller && $seller->status == 1) {
        $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
            ->where('type', 'Debit')
            ->sum('amount');

        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        $rateCard = RateCard::where('seller_id', $seller->id)->first();
        $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');

        // Build query for orders with date filtering
        $orderQuery = Order::where('seller_id', $seller->id)
            ->whereNull('awb_number');
            
        // Apply date filters if provided
        if (request('start_date')) {
            $orderQuery->whereDate('created_at', '>=', request('start_date'));
        }
        
        if (request('end_date')) {
            $orderQuery->whereDate('created_at', '<=', request('end_date'));
        }
        
        // Paginate orders
        $orders = $orderQuery->latest()->paginate(50);

        $Warehouse = Warehouse::where('seller_id', $seller->id)->get();
    }




    
     $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];
    return view('sellerdashboard.order', compact(
        'seller', 
        'totalAmount', 
        'rateCard', 
        'commonPdf', 
        'orders',
        'Warehouse',
        'orderCounts'
    ));
}

    
    public function updateWarehouseBulk(Request $request)
{
    $request->validate([
        'order_ids' => 'required|array',
        'order_ids.*' => 'exists:orders,id',
        'warehouse_id' => 'required|exists:warehouses,id'
    ]);

    // Get the warehouse details
    $warehouse = Warehouse::findOrFail($request->warehouse_id);
    // dd($warehouse);
    
    // Common warehouse data structures
    $pickupDataxpres = [
        'warehouse_name' => $warehouse->name,
        'name' => $warehouse->name,
        'address' => $warehouse->address_line1,
        'address_2' => $warehouse->address_line2 ?? '.',
        'pincode' => $warehouse->pincode,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'phone' => $warehouse->phone,
    ];

        $pickupData = [
        'warehouse_name' => $warehouse->name,
        'name' => $warehouse->name,
        'address' => $warehouse->address_line1,
        'address_2' => $warehouse->address_line2 ?? '.',
        'pincode' => $warehouse->pincode,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'phone' => $warehouse->phone,
    ];



    $rtoData = [
        'warehouse_name' => $warehouse->name,
        'name' => $warehouse->name,
        'address' => $warehouse->address_line1,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'pincode' => $warehouse->pincode,
        'phone' => $warehouse->phone
    ];

    // Shadowfax data
    $shadowfaxPickupDetails = [
        'name' => $warehouse->name,
        'contact' => $warehouse->phone,
        'address_line_1' => $warehouse->address_line1,
        'address_line_2' => $warehouse->address_line2 ?? '.',
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'pincode' => $warehouse->pincode,
        'latitude' => null,
        'longitude' => null,
        'unique_code' => null
    ];

    $shadowfaxRtsDetails = [
        'name' => $warehouse->name,
        'contact' => $warehouse->phone,
        'address_line_1' => $warehouse->address_line1,
        'address_line_2' => $warehouse->address_line2,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'pincode' => $warehouse->pincode,
        'email' => null,
        'latitude' => null,
        'longitude' => null,
        'unique_code' => null
    ];

    // Delhivery data (common for both B2C and B2C Air)
    $delhiveryPickupLocation = [
        'name' => $warehouse->warehouse_name,
        'add' => $warehouse->address_line1,
        'city' => $warehouse->city,
        'pin_code' => $warehouse->pincode,
        'country' => $warehouse->country ?? 'India',
        'phone' => $warehouse->phone
    ];

    // Get all orders to update
    $orders = Order::whereIn('id', $request->order_ids)->get();

    foreach ($orders as $order) {
        // Update encode_data
        $encodeData = json_decode($order->encode_data, true);
        if (isset($encodeData['pickup'])) {
            $encodeData['pickup'] = $pickupDataxpres;
        }
        $updatedEncodeData = json_encode($encodeData);
        
        // Update shadowfax_json
        $shadowfaxJson = json_decode($order->shadowfax_json, true);
        if (isset($shadowfaxJson['pickup_details'])) {
            $shadowfaxJson['pickup_details'] = $shadowfaxPickupDetails;
        }
        if (isset($shadowfaxJson['rts_details'])) {
            $shadowfaxJson['rts_details'] = $shadowfaxRtsDetails;
        }
        $updatedShadowfaxJson = json_encode($shadowfaxJson);
        
        // Update delhivery_b2c
        $delhiveryB2c = json_decode($order->delhivery_b2c, true);
        if (isset($delhiveryB2c['pickup_location'])) {
            $delhiveryB2c['pickup_location'] = $delhiveryPickupLocation;
        }
        $updatedDelhiveryB2c = json_encode($delhiveryB2c);
        
        // Update delhivery_b2c_air
        $delhiveryB2cAir = json_decode($order->delhivery_b2c_air, true);
        if (isset($delhiveryB2cAir['pickup_location'])) {
            $delhiveryB2cAir['pickup_location'] = $delhiveryPickupLocation;
        }
        $updatedDelhiveryB2cAir = json_encode($delhiveryB2cAir);
        
        // Update the order
        $order->update([
            'pickup' => $pickupData,
            'rto' => $rtoData,
            'encode_data' => $updatedEncodeData,
            'shadowfax_json' => $updatedShadowfaxJson,
            'delhivery_b2c' => $updatedDelhiveryB2c,
            'delhivery_b2c_air' => $updatedDelhiveryB2cAir
        ]);
    }

    return back()->with('success', 'Warehouse details updated for all selected orders');
}




    public function updateWarehouseBulkji(Request $request)
{
    $request->validate([
        'order_ids' => 'required|array',
        'order_ids.*' => 'exists:orders,id',
        'warehouse_id' => 'required|exists:warehouses,id'
    ]);

    // Get the warehouse details
    $warehouse = Warehouse::findOrFail($request->warehouse_id);
    
    // Common warehouse data structures
    $pickupDataxpres = [
        'warehouse_name' => $warehouse->warehouse_name,
        'name' => $warehouse->name,
        'address' => $warehouse->address,
        'address_2' => $warehouse->address_2 ?? '.',
        'pincode' => $warehouse->pincode,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'phone' => $warehouse->phone,
    ];

        $pickupData = [
        'warehouse_name' => $warehouse->warehouse_name,
        'name' => $warehouse->name,
        'address' => $warehouse->address,
        'address_2' => $warehouse->address_2 ?? '.',
        'pincode' => $warehouse->pincode,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'phone' => $warehouse->phone,
    ];



    $rtoData = [
        'warehouse_name' => $warehouse->warehouse_name,
        'name' => $warehouse->name,
        'address' => $warehouse->address,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'pincode' => $warehouse->pincode,
        'phone' => $warehouse->phone
    ];

    // Shadowfax data
    $shadowfaxPickupDetails = [
        'name' => $warehouse->name,
        'contact' => $warehouse->phone,
        'address_line_1' => $warehouse->address,
        'address_line_2' => $warehouse->address_2 ?? '.',
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'pincode' => $warehouse->pincode,
        'latitude' => null,
        'longitude' => null,
        'unique_code' => null
    ];

    $shadowfaxRtsDetails = [
        'name' => $warehouse->name,
        'contact' => $warehouse->phone,
        'address_line_1' => $warehouse->address,
        'address_line_2' => $warehouse->address,
        'city' => $warehouse->city,
        'state' => $warehouse->state,
        'pincode' => $warehouse->pincode,
        'email' => null,
        'latitude' => null,
        'longitude' => null,
        'unique_code' => null
    ];

    // Delhivery data (common for both B2C and B2C Air)
    $delhiveryPickupLocation = [
        'name' => $warehouse->warehouse_name,
        'add' => $warehouse->address,
        'city' => $warehouse->city,
        'pin_code' => $warehouse->pincode,
        'country' => $warehouse->country ?? 'India',
        'phone' => $warehouse->phone
    ];

    // Get all orders to update
    $orders = Order::whereIn('id', $request->order_ids)->get();

    foreach ($orders as $order) {
        // Update encode_data
        $encodeData = json_decode($order->encode_data, true);
        if (isset($encodeData['pickup'])) {
            $encodeData['pickup'] = $pickupDataxpres;
        }
        $updatedEncodeData = json_encode($encodeData);
        
        // Update shadowfax_json
        $shadowfaxJson = json_decode($order->shadowfax_json, true);
        if (isset($shadowfaxJson['pickup_details'])) {
            $shadowfaxJson['pickup_details'] = $shadowfaxPickupDetails;
        }
        if (isset($shadowfaxJson['rts_details'])) {
            $shadowfaxJson['rts_details'] = $shadowfaxRtsDetails;
        }
        $updatedShadowfaxJson = json_encode($shadowfaxJson);
        
        // Update delhivery_b2c
        $delhiveryB2c = json_decode($order->delhivery_b2c, true);
        if (isset($delhiveryB2c['pickup_location'])) {
            $delhiveryB2c['pickup_location'] = $delhiveryPickupLocation;
        }
        $updatedDelhiveryB2c = json_encode($delhiveryB2c);
        
        // Update delhivery_b2c_air
        $delhiveryB2cAir = json_decode($order->delhivery_b2c_air, true);
        if (isset($delhiveryB2cAir['pickup_location'])) {
            $delhiveryB2cAir['pickup_location'] = $delhiveryPickupLocation;
        }
        $updatedDelhiveryB2cAir = json_encode($delhiveryB2cAir);
        
        // Update the order
        $order->update([
            'pickup' => $pickupData,
            'rto' => $rtoData,
            'encode_data' => $updatedEncodeData,
            'shadowfax_json' => $updatedShadowfaxJson,
            'delhivery_b2c' => $updatedDelhiveryB2c,
            'delhivery_b2c_air' => $updatedDelhiveryB2cAir
        ]);
    }

    return back()->with('success', 'Warehouse details updated for all selected orders');
}




// public function updateWarehouseBulk(Request $request)
// {
//     // dd($request);
//     $request->validate([
//         'order_ids' => 'required|array',
//         'order_ids.*' => 'exists:orders,id',
//         'warehouse_id' => 'required|exists:warehouses,id'
//     ]);


//     $Warehouse = Warehouse::where('id', $request->warehouse_id)->first();
//     // dd($Warehouse);
//    $warehouse_name = $Warehouse->warehouse_name;
//     $name = $Warehouse->name;
//     $address = $Warehouse->address;
//     $address_2 = $Warehouse->address_2;
//    $pincode = $Warehouse->pincode;
//    $city = $Warehouse->city;
//    $state = $Warehouse->state;
//    $phone = $Warehouse->phone;
//    $country = $Warehouse->country;


//     Order::whereIn('id', $request->order_ids)
//         ->update(['warehouse_id' => $request->warehouse_id]);

//     return back()->with('success', 'Warehouse updated for selected orders');
// }


    public function deleteOrder($id)
    {
        // dd($id);
        $order = \App\Models\Order::find($id);

        if (!$order) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        $order->delete();

        return redirect()->back()->with('success', 'Order deleted successfully.');
    }



    
    public function index_Assigned(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect();
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');
            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');
            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');
            
            // Build query with date filtering
            $query = Order::where('seller_id', $seller->id)
                ->whereNotNull('awb_number')
                ->where(function ($query) {
                    $query->whereNull('order_status')
                        ->orWhere('order_status', '');
                })
                ->where(function ($query) {
                    $query->whereNull('shipping_status')
                        ->orWhereIn('shipping_status', ['Assigned', 'Pending Pickup', 'Manifested', 'Not Picked','courier Assigned','assigned']);
                });

            // Search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_order_id', 'like', "%{$searchTerm}%")
                      ->orWhere('awb_number', 'like', "%{$searchTerm}%")
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.name") LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.email") LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.phone") LIKE ?', ["%{$searchTerm}%"]);
                });
            }

            // Apply date filters if provided
            if ($request->has('from_date') && $request->from_date) {
                $query->whereDate('shipping_date', '>=', $request->from_date);
            }
            
            if ($request->has('to_date') && $request->to_date) {
                $query->whereDate('shipping_date', '<=', $request->to_date);
            }

            // Legacy date filters for backward compatibility
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('shipping_date', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('shipping_date', '<=', $request->date_to);
            }

            // Single date filter
            if ($request->has('filter_date') && $request->filter_date) {
                $query->whereDate('shipping_date', $request->filter_date);
            }

            $orders = $query->latest()
                ->orderby('order_number','desc')
                ->paginate(50)
                ->appends($request->query());
        }

           $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];

        return view('sellerdashboard.order_assigned', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders','orderCounts', 'request'));
    }


    // public function index_Assigned()
    // {
    //     $seller = Auth::guard('seller')->user();
    //     $totalAmount = 0;
    //     $rateCard = null;
    //     $commonPdf = null;
    //     $orders = collect();
    //     if ($seller && $seller->status == 1) {
    //         $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
    //             ->where('status', 1)
    //             ->where('type', 'Credit')
    //             ->sum('amount');
    //         $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
    //             ->where('type', 'Debit')
    //             ->sum('amount');
    //         $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
    //         $rateCard = RateCard::where('seller_id', $seller->id)->first();
    //         $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');
    //         $orders = Order::where('seller_id', $seller->id)
    //             ->whereNotNull('awb_number')
    //             ->where(function ($query) {
    //                 $query->whereNull('order_status')
    //                     ->orWhere('order_status', '');
    //             })
    //             ->where(function ($query) {
    //                 $query->whereNull('shipping_status')
    //                     ->orWhereIn('shipping_status', ['Assigned', 'Pending Pickup', 'Manifested', 'Not Picked']);
    //             })
    //             ->latest()
    //             ->orderby('id','desc')
    //             ->paginate(50);
    //     }

    //        $orderCounts = [
    //     'new' => 10,
    //     'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
    //     'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
    //     'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
    //     'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
    //     'delivered' => Order::where('shipping_status', 'delivered')->count(),
    //     'ndr' => Order::where('shipping_status', 'ndr')->count(),
    //     'rto' => Order::where('shipping_status', 'rto')->count(),
    //     'all' => Order::count(),
    //     'other' => Order::whereNotIn('shipping_status', [
    //         'new', 'courier_assigned', 'cancelled', 'in_transit', 
    //         'out_for_delivery', 'delivered', 'ndr', 'rto'
    //     ])->count(),
    // ];

    //     return view('sellerdashboard.order_assigned', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders','orderCounts'));
    // }




    public function Cancelled_order(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection

        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id'=> $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');

            // Build query with search functionality
            $query = Order::where('seller_id', $seller->id)
                ->where(function ($query) {
                    $query->where('order_status', 'cancelled')
                        ->orWhere('shipping_status', 'Not Picked');
                });

            // Add search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('customer_order_id', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('awb_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('consignee', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.name") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.phone") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.email") LIKE ?', ['%' . $searchTerm . '%']);
                });
            }

            $orders = $query->latest()->paginate(50)->appends($request->query());
        }



        
           $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'))->with('pageType', 'cancelled');
    }



    public function all_order()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection

        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id'=> $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');

            // ✅ Paginate orders
            $orders = Order::where(['seller_id' => $seller->id])->latest()->paginate(10);
        }

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders'));
    }



    public function index_other()
    {
        $seller = Auth::guard('seller')->user();
        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');
            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');
            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');
            // ✅ Paginate orders
             $orders = Order::where('seller_id', $seller->id)
               ->whereNotIn('shipping_status', ['transit', 'out for delivery', 'delivered', 'rto'])
               ->latest()
               ->paginate(10);
        }
        return view('sellerdashboard.other', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders'));
    }



    public function InTransit(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id'=> $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');
            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');
            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');
            
            // Build query with search functionality
            $query = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'transit']);
            
            // Add search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('customer_order_id', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('awb_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('consignee', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.name") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.phone") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.email") LIKE ?', ['%' . $searchTerm . '%']);
                });
            }
            
            $orders = $query->latest()->paginate(50)->appends($request->query());
        }


        
           $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'))->with('pageType', 'intransit');
    }





    public function OutForDelivery(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection

        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id'=> $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');

            // Build query with search functionality
            $query = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'out for delivery']);
            
            // Add search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('customer_order_id', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('awb_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('consignee', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.name") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.phone") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.email") LIKE ?', ['%' . $searchTerm . '%']);
                });
            }
            
            $orders = $query->latest()->paginate(50)->appends($request->query());
        }

   $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'))->with('pageType', 'outfordelivery');
    }




    public function Delivered(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection

        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id'=> $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');

            // Build query with search functionality  
            $query = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'delivered']);
            
            // Add search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('customer_order_id', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('awb_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('consignee', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.name") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.phone") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.email") LIKE ?', ['%' . $searchTerm . '%']);
                });
            }
            
            $orders = $query->latest()->paginate(50)->appends($request->query());
        }


           $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'))->with('pageType', 'delivered');
    }





//     public function NDR(Request $request)
// {
//     $seller = Auth::guard('seller')->user();

//     $totalAmount = 0;
//     $rateCard = null;
//     $commonPdf = null;
//     $orders = collect(); // default empty collection

//     // Step 1: Get all NDR AWB numbers from active logistic providers
//     $ndrAwbList = LogisticProvider::active()
//         ->get()
//         ->flatMap(function ($provider) {
//             $key = "courier.{$provider->code}";

//             if (!app()->bound($key)) {
//                 return [];
//             }

//             $params = null; // replace with actual if needed
//             $data = app($key)->fetchNdrData($params);

//             return is_array($data) ? $data : [];
//         });

//     $awbNumbers = collect($ndrAwbList)
//         ->filter() // remove nulls
//         ->unique()
//         ->values();

//     // Step 2: If seller is authenticated and active
//     if ($seller && $seller->status == 1) {
//         $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//             ->where('status', 1)
//             ->where('type', 'Credit')
//             ->sum('amount');

//         $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//             ->where('type', 'Debit')
//             ->sum('amount');

//         $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//         $rateCard = RateCard::where('seller_id', $seller->id)->first();
//         $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');

//         // Step 3: Filter only matched RTO orders
//         $orders = Order::where('seller_id', $seller->id)
//             // ->where('shipping_status', 'rto')
//             ->whereIn('awb_number', $awbNumbers)
//             ->latest()
//             ->paginate(10);
//     }

//     return view('sellerdashboard.order_cancel', compact(
//         'seller',
//         'totalAmount',
//         'rateCard',
//         'commonPdf',
//         'orders'
//     ));
// }


    
    public function NDR(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection

        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id'=> $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');

            // Build query with search functionality
            $query = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'NDR']);
            
            // Add search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('customer_order_id', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('awb_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('consignee', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.name") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.phone") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.email") LIKE ?', ['%' . $searchTerm . '%']);
                });
            }
            
            $orders = $query->latest()->paginate(50)->appends($request->query());
        }


           $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'))->with('pageType', 'ndr');
    }


    
    public function RTO(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        $rateCard = null;
        $commonPdf = null;
        $orders = collect(); // default empty collection

        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id'=> $seller->id,'status'=>'1'])->sum('amount');
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

            $rateCard = RateCard::where('seller_id', $seller->id)->first();
            $commonPdf = AppSetting::where('key', 'common_rate_pdf_3')->value('value');
            
        // Build query with search functionality
        $query = Order::where('seller_id', $seller->id)
            ->whereIn('shipping_status', ['rto', 'rto delivered','rto-it','rto-dispatched','rto-pending','rto delivered','rto_ofd','rts']);
            
            // Add search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('customer_order_id', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('awb_number', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('consignee', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.name") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.phone") LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('JSON_EXTRACT(consignee, "$.email") LIKE ?', ['%' . $searchTerm . '%']);
                });
            }
            
        $orders = $query->latest()->paginate(50)->appends($request->query());

            // ✅ Paginate orders
            // $orders = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'rto','rto delivered'])->latest()->paginate(10);
        }



           $orderCounts = [
        'new' => 10,
        'assigned' => Order::where('shipping_status', 'courier_assigned')->count(),
        'cancelled' => Order::where('shipping_status', 'cancelled')->count(),
        'in_transit' => Order::where('shipping_status', 'in_transit')->count(),
        'out_for_delivery' => Order::where('shipping_status', 'out_for_delivery')->count(),
        'delivered' => Order::where('shipping_status', 'delivered')->count(),
        'ndr' => Order::where('shipping_status', 'ndr')->count(),
        'rto' => Order::where('shipping_status', 'rto')->count(),
        'all' => Order::count(),
        'other' => Order::whereNotIn('shipping_status', [
            'new', 'courier_assigned', 'cancelled', 'in_transit', 
            'out_for_delivery', 'delivered', 'ndr', 'rto'
        ])->count(),
    ];
        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'))->with('pageType', 'rto');
    }





    public function ticket()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.ticket.index', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }




    public function save(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'code' => ['nullable', 'string'],
        ]);

        $sellerId = auth('seller')->id();
        $validated['seller_id'] = $sellerId;
        $validated['type'] = 'Credit'; // Assuming all recharges are credits

        $recharge = Recharge::create($validated);
        return redirect()->route('seller.recharge.payu', $recharge->id);

        // return to_route('seller.dashboard')->withSuccess('Recharge Successfully..!!');
    }


    public function redirectToPayU($id)
    {
        $recharge = Recharge::findOrFail($id);

        $MERCHANT_KEY = "ybWKok";
        $SALT = "H7fjhDB0DJmk7UwPOSVYmrZv8e0QjlYf";
        $PAYU_BASE_URL = "https://secure.payu.in"; // Live

        $txnid = 'TXN_' . $recharge->id . '_' . time();
        $amount = $recharge->amount; // For testing
        $firstname = auth('seller')->user()->name ?? 'Test';
        $email = auth('seller')->user()->email ?? 'test@example.com';
        $phone = auth('seller')->user()->phone ?? '9999999999';
        $productinfo = "Wallet Recharge";

        // $successUrl = url('/pesa-payment-success-new',$id);
        // $failureUrl = url('/pesa-payment-failure-new',$id);

        $successUrl = route('pesa.payu.success.new', $id); // browser-friendly
        $failureUrl = route('pesa.payu.failure.new', $id);

        $hash_string = $MERCHANT_KEY . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|||||||||||' . $SALT;
        $hash = strtolower(hash('sha512', $hash_string));

        return view('payu_form', compact(
            'MERCHANT_KEY',
            'txnid',
            'amount',
            'firstname',
            'email',
            'phone',
            'productinfo',
            'successUrl',
            'failureUrl',
            'hash',
            'PAYU_BASE_URL'
        ));
    }




    public function payuSuccess_pesa($id)
    {
        // dd($id);
        $recharge = Recharge::find($id);

        if ($recharge) {

            $user = Sellerlist::find($recharge->seller_id); // assuming relation Recharge belongsTo User
            //dd($user);
            // If user exists, log them in
            if ($user) {
                Auth::guard('seller')->login($user);
            }
            $recharge->update(['status' => '1']);
        }
        return redirect()->route('seller.dashboard')->withSuccess('Payment Successful');
    }

    public function payuFailure_pesa(Request $request, $id)
    {
        // dd($request);
        $recharge = Recharge::find($id);
        if ($recharge) {
            $recharge->update(['status' => '0']);
        }
        return redirect()->route('seller.dashboard')->with('error', 'Payment Failed');
    }





    public function redirectToPayUold($id)
    {
        $recharge = Recharge::findOrFail($id);
        // dd($recharge);
        $MERCHANT_KEY = "ybWKok";
        $SALT = "H7fjhDB0DJmk7UwPOSVYmrZv8e0QjlYf";
        $PAYU_BASE_URL = "https://secure.payu.in"; // use secure.payu.in for live

        $txnid = 'TXN_' . $recharge->id . '_' . time();
        $amount = 1;
        $firstname = auth('seller')->user()->name;
        $email = auth('seller')->user()->email;
        $phone = auth('seller')->user()->phone ?? '9999999999';
        $productinfo = "Wallet Recharge";

        $successUrl = url('/payu-success');
        $failureUrl = url('/payu-failure');


        $key = "ybWKok";
        $salt = "H7fjhDB0DJmk7UwPOSVYmrZv8e0QjlYf";
        $txnid = "txn123456";
        $amount = "1";
        $productinfo = "Recharge";
        $firstname = "John";
        $email = "john@example.com";



        $hash_string = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|'
            . '|||||||||' . $salt;

        $hash = strtolower(hash('sha512', $hash_string));



        return view('payu_form', compact(
            'MERCHANT_KEY',
            'txnid',
            'amount',
            'firstname',
            'email',
            'phone',
            'productinfo',
            'successUrl',
            'failureUrl',
            'hash',
            'PAYU_BASE_URL',
        ));
    }

    public function profile()
    {
        $seller = Auth::guard('seller')->user();

        if (!$seller || $seller->status != 1) {
            return redirect()->route('seller.login')->with('error', 'Unauthorized access.');
        }

        // $totalAmount = Recharge::where(['seller_id' => $seller->id, 'status' => '1'])->sum('amount');


            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;




        $address = SellerAddress::where('seller_id', $seller->id)->first();

        $states = State::where('status', 1)->get();
        $cities = City::where('status', 1)->get();
        $bankDetails = SellerBankDetail::where('seller_id', $seller->id)->first();

        $kyc = SellerList::where('id', $seller->id)->first();
        // $kyc->
        // dd( ['seller' => $seller,
        //     'totalAmount' => $totalAmount,
        //     'address' => $address,
        //     'states' => $states,
        //     'cities' => $cities,
        //     'bankDetails' => $bankDetails,
        //     'kyc' => $kyc,]);
        return view('sellerdashboard.profile.profile', [
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'address' => $address,
            'states' => $states,
            'cities' => $cities,
            'bankDetails' => $bankDetails,
            'kyc' => $kyc,
        ]);
    }
    public function showprofile()
    {
        $seller = Auth::guard('seller')->user();

        if (!$seller || $seller->status != 1) {
            return redirect()->route('seller.login')->with('error', 'Unauthorized access.');
        }
        $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        $sellerDetails = SellerList::find($seller->id);

        $selleraddress = SellerAddress::where('seller_id', $seller->id)->first();
        $sellerbankdetails = SellerBankDetail::where('seller_id', $seller->id)->first();

        return view('sellerdashboard.profile.showprofile', [
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'sellerName' => $sellerDetails ? $sellerDetails->name : null,
            'userType' => $sellerDetails ? $sellerDetails->user_type : null,
            'selleraddress' => $selleraddress,
            'sellerbankdetails' => $sellerbankdetails
        ]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'ifsc_code' => 'required|string|max:255',
            'account_type' => 'required|string|in:savings,current',
        ]);

        $bankDetail = SellerBankDetail::findOrFail($request->bank_detail_id);
        $bankDetail->update([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder_name' => $request->account_holder_name,
            'ifsc_code' => $request->ifsc_code,
            'account_type' => $request->account_type,
        ]);

        return redirect()->route('seller.profile')->with('success', 'Bank details updated successfully');
    }



    public function updateProfile(Request $request)
{
    // dd($request);
    $seller = Auth::guard('seller')->user();

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email',
        'phone_number' => 'required',
        'user_type' => 'required|in:1,2',
        'profile' => 'nullable',
    ]);

    $data = [
        'name' => $request->name,
        'email' => $request->email,
        'phone_number' => $request->phone_number,
        'user_type' => $request->user_type,
    ];

   
    if ($request->hasFile('profile')) {
//  echo 'ascasc';die;
        $image = $request->file('profile');
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('uploads/seller_profiles'), $imageName);

        $data['profile'] = $imageName;

  
    }

    $seller->update($data);

    return back()->with('success', 'Profile updated successfully.');
}

/**
 * Search AWB numbers for auto-suggestions
 */
public function searchAwb(Request $request)
{
    try {
        $query = $request->input('q');
        $seller = Auth::guard('seller')->user();
        
        if (!$seller || strlen($query) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search query',
                'orders' => []
            ]);
        }

        $orders = Order::where('seller_id', $seller->id)
            ->where(function($q) use ($query) {
                $q->where('awb_number', 'like', '%' . $query . '%')
                  ->orWhere('receiver_name', 'like', '%' . $query . '%')
                  ->orWhere('receiver_phone', 'like', '%' . $query . '%');
            })
            ->select('id', 'awb_number', 'receiver_name', 'receiver_address', 'receiver_phone')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Search failed',
            'orders' => []
        ]);
    }
}

/**
 * Get full order details by AWB number
 */


public function getOrderDetails(Request $request, $awb)
{
    try {
        $seller = Auth::guard('seller')->user();
        
        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        $order = Order::where('seller_id', $seller->id)
            ->where('awb_number', $awb)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }
   $source = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
    $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;

        // Format order data for display
        $orderData = [
            'id' => $order->id,
            'awb_number' => $order->awb_number,
            'status' => $order->status ?? 'Pending',
            'shipping_status' => $order->shipping_status,
            'sender_name' => $seller->name,
            'sender_phone' => $seller->phone_number,
            'sender_address' => $order->sender_address,
            'sender_pincode' => $source['pincode'],
            'receiver_name' => $consignee['name'],
            'receiver_phone' => $consignee['phone'],
            'receiver_address' => $consignee['address'],
            'receiver_pincode' => $consignee['pincode'],
            'weight' => $order->package_weight,
            'length' => $order->package_length,
            'width' => $order->package_breadth,
            'height' => $order->package_height,
            'cod_amount' => $order->collectable_amount,
            'shipping_cost' => $order->seller_amount_walate,
            'payment_mode' => $order->payment_type,
            'created_at' => $order->created_at,
            'tracking_url' => $order->smartship_tracking_url ?? $order->tracking_url
        ];

        return response()->json([
            'success' => true,
            'order' => $orderData
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching order details: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch order details'
        ]);
    }
}




// public function getOrderDetails(Request $request, $awb)
// {
//     try {
//         $seller = Auth::guard('seller')->user();
        
//         if (!$seller) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Unauthorized'
//             ]);
//         }

//         $order = Order::where('seller_id', $seller->id)
//             ->where('awb_number', $awb)
//             ->first();

//         if (!$order) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Order not found'
//             ]);
//         }

//         // Format order data for display
//         $orderData = [
//             'id' => $order->id,
//             'awb_number' => $order->awb_number,
//             'status' => $order->status ?? 'Pending',
//             'shipping_status' => $order->shipping_status,
//             'sender_name' => $order->sender_name,
//             'sender_phone' => $order->sender_phone,
//             'sender_address' => $order->sender_address,
//             'sender_pincode' => $order->sender_pincode,
//             'receiver_name' => $order->receiver_name,
//             'receiver_phone' => $order->receiver_phone,
//             'receiver_address' => $order->receiver_address,
//             'receiver_pincode' => $order->receiver_pincode,
//             'weight' => $order->weight,
//             'length' => $order->length,
//             'width' => $order->width,
//             'height' => $order->height,
//             'cod_amount' => $order->cod_amount,
//             'shipping_cost' => $order->shipping_cost,
//             'payment_mode' => $order->payment_mode,
//             'created_at' => $order->created_at,
//             'tracking_url' => $order->smartship_tracking_url ?? $order->tracking_url
//         ];

//         return response()->json([
//             'success' => true,
//             'order' => $orderData
//         ]);
//     } catch (\Exception $e) {
//         Log::error('Error fetching order details: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,
//             'message' => 'Failed to fetch order details'
//         ]);
//     }
// }




    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed|min:6',
        ]);

        $seller = Auth::guard('seller')->user();

        if (!Hash::check($request->current_password, $seller->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $seller->password = Hash::make($request->new_password);
        $seller->save();

        if ($request->has('logout_other_devices')) {
            Auth::guard('seller')->logoutOtherDevices($request->new_password);
        }

        return back()->with('success', 'Password updated successfully.');
    }
    public function updateImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = Auth::guard('seller')->user();
        $imagePath = $request->file('image')->store('uploads/sellers', 'public');

        // Optional: delete old image
        if ($user->profile && Storage::disk('public')->exists($user->profile)) {
            Storage::disk('public')->delete($user->profile);
        }

        $user->profile = $imagePath;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile image updated successfully.',
            'image' => asset('storage/' . $imagePath),
        ]);
    }



    public function trackOrder(Request $request)
    {


      $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        //dd($seller);
        $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
            ->latest()
            ->take(5)
            ->get();


        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }



        return view('sellerdashboard.trackorder', compact('seller', 'totalAmount', 'transactions'));
    }



}
