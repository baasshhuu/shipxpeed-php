<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Order;
use App\Models\Recharge;
use App\Models\SellerAddress;
use App\Models\State;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

use GuzzleHttp\Exception\RequestException;

class InvoiceController extends Controller
{






public function add()
{
    $seller = Auth::guard('seller')->user();
    $sellerId = $seller->id;

    $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

    // Get available months based on 7th date rule
    $months = collect();
    $today = Carbon::now();
    
    // Check if today is 7th or later in the month
    if ($today->day >= 7) {
        // If today is 7th or later, allow invoice creation for previous month
        $months->push($today->copy()->subMonth()->format('F Y'));
    }
    
    // Also check for previous months where 7th has passed
    for ($i = 2; $i <= 4; $i++) {
        $checkMonth = $today->copy()->subMonths($i);
        // For previous months, 7th has definitely passed, so add them
        $months->push($checkMonth->format('F Y'));
    }

    return view('sellerdashboard.invoice.create', compact(
        'totalAmount',
        'months',
        'seller'
    ));
}


// public function add()
// {
//     $seller = Auth::guard('seller')->user();
//     $sellerId = $seller->id;

//     $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     // Get last 3 months (excluding current)
//     $months = collect();
//     for ($i = 1; $i <= 3; $i++) {
//         $months->push(Carbon::now()->subMonths($i)->format('F Y')); // e.g., May 2025
//     }

//     return view('sellerdashboard.invoice.create', compact(
//         'totalAmount',
//         'months',
//         'seller'
//     ));
// }



public function sellermonthly($month)
{
    // dd($month);
    $seller = Auth::guard('seller')->user();
    $sellerId = $seller->id;

    
    try {
        $startDate = Carbon::parse('01 ' . $month)->startOfMonth(); // 2025-05-01
        $endDate = Carbon::parse('01 ' . $month)->endOfMonth();     // 2025-05-31
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Invalid month format.');
    }
    
    $monthlyOrders = Order::where('seller_id', $sellerId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
    $monthlySellerAmount = Recharge::where('seller_id', $sellerId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->where('type', 'Debit')
        ->sum('amount');

    // $monthlySellerAmount = $monthlyOrders->sum('seller_amount_walate');

    // Wallet total balance
    $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

    return view('sellerdashboard.invoice.invoiceindex', compact(
        'totalAmount',
        'monthlySellerAmount',
        'month',
        'seller'
    ));
}



public function downloadInvoice(Request $request)
{
    $month = $request->month;

    try {
        $startDate = Carbon::parse('01 ' . $month)->startOfMonth(); // e.g. 2025-08-01
        $endDate = Carbon::parse('01 ' . $month)->endOfMonth();     // e.g. 2025-08-31
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Invalid month format.');
    }

    $seller = Auth::guard('seller')->user();

    // Calculate total & paid amounts
    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');
    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');
    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
    $paidAmount = $totalAmount < 0 ? abs($totalAmount) : 0;

    // Get seller address
    $sellerAddress = SellerAddress::where('seller_id', $seller->id)->first();
    if (!$sellerAddress || !$sellerAddress->state_id) {
        return redirect()->back()->with('error', 'Please update your address before downloading invoice.');
    }

    // Get state name
    $sellerstatename = State::where('id', $sellerAddress->state_id)->value('name');

    // 🟢 Generate Next Month's 1st Date
    $nextMonthFirstDate = $startDate->copy()->addMonth()->startOfMonth(); // 1st of next month

    // 🟢 Format invoiceDate and invoiceNumber
    $invoiceDate = $nextMonthFirstDate->format('d-m-Y'); // 01-09-2025
    $invoiceNumber = $invoiceDate . '-0001'; // 01-09-2025-0001

    // Get monthly seller amount
    $monthlySellerAmount = Recharge::where('seller_id', $seller->id)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->where('type', 'Debit')
        ->sum('amount');

    $gst = $monthlySellerAmount * 0.18;
    $cgst = $monthlySellerAmount * 0.09;
    $sgst = $monthlySellerAmount * 0.09;

    // Generate PDF
    $pdf = Pdf::loadView('sellerdashboard.invoice.invoice', [
        'month' => $month,
        'seller' => $seller,
        'monthlySellerAmount' => $monthlySellerAmount,
        'gst' => $gst,
        'cgst' => $cgst,
        'sgst' => $sgst,
        'sellerAddress' => $sellerAddress,
        'sellerstatename' => $sellerstatename,
        'invoiceNumber' => $invoiceNumber,
        'invoiceDate' => $invoiceDate,
        'paidAmount' => $paidAmount,
    ]);

    return $pdf->download('invoice_' . str_replace(' ', '_', strtolower($month)) . '_' . $invoiceNumber . '.pdf');
}



// public function downloadInvoice(Request $request)
// {
//     $month = $request->month;
//     // dd($month);
// try {
//         $startDate = Carbon::parse('01 ' . $month)->startOfMonth(); // 2025-05-01
//         $endDate = Carbon::parse('01 ' . $month)->endOfMonth();     // 2025-05-31
//     } catch (\Exception $e) {
//         return redirect()->back()->with('error', 'Invalid month format.');
//     }


//     $seller = Auth::guard('seller')->user();


//         $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');
//         $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//             ->where('type', 'Debit')
//             ->sum('amount');
//        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
//       $paidAmount = $totalAmount < 0 ? abs($totalAmount) : 0;

//     // Step 1: Get seller address
//     $sellerAddress = SellerAddress::where('seller_id', $seller->id)->first();

//     // Step 2: Check if address or state is missing
//     if (!$sellerAddress || !$sellerAddress->state_id) {
//         return redirect()->back()->with('error', 'Please update your address before downloading invoice.');
//     }

//     // Step 3: Get state name
//     $sellerstatename = State::where('id', $sellerAddress->state_id)->value('name');

//     // Step 4: Generate invoice number
//     // $invoiceNumber = 'INV-' . $seller->id . '-' . now()->format('YmdHis');
//     $invoiceNumber = 'INV-' . $seller->id . '-' . $month;

//     // Step 5: Calculate amounts
//     $monthlySellerAmount = Recharge::where('seller_id', $seller->id)
//             ->whereBetween('created_at', [$startDate, $endDate])

//         ->where('type', 'Debit')
//         ->sum('amount');

//     $gst = $monthlySellerAmount * 0.18;
//     $cgst = $monthlySellerAmount * 0.09;
//     $sgst = $monthlySellerAmount * 0.09;

//     // Step 6: Generate PDF
//     $pdf = Pdf::loadView('sellerdashboard.invoice.invoice', [
//         'month' => $month,
//         'seller' => $seller,
//         'monthlySellerAmount' => $monthlySellerAmount,
//         'gst' => $gst,
//         'cgst' => $cgst,
//         'sgst' => $sgst,
//         'sellerAddress' => $sellerAddress,
//         'sellerstatename' => $sellerstatename,
//         'invoiceNumber' => $invoiceNumber,
//         'invoiceDate' => now()->format('d-m-Y'),
//                 'paidAmount' => $paidAmount,

//     ]);

//     return $pdf->download('invoice_' . str_replace(' ', '_', strtolower($month)) . '_' . $invoiceNumber . '.pdf');
// }



    public function index()
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
        return view('sellerdashboard.invoice.index', compact('transactions', 'seller', 'totalAmount'));
    }
    public function addii()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = $seller->id;

        $transactions = Recharge::where('seller_id', $sellerId)->latest()->get();

        $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
            ->where('type', 'Debit')
            ->sum('amount');

        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        $totalWithGST = Order::where('seller_id', $sellerId)->sum('seller_amount_walate');
        $baseAmount = $totalWithGST / 1.18;
        $gstAmount = $totalWithGST - $baseAmount;

        $sellerAddress = SellerAddress::with(['city', 'state'])
            ->where('seller_id', $sellerId)
            ->first();
            // dd($sellerAddress);

        $monthsData = [];

        for ($i = 0; $i < 3; $i++) {
            $date = now()->subMonths($i);

            $monthName = $date->format('F');
            $year = $date->format('Y');

            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $orders = Order::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->get();

            $monthTotalWithGST = $orders->sum('seller_amount_walate');
            $monthBaseAmount = $monthTotalWithGST / 1.18;
            $monthGstAmount = $monthTotalWithGST - $monthBaseAmount;

            $monthsData[] = [
                'month' => $monthName,
                'year' => $year,
                'orders' => $orders,
                'baseAmount' => $monthBaseAmount,
                'gstAmount' => $monthGstAmount,
                'totalWithGST' => $monthTotalWithGST,
            ];
        }

        return view('sellerdashboard.invoice.create', compact(
            'transactions',
            'seller',
            'sellerAddress',
            'totalAmount',
            'totalWithGST',
            'baseAmount',
            'gstAmount',
            'monthsData'
        ));
    }

    public function printableView()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = $seller->id;

        $monthsData = [];

        for ($i = 0; $i < 3; $i++) {
            $monthStart = Carbon::now()->startOfMonth()->subMonths($i);
            $monthEnd = Carbon::now()->startOfMonth()->subMonths($i)->endOfMonth();

            $monthlyTotal = Order::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('seller_amount_walate');

            if ($monthlyTotal > 0) {
                $base = $monthlyTotal / 1.18;
                $gst = $monthlyTotal - $base;

                $monthsData[] = [
                    'month' => $monthStart->format('F Y'),
                    'base' => $base,
                    'gst' => $gst,
                    'total' => $monthlyTotal
                ];
            }
        }

        $sellerAddress = SellerAddress::with(['city', 'state'])->where('seller_id', $sellerId)->first();

        return view('sellerdashboard.invoice.printable', compact(
            'seller',
            'sellerAddress',
            'monthsData'
        ));
    }
}
