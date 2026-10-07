<?php

namespace App\Http\Controllers\selleradmin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\SellerList;
use App\Models\WeightDisputes;
 use App\Imports\WeightDisputeImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Models\Recharge;
use Auth;
use Maatwebsite\Excel\Facades\Excel as ExcelExport;
use App\Exports\WeightDiscrepancyExport;


class WeightDispatchingController extends Controller
{





public function index(Request $request)
{

    $sellerId = Auth::guard('seller')->id();
    $seller = Auth::guard('seller')->user();

    // Optimize recharge calculations with single query
    $rechargeData = Recharge::where('seller_id', $seller->id)
        ->selectRaw('
            SUM(CASE WHEN status = 1 AND type = "Credit" THEN amount ELSE 0 END) as credit_amount,
            SUM(CASE WHEN type = "Debit" THEN amount ELSE 0 END) as debit_amount
        ')
        ->first();

    $totalAmount = ($rechargeData->credit_amount ?? 0) - ($rechargeData->debit_amount ?? 0);

    // Get all seller's AWB numbers first to avoid N+1 queries
    $sellerAwbs = Order::where('seller_id', $sellerId)
        ->pluck('awb_number')
        ->toArray();

    if (empty($sellerAwbs)) {
        return view('sellerdashboard.WeightDispatching.index', [
            'data' => [],
            'totalDiscrepancies' => 0,
            'pendingCount' => 0,
            'acceptedCount' => 0,
            'rejectedCount' => 0,
            'seller' => $seller,
            'totalAmount' => $totalAmount,
        ]);
    }

    // Build optimized query for disputes
    $query = WeightDisputes::whereIn('awb', $sellerAwbs);

    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('created_at', '<=', $request->end_date);
    }

    if ($request->filled('awb')) {
        $query->where('awb', 'like', '%' . $request->awb . '%');
    }

    $disputes = $query->paginate(20);

    // Get seller info once
    $sellerInfo = SellerList::find($sellerId);
    $sellerName = $sellerInfo ? $sellerInfo->name : '';

    // Map disputes data efficiently
    $mappedData = $disputes->map(function ($dispute) use ($sellerId, $sellerName) {
        return [
            'id' => $dispute->id,
            'awb' => $dispute->awb,
            'courier' => $dispute->courier,
            'mentionedweight' => $dispute->Mentionedweight,
            'chargedweight' => $dispute->chargedweight,
            'weightmissmatched' => $dispute->weightmissmatched,
            'weightdisputecharges' => $dispute->weightdisputecharges,
            'seller_id' => $sellerId,
            'seller_name' => $sellerName,
        ];
    })->toArray();

    // Calculate summary stats efficiently
    $totalDiscrepancies = $disputes->count();
    $pendingCount = $disputes->where('courier_status', 'Pending')->count();
    $acceptedCount = $disputes->where('courier_status', 'Accepted')->count();
    $rejectedCount = $disputes->where('courier_status', 'Rejected')->count();

    return view('sellerdashboard.WeightDispatching.index', [
        'data' => $mappedData,
        'disputes' => $disputes,
        'totalDiscrepancies' => $totalDiscrepancies,
        'pendingCount' => $pendingCount,
        'acceptedCount' => $acceptedCount,
        'rejectedCount' => $rejectedCount,
        'seller' => $seller,
        'totalAmount' => $totalAmount,
    ]);
}


// public function downloadExcel(Request $request)
// {
//     $sellerId = Auth::guard('seller')->id();
//     $seller = Auth::guard('seller')->user();

//     // Get all seller's AWB numbers first
//     $sellerAwbs = Order::where('seller_id', $sellerId)
//         ->pluck('awb_number')
//         ->toArray();

//     if (empty($sellerAwbs)) {
//         return back()->with('error', 'No data found to export.');
//     }

//     // Build query for disputes with same filters as index method
//     $query = WeightDisputes::whereIn('awb', $sellerAwbs);

//     if ($request->filled('start_date')) {
//         $query->whereDate('created_at', '>=', $request->start_date);
//     }

//     if ($request->filled('end_date')) {
//         $query->whereDate('created_at', '<=', $request->end_date);
//     }

//     if ($request->filled('awb')) {
//         $query->where('awb', 'like', '%' . $request->awb . '%');
//     }

//     $disputes = $query->get();

//     // Get seller info
//     $sellerInfo = SellerList::find($sellerId);
//     $sellerName = $sellerInfo ? $sellerInfo->name : '';

//     // Map data for export
//     $exportData = $disputes->map(function ($dispute) use ($sellerId, $sellerName) {
//         return [
//             'AWB' => $dispute->awb,
//             'Courier' => $dispute->courier,
//             'Mentioned Weight' => $dispute->Mentionedweight,
//             'Charged Weight' => $dispute->chargedweight,
//             'Weight Mismatch' => $dispute->weightmissmatched,
//             'Dispute Charges' => $dispute->weightdisputecharges,
//             'Seller Name' => $sellerName,
//             'Date' => $dispute->created_at->format('Y-m-d'),
//             'order Status' => $dispute->order_status,
//             'shipping Status' => $dispute->shipping_status

//         ];
//     });

//     // Generate filename with current date and filters
//     $filename = 'weight_discrepancy_report_' . date('Y_m_d_H_i_s') . '.xlsx';

//     // Create Excel file and download
//     return ExcelExport::download(new WeightDiscrepancyExport($exportData), $filename);
// }




public function downloadExcel(Request $request)
{
    $sellerId = Auth::guard('seller')->id();

    // Seller info
    $sellerInfo = SellerList::find($sellerId);
    $sellerName = $sellerInfo ? $sellerInfo->name : '';

    // Build query: JOIN weight_disputes with orders using AWB
    $query = WeightDisputes::join('orders', 'orders.awb_number', '=', 'weight_disputes.awb')
        ->where('orders.seller_id', $sellerId)
        ->select(
            'weight_disputes.*',
            'orders.order_status as order_status',
            'orders.shipping_status as shipping_status'
        );

    // Filters
    if ($request->filled('start_date')) {
        $query->whereDate('weight_disputes.created_at', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('weight_disputes.created_at', '<=', $request->end_date);
    }

    if ($request->filled('awb')) {
        $query->where('weight_disputes.awb', 'like', '%' . $request->awb . '%');
    }

    $disputes = $query->get();

    if ($disputes->isEmpty()) {
        return back()->with('error', 'No data found to export.');
    }

    // Map data for Excel
    $exportData = $disputes->map(function ($dispute) use ($sellerName) {
        return [
            'AWB'                => $dispute->awb,
            'Courier'            => $dispute->courier,
            'Mentioned Weight'   => $dispute->Mentionedweight,
            'Charged Weight'     => $dispute->chargedweight,
            'Weight Mismatch'    => $dispute->weightmissmatched,
            'Dispute Charges'    => $dispute->weightdisputecharges,
            'Seller Name'        => $sellerName,
            'Date'               => \Carbon\Carbon::parse($dispute->created_at)->format('Y-m-d'),
            'Order Status'       => $dispute->order_status,
            'Shipping Status'    => $dispute->shipping_status,
        ];
    });

    // File name
    $filename = 'weight_discrepancy_report_' . date('Y_m_d_H_i_s') . '.xlsx';

    // Download Excel
    return ExcelExport::download(
        new WeightDiscrepancyExport($exportData),
        $filename
    );
}





        public function create()
    {
        return view('WeightDispatching.add');
      
    }





public function importWeightDisputes(Request $request)
{
    // dd($request);
    $request->validate([
        'excel_file' => 'required|mimes:xlsx,xls'
    ]);

    Excel::import(new WeightDisputeImport, $request->file('excel_file'));

    return back()->with('success', 'Weight Dispute Data Imported Successfully!');
}





public function submit(Request $request)
{
    $sellers = $request->input('sellers');
    // dd($sellers);

    foreach ($sellers as $sellerData) {
        // dd($sellerData);
        if (!empty($sellerData['amount'])) {
            // dd($sellerData);
            // echo 'dwdada';die;
            // 1. Recharge Entry
            Recharge::create([
                'seller_id'                  => $sellerData['seller_id'],
                'type'                       => 'Debit',
                'amount'                     => $sellerData['amount'],
                'status'                     => 1,
                'description'                => 'Weight Dispute Adjustment',
                'weight_id'                  => $sellerData['weight_id'],
                'weight_awb'                 => $sellerData['weight_awb'],
                'weight_courier'             => $sellerData['weight_courier'],
                'weight_mentionedweight'     => $sellerData['weight_mentionedweight'],
                'weight_chargedweight'       => $sellerData['weight_chargedweight'],
                'weight_weightmissmatched'   => $sellerData['weight_weightmissmatched'],
                'weight_seller_name'         => $sellerData['weight_seller_name'],
            ]);

            // 2. Update WeightDisputes Status
            WeightDisputes::where('id', $sellerData['weight_id'])->update([
                'status' => 1,
            ]);
        }
    }

    return back()->with('success', 'All seller amounts have been debited and disputes updated.');
}















// public function index(Request $request)
// {

//     $sellerId = Auth::guard('seller')->id();
//         $seller = Auth::guard('seller')->user();

        
//     $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     // Start with all disputes (we’ll filter manually by matching orders)
//     $query = WeightDisputes::query();

//     if ($request->filled('start_date')) {
//         $query->whereDate('created_at', '>=', $request->start_date);
//     }

//     if ($request->filled('end_date')) {
//         $query->whereDate('created_at', '<=', $request->end_date);
//     }

//     if ($request->filled('awb')) {
//         $query->where('awb', 'like', '%' . $request->awb . '%');
//     }

//     $disputes = $query->get();
//     $mappedData = [];

//     foreach ($disputes as $dispute) {
//         // Match AWB in order table and check if it belongs to the logged-in seller
//         $order = Order::where('awb_number', $dispute->awb)
//                       ->where('seller_id', $sellerId)
//                       ->first();

//         if ($order) {
//             $seller = SellerList::find($order->seller_id);
//             if ($seller) {
//                 $mappedData[] = [
//                     'id' => $dispute->id,
//                     'awb' => $dispute->awb,
//                     'courier' => $dispute->courier,
//                     'mentionedweight' => $dispute->Mentionedweight,
//                     'chargedweight' => $dispute->chargedweight,
//                     'weightmissmatched' => $dispute->weightmissmatched,
//                     'weightdisputecharges' => $dispute->weightdisputecharges,
//                     'seller_id' => $seller->id,
//                     'seller_name' => $seller->name,
//                 ];
//             }
//         }
//     }

//     // Summary stats (only from disputes where the order matches the current seller)
//     $allDisputes = WeightDisputes::all()->filter(function ($dispute) use ($sellerId) {
//         $order = Order::where('awb_number', $dispute->awb)->where('seller_id', $sellerId)->first();
//         return $order !== null;
//     });

//     return view('sellerdashboard.WeightDispatching.index', [
//         'data' => $mappedData,
//         'totalDiscrepancies' => $allDisputes->count(),
//         'pendingCount' => $allDisputes->where('courier_status', 'Pending')->count(),
//         'acceptedCount' => $allDisputes->where('courier_status', 'Accepted')->count(),
//         'rejectedCount' => $allDisputes->where('courier_status', 'Rejected')->count(),
//         'seller' => $seller,
//                 'totalAmount' => $totalAmount,

//     ]);
// }



}
