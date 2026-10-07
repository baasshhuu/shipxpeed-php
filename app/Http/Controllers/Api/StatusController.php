<?php
namespace App\Http\Controllers\Api;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\ShippingNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\LogisticProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\Schema;
class StatusController extends Controller
{



public function bulkCancelShipment(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls'
    ]);

    // $seller = Auth::guard('seller')->user();

    $rows = Excel::toArray([], $request->file('file'));
    $sheet = $rows[0]; // first sheet

    $success = [];
    $failed  = [];

    foreach ($sheet as $index => $row) {

        // Skip header row
        if ($index == 0) continue;

        // Column E = index 4 (0 based)
        $awb = trim($row[4] ?? '');

        if (!$awb) {
            $failed[] = [
                'row' => $index + 1,
                'reason' => 'AWB missing'
            ];
            continue;
        }

        $order = Order::where('awb_number', $awb)->first();

        if (!$order) {
            $failed[] = [
                'awb' => $awb,
                'reason' => 'Order not found'
            ];
            continue;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
            ])->post(
                'https://app.parcelx.in/api/v3/order/cancel_order',
                ['awb' => $awb]
            );

            $res = $response->json();

            if ($response->successful() && isset($res['status']) && $res['status'] === true) {

                // Update order
                $order->order_status = 'cancelled';
                $order->save();

                // Refund
                // Recharge::create([
                //     'seller_id'   => $seller->id,
                //     'type'        => 'Credit',
                //     'amount'      => $order->seller_amount_walate,
                //     'status'      => 1,
                //     'description' => 'Order cancelled (Bulk Excel)',
                // ]);

                $success[] = $awb;

            } else {
                $failed[] = [
                    'awb' => $awb,
                    'reason' => $res['message'] ?? 'ParcelX cancel failed'
                ];
            }

        } catch (\Exception $e) {
            $failed[] = [
                'awb' => $awb,
                'reason' => $e->getMessage()
            ];
        }
    }

    return response()->json([
        'status' => true,
        'total_records' => count($sheet) - 1,
        'success_count' => count($success),
        'failed_count'  => count($failed),
        'success_awbs'  => $success,
        'failed_awbs'   => $failed
    ]);
}











    public function cancelShipment()
{
    try {


        $orders = Order::where('shipping_status', 'Cancelled')
        ->where(function ($q) {
            $q->whereNull('order_status')
            ->orWhereRaw("LOWER(TRIM(order_status)) != ?", ['cancelled']);
        })
        ->where(function ($q) {
            $q->where('cancelled_amount', 0)
            ->orWhereNull('cancelled_amount');
        })
        ->get();

//  dd($orders);
        if ($orders->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No eligible cancelled orders found for refund.',
            ];
        }

        $refundedOrders = [];

        foreach ($orders as $order) {

            $amount = $order->seller_amount_walate ?? 0;

            // 💰 Credit seller wallet
            if ($amount > 0) {
                Recharge::create([
                    'seller_id'   => $order->seller_id, // ✅ seller_id from order table
                    'type'        => 'Credit',
                    'amount'      => $amount,
                    'status'      => 1,
                    'description' => 'Order cancelled refund',
                ]);
            }

            // 🏷️ Mark order as refunded so it never repeats
            $order->update([
                'order_status'     => 'cancelled',
                'cancelled_amount' => 1,
            ]);

            $refundedOrders[] = [
                'order_id'  => $order->id,
                'seller_id' => $order->seller_id,
                'amount'    => $amount,
            ];
        }

        return [
            'status' => true,
            'message' => 'All cancelled orders processed successfully.',
            'total_orders' => count($refundedOrders),
            'data' => $refundedOrders,
        ];

    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Process failed: ' . $e->getMessage(),
        ];
    }
}

public function index(Request $request)
{
    $skip  = max((int) $request->get('skip', 0), 0);
    $limit = min((int) $request->get('limit', 20), 100);

    // Total records (without any condition)
    $total = Order::count();

    // Auto-fix skip agar zyada ho
    if ($skip >= $total) {
        $skip = max($total - $limit, 0);
    }

    $orders = Order::orderBy('id', 'desc') // last se
        ->offset($skip)
        ->limit($limit)
        ->get();

    return response()->json([
        'status' => true,
        'total'  => $total,
        'skip'   => $skip,
        'limit'  => $limit,
        'count'  => $orders->count(),
        'data'   => $orders
    ]);
}




