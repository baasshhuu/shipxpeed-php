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
use App\Models\PriceSetting;
use App\Models\SellerList;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\SellerAddress;
use App\Models\State;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoices;
use ZipArchive;

class InvoiceController extends Controller
{





    public function add()
    {

        $months = collect();
        for ($i = 1; $i <= 3; $i++) {
            $months->push(Carbon::now()->subMonths($i)->format('F Y')); // e.g., May 2025
        }

        return view('invoice.create', compact(

            'months',

        ));
    }

public function sellermonthly($month)
{
    try {
        $startDate = Carbon::parse('01 ' . $month)->startOfMonth();
        $endDate = Carbon::parse('01 ' . $month)->endOfMonth();
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Invalid month format.');
    }

    // Get paginated sellers
    $sellers = SellerList::with(['recharges' => function ($query) use ($startDate, $endDate) {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }])
        ->paginate(10)
        ->through(function ($seller) use ($startDate, $endDate) {

            // 🧾 1️⃣ Monthly Debit (Weight Dispute + Order Created)
            $monthlySellerAmountDebit = Recharge::where('seller_id', $seller->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', 'Debit')
                ->whereIn('description', ['Weight Dispute Adjustment', 'Order created','Bulk order created','RTO Debit'])
                ->sum('amount');

                    $orderdata = Order::where('seller_id', $seller->id)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->where('order_status', '!=', 'cancelled')
        ->whereIn('shipping_status', ['assigned', 'courier Assigned'])
        ->sum('seller_amount_walate');
    
    // Subtract order amount from debit
    $monthlySellerAmountDebit = $monthlySellerAmountDebit - $orderdata;

            // 🧾 2️⃣ Monthly Credit (Order Cancelled)
            $monthlySellerAmountCredit = Recharge::where('seller_id', $seller->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', 'Credit')
                ->whereIn('description', ['Order cancelled','Order cancelled refund','Shadowfax order cancelled'])
                ->sum('amount');

            // 🧾 3️⃣ Final Monthly Amount = Debit - Credit
            $monthlySellerAmount = $monthlySellerAmountDebit - $monthlySellerAmountCredit;
            $seller->monthlyAmount = $monthlySellerAmount;

            // 🧾 4️⃣ GST & Final Payable Calculation
            $seller->gst = $seller->monthlyAmount * 0.18;
            $seller->finalAmount = $seller->monthlyAmount - $seller->gst;

            // 🧾 5️⃣ Total Recharge / Used / Remaining
            $seller->rechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $seller->usedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $seller->totalAmount = $seller->rechargeAmount - $seller->usedAmount;

            return $seller;
        });

    return view('invoice.invoiceindex', compact('sellers', 'month'));
}




    // public function sellermonthly($month)
    // {
    //     try {
    //         $startDate = Carbon::parse('01 ' . $month)->startOfMonth();
    //         $endDate = Carbon::parse('01 ' . $month)->endOfMonth();
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Invalid month format.');
    //     }

    //     // Get paginated sellers
    //     $sellers = SellerList::with(['recharges' => function ($query) use ($startDate, $endDate) {
    //         $query->whereBetween('created_at', [$startDate, $endDate]);
    //     }])
    //         ->paginate(10) // 10 sellers per page
    //         ->through(function ($seller) use ($startDate, $endDate) {
    //             // Calculate amounts
    //             $seller->monthlyAmount = $seller->recharges
    //                 ->where('type', 'Debit')
    //                 ->sum('amount');

    //             $seller->gst = $seller->monthlyAmount * 0.18;
    //             $seller->finalAmount = $seller->monthlyAmount - $seller->gst;

    //             $seller->rechargeAmount = Recharge::where('seller_id', $seller->id)
    //                 ->where('status', 1)
    //                 ->where('type', 'Credit')
    //                 ->sum('amount');

    //             $seller->usedAmount = Recharge::where('seller_id', $seller->id)
    //                 ->where('type', 'Debit')
    //                 ->sum('amount');

    //             $seller->totalAmount = $seller->rechargeAmount - $seller->usedAmount;

    //             return $seller;
    //         });

    //     return view('invoice.invoiceindex', compact('sellers', 'month'));
    // }



public function downloadInvoice(Request $request)
{
    $month = $request->month;
    $sellerId = $request->seller_id ?? Auth::guard('seller')->id();

    try {
        $startDate = Carbon::parse('01 ' . $month)->startOfMonth();
        $endDate = Carbon::parse('01 ' . $month)->endOfMonth();
        $nextMonthFirstDate = $startDate->copy()->addMonth()->startOfMonth();
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Invalid month format.');
    }

    $seller = $sellerId
        ? SellerList::find($sellerId)
        : Auth::guard('seller')->user();

    if (!$seller) {
        return redirect()->back()->with('error', 'Seller not found.');
    }

    // ✅ Check if already exists for same month & seller
    $existingInvoice = Invoices::where('seller_id', $seller->id)
        ->where('month', $month)
        ->first();

    if ($existingInvoice) {
        // agar already invoice hai to wahi download kara do
        $invoiceNumber = $existingInvoice->Invoice_number;
    } else {
        // ✅ Get next invoice number
        $lastInvoice = Invoices::orderBy('id', 'desc')->first();
        $nextInvoiceNumber = $lastInvoice ? $lastInvoice->Invoice_number + 1 : 1;

        // ✅ Save entry
        $newInvoice = new Invoices();
        $newInvoice->seller_id = $seller->id;
        $newInvoice->Invoice_number = $nextInvoiceNumber;
        $newInvoice->month = $month;
        $newInvoice->save();

        $invoiceNumber = $newInvoice->Invoice_number;
    }

    // ✅ Your old calculations (recharge, GST etc.)
    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
    $paidAmount = $totalAmount < 0 ? abs($totalAmount) : 0;

    $sellerAddress = SellerAddress::where('seller_id', $seller->id)->first();

    if (!$sellerAddress || !$sellerAddress->state_id) {
        return redirect()->back()->with('error', 'Seller address information is incomplete.');
    }

    $sellerstatename = State::where('id', $sellerAddress->state_id)->value('name');

    $monthlySellerAmount = Recharge::where('seller_id', $seller->id)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->where('type', 'Debit')
        ->sum('amount');

    $gst = $monthlySellerAmount * 0.18;
    $cgst = $monthlySellerAmount * 0.09;
    $sgst = $monthlySellerAmount * 0.09;

    $invoiceDate = $nextMonthFirstDate->format('dmY');
    $invoiceDatelast1 = $nextMonthFirstDate->format('d:m:Y');

    // ✅ Generate PDF
    $pdf = Pdf::loadView('invoice.invoice', [
        'month' => $month,
        'seller' => $seller,
        'monthlySellerAmount' => $monthlySellerAmount,
        'gst' => $gst,
        'cgst' => $cgst,
        'sgst' => $sgst,
        'sellerAddress' => $sellerAddress,
        'sellerstatename' => $sellerstatename,
        'invoiceDate' => $invoiceDate,
        'invoiceNumber' => $invoiceNumber,
        'paidAmount' => $paidAmount,
        'invoiceDatelast1' => $invoiceDatelast1,

    ]);

    $filename = 'invoice_' . str_replace(' ', '_', strtolower($month)) . '_' . $invoiceNumber . '.pdf';

    return $pdf->download($filename);
}


/**
 * Bulk download all seller invoices for a specific month as ZIP file
 */
public function bulkDownloadInvoices($month)
{
    try {
        $startDate = Carbon::parse('01 ' . $month)->startOfMonth();
        $endDate = Carbon::parse('01 ' . $month)->endOfMonth();
        $nextMonthFirstDate = $startDate->copy()->addMonth()->startOfMonth();
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Invalid month format.');
    }

    // Get all sellers (including those without transactions)
    $sellers = SellerList::get();

    if ($sellers->isEmpty()) {
        return redirect()->back()->with('error', 'No sellers found.');
    }

    // Create temporary directory for PDFs
    $tempDir = storage_path('app/temp_invoices');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $zipFileName = 'bulk_invoices_' . str_replace(' ', '_', strtolower($month)) . '.zip';
    $zipFilePath = storage_path('app/' . $zipFileName);

    $zip = new ZipArchive;
    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
        return redirect()->back()->with('error', 'Could not create ZIP file.');
    }

    foreach ($sellers as $seller) {
        // Get or create invoice for this seller and month
        $existingInvoice = Invoices::where('seller_id', $seller->id)
            ->where('month', $month)
            ->first();

        if ($existingInvoice) {
            $invoiceNumber = $existingInvoice->Invoice_number;
        } else {
            // Create new invoice entry
            $lastInvoice = Invoices::orderBy('id', 'desc')->first();
            $nextInvoiceNumber = $lastInvoice ? $lastInvoice->Invoice_number + 1 : 1;

            $newInvoice = new Invoices();
            $newInvoice->seller_id = $seller->id;
            $newInvoice->Invoice_number = $nextInvoiceNumber;
            $newInvoice->month = $month;
            $newInvoice->save();

            $invoiceNumber = $newInvoice->Invoice_number;
        }

        // Calculate seller amounts
        $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
            ->where('type', 'Debit')
            ->sum('amount');

        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
        $paidAmount = $totalAmount < 0 ? abs($totalAmount) : 0;

        $sellerAddress = SellerAddress::where('seller_id', $seller->id)->first();

        // If seller address not found, use default seller ID 14's address
        if (!$sellerAddress || !$sellerAddress->state_id) {
            $sellerAddress = SellerAddress::where('seller_id', 14)->first();
            
            // If even default seller address not found, create a dummy address
            if (!$sellerAddress) {
                $sellerAddress = (object) [
                    'address_line' => 'Default Address',
                    'state_id' => 1 // Assuming state ID 1 exists
                ];
            }
        }

        $sellerstatename = State::where('id', $sellerAddress->state_id)->value('name') ?? 'Delhi';

        $monthlySellerAmount = Recharge::where('seller_id', $seller->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'Debit')
            ->sum('amount');

        // If no monthly transactions, set amount to 0 but still generate invoice
        if ($monthlySellerAmount <= 0) {
            $monthlySellerAmount = 0;
        }

        $gst = $monthlySellerAmount * 0.18;
        $cgst = $monthlySellerAmount * 0.09;
        $sgst = $monthlySellerAmount * 0.09;

        $invoiceDate = $nextMonthFirstDate->format('dmY');
        $invoiceDatelast1 = $nextMonthFirstDate->format('d:m:Y');

        // Generate PDF for this seller
        $pdf = Pdf::loadView('invoice.invoice', [
            'month' => $month,
            'seller' => $seller,
            'monthlySellerAmount' => $monthlySellerAmount,
            'gst' => $gst,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'sellerAddress' => $sellerAddress,
            'sellerstatename' => $sellerstatename,
            'invoiceDate' => $invoiceDate,
            'invoiceNumber' => $invoiceNumber,
            'paidAmount' => $paidAmount,
            'invoiceDatelast1' => $invoiceDatelast1,
        ]);

        // Save PDF temporarily
        $filename = 'invoice_' . str_replace(' ', '_', strtolower($month)) . '_' . $seller->id . '_' . $invoiceNumber . '.pdf';
        $tempFilePath = $tempDir . '/' . $filename;
        $pdf->save($tempFilePath);

        // Add to ZIP
        $zip->addFile($tempFilePath, $filename);
    }

    $zip->close();

    // Clean up temporary files
    $files = glob($tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($tempDir);

    // Download the ZIP file
    return response()->download($zipFilePath)->deleteFileAfterSend(true);
}

/**
 * Bulk download selected seller invoices for a specific month as ZIP file
 */




public function bulkDownloadSelectedInvoices(Request $request, $month)
{
    $sellerIds = $request->seller_ids ?? [];
    
    if (empty($sellerIds)) {
        return redirect()->back()->with('error', 'No sellers selected.');
    }

    try {
        $startDate = Carbon::parse('01 ' . $month)->startOfMonth();
        $endDate = Carbon::parse('01 ' . $month)->endOfMonth();
        $nextMonthFirstDate = $startDate->copy()->addMonth()->startOfMonth();
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Invalid month format.');
    }

    // Get selected sellers
    $sellers = SellerList::whereIn('id', $sellerIds)->get();

    if ($sellers->isEmpty()) {
        return redirect()->back()->with('error', 'Selected sellers not found.');
    }

    // Create temporary directory for PDFs
    $tempDir = storage_path('app/temp_invoices');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $zipFileName = 'selected_invoices_' . str_replace(' ', '_', strtolower($month)) . '_' . date('Y-m-d_H-i-s') . '.zip';
    $zipFilePath = storage_path('app/' . $zipFileName);

    $zip = new ZipArchive;
    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
        return redirect()->back()->with('error', 'Could not create ZIP file.');
    }

    foreach ($sellers as $seller) {
        // Get or create invoice for this seller and month
        $existingInvoice = Invoices::where('seller_id', $seller->id)
            ->where('month', $month)
            ->first();

        if ($existingInvoice) {
            $invoiceNumber = $existingInvoice->Invoice_number;
        } else {
            // Create new invoice entry
            $lastInvoice = Invoices::orderBy('id', 'desc')->first();
            $nextInvoiceNumber = $lastInvoice ? $lastInvoice->Invoice_number + 1 : 1;

            $newInvoice = new Invoices();
            $newInvoice->seller_id = $seller->id;
            $newInvoice->Invoice_number = $nextInvoiceNumber;
            $newInvoice->month = $month;
            $newInvoice->save();

            $invoiceNumber = $newInvoice->Invoice_number;
        }

        // Calculate seller amounts
        $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
            ->where('type', 'Debit')
            ->sum('amount');

        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
        $paidAmount = $totalAmount < 0 ? abs($totalAmount) : 0;

        $sellerAddress = SellerAddress::where('seller_id', $seller->id)->first();

        // अगर address नहीं मिला तो seller ID 14 का address use करें
        if (!$sellerAddress || !$sellerAddress->state_id) {
            $sellerAddress = SellerAddress::where('seller_id', 14)->first();
            
            // अगर वो भी नहीं मिला तो dummy address
            if (!$sellerAddress) {
                $sellerAddress = (object) [
                    'address_line' => 'Default Address',
                    'state_id' => 1
                ];
            }
        }

        $sellerstatename = State::where('id', $sellerAddress->state_id)->value('name') ?? 'Delhi';

        // $monthlySellerAmount = Recharge::where('seller_id', $seller->id)
        //     ->whereBetween('created_at', [$startDate, $endDate])
        //     ->where('type', 'Debit')
        //     ->sum('amount');
        //     dd($monthlySellerAmount);
    $monthlySellerAmountDebit = Recharge::where('seller_id', $seller->id)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->where('type', 'Debit')
    ->whereIn('description', ['Weight Dispute Adjustment', 'Order created','Bulk order created','RTO Debit'])
    ->sum('amount');
    
    // Calculate order amount to subtract from debit
    $orderdata = Order::where('seller_id', $seller->id)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->where('order_status', '!=', 'cancelled')
        ->whereIn('shipping_status', ['assigned', 'courier Assigned'])
        ->sum('seller_amount_walate');
    
    // Subtract order amount from debit
    $monthlySellerAmountDebit = $monthlySellerAmountDebit - $orderdata;
    
    $monthlySellerAmountCredit = Recharge::where('seller_id', $seller->id)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->where('type', 'Credit')
    ->whereIn('description', ['Order cancelled','Order cancelled refund','Shadowfax order cancelled'])
    ->sum('amount');

$monthlySellerAmount = $monthlySellerAmountDebit - $monthlySellerAmountCredit;
        // If no monthly transactions, set amount to 0 but still generate invoice
        if ($monthlySellerAmount <= 0) {
            $monthlySellerAmount = 0;
        }

        $gst = $monthlySellerAmount * 0.18;
        $cgst = $monthlySellerAmount * 0.09;
        $sgst = $monthlySellerAmount * 0.09;

        $invoiceDate = $nextMonthFirstDate->format('dmY');
        $invoiceDatelast1 = $nextMonthFirstDate->format('d:m:Y');

        // Generate PDF for this seller
        $pdf = Pdf::loadView('invoice.invoice', [
            'month' => $month,
            'seller' => $seller,
            'monthlySellerAmount' => $monthlySellerAmount,
            'gst' => $gst,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'sellerAddress' => $sellerAddress,
            'sellerstatename' => $sellerstatename,
            'invoiceDate' => $invoiceDate,
            'invoiceNumber' => $invoiceNumber,
            'paidAmount' => $paidAmount,
            'invoiceDatelast1' => $invoiceDatelast1,
        ]);

        // Save PDF temporarily
        $filename = 'invoice_' . str_replace(' ', '_', strtolower($month)) . '_' . $seller->id . '_' . $seller->name . '_' . $invoiceNumber . '.pdf';
        $tempFilePath = $tempDir . '/' . $filename;
        $pdf->save($tempFilePath);

        // Add to ZIP
        $zip->addFile($tempFilePath, $filename);
    }

    $zip->close();

    // Clean up temporary files
    $files = glob($tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($tempDir);

    // Download the ZIP file
    return response()->download($zipFilePath)->deleteFileAfterSend(true);
}





}
