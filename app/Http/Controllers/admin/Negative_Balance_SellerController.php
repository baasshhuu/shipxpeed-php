<?php

namespace App\Http\Controllers\admin;

use App\Models\Brand;
use App\Models\Cms;
use App\Helper\Helper;
use App\Models\Testimonial;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\LogisticProvider;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\SellerList;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NegativeBalanceSellerExport;
use Illuminate\Support\Facades\Validator;


class Negative_Balance_SellerController extends Controller
{




public function index(Request $request)
{
    // Get sellers with negative balance
    $negativeSellersQuery = SellerList::where('negative_balance', 1);

    // Filter by specific seller if provided
    if ($request->filled('seller')) {
        $negativeSellersQuery->where('id', $request->seller);
    }

    // Get sellers with their collectable amounts and wallet balances
    $negativeBalanceSellers = $negativeSellersQuery->with(['orders' => function($query) use ($request) {
        $query->where('payment_type', 'cod')
              ->where('shipping_status', 'delivered')
              ->where('payment_status', '!=', 'Paid');
        
        // Filter by date range if provided
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
    }])->get();

    // Calculate totals for each seller
    $sellerData = $negativeBalanceSellers->map(function ($seller) use ($request) {
        $collectableAmount = $seller->orders->sum('collectable_amount') ?? 0;
        
        // Calculate wallet balance using Recharge model
        $rechargeQuery = Recharge::where('seller_id', $seller->id);
        $debitQuery = Recharge::where('seller_id', $seller->id);
        
        // Apply date filters to recharge queries if provided
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $rechargeQuery->whereDate('created_at', '>=', $startDate);
            $debitQuery->whereDate('created_at', '>=', $startDate);
        }
        
        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $rechargeQuery->whereDate('created_at', '<=', $endDate);
            $debitQuery->whereDate('created_at', '<=', $endDate);
        }
        
        $sellerRechargeAmount = $rechargeQuery->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = $debitQuery->where('type', 'Debit')
            ->sum('amount');

        $walletBalance = $sellerRechargeAmount - $sellerUsedAmount;
        $negativeAmount = $walletBalance < 0 ? abs($walletBalance) : 0;

        return [
            'seller' => $seller,
            'collectable_amount' => $collectableAmount,
            'wallet_balance' => $walletBalance,
            'negative_amount' => $negativeAmount,
            'orders_count' => $seller->orders->count()
        ];
    });

    // Calculate overall totals
    $totalCollectable = $sellerData->sum('collectable_amount');
    $totalNegativeAmount = $sellerData->sum('negative_amount');

    // All sellers for dropdown filter
    $allSellers = SellerList::where('negative_balance', 1)->pluck('name', 'id');

    return view('negative_balance.index', compact(
        'sellerData', 
        'allSellers', 
        'totalCollectable', 
        'totalNegativeAmount'
    ));
}

/**
 * Export orders for selected seller
 */
public function exportOrders(Request $request)
{
    $sellerId = $request->get('seller_id');
    
    if (!$sellerId) {
        return redirect()->back()->with('error', 'Please select a seller to export orders.');
    }

    $query = Order::where('seller_id', $sellerId)
        ->where('payment_type', 'cod')
        ->where('shipping_status', 'delivered')
        ->where('payment_status', '!=', 'Paid');

    // Apply date filters if provided
    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
    }

    if ($request->filled('end_date')) {
        $query->whereDate('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
    }

    $orders = $query->orderBy('created_at', 'desc')->get();

    if ($orders->isEmpty()) {
        return redirect()->back()->with('error', 'No orders found for the selected seller and date range.');
    }

    $seller = SellerList::find($sellerId);
    $fileName = 'negative_balance_seller_orders_' . ($seller->name ?? 'seller') . '_' . date('Y-m-d') . '.xlsx';

    return Excel::download(new NegativeBalanceSellerExport($orders), $fileName);
}

/**
 * Upload Excel file and mark orders as paid
 */
public function uploadExcel(Request $request)
{
    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,xls'
    ]);

    try {
        $data = Excel::toArray([], $request->file('excel_file'));

        $awbNumbers = collect($data[0])
            ->skip(1) // skip header row
            ->pluck(0) // assuming AWB numbers are in the first column
            ->map(fn($val) => trim((string)$val))
            ->filter()
            ->unique()
            ->toArray();

        if (empty($awbNumbers)) {
            return redirect()->back()->with('error', 'No valid AWB numbers found in the uploaded file.');
        }

        $updated = $this->markOrdersAsPaid($awbNumbers);

        return redirect()->back()->with('success', "$updated orders have been marked as paid successfully.");

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error processing file: ' . $e->getMessage());
    }
}

/**
 * Mark orders as paid based on AWB numbers
 */
private function markOrdersAsPaid(array $awbNumbers): int
{
    return Order::whereIn('awb_number', $awbNumbers)
        ->where('payment_type', 'cod')
        ->where('shipping_status', 'delivered')
        ->where('payment_status', '!=', 'Paid')
        ->update([
            'payment_status' => 'Paid',
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
}

/**
 * Show add money form
 */
public function addMoney()
{
    $sellers = SellerList::where('negative_balance', 1)
        ->orderBy('name')
        ->get(['id', 'name']);

    return view('negative_balance.add_money', compact('sellers'));
}

/**
 * Process adding money to seller wallet
 */
public function storeMoney(Request $request)
{
    $validated = $request->validate([
        'seller_id' => 'required|exists:seller_lists,id',
        'amount' => 'required|numeric|min:1',
        'description' => 'nullable|string|max:255'
    ]);

    try {
        // Add money to seller wallet
        Recharge::create([
            'seller_id' => $validated['seller_id'],
            'amount' => $validated['amount'],
            'type' => 'Credit',
            'status' => 1,
            'code' => 'WALLET_CREDIT_' . time(),
            'description' => $validated['description'] ?? 'Wallet credit for negative balance adjustment'
        ]);

        $seller = SellerList::find($validated['seller_id']);
        
        return redirect()->route('negative-balance.index')
            ->with('success', 'Successfully added ₹' . number_format($validated['amount'], 2) . ' to ' . $seller->name . '\'s wallet.');

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Error adding money to wallet: ' . $e->getMessage())
            ->withInput();
    }
}

/**
 * Get seller orders via AJAX for export preview
 */
public function getSellerOrders(Request $request)
{
    $sellerId = $request->get('seller_id');
    
    if (!$sellerId) {
        return response()->json(['error' => 'Seller ID is required'], 400);
    }

    $query = Order::where('seller_id', $sellerId)
        ->where('payment_type', 'cod')
        ->where('shipping_status', 'delivered')
        ->where('payment_status', '!=', 'Paid');

    // Apply date filters if provided
    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', Carbon::parse($request->start_date));
    }

    if ($request->filled('end_date')) {
        $query->whereDate('created_at', '<=', Carbon::parse($request->end_date));
    }

    $orders = $query->select('awb_number', 'order_number', 'collectable_amount', 'delivered_date')
        ->orderBy('created_at', 'desc')
        ->limit(10) // Preview only first 10 orders
        ->get();

    $totalOrders = $query->count();
    $totalAmount = $query->sum('collectable_amount');

    return response()->json([
        'orders' => $orders,
        'total_orders' => $totalOrders,
        'total_amount' => $totalAmount
    ]);
}




}
