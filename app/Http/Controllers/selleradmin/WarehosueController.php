<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Warehouse; 
use App\Models\Order; 


class WarehosueController extends Controller
{





    public function smartshipWebhook(Request $request)
{
    try {
        $payload = $request->all();

        $trackingNumber = $payload['awbno'] ?? null;
        $statusCode = $payload['status'] ?? null;

        if ($trackingNumber && $statusCode !== null) {

            // Mapping status codes to shipping status
            $status = match (true) {
                in_array($statusCode, [26, 185]) => 'cancelled', // Cancelled

                in_array($statusCode, [11]) => 'delivered', // Delivered

                in_array($statusCode, [30]) => 'out for delivery', // Out for Delivery

                in_array($statusCode, [10, 27, 59, 207, 209, 213]) => 'transit', // In-Transit

                in_array($statusCode, [189, 12, 13, 14, 15, 16, 17, 22, 23, 210, 211]) => 'NDR', // NDR/Undelivered

                in_array($statusCode, [18, 19, 28, 198, 199, 201, 212]) => 'rto', // RTO

                in_array($statusCode, [0, 2, 3, 4, 24]) => 'Assigned', // Before Dispatch

                in_array($statusCode, [9]) => 'cancelled', // NSS

                default => $statusCode,
            };

            // Update order if mapping exists
            if ($status !== '') {
                $order = Order::where('awb_number', $trackingNumber)->first();

                if ($order) {
                    $order->shipping_status = $status;

                    if ($status === 'delivered') {
                        $order->delivered_date = now();
                    }

                    $order->save();
                }
            }
        }

        return response()->json([
            'data' => [
                'message' => [
                    'success' => true,
                    'description' => 'response message'
                ]
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Smartship Webhook Error', ['message' => $e->getMessage()]);

        return response()->json([
            'data' => [
                'message' => [
                    'success' => false,
                    'description' => 'Webhook processing failed'
                ]
            ]
        ], 500);
    }
}



//     public function smartshipWebhook(Request $request)
// {
//     try {
//         $payload = $request->all();

//         $trackingNumber = $payload['awbno'] ?? null;
//         $action = strtolower($payload['status_description'] ?? '');

//         if ($trackingNumber && $action) {
//             // Grouped mapping
//             $status = match (true) {
 
//            in_array($action, [
//                     'Cancellation Requested By Client',
//                     'Cancelled By Client',  
//                 ]) => 'Cancelled',

//                 in_array($action, [
//                     'Delivered',   
//                 ]) => 'delivered',


//                 in_array($action, [
//                     'Out For Delivery',
//                 ]) => 'out for delivery',


//              in_array($action, [
//                     'Shipped',
//                     'In Transit',
//                     'In Transit Delay - ODA Location/ Area Not Accessible',
//                     'Shipped - In Transit - Misrouted',
//                     'Shipped - In Transit - Destination Reached',
//                     'In Transit - Damaged',
//                 ]) => 'transit',


//             in_array($action, [
//                     'Forward Shipment Lost',
//                     'Delivery Attempted-Out Of Delivery Area',
//                     'Delivery Attempted-Address Issue / Wrong Address',
//                     'Delivery Attempted-COD Not ready',
//                     'Delivery Attempted-Customer Not Available/Contactable',
//                     'Delivery Attempted-Customer Refused To Accept Delivery',
//                     'Delivery Attempted-Requested for Future Delivery',
//                     'Delivery Attempted - Requested For Open Delivery',
//                     'Delivery Attempted - Others',
//                     'Delivery Not Attempted',
//                     'Delivery Attempted-Refused by Customer with OTP',
//                 ]) => 'NDR',



//                 in_array($action, [
//                     'Return To Origin',
//                     'RTO Delivered To Shipper',
//                     'RTO In Transit',
//                     'RTO-Rejected by Merchant',
//                     'RTO-Delivered to FC',
//                     'RTO - In Transit - Damaged'
//                 ]) => 'rto',

             
//                 default => '',
//             };

//             // Find and update the order
//             $order = Order::where('awb_number', $trackingNumber)->first();

//             if ($order) {
//                 $order->shipping_status = $status;

//                 if ($status === 'delivered') {
//                     $order->delivered_date = now();
//                 }

//                 $order->save();
//             }
//         }

//         return response()->json([
//             'data' => [
//                 'message' => [
//                     'success' => true,
//                     'description' => 'response message'
//                 ]
//             ]
//         ]);
//     } catch (\Exception $e) {
//         \Log::error('Smartship Webhook Error', ['message' => $e->getMessage()]);

//         return response()->json([
//             'data' => [
//                 'message' => [
//                     'success' => false,
//                     'description' => 'Webhook processing failed'
//                 ]
//             ]
//         ], 500);
//     }
// }
    
//     public function smartshipWebhook(Request $request)
// {
//     try {
//         $payload = $request->all();

//         $trackingNumber = $payload['awbno'] ?? null;
//         $action = strtolower($payload['status_description'] ?? '');

//         if ($trackingNumber && $action) {
//             // Grouped mapping
//             $status = match (true) {
 
//            in_array($action, [
//                     'Cancellation Requested By Client',
//                     'Cancelled By Client',
//                 ]) => 'Cancelled',

//                 in_array($action, [
//                     'Delivered',
//                 ]) => 'delivered',


//                 in_array($action, [
//                     'Out For Delivery',
//                 ]) => 'out for delivery',


//              in_array($action, [
//                     'Shipped',
//                     'In Transit',
//                     'In Transit Delay - ODA Location/ Area Not Accessible',
//                     'Shipped - In Transit - Misrouted',
//                     'Shipped - In Transit - Destination Reached',
//                     'In Transit - Damaged',
//                 ]) => 'transit',


//             in_array($action, [
//                     'Forward Shipment Lost',
//                     'Delivery Attempted-Out Of Delivery Area',
//                     'Delivery Attempted-Address Issue / Wrong Address',
//                     'Delivery Attempted-COD Not ready',
//                     'Delivery Attempted-Customer Not Available/Contactable',
//                     'Delivery Attempted-Customer Refused To Accept Delivery',
//                     'Delivery Attempted-Requested for Future Delivery',
//                     'Delivery Attempted - Requested For Open Delivery',
//                     'Delivery Attempted - Others',
//                     'Delivery Not Attempted',
//                     'Delivery Attempted-Refused by Customer with OTP',
//                 ]) => 'NDR',



//                 in_array($action, [
//                     'Return To Origin',
//                     'RTO Delivered To Shipper',
//                     'RTO In Transit',
//                     'RTO-Rejected by Merchant',
//                     'RTO-Delivered to FC',
//                     'RTO - In Transit - Damaged'
//                 ]) => 'rto',

             
//                 default => '',
//             };

//             // Find and update the order
//             $order = Order::where('awb_number', $trackingNumber)->first();

//             if ($order) {
//                 $order->shipping_status = $status;

//                 if ($status === 'delivered') {
//                     $order->delivered_date = now();
//                 }

//                 $order->save();
//             }
//         }

//         return response()->json([
//             'data' => [
//                 'message' => [
//                     'success' => true,
//                     'description' => 'response message'
//                 ]
//             ]
//         ]);
//     } catch (\Exception $e) {
//         \Log::error('Smartship Webhook Error', ['message' => $e->getMessage()]);

//         return response()->json([
//             'data' => [
//                 'message' => [
//                     'success' => false,
//                     'description' => 'Webhook processing failed'
//                 ]
//             ]
//         ], 500);
//     }
// }













    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        $transactions = Recharge::where('seller_id', $sellerId)->latest()->get();
        $totalAmount = $seller && $seller->status == 1
            ? Recharge::where('seller_id', $seller->id)->sum('amount')
            : 0;
        return view('sellerdashboard.invoice.index', compact('transactions', 'seller', 'totalAmount'));
    }



        public function indexwarehouse()
    {
        //  echo "hello";die;
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

    $Wewarehouse = Warehouse::where('seller_id', $sellerId)->latest()->get();
    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;


        return view('sellerdashboard/warehouse/indexwarehouse', compact('seller', 'totalAmount','Wewarehouse'));
    }

    public function add()
    {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where('seller_id', $sellerId)->latest()->get();
        $totalAmount = $seller && $seller->status == 1
            ? Recharge::where('seller_id', $seller->id)->sum('amount')
            : 0;

        return view('sellerdashboard.warehouse.create', compact('transactions', 'seller', 'totalAmount'));
    }




    public function createWarehouse(Request $request)
    {
        $request->validate([
            'phone'            => 'required',
            'city'             => 'required',
            'name'             => 'required',
            'pin'              => 'required',
            'address'          => 'required',
            'country'          => 'required',
            'registered_name'  => 'required',
            'return_address'   => 'required',
            'return_pin'       => 'required',
            'return_city'      => 'required',
            'return_state'     => 'required',
        ]);


          $seller = Auth::guard('seller')->user();
          $sellerId = $seller?->id;
          $wewarehouse = new Warehouse();

          
          $wewarehouse->seller_id = $sellerId;   
            $wewarehouse->name = $request->name;



            $wewarehouse->phone = $request->phone;
            $wewarehouse->city = $request->city;
            $wewarehouse->pincode = $request->pin;
            $wewarehouse->address_title = $request->address;
            $wewarehouse->country = $request->country;
            $wewarehouse->email = $request->email ?? "";
            $wewarehouse->registered_name = $request->registered_name;
             $wewarehouse->return_address = $request->return_address;
            $wewarehouse->return_pin = $request->return_pin;
            $wewarehouse->return_city = $request->return_city;
            $wewarehouse->return_state = $request->return_state;
            $wewarehouse->return_country = $request->country;
            $wewarehouse->state = $request->state;

            $wewarehouse->address_line1 = $request->address;
            $wewarehouse->address_line2 = $request->address_two;

            $wewarehouse->save();
                return redirect()->route('seller.warehouse.index')->with('success', 'Warehouse created successfully!');
                //   return redirect()->back()->with('success', 'Warehouse created successfully!');
    }






    public function indexwarehouseedit($id)
{
    // dd($id);
            $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

    $warehouse = Warehouse::findOrFail($id);
    return view('sellerdashboard.warehouse.edit', compact('warehouse','totalAmount','seller'));
}




public function updateWarehouse(Request $request, $id)
{
    $request->validate([
        'phone'            => 'required',
        'city'             => 'required',
        'name'             => 'required',
        'pin'              => 'required',
        'address'          => 'required',
        'country'          => 'required',
        'registered_name'  => 'required',
        'return_address'   => 'required',
        'return_pin'       => 'required',
        'return_city'      => 'required',
        'return_state'     => 'required',
    ]);

    $seller = Auth::guard('seller')->user();
    $sellerId = $seller?->id;

    $warehouse = Warehouse::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();

    $warehouse->name = $request->name;
    $warehouse->phone = $request->phone;
    $warehouse->city = $request->city;
    $warehouse->pincode = $request->pin;
    $warehouse->address_title = $request->address;
    $warehouse->country = $request->country;
    $warehouse->email = $request->email ?? "";
    $warehouse->registered_name = $request->registered_name;
    $warehouse->return_address = $request->return_address;
    $warehouse->return_pin = $request->return_pin;
    $warehouse->return_city = $request->return_city;
    $warehouse->return_state = $request->return_state;
    $warehouse->return_country = $request->country;
    $warehouse->state = $request->state;

    $warehouse->address_line1 = $request->address;
    $warehouse->address_line2 = $request->address_two;

    $warehouse->save();

    return redirect()->route('seller.warehouse.index')->with('success', 'Warehouse updated successfully!');
}




public function deleteWarehouse($id)
{
    // dd($id);
    $seller = Auth::guard('seller')->user();
    $warehouse = Warehouse::where('id', $id)->where('seller_id', $seller->id)->first();

    if (!$warehouse) {
        return redirect()->back()->with('error', 'Warehouse not found or unauthorized access.');
    }

    $warehouse->delete();

    return redirect()->route('seller.warehouse.index')->with('success', 'Warehouse deleted successfully!');
}


}
