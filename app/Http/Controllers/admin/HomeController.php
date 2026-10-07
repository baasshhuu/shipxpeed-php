<?php

namespace App\Http\Controllers\admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;
use App\Models\SellerList;
use App\Models\Recharge;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;


class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

public function index(Request $request): View
{
    $sellerRechargeAmount = Recharge::where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    // Orders
    $todayOrder   = Order::whereDate('created_at', Carbon::today())->count();
    $allOrder     = Order::whereNotNull('awb_number')->count();
    $monthOrder   = Order::whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year)
                        ->count();
    $weekOrder    = Order::where('created_at', '>=', Carbon::now()->subDays(7))->count();
    $canceledOrder = Order::where('status', 'cancelled')->count();

    // Users
    $activeUser   = SellerList::where('status', '1')->count();
    $inactiveUser = SellerList::where('status', '0')->count();
    $kycPending   = SellerList::where('kyc_status', '0')->count();
    $approveKyc   = SellerList::where('kyc_status', '1')->count();

    // Tickets
    $openTicket    = Ticket::where('status', 'open')->count();
    $pendingTicket = Ticket::where('status', 'pending')->count();
    $closeTicket   = Ticket::where('status', 'closed')->count();
    $allTicket     = Ticket::count();

    // Registrations
    $todayRegister = SellerList::whereDate('created_at', Carbon::today())->count();
    $weekRegister  = SellerList::where('created_at', '>=', Carbon::now()->subDays(7))->count();
    $monthRegister = SellerList::whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year)
                        ->count();
    $allRegister   = SellerList::count();

    return view('home', compact(
        'todayOrder',
        'monthOrder',
        'weekOrder',
        'canceledOrder',
        'activeUser',
        'inactiveUser',
        'kycPending',
        'approveKyc',
        'openTicket',
        'pendingTicket',
        'closeTicket',
        'allTicket',
        'todayRegister',
        'weekRegister',
        'monthRegister',
        'allRegister',
        'allOrder',
        'sellerRechargeAmount'
    ));
}



public function shipment_report(Request $request)
{
    // echo 'xxsxs';die;
    // All sellers for dropdown
    $sellers = SellerList::select('id', 'name')->get();

    // Start building query
    $query = Order::with('seller')->whereNotNull('awb_number');

    // Filter by seller
    if ($request->filled('seller_id')) {
        $query->where('seller_id', $request->seller_id);
    }

    // Filter by start date
    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }

    // Filter by end date
    if ($request->filled('end_date')) {
        $query->whereDate('created_at', '<=', $request->end_date);
    }

    // Get filtered data
    $data = $query->paginate(10);

    return view('shipment_report', compact('data', 'sellers'));
}

/**
 * Display the Excel upload form for order status updates
 */
public function orderStatusUploadForm()
{
    return view('index');
}

/**
 * Handle Excel file upload and update order statuses
 */
