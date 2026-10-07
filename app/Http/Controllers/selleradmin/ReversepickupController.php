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

class ReversepickupController extends Controller
{



    


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





    public function index()
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

        $deliveredCount = Order::where('seller_id', $sellerId)
            ->whereIn('shipping_status', ['Delivered', 'delivered', 'DELIVERED'])
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
            ->where('shipping_status', 'rto')
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
            'returningVisitorsData'
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



    public function reverseorderadd()
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



        return view('sellerdashboard.reverse.add', [
            'seller' => $seller,
            'totalAmount' => $totalAmount,
            'warehouses' => $warehouses,
            'allSellers' => $allSellers,
            'couriers' => 'vicky', // Pass to view
        ]);
    }

public function reverse_order()
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

        // Paginate orders
        $orders = Order::where(['seller_id' => $seller->id, 'reverse' => 'reverse'])
            ->whereNull('awb_number')
            ->latest()
            ->paginate(10);

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
    return view('sellerdashboard.reverse.order', compact(
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




    public function Cancelled_order()
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
            // $orders = Order::where(['seller_id' => $seller->id, 'order_status' => 'cancelled'])->latest()->paginate(10);
            $orders = Order::where('seller_id', $seller->id)
            ->where(function ($query) {
                $query->where('order_status', 'cancelled')
                    ->orWhere('shipping_status', 'Not Picked');
            })
            ->latest()
            ->paginate(10);

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

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'));
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



    public function InTransit()
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
            $orders = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'transit'])->latest()->paginate(10);
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

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'));
    }





    public function OutForDelivery()
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
            $orders = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'out for delivery'])->latest()->paginate(10);
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

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'));
    }




    public function Delivered()
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
            $orders = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'delivered'])->latest()->paginate(10);
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

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'));
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


    
    public function NDR()
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
            $orders = Order::where(['seller_id' => $seller->id, 'shipping_status' => 'NDR'])->latest()->paginate(10);
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

        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'));
    }


    
    public function RTO()
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
        $orders = Order::where('seller_id', $seller->id)
            ->whereIn('shipping_status', ['rto', 'rto delivered'])
            ->latest()
            ->paginate(10);

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
        return view('sellerdashboard.order_cancel', compact('seller', 'totalAmount', 'rateCard', 'commonPdf', 'orders', 'orderCounts'));
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
