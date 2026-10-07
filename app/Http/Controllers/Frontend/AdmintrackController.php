<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helper\Helper;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;




use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\Recharge;
use App\Models\SellerAgreement;
use App\Models\SellerBankDetail;

use App\Models\{Buyer, OrderItem, Warehouse, OrderPackageDetail, SellerList};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;

use App\Models\LogisticProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\Imports\OrdersImport;

class AdmintrackController extends Controller
{




public function trackOrder(Request $request)
{
    // dd($request);
    $request->validate([
        'awb' => 'required|string',
    ]);

    $awb = $request->input('awb');

    $order = Order::where('awb_number', $awb)->first();

    if (!$order) {
        return redirect()->back()->withErrors(['error' => 'Order not found for the given AWB number.']);
    }

    switch (strtolower($order->courier_id)) {
        case 'xpressbees':
            return $this->trackWithXpressBees($awb);
        case 'shadowfax':
            return $this->trackWithshadowfax($awb);
                    case 'parcelx':
            return $this->trackWithparcelx($awb);
        case 'delhivery_b2c':
            return $this->trackWithDelhiveryB2C($awb);
        default:
            return redirect()->back()->withErrors(['error' => 'Tracking not available for this courier.']);
    }
}



private function trackWithparcelx($awb)
{
    $seller = Auth::guard('seller')->user();
    $sellerId = Auth::guard('seller')->id();

    $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
        ->latest()
        ->take(5)
        ->get();

    $totalAmount = 0;

    if ($seller && $seller->status == 1) {

        $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
            ->where('type', 'Debit')
            ->sum('amount');

        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
    }

    // 🔐 ParcelX Token
    $token = "MzM3YTIyMDA4MzQ5ZTliNDNkNWI2NGE2ZmI1NjBjMzJjMzBhMDU0ZjVjM2I0NWE0MTEyNjIyMTk3MzpjYjMyNDFiM2NmNzZiZDJkYzNlNjZlZmMxOTM5ODQxMzJjOGI0ZWEzMmQzOWZkMzNjYWI0NmE3MmM1ZDliY2Y1ODRjNTk2YzhiMDdkMGJlZTFl";

    $url = "https://app.parcelx.in/api/v3/track_order?awb={$awb}";

    $response = Http::withHeaders([
        'access-token' => $token,
        'Accept' => 'application/json',
    ])
    ->timeout(30)
    ->get($url);

    \Log::info('ParcelX Track API Response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);

    if ($response->successful() && $response->json()) {

        $trackingData = $response->json();

        return view('sellerdashboard.trackorder', [
            'ParcelX' => $trackingData,
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'seller' => $seller,
        ]);
    }

    return redirect()->back()->withErrors([
        'error' => 'ParcelX tracking failed or invalid response.'
    ]);
}




// private function trackWithparcelx($clientRequestId)
// {


//       $seller = Auth::guard('seller')->user();
//         $sellerId = Auth::guard('seller')->id();
//         //dd($seller);
//         $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
//             ->latest()
//             ->take(5)
//             ->get();


//         $totalAmount = 0;
//         if ($seller && $seller->status == 1) {
//             // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
//             $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//                 ->where('status', 1)
//                 ->where('type', 'Credit')
//                 ->sum('amount');

//             $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//                 ->where('type', 'Debit')
//                 ->sum('amount');

//             $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//         }

//     $token = "fec1949bfc737bd52df914d18673e27b67a7f92d"; // Replace with real token or use env()

//     $response = Http::withHeaders([
//         'Authorization' => "Token $token",
//         'Accept' => 'application/json',
//     ])
//     ->timeout(30)
//     ->get("https://private-anon-9a4bb70501-sfxreversepickupsellerdelivery.apiary-mock.com/api/v4/clients/requests/$clientRequestId");

//     \Log::info('Shadowfax Track API Response', [
//         'status' => $response->status(),
//         'body' => $response->body(),
//     ]);
//     // dd($response->json());

//     if ($response->successful() && $response->json()) {
//         $trackingData = $response->json();

//         return view('sellerdashboard.trackorder', [
//             'Shadowfax' => $trackingData,
//             'transactions' => $transactions,
//             'totalAmount' => $totalAmount,
//             'seller' => $seller,
//         ]);
//     }

//     return redirect()->back()->withErrors(['error' => 'Shadowfax tracking failed or invalid response.']);
// }





private function trackWithDelhiveryB2C($awb)
{
    try {
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
            ->latest()
            ->take(5)
            ->get();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
        }

        if (empty($awb)) {
            throw new \Exception("AWB number is missing.");
        }

        $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';


//         $curl = curl_init();

// curl_setopt_array($curl, array(
//   CURLOPT_URL => 'https://track.delhivery.com/api/v2/packages/json?waybill=38528410015422&token=a6cd5bb955fddcb41757ec23ee92cf62b6650607',
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_ENCODING => '',
//   CURLOPT_MAXREDIRS => 10,
//   CURLOPT_TIMEOUT => 0,
//   CURLOPT_FOLLOWLOCATION => true,
//   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//   CURLOPT_CUSTOMREQUEST => 'GET',
//   CURLOPT_HTTPHEADER => array(
//     'Cookie: sessionid='
//   ),
// ));

// $response = curl_exec($curl);

// curl_close($curl);
// echo $response;
// die;

        
        // 🔑 Curl jaisa request: Token as query param
        $url = "https://track.delhivery.com/api/v2/packages/json?waybill=" . $awb . "&token=" . $token;
    //    dd($url);
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            // 'Cookie' => 'sessionid=az2jkgzegag48ne6pzxau4saw10orynn'
        ])->get($url);

