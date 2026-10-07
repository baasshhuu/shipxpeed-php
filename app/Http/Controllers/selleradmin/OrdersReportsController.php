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

use Maatwebsite\Excel\Facades\Excel; // for export
use App\Exports\ShipmentReportExport;

class OrdersReportsController extends Controller
{



public function shipment_report(Request $request)
{
    $seller = Auth::guard('seller')->user();


    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;


    $query = Order::where('seller_id', $seller->id)
                ->with('seller')
                ->whereNotNull('awb_number');
// dd($query->count());
    // Filter by AWB Number
    if ($request->filled('awb')) {
        $query->where('awb_number', 'LIKE', '%' . $request->awb . '%');
    }

    // Filter by start date
    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }

    // Filter by end date
    if ($request->filled('end_date')) {
        $query->whereDate('created_at', '<=', $request->end_date);
    }

    $data = $query->paginate(10);

    return view('seller_shipment_report', compact('data','seller','totalAmount'));
}

// Download Excel
public function shipment_report_download(Request $request)
{
    return Excel::download(new ShipmentReportExport($request), 'shipment_report.xlsx');
}




}