public function destroy(Request $request)
{
    $skip  = max((int) $request->get('skip', 0), 0);
    $limit = min((int) $request->get('limit', 20), 100);

    $total = Order::count();

    if ($total === 0) {
        return response()->json([
            'status' => false,
            'message' => 'No orders found to delete'
        ], 404);
    }

    // Auto-fix skip
    if ($skip >= $total) {
        $skip = max($total - $limit, 0);
    }

    // Pehle IDs nikaalo (SAFE WAY)
    $orderIds = Order::orderBy('id', 'desc')
        ->offset($skip)
        ->limit($limit)
        ->pluck('id');

    if ($orderIds->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No records found for given skip & limit'
        ], 404);
    }

    // Delete
    $deletedCount = Order::whereIn('id', $orderIds)->delete();

    return response()->json([
        'status' => true,
        'total_before' => $total,
        'skip' => $skip,
        'limit' => $limit,
        'deleted' => $deletedCount
    ]);
}




       public function processrto(Request $request)
    {
        $result = [
            'processed' => 0,
            'skipped'   => 0,
            'failed'    => 0,
            'details'   => [], // optional per-order info
        ];

        // Optionally accept a limit param (for safety / testing)
        $limit = (int) $request->get('limit', 100);

        try {
            // Use chunk to avoid memory issues. Only fetch relevant orders.
            Order::where('shipping_status', 'rto')
                ->where(function($q) {
                    $q->where('rto_amount', '0')
                      ->orWhereNull('rto_amount');
                })
                ->whereNotNull('seller_id')
                ->whereNotNull('seller_amount_walate')
                ->where('seller_amount_walate', '>', 0)
                // ->limit($limit)
                ->get()
                ->each(function ($order) use (&$result) {
                    // wrap per-order processing in transaction to be safe
                    DB::beginTransaction();
                    try {
                        $creditAmount = $order->seller_amount_walate ?? 0;

                        if ($creditAmount <= 0) {
                            $result['skipped']++;
                            $result['details'][] = [
                                'order_id' => $order->id,
                                'reason' => 'Invalid credit amount',
                            ];
                            DB::rollBack();
                            return;
                        }

                        // fetch seller row (using query builder as in original)
                        $seller = DB::table('seller_lists')->where('id', $order->seller_id)->first();

                        if (!$seller || $seller->fixed_price == 1 ) {
                            // mark as processed to avoid reprocessing (as original)
                            $order->rto_amount = 1;
                            $order->save();

                            $result['skipped']++;
                            $result['details'][] = [
                                'order_id' => $order->id,
                                'reason' => 'Seller fixed_price is 1',
                            ];
                            DB::commit();
                            return;
                        }

                        // create recharge (credit to seller wallet)
                        Recharge::create([
                            'seller_id'   => $order->seller_id,
                            'type'        => 'Debit', // kept same as original logic; change to 'Credit' if needed
                            'amount'      => $creditAmount,
                            'status'      => 1,
                            'description' => 'RTO Debit',
                        ]);

                        // mark order as processed
                        $order->rto_amount = 1;
                        $order->save();

                        $result['processed']++;
                        $result['details'][] = [
                            'order_id' => $order->id,
                            'amount'   => $creditAmount,
                            'seller_id'=> $order->seller_id,
                        ];

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $result['failed']++;
                        $result['details'][] = [
                            'order_id' => $order->id,
                            'error'    => $e->getMessage(),
                        ];
                        // log for debugging
                        Log::channel('scheduler')->error("RTO processing failed for order {$order->id}: " . $e->getMessage());
                    }
                });

            return response()->json([
                'success' => true,
                'message' => 'RTO processing finished',
                'result'  => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('scheduler')->error('RTO_Amount API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error while processing RTO orders',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    public function processRTOFromExcel(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv'
        ]);
           
        try {
            $file = $request->file('excel_file');
            $data = Excel::toArray([], $file)[0];
            //  dd($data);
            // Skip header row if exists
            if (isset($data[0]) && !is_numeric($data[0][0])) {
                array_shift($data);
            }

            $processedCount = 0;
            $errors = [];
            $results = [];

            foreach ($data as $row) {
                try {
                    // AWB is in column A (index 0)
                    $awbNumber = trim($row[0] ?? '');
                    
                    // NDR Remarks is in last column (column Q, index 16)
                    $rtoReason = trim($row[16] ?? '');
                    
                    if (empty($awbNumber)) {
                        continue;
                    }

                    // Find order by awb_number
                    $order = Order::where('awb_number', $awbNumber)->first();

                    if (!$order) {
                        $errors[] = "Order not found with AWB: {$awbNumber}";
                        continue;
                    }

                    // Save RTO reason to order
                    if (!empty($rtoReason)) {
                        $order->rto_reason = $rtoReason;
                        $order->save();
                    }

                    // Process RTO amount deduction
                    $result = $this->processRTOForOrder($order, $rtoReason);
                    
                    if ($result['success']) {
                        $processedCount++;
                        $results[] = [
                            'awb_number' => $awbNumber,
                            'order_id' => $order->id,
                            'deducted_amount' => $result['amount'],
                            'rto_reason' => $rtoReason,
                            'status' => 'success'
                        ];
                    } else {
                        $errors[] = "Failed to process AWB {$awbNumber}: {$result['message']}";
                    }

                } catch (\Exception $e) {
                    $errors[] = "Error processing row: " . $e->getMessage();
                }
            }

            return response()->json([
                'status' => true,
                'message' => "Successfully processed {$processedCount} orders",
                'data' => [
                    'processed_count' => $processedCount,
                    'results' => $results,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error processing Excel file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processRTOForOrder($order, $rtoReason = null)
    {
        try {
            // Check if already processed
            if ($order->rto_amount == 1) {
                return [
                    'success' => false,
                    'message' => 'RTO amount already processed for this order'
                ];
            }

            // Save RTO reason if provided
            if (!empty($rtoReason) && empty($order->rto_reason)) {
                $order->rto_reason = $rtoReason;
            }

            // Check if seller amount exists
            $creditAmount = $order->seller_amount_walate ?? 0;
            
            if ($creditAmount <= 0) {
                $order->rto_amount = 1;
                $order->save();
                return [
                    'success' => false,
                    'message' => 'No seller amount to deduct'
                ];
            }

            // Check seller existence
            $seller = DB::table('seller_lists')->where('id', $order->seller_id)->first();

            if (!$seller || $seller->fixed_price == 0 || is_null($seller->fixed_price)) {
                $order->rto_amount = 1;
                $order->save();
                return [
                    'success' => false,
                    'message' => 'Seller not found or fixed price not set'
                ];
            }

            // Create recharge entry for deduction
            $description = 'RTO Debit for order: ' . ($order->order_number ?? $order->id);
            if (!empty($rtoReason)) {
                $description .= ' - Reason: ' . $rtoReason;
            }

            $recharge = Recharge::create([
                'seller_id' => $order->seller_id,
                'type'      => 'Debit',
                'amount'    => $creditAmount,
                'status'    => 1,
                'description' => 'RTO Debit'
            ]);

            // Mark as processed
            $order->rto_amount = 1;
            $order->save();

            return [
                'success' => true,
                'amount' => $creditAmount,
                'message' => 'RTO amount deducted successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }




    public function trackShadowfax(Request $request)
{
    $awb = $request->input('awb');

    // Shadowfax Production Token
    $shadowfaxToken = 'fec1949bfc737bd52df914d18673e27b67a7f92d';

    // API Endpoints
    $singleTrackUrl = "https://dale.shadowfax.in/api/v4/clients/orders";
    $bulkTrackUrl   = "https://dale.shadowfax.in/api/v4/clients/bulk_track/";

    // Fetch Orders
    $ordersQuery = Order::where(function($q) {
                        $q->whereNull('shipping_status')
                          ->orWhereNotIn('shipping_status', ['delivered','rto']);
                    })
                    ->where(function($q) {
                        $q->whereNull('order_status')
                          ->orWhere('order_status', '!=', 'cancelled');
                    })
                    ->whereNotNull('awb_number')
                    ->where('courier_id', 'shadowfax');

    if ($awb) {
        $ordersQuery->where('awb_number', $awb);
    }

    $orders = $ordersQuery->get();

    if ($orders->isEmpty()) {
        return response()->json([
            'status'  => false,
            'message' => 'No Shadowfax AWB found to track.'
        ], 404);
    }

    // Extract AWBs
    $awbNumbers = $orders->pluck('awb_number')->toArray();

    // If single AWB request → call single API
    if ($awb) {
        $trackUrl = $singleTrackUrl . "/{$awb}/track/";

        $response = Http::withHeaders([
            'Authorization' => "Token $shadowfaxToken",
            'Content-Type'  => 'application/json',
        ])->get($trackUrl);

        $shadowfaxData = [
            $awb => $response->json()
        ];
    }
    else {
        // Bulk Track API with batch processing (max 50 AWBs per request)
        $shadowfaxData = [];
        $batches = array_chunk($awbNumbers, 50); // Split into batches of 50

        foreach ($batches as $batch) {
            $response = Http::withHeaders([
                'Authorization' => "Token $shadowfaxToken",
                'Content-Type'  => 'application/json',
            ])->post($bulkTrackUrl, [
                'awb_numbers' => $batch
            ]);

            if ($response->successful()) {
                $batchResponse = $response->json();
                
                // Handle new response structure: {"message": "Success", "data": [...]}
                if (isset($batchResponse['data']) && is_array($batchResponse['data'])) {
                    foreach ($batchResponse['data'] as $orderData) {
                        $awbNumber = $orderData['awb_number'] ?? null;
                        if ($awbNumber) {
                            $shadowfaxData[$awbNumber] = $orderData;
                        }
                    }
                }
            } else {
                // Log error but continue with other batches
                Log::error('Shadowfax API Error for batch', [
                    'batch' => $batch,
                    'response' => $response->body()
                ]);
            }
        }
    }

    $results = [];

    foreach ($orders as $order) {

        $awbNum = $order->awb_number;

        if (!isset($shadowfaxData[$awbNum])) {
            $results[] = [
                'awb_number' => $awbNum,
                'status' => 'failed',
                'message' => 'Invalid response from Shadowfax'
            ];
            continue;
        }

        $data = $shadowfaxData[$awbNum];

        // Shadowfax returns status in different field based on API response structure
        $newStatus = strtolower($data['status'] ?? 'unknown');

        // Status Mapping - Updated to handle new status values
        $mappedStatus = match ($newStatus) {
            'in_transit', 'received_at_hub', 'picked_up' => 'transit',
            'out_for_delivery' => 'out for delivery',
            'delivered' => 'delivered',
            'cancelled_by_customer' => 'cancelled',
            'rts_d' => 'rto',
             'recd_at_rev_hub' => 'transit',
             'rts_in_process' => 'rto',
             'recd_at_fwd_dc' => 'transit',
             'seller_initiated_delay' => 'assigned',
             'new' => 'assigned',
            'ofp' => 'assigned',


            'rto_in_transit', 'rto_delivered', 'return_to_origin' => 'rto',
            default => $newStatus,
        };

        $previousStatus = $order->shipping_status;
        $statusChanged = $previousStatus !== $mappedStatus;

        // Update Database
        $order->shipping_status = $mappedStatus;

        if ($mappedStatus === 'delivered') {
            $order->delivered_date = date('Y-m-d');
        }

        $order->save();

        $results[] = [
            'awb_number' => $awbNum,
            'previous_status' => $previousStatus,
            'updated_status' => $mappedStatus,
            'status_changed'  => $statusChanged,
        ];
    }

    return response()->json([
        'status' => true,
        'message' => 'Shadowfax tracking updated successfully.',
        'data' => $results,
    ]);
}


    public function trackSedoFedexold()
    {
        $sedoApiToken = 'fec1949bfc737bd52df914d18673e27b67a7f92d'; // Replace with real token
        $baseUrl = "https://private-anon-9a4bb70501-sfxreversepickupsellerdelivery.apiary-mock.com/api/v4/clients/requests"; // Replace with actual URL base

        $orders = Order::where(function ($q) {
                            $q->whereNull('shipping_status')
                              ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
                        })
                        ->where(function ($q) {
                            $q->whereNull('order_status')
                              ->orWhere('order_status', '!=', 'cancelled');
                        })
                        ->whereNotNull('awb_number')
                        ->where('courier_id', 'shadowfax')
                        ->get();

        foreach ($orders as $order) {
            $clientRequestId = $order->awb_number;

            $response = Http::withHeaders([
                'Authorization' => "Token $sedoApiToken",
                'Accept'         => 'application/json',
            ])
            ->timeout(30)
            ->get("{$baseUrl}/{$clientRequestId}");

            // Log::channel('scheduler')->info("📦 SedoFedEx API Response for AWB {$clientRequestId}", [
            //     'status' => $response->status(),
            //     'body'   => $response->body(),
            // ]);

            if (!$response->successful()) {
                // Log::channel('scheduler')->warning("✖ SedoFedEx HTTP {$response->status()} for AWB {$clientRequestId}");
                continue;
            }

            $data = $response->json();
            $newStatus = $data['status'] ?? null;

            if ($newStatus) {
                $statusrr = match (strtolower($newStatus)) {
                    'recd_at_rev_hub', 'recd_at_fwd_dc', 'recd_at_fwd_hub' => 'transit',
                    'ofd' => 'out for delivery',
                    'delivered' => 'delivered',
                    'rts', 'rts_in_process', 'rts_d', 'rts_nd' => 'rto',
                    default => $newStatus,
                };

                $previousStatus = $order->shipping_status;
                $statusChanged = $previousStatus !== $statusrr;

                $order->shipping_status = $statusrr;
                if ($statusrr === 'delivered') {
                    $order->delivered_date = date('Y-m-d');
                }
                $order->save();

                if ($statusChanged) {
                    // $this->sendWhatsAppMessage($order, $statusrr);
                    // $this->sendEmailNotification($order, $statusrr);
                }

                // Log::channel('scheduler')->info("✔ SedoFedEx AWB {$clientRequestId} updated to {$statusrr}");
            } else {
                // Log::channel('scheduler')->warning("✖ Status missing for AWB {$clientRequestId}");
            }

            // if (!empty($data['pickup_request_state_histories'])) {
            //     foreach ($data['pickup_request_state_histories'] as $event) {
            //         Log::channel('scheduler')->info("📍 History [{$clientRequestId}]: "
            //             . ($event['state'] ?? 'N/A') . " at "
            //             . ($event['created_at'] ?? 'N/A') . " - "
            //             . ($event['state_description'] ?? 'N/A')
            //         );
            //     }
            // }
        }

        return response()->json(['success' => true, 'message' => 'SedoFedEx tracking job executed successfully']);
    }



public function shiprocketLogin()
{
    $url = "https://apiv2.shiprocket.in/v1/external/auth/login";
    $payload = [
        "email" => "vk0553723@gmail.com",
        "password" => "YQcM!PpO#l2fF0@D"
    ];
    $response = Http::withHeaders([
        'Content-Type' => 'application/json'
    ])->post($url, $payload);
    // dd($response);
    return $response->json();
}



public function downloadDeliveredOrdersExcel()
{
    $startDate = '2025-11-11';
    $endDate   = '2025-11-15';

    // Allowed courier IDs
    $courierIds = [
        'boxd',
        'Bluedart Surface 500gms',
        'Bluedart Air 500gms',
        'Delhivery 250gms'
    ];

    // Fetch filtered orders
    $orders = Order::whereBetween('delivered_date', [$startDate, $endDate])
        ->where('shipping_status', 'delivered')
         ->where('payment_type', 'cod')
        ->whereIn('courier_id', $courierIds)
        ->select('awb_number', 'order_amount')
        ->get();
//  dd($orders);
    // Create excel data
    $exportData = $orders->map(function ($order) {
        return [
            'AWB Number'    => $order->awb_number,
            'Order Amount'  => $order->order_amount,
        ];
    })->toArray();

    // Create Excel file
    return Excel::download(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray {
        protected $data;

        public function __construct(array $data)
        {
            $this->data = $data;
        }

        public function array(): array
        {
            return $this->data;
        }
    }, 'DeliveredOrders.xlsx');
}


// public function sendTransitWhatsAppMessages()
// {
//     $sellerId = 369; // Or pass dynamically

//     $orders = Order::where('seller_id', $sellerId)
//         ->where('shipping_status', 'transit')
//         ->get();

//     if ($orders->isEmpty()) {
//         // Log::channel('scheduler')->info("No transit orders found for seller {$sellerId}");
//         return;
//     }

//     foreach ($orders as $order) {
//         $this->sendWhatsAppMessage($order, 'transit');
//     }
// }




//     private function sendWhatsAppMessage($order, $status)
//     {
//         try {
//             // $whatsappNotification = ShippingNotification::where('seller_id', $order->seller_id)
//             //     ->where('notification_type', 'whatsapp')
//             //     ->where('order_status', $status)
//             //     ->where('enabled', '1')
//             //     ->first();

//             // if (!$whatsappNotification) {
//             //     Log::channel('scheduler')->info("✖ WhatsApp notification disabled for seller {$order->seller_id} - Status: {$status}");
//             //     return;
//             // }
//             $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;
//         //    dd($consignee);
  

//             $customerName = $consignee['name'] ?? 'Customer';
//             $orderNumber = $order->order_number ?? 'N/A';
//             $awbNumber = $order->awb_number ?? 'N/A';
//             $phoneNumber = $consignee['phone'] ?? 'N/A';
//             //   dd($phoneNumber);
//             // Ensure phone number starts with +91
//             if (!str_starts_with($phoneNumber, '+')) {
//                 $phoneNumber = '+91' . ltrim($phoneNumber, '+91');
//             }

//             // Determine campaign and template parameters based on status
//             $campaignData = $this->getWhatsAppCampaignData($status, $customerName, $orderNumber, $awbNumber);
            
//             if (!$campaignData) {
//                 return; // Skip if no campaign configured for this status
//             }
//             //   dd($phoneNumber);
//             $payload = [
//                 "apiKey" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY4OTMwNmIxNTk2NTBhMGMwYmEyNmM1NyIsIm5hbWUiOiJTSElQWFBFRUQgTE9HSVNUSUNTICIsImFwcE5hbWUiOiJBaVNlbnN5IiwiY2xpZW50SWQiOiI2ODkzMDZiMTU5NjUwYTBjMGJhMjZjNTIiLCJhY3RpdmVQbGFuIjoiRlJFRV9GT1JFVkVSIiwiaWF0IjoxNzU0NDY1OTY5fQ.KrQxCgEKxGJLJ6KCLk6vmNrmakhhqnM19ycdHjskf84",
//                 "campaignName" => $campaignData['campaign'],
//                 "destination" => $phoneNumber,
//                 "userName" => $customerName,
//                 "templateParams" => $campaignData['params']
//             ];

//             $response = Http::withHeaders([
//                 'Content-Type' => 'application/json',
//             ])->post('https://backend.aisensy.com/campaign/t1/api/v2', $payload);
// // dd($response->json());
//             if ($response->successful()) {
//                 // Log::channel('scheduler')->info("✔ WhatsApp message sent for order {$orderNumber} - Status: {$status}");
                
//                 // Deduct ₹1 from seller's account for successful WhatsApp message
//                 try {
//                     Recharge::create([
//                         'seller_id' => $order->seller_id,
//                         'type' => 'Debit',
//                         'amount' => 1.00,
//                         'status' => 1,
//                         'description' => "WhatsApp notification charge for order: {$orderNumber}"
//                     ]);
//                     // Log::channel('scheduler')->info("✔ ₹1 deducted from seller {$order->seller_id} for WhatsApp notification");
//                 } catch (\Exception $e) {
//                     // Log::channel('scheduler')->error("❌ Failed to deduct WhatsApp charge for seller {$order->seller_id}: " . $e->getMessage());
//                 }
//             } else {
//                 // Log::channel('scheduler')->warning("✖ Failed to send WhatsApp message for order {$orderNumber} - HTTP {$response->status()}");
//             }

//         } catch (\Exception $e) {
//             // Log::channel('scheduler')->error("❌ WhatsApp message error for order {$order->order_number}: " . $e->getMessage());
//         }
//     }

//     /**
//      * Get campaign data based on shipping status
//      */
//     private function getWhatsAppCampaignData($status, $customerName, $orderNumber, $awbNumber)
//     {
//         return match ($status) {
//             'transit' => [
//                 'campaign' => 'intransitorder',
//                 'params' => [$customerName, $orderNumber, $awbNumber]
//             ],
//             'out for delivery' => [
//                 'campaign' => 'out_for_delivery_test',
//                 'params' => [$customerName, $orderNumber, $awbNumber]
//             ],
//             'delivered' => [
//                 'campaign' => 'deliveredorder',
//                 'params' => [$customerName, $orderNumber, $awbNumber]
//             ],
//             'rto' => [
//                 'campaign' => 'ndrqorders',
//                 'params' => [$customerName, $orderNumber, $awbNumber]
//             ],
//             default => null // No message for other statuses
//         };
//     }















// public function trackDTDC(Request $request)
// {
//     // Optional: Single AWB testing
//     $awb = $request->input('awb');

//     // DTDC Token
//     $token = "GL11173_trk_json:fa2bddc27d950d2dedae9473486781c5";

//     // Base query for orders
//     $ordersQuery = Order::where(function ($q) {
//             $q->whereNull('shipping_status')
//               ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
//         })
//         ->where(function ($q) {
//             $q->whereNull('order_status')
//               ->orWhere('order_status', '!=', 'cancelled');
//         })
//         ->whereNotNull('awb_number')
//         // ->where('courier_id', 'dtdc','DTDC Surface 500gm','DTDC Surface 1kg','DTDC Air');
//         ->whereIn('courier_id', [
//             'dtdc',
//             'DTDC Surface 500gm',
//             'DTDC Surface 1kg',
//             'DTDC Air'
//         ]);


//     // If AWB provided, track only that
//     if ($awb) {
//         $ordersQuery->where('awb_number', $awb);
//     }

//     $orders = $ordersQuery->get();

//     if ($orders->isEmpty()) {
//         return response()->json([
//             'status'  => false,
//             'message' => 'No DTDC orders found to track.',
//         ], 404);
//     }

//     $results = [];

//     foreach ($orders as $order) {
//         $url = "https://blktracksvc.dtdc.com/dtdc-api/rest/JSONCnTrk/getTrackDetails";

//         // Request payload
//         $payload = [
//             "trkType"   => "cnno",
//             "strcnno"   => $order->awb_number,
//             "addtnlDtl" => "Y"
//         ];

//         // Make request
//         $response = Http::withHeaders([
//             'Content-Type'  => 'application/json',
//             'x-access-token' => $token,
//         ])->post($url, $payload);

//         // Handle HTTP errors
//         if (! $response->successful()) {
//             $results[] = [
//                 'awb_number' => $order->awb_number,
//                 'status' => 'failed',
//                 'message' => "HTTP {$response->status()}",
//             ];
//             continue;
//         }

//         $data = $response->json();

//         if (
//             empty($data['trackHeader']) ||
//             empty($data['trackDetails'])
//         ) {
//             $results[] = [
//                 'awb_number' => $order->awb_number,
//                 'status' => 'failed',
//                 'message' => 'Invalid DTDC response',
//             ];
//             continue;
//         }

//         // Extract latest status from trackHeader
//         $latestStatus = $data['trackHeader']['strStatus'] ?? '';
//         $statusDate   = $data['trackHeader']['strStatusTransOn'] ?? '';
//         $statusTime   = $data['trackHeader']['strStatusTransTime'] ?? '';

//         // Map status
//         $mappedStatus = match (strtolower($latestStatus)) {
//             'delivered'   => 'delivered',
//             'out for delivery' => 'out for delivery',
//             'in transit'  => 'transit',
//             'rto'         => 'rto',
//             'booked'      => 'manifested',
//             'cancelled'   => 'cancelled',
//             default       => strtolower($latestStatus),
//         };

//         $previousStatus = $order->shipping_status;
//         $statusChanged = $previousStatus !== $mappedStatus;

//         // Update order
//         $order->shipping_status = $mappedStatus;

//         if ($mappedStatus === 'delivered') {
//             $order->delivered_date = now()->format('Y-m-d');
//         }

//         $order->save();

//         // Send notifications only when status changes
//         if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto'])) {
//             $this->sendWhatsAppMessage($order, $mappedStatus);
//             $this->sendEmailNotification($order, $mappedStatus);
//         }

//         $results[] = [
//             'awb_number' => $order->awb_number,
//             'previous_status' => $previousStatus,
//             'updated_status' => $mappedStatus,
//             'status_changed' => $statusChanged,
//             'status_time' => "{$statusDate} {$statusTime}",
//         ];
//     }

//     return response()->json([
//         'status' => true,
//         'message' => 'DTDC tracking updated successfully.',
//         'data' => $results,
//     ]);
// }


public function trackShiprocket(Request $request)
{
    $awb = $request->input('awb');
    $skip = (int) $request->input('skip', 0);
    $autoProcess = filter_var($request->input('auto_process', false), FILTER_VALIDATE_BOOLEAN); // Auto-pagination flag

    // Get Shiprocket auth token
    $authResponse = $this->shiprocketLogin();
    if (!isset($authResponse['token'])) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to authenticate with Shiprocket'
        ], 401);
    }
    $token = $authResponse['token'];

    $allResults = [];
    $totalProcessed = 0;
    $currentSkip = $skip;

    do {
        // Base query for orders
        $ordersQuery = Order::where(function ($q) {
                $q->whereNull('shipping_status')
                  ->orWhereNotIn('shipping_status', ['delivered']);
            })
            ->where(function ($q) {
                $q->whereNull('order_status')
                  ->orWhere('order_status', '!=', 'cancelled');
            })
            ->whereNotNull('awb_number')
            ->where('courier_id', 'shiprocket');

        if ($awb) {
            $ordersQuery->where('awb_number', $awb);
        }

        // Get total count for pagination info
        if (!$awb) {
            $totalCount = Order::where(function ($q) {
                    $q->whereNull('shipping_status')
                      ->orWhereNotIn('shipping_status', ['delivered']);
                })
                ->where(function ($q) {
                    $q->whereNull('order_status')
                      ->orWhere('order_status', '!=', 'cancelled');
                })
                ->whereNotNull('awb_number')
                ->where('courier_id', 'shiprocket')
                ->count();
                
            $orders = $ordersQuery
                ->latest('id')
                ->skip($currentSkip)
                ->take(50) // Process 50 orders at a time using bulk API
                ->get();
                
            $remainingOrders = max(0, $totalCount - $currentSkip - 50);
        } else {
            $orders = $ordersQuery->get();
            $totalCount = $orders->count();
            $remainingOrders = 0;
        }
        
        // If no orders found and this is the first iteration, return error
        if ($orders->isEmpty() && $currentSkip === $skip && !$autoProcess) {
            return response()->json([
                'status'  => false,
                'message' => 'No Shiprocket orders found to track.',
            ], 404);
        }
        
        // If no orders found but we're in auto-process mode, break the loop
        if ($orders->isEmpty()) {
            break;
        }

        $results = [];

        if ($awb) {
            // Single AWB - use individual API
            foreach ($orders as $order) {
                $url = "https://apiv2.shiprocket.in/v1/external/courier/track/awb/{$order->awb_number}";

                $response = Http::timeout(45)->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$token}",
                ])->get($url);

                if (!$response->successful()) {
                    $results[] = [
                        'awb_number' => $order->awb_number,
                        'status' => 'failed',
                        'message' => "HTTP {$response->status()}",
                    ];
                    continue;
                }

                $data = $response->json();
                $result = $this->processSingleShiprocketOrder($order, $data);
                if ($result) {
                    $results[] = $result;
                }
            }
        } else {
            // Bulk AWB tracking - use bulk API
            $awbNumbers = $orders->pluck('awb_number')->toArray();
            
            $url = "https://apiv2.shiprocket.in/v1/external/courier/track/awbs";
            
            $response = Http::timeout(60)->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ])->post($url, [
                'awbs' => $awbNumbers
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'status' => false,
                    'message' => "Bulk tracking API failed with HTTP {$response->status()}",
                ], 500);
            }

            $bulkData = $response->json();

            // Process each order with bulk response
            foreach ($orders as $order) {
                $awbNumber = $order->awb_number;
                
                if (!isset($bulkData[$awbNumber])) {
                    $results[] = [
                        'awb_number' => $awbNumber,
                        'status' => 'failed',
                        'message' => 'AWB not found in bulk response',
                    ];
                    continue;
                }

                $orderData = $bulkData[$awbNumber];
                $result = $this->processSingleShiprocketOrder($order, $orderData);
                if ($result) {
                    $results[] = $result;
                }
            }
        }

        // Collect results
        $allResults = array_merge($allResults, $results);
        $totalProcessed += count($results);
        
        // Move to next batch
        $currentSkip += 50;
        
        // Add delay to avoid rate limiting
        if (!$awb && $remainingOrders > 0) {
            sleep(2); // 2 second delay between batches
        }
        
    } while (!$awb && $remainingOrders > 0 && ($autoProcess || $request->input('continue_processing', false)));

    return response()->json([
        'status' => true,
        'message' => $autoProcess ? 
            'Auto-processing completed. All Shiprocket orders processed.' : 
            'Shiprocket tracking updated successfully.',
        'pagination' => [
            'total_processed' => $totalProcessed,
            'total_batches_completed' => ceil(($currentSkip - $skip) / 50),
            'current_skip' => $currentSkip,
            'remaining_orders' => max(0, ($totalCount ?? 0) - $currentSkip),
            'total_orders' => $totalCount ?? 0,
            'next_skip' => $currentSkip,
            'auto_processed' => $autoProcess
        ],
        'data' => $allResults,
    ]);
}

private function processSingleShiprocketOrder($order, $data)
{
    // Validate response structure
    if (empty($data['tracking_data'])) {
        return [
            'awb_number' => $order->awb_number,
            'status' => 'failed',
            'message' => 'No tracking_data in Shiprocket response',
        ];
    }

    if (empty($data['tracking_data']['shipment_track']) || !is_array($data['tracking_data']['shipment_track'])) {
        return [
            'awb_number' => $order->awb_number,
            'status' => 'failed', 
            'message' => 'No shipment_track data in Shiprocket response',
        ];
    }

    // Extract current status from shipment_track
    $shipmentData = $data['tracking_data']['shipment_track'][0] ?? [];
    $latestStatus = $shipmentData['current_status'] ?? '';

    // Get latest activity from shipment_track_activities
    $activities = $data['tracking_data']['shipment_track_activities'] ?? [];
    $latestActivity = !empty($activities) ? $activities[0] : [];
    $statusDate = $latestActivity['date'] ?? '';
    $location = $latestActivity['location'] ?? '';

    // Map Shiprocket status → internal shipping_status
    $mappedStatus = match (strtolower($latestStatus)) {
        'delivered'           => 'delivered',
        'out for delivery'    => 'out for delivery',
        'in transit'          => 'transit',
        'shipped'             => 'transit',
        'picked up'           => 'picked up',
        'manifested'          => 'manifested',
        'awb assigned'        => 'assigned',
        'pickup generated'    => 'assigned',
        'out for pickup'      => 'assigned',
        'rto delivered'       => 'rto delivered',
        'rto'                 => 'rto',
        'rto in intransit'    => 'rto',
        'rto initiated'       => 'rto',
        'ndr'                 => 'NDR',
        'cancelled'           => 'cancelled',
        'canceled'            => 'cancelled',
        'cancellation requested' => 'cancelled',
        'undelivered-at destination hub' => 'NDR',
        'reached at destination hub' => 'transit',
        'in transit-en-route' => 'transit',
        'pickup awaited'      => 'assigned',
        'pickup exception'    => 'assigned',

        default               => strtolower($latestStatus),
    };

    $previousStatus = $order->shipping_status;
    $statusChanged = $previousStatus !== $mappedStatus;

    // Update order in DB
    $order->shipping_status = $mappedStatus;

    if ($mappedStatus === 'delivered') {
        $order->delivered_date = $shipmentData['delivered_date'] 
            ? date('Y-m-d', strtotime($shipmentData['delivered_date'])) 
            : now()->format('Y-m-d');
    }

    $order->save();

    // Send notifications if status changed
    if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto'])) {
        $this->sendWhatsAppMessage($order, $mappedStatus);
        $this->sendEmailNotification($order, $mappedStatus);
    }

    return [
        'awb_number' => $order->awb_number,
        'previous_status' => $previousStatus,
        'updated_status' => $mappedStatus,
        'status_changed' => $statusChanged,
        'status_time' => $statusDate,
        'location' => $location,
        'courier_name' => $shipmentData['courier_name'] ?? '',
    ];
}

public function trackDTDC(Request $request)
{
    $awb = $request->input('awb');

    // DTDC Token
    $token = "GL11173_trk_json:fa2bddc27d950d2dedae9473486781c5";

    // Base query for orders
    $ordersQuery = Order::where(function ($q) {
            $q->whereNull('shipping_status')
              ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
        })
        ->where(function ($q) {
            $q->whereNull('order_status')
              ->orWhere('order_status', '!=', 'cancelled');
        })
        ->whereNotNull('awb_number')
        ->whereIn('courier_id', [
            'dtdc',
            'DTDC Surface 500gm',
            'DTDC Surface 1kg',
            'DTDC Air'
        ]);

    if ($awb) {
        $ordersQuery->where('awb_number', $awb);
    }

    $orders = $ordersQuery->get();

    if ($orders->isEmpty()) {
        return response()->json([
            'status'  => false,
            'message' => 'No DTDC orders found to track.',
        ], 404);
    }

    $results = [];

    foreach ($orders as $order) {
        $url = "https://blktracksvc.dtdc.com/dtdc-api/rest/JSONCnTrk/getTrackDetails";

        $payload = [
            "trkType"   => "cnno",
            "strcnno"   => $order->awb_number,
            "addtnlDtl" => "Y"
        ];

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'x-access-token' => $token,
        ])->post($url, $payload);

        if (!$response->successful()) {
            $results[] = [
                'awb_number' => $order->awb_number,
                'status' => 'failed',
                'message' => "HTTP {$response->status()}",
            ];
            continue;
        }

        $data = $response->json();

        if (empty($data['trackDetails'])) {
            $results[] = [
                'awb_number' => $order->awb_number,
                'status' => 'failed',
                'message' => 'Invalid DTDC response (no track details)',
            ];
            continue;
        }

        // ✅ Pick the LAST status record from trackDetails
        $lastTrack = end($data['trackDetails']);
        $latestStatus = $lastTrack['strAction'] ?? '';
        $statusDate   = $lastTrack['strActionDate'] ?? '';
        $statusTime   = $lastTrack['strActionTime'] ?? '';
        $remarks      = $lastTrack['sTrRemarks'] ?? '';

        // Map DTDC status text → internal shipping_status
        $mappedStatus = match (strtolower($latestStatus)) {
            'delivered'         => 'delivered',
            'out for delivery'  => 'out for delivery',
            'in transit'        => 'transit',
            'booked'            => 'manifested',
            'pickup awaited'    => 'pickup awaited',
            'picked up'         => 'picked up',
            'rto delivered'     => 'rto delivered',
            'rto'               => 'rto',
            'not picked'               => 'assigned',
            'mis route'               => 'transit',
            'not delivered'               => 'NDR',
            'return as per client instruction'               => 'rto',
            'pickup scheduled'               => 'assigned',
            'return as per client instruction.'               => 'rto',
            'rto booked'               => 'rto',
            'set rto initiated'               => 'rto',
            'pickup reassigned'               => 'assigned',

            default             => strtolower($latestStatus),
        };

        $previousStatus = $order->shipping_status;
        $statusChanged = $previousStatus !== $mappedStatus;

        // ✅ Update order in DB
        $order->shipping_status = $mappedStatus;
        // $order->shipping_remark = $remarks;

        if ($mappedStatus === 'delivered') {
            $order->delivered_date = now()->format('Y-m-d');
        }

        $order->save();

        // ✅ Optional: Send notifications if status changed
        if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto'])) {
            $this->sendWhatsAppMessage($order, $mappedStatus);
            $this->sendEmailNotification($order, $mappedStatus);
        }

        $results[] = [
            'awb_number' => $order->awb_number,
            'previous_status' => $previousStatus,
            'updated_status' => $mappedStatus,
            'status_changed' => $statusChanged,
            'status_time' => "{$statusDate} {$statusTime}",
            'remarks' => $remarks,
        ];
    }

    return response()->json([
        'status' => true,
        'message' => 'DTDC tracking updated successfully (latest status used).',
        'data' => $results,
    ]);
}