        // dd($response->json());
        if ($response->successful()) {
            $responseData = $response->json();
            $shipmentData = $responseData['ShipmentData'][0]['Shipment'] ?? null;

            if (!$shipmentData) {
                throw new \Exception("No shipment data found.");
            }

            $scans = $shipmentData['Scans'] ?? [];

            $history = array_map(function ($scan) {
                $scanDetail = $scan['ScanDetail'] ?? [];
                return [
                    'event_time' => $scanDetail['ScanDateTime'] ?? '',
                    'message' => $scanDetail['Scan'] ?? '',
                    'location' => $scanDetail['ScannedLocation'] ?? '',
                ];
            }, $scans);

            $trackWithDelhiveryB2C = [
                'awb_number' => $shipmentData['AWB'] ?? 'N/A',
                'order_number' => $shipmentData['ReferenceNo'] ?? 'N/A',
                'order_id' => $shipmentData['ReferenceNo'] ?? 'N/A',
                'status' => $shipmentData['Status']['Status'] ?? 'N/A',
                'created' => $shipmentData['PickUpDate'] ?? 'N/A',
                'history' => $history,
            ];

            return view('sellerdashboard.trackorder', compact('trackWithDelhiveryB2C', 'transactions', 'totalAmount', 'seller'));
        } else {
            \Log::error('Delhivery B2C API error', ['response' => $response->body()]);
            throw new \Exception("Delhivery API responded with error.");
        }
    } catch (\Exception $e) {
        \Log::error('Delhivery B2C Exception', ['message' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'Error occurred during Delhivery B2C tracking.']);
    }
}



// private function trackWithDelhiveryB2C($awb)
// {
//     // dd($awb);
//     try {

//   $seller = Auth::guard('seller')->user();
//         $sellerId = Auth::guard('seller')->id();
//         //dd($seller);
//         $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
//             ->latest()
//             ->take(5)
//             ->get();


//         $totalAmount = 0;
//         if ($seller && $seller->status == 1) {
//             // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
//             $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//                 ->where('status', 1)
//                 ->where('type', 'Credit')
//                 ->sum('amount');

//             $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//                 ->where('type', 'Debit')
//                 ->sum('amount');

//             $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//         }


        

//         if (empty($awb)) {
//             throw new \Exception("AWB number is missing.");
//         }

//         $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';


//         $response = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//             'Content-Type' => 'application/json',
//         ])->get("https://track.delhivery.com/api/v1/packages/json?waybill=" . $awb);

// dd($response->json());
//         if ($response->successful()) {
//             $responseData = $response->json();
//         //    dd($responseData);
//             $shipmentData = $responseData['ShipmentData'][0]['Shipment'] ?? null;

//             if (!$shipmentData) {
//                 throw new \Exception("No shipment data found.");
//             }

//             $scans = $shipmentData['Scans'] ?? [];

//             $history = array_map(function ($scan) {
//                 $scanDetail = $scan['ScanDetail'] ?? [];
//                 return [
//                     'event_time' => $scanDetail['ScanDateTime'] ?? '',
//                     'message' => $scanDetail['Scan'] ?? '',
//                     'location' => $scanDetail['ScannedLocation'] ?? '',
//                 ];
//             }, $scans);

