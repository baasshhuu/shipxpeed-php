<?php

namespace App\Http\Controllers\admin;

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
use App\Exports\WeightDispatchingExport;
use App\Exports\CodRemittanceExport;
use Illuminate\Support\Facades\Validator;


class CODRemittanceController extends Controller
{


// public function index(Request $request)
// {
//     $query = Order::where('payment_type', 'cod')
//         ->where('shipping_status', 'delivered')
//         ->where('payment_status', '!=', 'Paid');
       
//     // 🔍 Filter by seller (assuming seller_id column exists)
//     if ($request->filled('seller')) {
//         $query->where('seller_id', $request->seller);
//     }

//     // 🔍 Filter by date range
//     if ($request->filled('start_date')) {
//         $query->whereDate('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
//     }

//     if ($request->filled('end_date')) {
//         $query->whereDate('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
//     }

//     $codOrders = $query->orderBy('created_at', 'desc')->paginate(10);

//     $sellers = \App\Models\SellerList::pluck('name', 'id');

//     return view('codremittance.index', compact('codOrders', 'sellers'));
// }


public function index(Request $request)
{
    $query = Order::where('payment_type', 'cod')
        ->where('shipping_status', 'delivered')
        ->where('payment_status', '!=', 'Paid')
        ->whereHas('seller', function ($q) {
            $q->where('negative_balance', '0');
        }); // Filter sellers with negative_balance = 0

    // Filter by seller
    if ($request->filled('seller')) {
        $query->where('seller_id', $request->seller);
    }

    // Filter by date range
    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
    }

    if ($request->filled('end_date')) {
        $query->whereDate('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
    }

    $codOrders = $query->orderBy('created_at', 'desc')->paginate(10);

    // Only fetch sellers where negative_balance = 0
    $sellers = \App\Models\SellerList::where('negative_balance', '0')
        ->pluck('name', 'id');

    return view('codremittance.index', compact('codOrders', 'sellers'));
}



      public function create()
    {
        return view('codremittance.add');
      
    }

   
// public function export(Request $request)
// {
//     $orders = Order::where('payment_type', 'cod')
//         ->where('shipping_status', 'delivered')
//         ->where('payment_status', '!=', 'Paid') // ✅ only unpaid COD orders
//         ->orderBy('created_at', 'desc')
//         ->get();

//     return Excel::download(new CodRemittanceExport($orders), 'cod_remittance_page.xlsx');
// }



public function export(Request $request)
{
    $orders = Order::where('payment_type', 'cod')
        ->where('shipping_status', 'delivered')
        ->where('payment_status', '!=', 'Paid')
        ->whereHas('seller', function ($query) {
            $query->where('negative_balance', '0');
        }) 
        ->orderBy('created_at', 'desc')
        ->get();

    return Excel::download(new CodRemittanceExport($orders), 'cod_remittance_page.xlsx');
}


public function upload(Request $request)
{
    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,xls'
    ]);

    $data = Excel::toArray([], $request->file('excel_file'));

    $awbNumbers = collect($data[0])
        ->skip(1) // skip header row
        ->pluck(0)
        ->map(fn($val) => trim((string)$val))
        ->filter()
        ->unique()
        ->toArray();

    $updated = $this->save($awbNumbers);

    return redirect()->back()->with('success', "$updated orders updated to Paid.");
}


public function save(array $awbNumbers): int
{
    return Order::whereIn('awb_number', $awbNumbers)
        ->update([
            'payment_status' => 'Paid',
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
}



}