public function trackParcelX(Request $request)
{
    // Optional: track single AWB via request input
    $awb = $request->input('awb');

    // 🔐 ParcelX Access Token (replace with your real one)
    $token = "MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl";

    // Base order query (same structure as Tekipost)
    $ordersQuery = Order::where(function ($q) {
            $q->whereNull('shipping_status')
              ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
        })
        ->where(function ($q) {
            $q->whereNull('order_status')
              ->orWhere('order_status', '!=', 'cancelled');
        })
        ->whereNotNull('awb_number')
            ->whereIn('courier_id', ['parcelx', 'Delhivery_500gm', 'Amazon_500gm']);

        // ->where('courier_id', 'parcelx');

    // If specific AWB provided, track only that one
    if ($awb) {
        $ordersQuery->where('awb_number', $awb);
    }

    $orders = $ordersQuery->get();

    if ($orders->isEmpty()) {
        return response()->json([
            'status'  => false,
            'message' => 'No ParcelX orders found to track.',
        ], 404);
    }

    $results = [];

    foreach ($orders as $order) {
        $url = "https://app.parcelx.in/api/v3/track_order?awb={$order->awb_number}";

        $response = Http::withHeaders([
            'access-token' => $token,
            'Accept'       => 'application/json',
        ])->get($url);

        if (!$response->successful()) {
            $results[] = [
                'awb_number' => $order->awb_number,
                'status' => 'failed',
                'message' => "HTTP {$response->status()}",
            ];
            continue;
        }

        $data = $response->json();
        //  dd($data);
        if (empty($data) || !isset($data['status']) || !$data['status']) {
            $results[] = [
                'awb_number' => $order->awb_number,
                'status' => 'failed',
                'message' => 'Invalid ParcelX response',
            ];
            continue;
        }

        // 🧭 Extract current shipment status
        // $statusName = strtolower($data['data']['current_status'] ?? '');
// 🧭 Extract current shipment status
$statusName = strtolower($data['current_status']['status_title'] ?? '');

        // Map API status to internal ones
        $mappedStatus = match ($statusName) {
            'manifested'          => 'courier assigned',
            'in transit', 'transit' => 'transit',
            'out for delivery'    => 'out for delivery',
            'delivered'           => 'delivered',
            'rto'                 => 'rto',
            'rto delivered'       => 'rto delivered',
            'ndr'                 => 'ndr',
            'cancelled'           => 'cancelled',
            'not picked'           => 'courier Assigned',
            'dispatched'           => 'out for delivery',
            'booked'           => 'courier Assigned',

            default               => $statusName,
        };

        $previousStatus = $order->shipping_status;
        $statusChanged = $previousStatus !== $mappedStatus;

        // Update order
        $order->shipping_status = $mappedStatus;

        if (in_array($mappedStatus, ['delivered', 'rto delivered'])) {
            $order->delivered_date = $data['data']['delivery_date'] ?? now()->format('Y-m-d');
        }

        $order->save();

        // Optional notifications
        if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto'])) {
            $this->sendWhatsAppMessage($order, $mappedStatus);
            $this->sendEmailNotification($order, $mappedStatus);
        }

        $results[] = [
            'awb_number' => $order->awb_number,
            'previous_status' => $previousStatus,
            'updated_status' => $mappedStatus,
            'status_changed' => $statusChanged,
        ];
    }

    return response()->json([
        'status' => true,
        'message' => 'ParcelX tracking updated successfully.',
        'data' => $results,
    ]);
}



    public function trackTekipost(Request $request)
    {
        // Optional: Allow testing for a single AWB
        $awb = $request->input('awb');

        // Get token once
        $token = $this->getTokentekipost();

        // Build base query
        $ordersQuery = Order::where(function ($q) {
                $q->whereNull('shipping_status')
                  ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
            })
            ->where(function ($q) {
                $q->whereNull('order_status')
                  ->orWhere('order_status', '!=', 'cancelled');
            })
            ->whereNotNull('awb_number')
            ->where('courier_id', 'tekipost');

        // If AWB provided, track only that
        if ($awb) {
            $ordersQuery->where('awb_number', $awb);
        }

        $orders = $ordersQuery->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No Tekipost orders found to track.',
            ], 404);
        }

        $results = [];

        foreach ($orders as $order) {
            $url = "https://app.tekipost.com/api-tracking-details/{$order->awb_number}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type'  => 'application/json',
            ])->get($url);

            if (! $response->successful()) {
                $results[] = [
                    'awb_number' => $order->awb_number,
                    'status' => 'failed',
                    'message' => "HTTP {$response->status()}",
                ];
                continue;
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success'] || empty($data['data'])) {
                $results[] = [
                    'awb_number' => $order->awb_number,
                    'status' => 'failed',
                    'message' => 'Invalid Tekipost response',
                ];
                continue;
            }

            $statusName = $data['data']['status_name'] ?? '';

            $mappedStatus = match (strtolower($statusName)) {
                'in transit'       => 'transit',
                'dispatched'       => 'out for delivery',
                'delivered'        => 'delivered',
                'not picked'       => 'rto',
                'manifested'       => 'manifested',
                'cancelled'        => 'cancelled',
                'ndr'              => 'ndr',
                'rto delivered'    => 'rto delivered',
                'created'    => 'assigned',

                default            => strtolower($statusName),
            };

            $previousStatus = $order->shipping_status;
            $statusChanged = $previousStatus !== $mappedStatus;

            $order->shipping_status = $mappedStatus;

            if (in_array($mappedStatus, ['delivered', 'rto delivered'])) {
                $order->delivered_date = $data['data']['delivery_date'] ?? now()->format('Y-m-d');
            }

            $order->save();

            if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto', 'rto delivered'])) {
                $this->sendWhatsAppMessage($order, $mappedStatus);
                $this->sendEmailNotification($order, $mappedStatus);
            }


            $results[] = [
                'awb_number' => $order->awb_number,
                'previous_status' => $previousStatus,
                'updated_status' => $mappedStatus,
                'status_changed' => $statusChanged,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Tekipost tracking updated successfully.',
            'data' => $results,
        ]);
    }



    public static function getTokentekipost(): ?string
{
    try {
        // 🔹 If token already cached
        if (cache()->has('xpressbees_token')) {
            return cache('xpressbees_token');
        }

        // 🔹 Initialize cURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://app.tekipost.com/api-login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'email' => 'Bashu@shipxpeed.com',
                'password' => 'Shipxpeed@7722',
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // 🔹 Handle cURL errors
        if ($error) {
            dd('cURL Error: ' . $error);
            return null;
        }

        // 🔹 Decode JSON response
        $data = json_decode($response, true);


        // ✅ Corrected: token is inside 'data' key
        if (isset($data['data']['token'])) {
            $token = $data['data']['token'];

            // Cache token for 150 minutes
            cache()->put('xpressbees_token', $token, now()->addMinutes(150));

            return $token;
        } else {
            // dd('Token missing: ' . $response);
        }

        return null;
    } catch (\Exception $e) {
        // dd('Exception: ' . $e->getMessage());
        return null;
    }
}