//             $trackWithDelhiveryB2C = [
//                 'awb_number' => $shipmentData['AWB'] ?? 'N/A',
//                 'order_number' => $shipmentData['ReferenceNo'] ?? 'N/A',
//                 'order_id' => $shipmentData['ReferenceNo'] ?? 'N/A',
//                 'status' => $shipmentData['Status']['Status'] ?? 'N/A',
//                 'created' => $shipmentData['PickUpDate'] ?? 'N/A',
//                 'history' => $history,
//             ];
//         //  dd($trackWithDelhiveryB2C);
//             return view('sellerdashboard.trackorder', compact('trackWithDelhiveryB2C', 'transactions', 'totalAmount', 'seller'));
//         } else {
//             \Log::error('Delhivery B2C API error', ['response' => $response->body()]);
//             throw new \Exception("Delhivery API responded with error.");
//         }
//     } catch (\Exception $e) {
//         // dd($e->getMessage());
//         \Log::error('Delhivery B2C Exception', ['message' => $e->getMessage()]);
//         return redirect()->back()->withErrors(['error' => 'Error occurred during Delhivery B2C tracking.']);
//     }
// }


// private function trackWithDelhiveryB2C($awb)
// {
//     try {
//         $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607'; // use your token

//         $response = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//             'Content-Type' => 'application/json',
//         ])->get("https://track.delhivery.com/api/v1/packages/json/", [
//             'waybill' => $awb,
//             'ref_ids' => '',
//         ]);

//         if ($response->successful()) {
//             $responseData = $response->json();

//             $shipmentData = $responseData['ShipmentData'][0]['Shipment'] ?? null;

//             if (!$shipmentData) {
//                 return redirect()->back()->withErrors(['error' => 'No shipment data found.']);
//             }

//             $scans = $shipmentData['Scans'] ?? [];

//             $history = array_map(function ($scan) {
//                 $detail = $scan['ScanDetail'] ?? [];
//                 return [
//                     'event_time' => $detail['ScanDateTime'] ?? '',
//                     'message' => $detail['Scan'] ?? '',
//                     'location' => $detail['ScannedLocation'] ?? '',
//                 ];
//             }, $scans);

//             $trackWithDelhiveryB2C = [
//                 'awb_number' => $shipmentData['AWB'] ?? 'N/A',
//                 'order_number' => $shipmentData['ReferenceNo'] ?? 'N/A',
//                 'order_id' => $shipmentData['ReferenceNo'] ?? 'N/A',
//                 'status' => $shipmentData['Status']['Status'] ?? 'N/A',
//                 'created' => $shipmentData['PickUpDate'] ?? 'N/A',
//                 'history' => $history,
//             ];
//             // dd($trackWithDelhiveryB2C);

//             return view('frontend.website.track-order', compact('trackWithDelhiveryB2C'));
//         } else {
//             \Log::error('Delhivery B2C Error', ['body' => $response->body()]);
//             return redirect()->back()->withErrors(['error' => 'Delhivery B2C tracking failed.']);
//         }
//     } catch (\Exception $e) {
//         \Log::error('Delhivery B2C Exception', ['message' => $e->getMessage()]);
//         return redirect()->back()->withErrors(['error' => 'Error occurred during Delhivery B2C tracking.']);
//     }
// }



// private function trackWithDelhiveryB2C($awb)
// {
//     try {
//         $token = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607'; // Replace with your real token
//         $response = Http::withHeaders([
//             'Authorization' => 'Token ' . $token,
//             'Content-Type' => 'application/json',
//         ])->get("https://track.delhivery.com/api/v1/packages/json/?waybill=", [
//             'waybill' => $awb,
//             'ref_ids' => '',
//         ]);

//         if ($response->successful()) {
//             $data = $response->json();
// dd($data);
//             // Prepare data for Blade
//             $shipment = [
//                 'awb_number' => $awb,
//                 'order_number' => $data['shipment']['order_id'] ?? 'N/A',
//                 'order_id' => $data['shipment']['ref_id'] ?? 'N/A',
//                 'status' => $data['shipment']['status']['status'] ?? 'N/A',
//                 'created' => $data['shipment']['status']['status_date'] ?? 'N/A',
//                 'history' => $data['shipment']['status']['status_history'] ?? [],
//             ];

//             return view('frontend.website.track-order', compact('shipment'));
//         } else {
//             \Log::error('Delhivery B2C Tracking Failed', ['body' => $response->body()]);
//             return redirect()->back()->withErrors(['error' => 'Delhivery B2C tracking failed.']);
//         }
//     } catch (\Exception $e) {
//         \Log::error('Delhivery B2C Tracking Exception', ['message' => $e->getMessage()]);
//         return redirect()->back()->withErrors(['error' => 'An error occurred while tracking Delhivery B2C.']);
//     }
// }



