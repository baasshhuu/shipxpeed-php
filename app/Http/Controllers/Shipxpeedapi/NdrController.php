<?php

namespace App\Http\Controllers\Shipxpeedapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\PriceSetting;
use App\Models\SellerList;
use App\Models\LogisticProvider;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NdrController extends Controller
{

    public function ndrReattempt(Request $request)
    {
        $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success'=>false,'message'=>'Authorization token required'], 401);
            }

            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success'=>false,'message'=>'Invalid or expired token'], 401);
            }
        $sellerid = $seller->id;
        //  dd($sellerid);
        // Validate required input
        $validator = Validator::make($request->all(), [
            'awb_number' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        $awb = $request->awb_number;

        // Step 1: Fetch order using awb number and seller id
        $order = Order::where('awb_number', $awb)
                      ->where('seller_id', $sellerid)
                      ->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'Order not found for this AWB number or you do not have access to this order.'
            ];
        }

        // Step 2: Check courier_id and route to appropriate function
        $courierId = $order->courier_id;

        if (in_array($courierId, ['boxd', 'Delhivery 250gms'])) {
            return $this->boxdNdrReattempt($request, $sellerid);
        } elseif (in_array($courierId, ['parcelx', 'Delhivery 500gm'])) {
            return $this->parcelXNdrReattempt($request, $awb, $sellerid);
        } else {
            return [
                'status' => false,
                'message' => 'Unsupported courier: '
            ];
        }
    }

public function parcelXNdrReattempt(Request $request, $waybill, $sellerid)
{   
    // dd($request, $waybill, $sellerid);
    // Step 1: Fetch order using waybill and seller id
        $request->validate([
        'awb_number' => 'required',
    ]);
    $order = Order::where('awb_number', $request->awb_number)
                  ->where('seller_id', $sellerid)
                  ->first();

    if (!$order) {
        return [
            'status' => false,
            'message' => 'Order not found for this waybill or you do not have access to this order.'
        ];
    }

    // Step 2: Consignee JSON decode
    $consignee = is_string($order->consignee) 
        ? json_decode($order->consignee, true) 
        : $order->consignee;

    // Step 3: Address priority → request > consignee > order table
    $address = $request->address 
        ?? ($consignee['address'] ?? $order->address ?? '');

    // Step 4: Phone priority → request > consignee > order table
    $phone = $request->phone 
        ?? ($consignee['phone'] ?? $order->phone ?? '');

    // Step 5: Prepare API payload
    $data = [
        "action" => "REATTEMPT",
        "waybill" => $request->awb_number,
        "remark" => $request->remark ?? "Customer requested reattempt",
        "address" => $address,
        "phone" => $phone,
        "reattempt_date" => $request->reattempt_date 
            ?? date('Y-m-d', strtotime('+1 day')),
    ];

    // Step 6: API Request
    $url = "https://app.parcelx.in/api/v3/ndr-action";

    $response = Http::withHeaders([
        'access-token' => 'MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl',
        'Content-Type' => 'application/json'
    ])->post($url, $data);
 

    // dd($response->json());
    // Step 7: Return
    return [
        'sent_payload' => $data,
        'api_response' => $response->json(),
        'http_status' => $response->status(),
    ];
}



public function boxdNdrReattempt(Request $request, $sellerid)
{
    // Validate required input
    $request->validate([
        'awb_number' => 'required',
    ]);

    $awb = $request->awb_number;

    // Step 1: Fetch order using awb number and seller id
    $order = Order::where('awb_number', $awb)
                  ->where('seller_id', $sellerid)
                  ->first();

    if (!$order) {
        return [
            'status' => false,
            'message' => 'Order not found for this AWB number or you do not have access to this order.'
        ];
    }

    // Step 2: Consignee decoding (if JSON)
    $consignee = is_string($order->consignee) 
        ? json_decode($order->consignee, true)
        : $order->consignee;

    // Step 3: Priority values → request > order table
    $remarks = $request->remarks ?? "Customer requested reattempt";
    $rescheduleDate = $request->rescheduleDate 
        ?? date('Y-m-d', strtotime('+1 day')); // default next-day

    // Step 4: API Payload
    $data = [
        "awb_number" => $awb,
        "rescheduleDate" => $rescheduleDate,
        "remarks" => $remarks,
    ];

    // Step 5: API URL
    $url = "https://backend.boxdlogistics.in/vendor/v1/shipment/ndr_re_attempt";

    // Step 6: Make POST API call
    $response = Http::withHeaders([ 
        'secretkey' => 'POVHFT',
        'customerid' => 'c1754533690129',
        'Content-Type' => 'application/json'
    ])->post($url, $data);

    // Step 7: Return everything
    return [
        'sent_payload' => $data,
        'api_response' => $response->json(),
        'http_status' => $response->status(),
    ];
}

    

}