public function trackBoxdseller(Request $request)
{
    $skipLimit = $request->input('skip_limit', 0);   // default = 0

    // 🔍 Count total orders for seller_id = 244
    $totalCount = Order::where('seller_id', 376)
        ->where(function ($q) {
            $q->whereNull('shipping_status')
              ->orWhereNotIn('shipping_status', ['delivered']);
        })
        ->where(function ($q) {
            $q->whereNull('order_status')
              ->orWhere('order_status', '!=', 'cancelled');
        })
        ->whereNotNull('awb_number')
        ->whereIn('courier_id', ['boxd', 'delhivery_250gms', 'amazon_0_5kg'])
        ->count();

    // 🧭 Skip logic
    // if ($skipLimit == 0 && $totalCount > 2050) {
    //     $skipLimit = 2050;
    // }

    // 🧾 Fetch orders for seller_id = 267
    $orders = Order::where('seller_id', 376)
        ->where(function ($q) {
            $q->whereNull('shipping_status')
              ->orWhereNotIn('shipping_status', ['delivered']);
        })
        ->where(function ($q) {
            $q->whereNull('order_status')
              ->orWhere('order_status', '!=', 'cancelled');
        })
        ->whereNotNull('awb_number')
        ->whereIn('courier_id', ['boxd', 'delhivery_250gms', 'amazon_0_5kg'])
        ->latest()
        ->skip($skipLimit)
        ->take(50)
        ->get();

    if ($orders->isEmpty()) {
        return response()->json(['status' => false, 'message' => 'No pending Boxd orders found for seller 244.']);
    }

    $results = [];

    foreach ($orders as $order) {
        $response = Http::withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'secretkey' => 'POVHFT',
            'customerid' => 'c1754533690129',
        ])->asForm()->post('https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_tracking', [
            'awb_number' => $order->awb_number,
        ]);

        if (!$response->successful()) {
            $results[] = [
                'awb' => $order->awb_number,
                'status' => 'failed',
                'message' => "HTTP error {$response->status()}"
            ];
            continue;
        }

        $data = $response->json();

        if (!isset($data['status']) || !$data['status'] || empty($data['shipment_status'])) {
            $results[] = [
                'awb' => $order->awb_number,
                'status' => 'failed',
                'message' => 'Invalid Boxd response'
            ];
            continue;
        }

        $shipmentStatus = strtolower($data['shipment_status']);

        // ✅ Map Boxd statuses to local statuses
        $mappedStatus = match ($shipmentStatus) {
            'pickup awaited', 'pickup scheduled', 'manifested' => 'courier Assigned',
            'in transit', 'picked up', 'intransit' => 'transit',
            'out for delivery' => 'out for delivery',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'rto' => 'rto',
            'ndr' => 'NDR',
            default => $shipmentStatus,
        };

        $previousStatus = $order->shipping_status;
        $statusChanged = $previousStatus !== $mappedStatus;

        $order->shipping_status = $mappedStatus;
        if ($mappedStatus === 'delivered') {
            $order->delivered_date = now()->format('Y-m-d');
        }
        $order->save();

        if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto'])) {
            $this->sendWhatsAppMessage($order, $mappedStatus);
            $this->sendEmailNotification($order, $mappedStatus);
        }

        $results[] = [
            'awb' => $order->awb_number,
            'updated_status' => $mappedStatus,
            'previous_status' => $previousStatus,
            'changed' => $statusChanged ? 'yes' : 'no'
        ];
    }

    return response()->json([
        'status' => true,
        'message' => 'Tracking updated successfully for seller 244',
        'data' => $results
    ]);
}



    public function trackBoxd(Request $request)
    {

    $skipLimit = $request->input('skip_limit', 0);   // default = 0

        $totalCount = Order::where(function ($q) {
        $q->whereNull('shipping_status')
          ->orWhereNotIn('shipping_status', ['delivered', 'rto delivered']);
    })
    ->where(function ($q) {
        $q->whereNull('order_status')
          ->orWhere('order_status', '!=', 'cancelled');
    })
    ->whereNotNull('awb_number')
    ->whereIn('courier_id', ['boxd', 'delhivery_250gms', 'amazon_0_5kg'])
    ->count();

// Agar total orders 100 se zyada hain tabhi skip karenge
    // $skip = $totalCount > 2050 ? 2050 : 0;
        if ($skipLimit == 0 && $totalCount > 2050) {
            $skipLimit = 2050;
          }
        //    dd($skipLimit);
        $orders = Order::where(function ($q) {
        $q->whereNull('shipping_status')
          ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
    })
    ->where(function ($q) {
        $q->whereNull('order_status')
          ->orWhere('order_status', '!=', 'cancelled');
    })
    ->whereNotNull('awb_number')
    ->whereIn('courier_id', ['boxd', 'delhivery_250gms', 'amazon_0_5kg'])
    ->latest()    
    ->skip($skipLimit)  // 👈 last 100 skip kar diye
 // 📌 Orders ko newest first laata hai
    ->take(100)    // 📌 Sirf 100 orders fetch karega
    ->get();


        if ($orders->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No pending Boxd orders found.']);
        }

        $results = [];
    //    dd($orders);
        foreach ($orders as $order) {
            $response = Http::withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'secretkey' => 'POVHFT',
                'customerid' => 'c1754533690129',
            ])->asForm()->post('https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_tracking', [
                'awb_number' => $order->awb_number,
            ]);

            if (! $response->successful()) {
                // Log::channel('scheduler')->warning("✖ Boxd HTTP {$response->status()} for AWB {$order->awb_number}");
                $results[] = [
                    'awb' => $order->awb_number,
                    'status' => 'failed',
                    'message' => "HTTP error {$response->status()}"
                ];
                continue;
            }

            $data = $response->json();

            //   dd($data);
            if (!isset($data['status']) || !$data['status'] || empty($data['shipment_status'])) {
                // Log::channel('scheduler')->warning("✖ Invalid Boxd response for AWB {$order->awb_number}");
                $results[] = [
                    'awb' => $order->awb_number,
                    'status' => 'failed',
                    'message' => 'Invalid Boxd response'
                ];
                continue;
            }

            $shipmentStatus = strtolower($data['shipment_status']);
            // dd($shipmentStatus);
            // ✅ Status mapping
            $mappedStatus = match ($shipmentStatus) {
                'pickup awaited' => 'courier Assigned',
                'pickup scheduled' => 'courier Assigned',
                'in transit' => 'transit',
                 'In Transit' => 'transit',
                'picked up' => 'transit',
                'out for delivery' => 'out for delivery',
                'delivered' => 'delivered',
                'cancelled' => 'cancelled',
                'rto' => 'rto',
                'ndr' => 'NDR',
                 'manifested' => 'courier Assigned',

                default => $shipmentStatus,
            };

            $previousStatus = $order->shipping_status;
            $statusChanged = $previousStatus !== $mappedStatus;

            $order->shipping_status = $mappedStatus;
            if ($mappedStatus === 'delivered') {
                $order->delivered_date = now()->format('Y-m-d');
            }
            $order->save();

            if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto'])) {
                // Optional notifications
                $this->sendWhatsAppMessage($order, $mappedStatus);
                $this->sendEmailNotification($order, $mappedStatus);
            }

            // Log::channel('scheduler')->info("✔ Boxd AWB {$order->awb_number} updated to {$mappedStatus}");

            $results[] = [
                'awb' => $order->awb_number,
                'updated_status' => $mappedStatus,
                'previous_status' => $previousStatus,
                'changed' => $statusChanged ? 'yes' : 'no'
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Tracking updated successfully',
            'data' => $results
        ]);
    }