public function uploadOrderStatus(Request $request)
{
    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
    ]);

    try {
        DB::beginTransaction();

        $file = $request->file('excel_file');
        $data = Excel::toArray([], $file)[0]; // Get first sheet
        
        $updatedCount = 0;
        $notFoundCount = 0;
        $errorCount = 0;
        $results = [];
        // dd($data);
        // Skip header row (assuming first row contains headers)
        $rows = array_slice($data, 1);

        foreach ($rows as $index => $row) {
            try {
                // Assuming columns: [AWB_Number, Status, Delivered_Date]
                $awbNumber = trim($row[0] ?? '');
                $status = strtolower(trim($row[1] ?? ''));
                $deliveredDate = trim($row[2] ?? '');

                if (empty($awbNumber)) {
                    continue; // Skip empty rows
                }

                // Find order by AWB number
                $order = Order::where('awb_number', $awbNumber)->first();

                if (!$order) {
                    $notFoundCount++;
                    $results[] = [
                        'row' => $index + 2, // +2 because we start from 0 and skip header
                        'awb_number' => $awbNumber,
                        'status' => 'not_found',
                        'message' => 'Order not found'
                    ];
                    continue;
                }

                // Validate and map status
                $mappedStatus = $this->mapExcelStatus($status);
                
                if ($mappedStatus === null) {
                    $errorCount++;
                    $results[] = [
                        'row' => $index + 2,
                        'awb_number' => $awbNumber,
                        'status' => 'invalid_status',
                        'message' => 'Invalid status: ' . $status
                    ];
                    continue;
                }

                // Update order status
                $order->shipping_status = $mappedStatus;

                // Handle delivered date
                if ($status === 'delivered') {
                    if (!empty($deliveredDate)) {
                        try {
                            $order->delivered_date = Carbon::createFromFormat('Y-m-d', $deliveredDate)->format('Y-m-d');
                        } catch (\Exception $e) {
                            // If date parsing fails, use today's date
                            $order->delivered_date = Carbon::today()->format('Y-m-d');
                        }
                    } else {
                        $order->delivered_date = Carbon::today()->format('Y-m-d');
                    }
                }

                $order->save();
                $updatedCount++;

                $results[] = [
                    'row' => $index + 2,
                    'awb_number' => $awbNumber,
                    'status' => 'updated',
                    'message' => 'Successfully updated'
                ];

            } catch (\Exception $e) {
                $errorCount++;
                $results[] = [
                    'row' => $index + 2,
                    'awb_number' => $awbNumber ?? '',
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                Log::error('Order status update error for row ' . ($index + 2) . ': ' . $e->getMessage());
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Upload completed! Updated: {$updatedCount}, Not found: {$notFoundCount}, Errors: {$errorCount}",
            'summary' => [
                'updated' => $updatedCount,
                'not_found' => $notFoundCount,
                'errors' => $errorCount,
                'total_processed' => count($rows)
            ],
            'details' => $results
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Order status upload failed: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to process upload: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Download sample Excel template
 */
public function downloadOrderStatusTemplate()
{
    $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment; filename="order_status_template.xlsx"',
    ];

        // Create sample data with proper headers
    $data = [
        ['AWB_Number', 'Status', 'Delivered_Date'],
        ['AWB123456789', 'DELIVERED', '2024-01-15'],
        ['AWB987654321', 'SHIPPED', ''],
        ['AWB456789123', 'OUT_FOR_DELIVERY', ''],
        ['AWB789123456', 'RETURNING_TO_ORIGIN', ''],
        ['AWB555666777', 'READY_TO_SHIP', ''],
        ['AWB888999000', 'CANCELLED', ''],
    ];

    // Simple CSV download approach
    $filename = 'order_status_template.csv';
    $handle = fopen('php://memory', 'r+');
    foreach ($data as $row) {
        fputcsv($handle, $row);
    }
    rewind($handle);
    $content = stream_get_contents($handle);
    fclose($handle);

    return response($content)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
}

/**
 * Get orders for AJAX search
 */
public function searchOrders(Request $request)
{
    $query = Order::whereNotNull('awb_number');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('awb_number', 'LIKE', "%{$search}%")
              ->orWhere('order_number', 'LIKE', "%{$search}%");
        });
    }

    $orders = $query->select('id', 'order_number', 'awb_number', 'shipping_status', 'delivered_date', 'created_at')
                   ->orderBy('created_at', 'desc')
                   ->limit(20)
                   ->get();

    return response()->json($orders);
}

/**
 * Map Excel status values to internal status values
 */
private function mapExcelStatus($excelStatus)
{
    // Convert to uppercase for case-insensitive matching
    $status = strtoupper(trim($excelStatus));
    
    // Define status mapping
    $statusMapping = [
        'READY_TO_SHIP' => 'Assigned',
        'READY_FOR_PICKUP' => 'Assigned',
        'CANCELLED' => 'cancelled',
        'SHIPPED' => 'transit',
        'RETURNING_TO_ORIGIN' => 'rto',
        'DELIVERED' => 'delivered',
        'OUT_FOR_DELIVERY' => 'out for delivery',
    ];
    
    // If status exists in mapping, return mapped value
    if (isset($statusMapping[$status])) {
        return $statusMapping[$status];
    }
    
    // Define valid internal statuses (for direct mapping)
    $validInternalStatuses = [
        'pending', 'assigned', 'transit', 'out for delivery', 
        'delivered', 'rto', 'cancelled'
    ];
    
    // Convert back to lowercase for internal status check
    $lowerStatus = strtolower($excelStatus);
    
    // If it's already a valid internal status, use it as-is
    if (in_array($lowerStatus, $validInternalStatuses)) {
        return $lowerStatus;
    }
    
    // For any other status, use it as-is (lowercase)
    return $lowerStatus;
}




}