//     public function trackOrder(Request $request)
// {
//     $request->validate([
//         'awb' => 'required|string',
//     ]);

//     $awb = $request->input('awb');

//     // Step 1: Match order in DB
//     $order = Order::where('awb_number', $awb)->first();

//     if (!$order) {
//         return redirect()->back()->withErrors(['error' => 'Order not found for the given AWB number.']);
//     }

//     // Step 2: Decide based on courier name
//     switch (strtolower($order->courier_id)) {
//         case 'xpressbees':
//             return $this->trackWithXpressBees($awb);
//         case 'shadowfax':
//             return $this->trackWithshadowfax($awb);
//         case 'ecom':
//             return $this->trackWithEcom($awb);
//         default:
//             return redirect()->back()->withErrors(['error' => 'Tracking not available for this courier.']);
//     }
// }





private function trackWithXpressBees($awb)
{


  $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        //dd($seller);
        $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
            ->latest()
            ->take(5)
            ->get();


        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }



    $token = Helper::getXpressBeesTokenuse();

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ])
    ->withoutVerifying()
    ->timeout(30)
    ->get("https://shipment.xpressbees.com/api/shipments2/track/$awb");

    \Log::info('XpressBees Track API Response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]); 
// dd($response->json('data'));
    if ($response->successful() && $response->json('status') === true) {
        return view('sellerdashboard.trackorder', [
            'shipment' => $response->json('data'),
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'seller' => $seller,
        ]);
    }

    $errorMessage = $response->json('message') ?? 'Unable to fetch tracking data.';
    return redirect()->back()->withErrors(['error' => $errorMessage]);
}



private function trackWithShadowfax($clientRequestId)
{


      $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        //dd($seller);
        $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
            ->latest()
            ->take(5)
            ->get();


        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }

    $token = "fec1949bfc737bd52df914d18673e27b67a7f92d"; // Replace with real token or use env()

    $response = Http::withHeaders([
        'Authorization' => "Token $token",
        'Accept' => 'application/json',
    ])
    ->timeout(30)
    ->get("https://private-anon-9a4bb70501-sfxreversepickupsellerdelivery.apiary-mock.com/api/v4/clients/requests/$clientRequestId");

    \Log::info('Shadowfax Track API Response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);
    // dd($response->json());

    if ($response->successful() && $response->json()) {
        $trackingData = $response->json();

        return view('sellerdashboard.trackorder', [
            'Shadowfax' => $trackingData,
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'seller' => $seller,
        ]);
    }

    return redirect()->back()->withErrors(['error' => 'Shadowfax tracking failed or invalid response.']);
}









private function trackWithEcom($awb)
{



      $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();
        //dd($seller);
        $transactions = Recharge::where(['seller_id' => $sellerId, 'status' => '1'])
            ->latest()
            ->take(5)
            ->get();


        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }

    $token = config('services.delhivery.token');

    $response = Http::withHeaders([
        'Authorization' => "Token $token"
    ])
    ->get("https://track.delhivery.com/api/v1/packages/json/?waybill=$awb");

    \Log::info('Delhivery Track API Response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);

    if ($response->successful() && isset($response['ShipmentData'][0]['Shipment'])) {
        return view('sellerdashboard.trackorder', [
            'shipment' => $response['ShipmentData'][0]['Shipment'],
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'seller' => $seller,
        ]);
    }

    return redirect()->back()->withErrors(['error' => 'Delhivery tracking failed.']);
}


    // public function trackOrder(Request $request)
    // {
    //     $request->validate([
    //         'awb' => 'required|string',
    //     ]);

    //     $awb = $request->input('awb');

    //     // Get token from .env
    //     // $token = env('XPRESSBEES_API_TOKEN');
    //     $token = Helper::getXpressBeesTokenuse();

    //     $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $token,
    //             'Accept' => 'application/json',
    //         ])
    //         ->withoutVerifying()
    //         ->timeout(30)
    //         ->get("https://shipment.xpressbees.com/api/shipments2/track/$awb");

    //     \Log::info('XpressBees Track API Response', [
    //         'status' => $response->status(),
    //         'body' => $response->body(),
    //     ]);

    //     if ($response->successful() && $response->json('status') === true) {
    //         return view('frontend.website.pages.trackorder', [
    //             'shipment' => $response->json('data'),
    //         ]);
    //     } else {
    //         $errorMessage = $response->json('message') ?? 'Unable to fetch tracking data.';
    //         return redirect()->back()->withErrors(['error' => $errorMessage]);
    //     }
    // }
}