public function apiTrackDelhivery(Request $request)
{
    try {
 
        // Static Delhivery token
        $delhiveryToken = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';

        // Get orders pending delivery tracking
        $orders = Order::where(function ($q) {
                $q->whereNull('shipping_status')
                  ->orWhereNotIn('shipping_status', ['delivered']);
            })
            ->where(function ($q) {
                $q->whereNull('order_status')
                  ->orWhere('order_status', '!=', 'cancelled');
            })
            ->whereNotNull('awb_number')
            ->where('courier_id', 'delhivery_b2c')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No Delhivery orders pending for tracking.'
            ]);
        }

        $results = [];

        foreach ($orders as $order) {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => "Token {$delhiveryToken}",
            ])->get('https://track.delhivery.com/api/v2/packages/json/', [
                'waybill' => $order->awb_number,
                'ref_ids' => '',
            ]);

            if (! $response->successful()) {
                $results[] = [
                    'awb'     => $order->awb_number,
                    'status'  => 'error',
                    'message' => 'HTTP ' . $response->status(),
                ];
                continue;
            }

            $data = $response->json();
            $shipment = $data['ShipmentData'][0]['Shipment'] ?? null;

            if (! $shipment || ! isset($shipment['Status']['Status'])) {
                $results[] = [
                    'awb'     => $order->awb_number,
                    'status'  => 'error',
                    'message' => 'Invalid response format',
                ];
                continue;
            }

            $newStatus = $shipment['Status']['Status'];
            $mappedStatus = match ($newStatus) {
                'In Transit' => 'transit',
                'Out for delivery' => 'out for delivery',
                'Delivered' => 'delivered',
                'RTO' => 'rto',
                'Dispatched' => 'transit',
                'Manifested' => 'courier Assigned',
                default => strtolower($newStatus),
            };

            $previousStatus = $order->shipping_status;
            $statusChanged  = $previousStatus !== $mappedStatus;

            // Update status in DB
            $order->shipping_status = $mappedStatus;
            if ($mappedStatus === 'delivered') {
                $order->delivered_date = now()->toDateString();
            }
            $order->save();

            // Notify if changed
            if ($statusChanged) {
                $this->sendWhatsAppMessage($order, $mappedStatus);
                $this->sendEmailNotification($order, $mappedStatus);
            }

            $results[] = [
                'awb' => $order->awb_number,
                'old_status' => $previousStatus,
                'new_status' => $mappedStatus,
                'changed' => $statusChanged,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Delhivery tracking updated successfully',
            'count'   => count($results),
            'data'    => $results,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}













   private function sendWhatsAppMessage($order, $status)
    {
        try {
            // Check if WhatsApp notifications are enabled for this seller
            $whatsappNotification = ShippingNotification::where('seller_id', $order->seller_id)
                ->where('notification_type', 'whatsapp')
                ->where('order_status', $status)
                ->where('enabled', '1')
                ->first();

            if (!$whatsappNotification) {
                return;
            }
            $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;

            // // Skip if customer phone is not available
            // if (empty($order->customer_phone)) {
            //     Log::channel('scheduler')->warning("✖ No phone number for order {$order->order_number}");
            //     return;
            // }

            $customerName = $consignee->name ?? 'Customer';
            $orderNumber = $order->order_number ?? 'N/A';
            $awbNumber = $order->awb_number ?? 'N/A';
            $phoneNumber = $consignee->phone ?? 'N/A';
            // $customerName = 'vicky';
            // $orderNumber = '12345';
            // $awbNumber = 'AWB12345';
            // $phoneNumber = '7357169546';

            // Ensure phone number starts with +91
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+91' . ltrim($phoneNumber, '+91');
            }

            // Determine campaign and template parameters based on status
            $campaignData = $this->getWhatsAppCampaignData($status, $customerName, $orderNumber, $awbNumber);
            
            if (!$campaignData) {
                return; // Skip if no campaign configured for this status
            }

            $payload = [
                "apiKey" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY4OTMwNmIxNTk2NTBhMGMwYmEyNmM1NyIsIm5hbWUiOiJTSElQWFBFRUQgTE9HSVNUSUNTICIsImFwcE5hbWUiOiJBaVNlbnN5IiwiY2xpZW50SWQiOiI2ODkzMDZiMTU5NjUwYTBjMGJhMjZjNTIiLCJhY3RpdmVQbGFuIjoiRlJFRV9GT1JFVkVSIiwiaWF0IjoxNzU0NDY1OTY5fQ.KrQxCgEKxGJLJ6KCLk6vmNrmakhhqnM19ycdHjskf84",
                "campaignName" => $campaignData['campaign'],
                "destination" => $phoneNumber,
                "userName" => $customerName,
                "templateParams" => $campaignData['params']
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://backend.aisensy.com/campaign/t1/api/v2', $payload);

            if ($response->successful()) {
                
                // Deduct ₹1 from seller's account for successful WhatsApp message
                try {
                    Recharge::create([
                        'seller_id' => $order->seller_id,
                        'type' => 'Debit',
                        'amount' => 1.00,
                        'status' => 1,
                        'description' => "WhatsApp notification charge for order: {$orderNumber}"
                    ]);
                } catch (\Exception $e) {
                }
            } else {
            }

        } catch (\Exception $e) {
        }
    }

    /**
     * Get campaign data based on shipping status
     */
    private function getWhatsAppCampaignData($status, $customerName, $orderNumber, $awbNumber)
    {
        return match ($status) {
            'transit' => [
                'campaign' => 'intransitorder',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            'out for delivery' => [
                'campaign' => 'out_for_delivery_test',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            'delivered' => [
                'campaign' => 'deliveredorder',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            'rto' => [
                'campaign' => 'ndrqorders',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            default => null // No message for other statuses
        };
    }

    /**
     * Send Email notification for order status updates
     */
    private function sendEmailNotification($order, $status)
    {
        try {
            // Check if Email notifications are enabled for this seller
            $emailNotification = ShippingNotification::where('seller_id', $order->seller_id)
                ->where('notification_type', 'email')
                ->where('order_status', $status)
                ->where('enabled', 1)
                ->first();

            if (!$emailNotification) {
                return;
            }

            // Skip if customer email is not available
            // if (empty($order->customer_email)) {
            //     Log::channel('scheduler')->warning("✖ No email address for order {$order->order_number}");
            //     return;
            // }
            $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;

            $customerName = $consignee['name'] ?? 'Customer';
            $orderNumber = $order->order_number ?? 'N/A';
            $awbNumber = $order->awb_number ?? 'N/A';
            $customerEmail = $consignee['email'] ?? 'N/A';

            // Get email content based on status
            $emailData = $this->getEmailContent($status, $customerName, $orderNumber, $awbNumber);
            
            if (!$emailData) {
                return; // Skip if no email configured for this status
            }

            // Send email using Laravel's Mail facade
            Mail::send([], [], function ($message) use ($customerEmail, $customerName, $emailData) {
                $message->to($customerEmail, $customerName)
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
            });

            
            // Deduct ₹0.50 from seller's account for successful Email
            try {
                Recharge::create([
                    'seller_id' => $order->seller_id,
                    'type' => 'Debit',
                    'amount' => 0.50,
                    'status' => 1,
                    'description' => "Email notification charge for order: {$orderNumber}"
                ]);
            } catch (\Exception $e) {
            }

        } catch (\Exception $e) {
        }
    }

    /**
     * Get email content based on shipping status
     */
    private function getEmailContent($status, $customerName, $orderNumber, $awbNumber)
    {
        return match ($status) {
            'transit' => [
                'subject' => "📦 Your Order is in Transit - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #2563eb; margin-bottom: 20px;'>🚚 Your Order is On the Move!</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                Great news! Your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) is currently in transit 🛣️ and moving closer to your city 🏙️.
                            </p>
                            
                            <div style='background-color: #e0f2fe; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #0277bd; font-weight: bold;'>📌 Order Details:</p>
                                <p style='margin: 5px 0; color: #0277bd;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #0277bd;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #0277bd;'>Status: In Transit</p>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                Stay tuned — we'll keep you posted until it reaches you! 💌
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for choosing our service!
                            </p>
                        </div>
                    </div>
                "
            ],
            'out for delivery' => [
                'subject' => "🚛 Out for Delivery - Your Order Arrives Today! - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #ff9800; margin-bottom: 20px;'>🚛 Out for Delivery!</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                Exciting news! Your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) is out for delivery and will arrive today! 🎉
                            </p>
                            
                            <div style='background-color: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #f57c00; font-weight: bold;'>📦 Delivery Information:</p>
                                <p style='margin: 5px 0; color: #f57c00;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #f57c00;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #f57c00;'>Status: Out for Delivery</p>
                                <p style='margin: 5px 0; color: #f57c00;'>Expected: Today</p>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                Please ensure someone is available to receive the package. Our delivery partner will contact you shortly! 📞
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for choosing our service!
                            </p>
                        </div>
                    </div>
                "
            ],
            'delivered' => [
                'subject' => "✅ Order Delivered Successfully! - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #4caf50; margin-bottom: 20px;'>🎁 Order Delivered Successfully!</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                Great news! Your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) has been delivered successfully! 🎁
                            </p>
                            
                            <div style='background-color: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #2e7d32; font-weight: bold;'>✅ Delivery Confirmation:</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>Status: Delivered</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>Delivered On: " . date('d M Y') . "</p>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                We hope you enjoy your purchase! 💜 Thank you for trusting us to deliver your orders safely and on time.
                            </p>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 15px;'>
                                If you have any questions about your order, please feel free to contact our customer support.
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for choosing our service! We look forward to serving you again.
                            </p>
                        </div>
                    </div>
                "
            ],
            'rto' => [
                'subject' => "❌ Delivery Attempt Unsuccessful - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #f44336; margin-bottom: 20px;'>❌ Delivery Attempt Unsuccessful</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                We tried delivering your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) today but it was unsuccessful 😔.
                            </p>
                            
                            <div style='background-color: #ffebee; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #c62828; font-weight: bold;'>📋 Order Details:</p>
                                <p style='margin: 5px 0; color: #c62828;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #c62828;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #c62828;'>Status: Delivery Failed</p>
                            </div>
                            
                            <div style='background-color: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #f57c00; font-weight: bold;'>Possible reasons:</p>
                                <ul style='margin: 10px 0; color: #f57c00; padding-left: 20px;'>
                                    <li>📍 Wrong or incomplete address</li>
                                    <li>📞 Phone number not reachable</li>
                                    <li>🚪 Customer not available at delivery location</li>
                                </ul>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                Please contact our customer support team to reschedule delivery or update your delivery information.
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for your patience. We'll resolve this issue quickly!
                            </p>
                        </div>
                    </div>
                "
            ],
            default => null // No email for other statuses
        };
    }




// public function updateColumnValue(Request $request)
// {
//     $request->validate([
//         'table' => 'required|string',
//         'column' => 'required|string',
//         'old_value' => 'required',
//         'new_value' => 'required',
//     ]);

//     try {
//         $table = $request->input('table');
//         $column = $request->input('column');
//         $oldValue = $request->input('old_value');
//         $newValue = $request->input('new_value');

//         // 🔒 Fixed date range (manual)
//         $startDate = '2025-10-01';
//         $endDate = '2025-10-31';
//         $dateColumn = 'created_at'; // Change if your table has a different column

//         // ✅ Safety checks
//         if (!Schema::hasTable($table)) {
//             return response()->json(['status' => false, 'message' => 'Table not found.']);
//         }

//         if (!Schema::hasColumn($table, $column)) {
//             return response()->json(['status' => false, 'message' => 'Column not found.']);
//         }

//         if (!Schema::hasColumn($table, $dateColumn)) {
//             return response()->json(['status' => false, 'message' => 'Date column not found.']);
//         }

//         // ✅ Perform update only for records between 1–31 Oct 2025
//         $updated = DB::table($table)
//             ->where($column, $oldValue)
//             ->whereBetween($dateColumn, [$startDate, $endDate])
//             ->update([$column => $newValue]);

//         if ($updated) {
//             return response()->json([
//                 'status' => true,
//                 'message' => "Successfully updated {$updated} record(s) between {$startDate} and {$endDate}.",
//                 'data' => [
//                     'table' => $table,
//                     'column' => $column,
//                     'old_value' => $oldValue,
//                     'new_value' => $newValue,
//                     'date_range' => [$startDate, $endDate],
//                     'updated_count' => $updated,
//                 ]
//             ]);
//         } else {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'No records found with the given old value and date range.'
//             ]);
//         }

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Error: ' . $e->getMessage()
//         ]);
//     }
// }

public function updateColumnValue(Request $request)
{
    $request->validate([
        'table' => 'required|string',
        'column' => 'required|string',
        'old_value' => 'required|string',
        'new_value' => 'required|string',
    ]);

    try {
        $table = $request->input('table');
        $column = $request->input('column');
        $oldValue = $request->input('old_value');
        $newValue = $request->input('new_value');

        // 🔒 Fixed date range (manual)
        $startDate = '2025-11-01';
        $endDate = '2025-12-30';
        $dateColumn = 'created_at'; // Change if your table uses different column

        // ✅ Safety checks
        if (!Schema::hasTable($table)) {
            return response()->json(['status' => false, 'message' => 'Table not found.']);
        }

        if (!Schema::hasColumn($table, $column)) {
            return response()->json(['status' => false, 'message' => 'Column not found.']);
        }

        if (!Schema::hasColumn($table, $dateColumn)) {
            return response()->json(['status' => false, 'message' => 'Date column not found.']);
        }

        // ✅ Fetch matching records where column contains the old substring
        $records = DB::table($table)
            ->where($column, 'LIKE', "%{$oldValue}%")
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No records found containing the old message within the date range.'
            ]);
        }

        $updateCount = 0;

        foreach ($records as $record) {
            $oldText = $record->$column;
            $newText = str_replace($oldValue, $newValue, $oldText);

            if ($newText !== $oldText) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update([$column => $newText]);
                $updateCount++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => "Successfully updated {$updateCount} record(s) between {$startDate} and {$endDate}.",
            'data' => [
                'table' => $table,
                'column' => $column,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'date_range' => [$startDate, $endDate],
                'updated_count' => $updateCount
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
















}







