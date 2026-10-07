<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\LogisticProvider;
use App\Models\Order;
use App\Models\PriceSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use OpenAPI\Client\Api\OrdersApi;
use OpenAPI\Client\Configuration;
use OpenAPI\Client\Model\CreateOrderRequest;
use OpenAPI\Client\Model\OrderMeta;
use OpenAPI\Client\Model\CustomerDetails;
class CashfreeController extends Controller
{



    public function submitKyc(Request $request)
{
    dd($request);
    $validated = $request->validate([
        'account_number' => 'required|string',
        'ifsc_code'      => 'required|string',
        'gst_number'     => 'required|string|max:15',
        'pan_number'     => 'required|string|max:10',
    ]);

    // Save KYC data
    KycDetail::create([
        'seller_id'      => auth()->id(),
        'account_number' => $validated['account_number'],
        'ifsc_code'      => $validated['ifsc_code'],
        'gst_number'     => $validated['gst_number'],
        'pan_number'     => $validated['pan_number'],
    ]);

    return back()->with('success', 'KYC submitted successfully!');
}
private function getCashfreeBaseUrl()
{
    return env('CASHFREE_ENV') === 'production'
        ? 'https://api.cashfree.com/pg'
        : 'https://sandbox.cashfree.com/pg';
}
   
private function cashfreeHeaders()
{
    return [

        'x-client-id' => env('CASHFREE_CLIENT_ID'),

        'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),

        'x-api-version' => '2025-01-01',

        'Content-Type' => 'application/json',

        'Accept' => 'application/json',

    ];
}
public function redirectToCashfree($id)
{
    $recharge = Recharge::findOrFail($id);

    $seller = Auth::guard('seller')->user();

    // Existing order ko reuse karo ya naya banao
    $orderId = $recharge->cashfree_order_id;

    if (empty($orderId)) {
        $orderId = 'CF_' . $recharge->id . '_' . time();

        $recharge->update([
            'cashfree_order_id' => $orderId
        ]);
    }

    $baseUrl = $this->getCashfreeBaseUrl();

    $response = Http::withHeaders($this->cashfreeHeaders())
        ->post($baseUrl . '/orders', [

            "order_id" => $orderId,

            "order_amount" => (float)$recharge->amount,

            "order_currency" => "INR",

            "customer_details" => [

                "customer_id" => (string)$seller->id,

                "customer_name" => $seller->name,

                "customer_email" => $seller->email,

                "customer_phone" => $seller->phone ?? "9999999999"

            ],

            "order_meta" => [

                "return_url" => route('cashfree.return') . "?order_id={order_id}"

            ]

        ]);

    if (!$response->successful()) {

        return back()->with('error', 'Unable to initiate payment.');
    }

    $json = $response->json();

    if (!isset($json['payment_session_id'])) {

        return back()->with('error', 'Payment Session ID not received.');
    }

    return view('seller.cashfree.checkout', [

        'paymentSessionId' => $json['payment_session_id']

    ]);
}
public function verifyBankAccount(Request $request)
{
    $request->validate([
        'account_number' => 'required|string',
        'ifsc'           => 'required|string',
    ]);

    $referenceId = Str::uuid()->toString();

    $base = env('CASHFREE_ENV') === 'production'
        ? 'https://api.cashfree.com'
        : 'https://sandbox.cashfree.com';

    // <-- THE IMPORTANT FIX
    $url = $base . '/verification/bank-account/sync';

    $res = Http::withHeaders([
        'x-client-id'     => env('CASHFREE_CLIENT_ID'),
        'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),
        'x-api-version'   => '2023-01-01',
        'accept'          => 'application/json',
        'content-type'    => 'application/json',
    ])->post($url, [
        'bank_account' => $request->account_number,
        'ifsc'         => $request->ifsc,
        'reference_id' => $referenceId,
        // Optional if your plan supports:
        // 'name' => $request->account_holder_name,
        // 'phone' => $request->phone,
    ]);

    $json = $res->json();

    if ($res->successful()) {
        return response()->json([
            'ok'   => true,
            'data' => [
                'status'       => data_get($json, 'status', 'SUCCESS'),
                'name_at_bank' => data_get($json, 'name_at_bank'),
                'bank_account' => data_get($json, 'bank_account'),
                'ifsc'         => data_get($json, 'ifsc'),
                'reference_id' => $referenceId,
                'raw'          => $json,
            ]
        ]);
    }

    return response()->json([
        'ok'    => false,
        'error' => data_get($json, 'message', 'Bank account verification failed'),
        'raw'   => $json,
    ], $res->status() ?: 400);
}






public function verifyCIN(Request $request)
{
    $request->validate([
        'cin_number' => 'required|string|max:21', // CIN format length
    ]);

    $cin = $request->cin_number;

    $url = env('CASHFREE_ENV') === 'production' 
        ? 'https://api.cashfree.com/verification/api/v1/cin'
        : 'https://sandbox.cashfree.com/verification/api/v1/cin';

    $response = Http::withHeaders([
        'x-client-id'     => env('CASHFREE_CLIENT_ID'),
        'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),
        'x-api-version'   => '2023-01-01',
        'accept'          => 'application/json',
        'content-type'    => 'application/json',
    ])->post($url, [
        'cin' => $cin,
    ]);

    if ($response->successful()) {
        $data = $response->json();
        return response()->json(['ok' => true, 'data' => $data]);
    } else {
        return response()->json(['ok' => false, 'error' => $response->json('message') ?? 'CIN verification failed']);
    }
}




    public function verifyGST(Request $request)
{
    $request->validate([
        'gst_number' => 'required|string|max:15',
    ]);

    $gstin = $request->gst_number;
    $base = env('CASHFREE_ENV') === 'production'
        ? 'https://api.cashfree.com'
        : 'https://sandbox.cashfree.com';

    $url = $base . '/verification/gstin';

    try {
        $res = Http::withHeaders([
            'x-client-id'     => env('CASHFREE_CLIENT_ID'),
            'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),
            'accept'          => 'application/json',
            'content-type'    => 'application/json',
        ])->post($url, ['gstin' => $gstin]);

        $data = $res->json();
        //  dd($data);
        if ($res->successful() && !empty($data['valid']) && $data['valid'] === true) {
            return response()->json(['ok' => true, 'data' => $data]);
        }

        return response()->json([
            'ok'    => false,
            'error' => $data['message'] ?? 'GST verification failed',
            'data'  => $data,
        ], $res->status() ?: 400);

    } catch (\Throwable $e) {
        return response()->json([
            'ok'    => false,
            'error' => 'Error during GST verification: ' . $e->getMessage(),
        ], 500);
    }
}






  
public function verifyPan(Request $request)
{
    // 1) Validate PAN format
    $request->validate([
        'pan_number' => ['required', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/']
    ], [
        'pan_number.regex' => 'Invalid PAN format'
    ]);

    $pan = $request->pan_number;

    // 2) Pick correct URL (sandbox/prod)
    $production = (bool) env('CASHFREE_PRODUCTION', false);
    $url = $production
        ? 'https://api.cashfree.com/verification/pan'
        : 'https://sandbox.cashfree.com/verification/pan';

    try {
        // 3) Make the call
        $res = Http::withHeaders([
            'x-client-id'     => env('CASHFREE_CLIENT_ID'),
            'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),
            'Content-Type'    => 'application/json',
        ])->post($url, [
            'pan' => $pan,
        ]);

        $json = $res->json();

        // 4) Normalize success condition
        $isValid = data_get($json, 'valid') === true
            || strtoupper((string) data_get($json, 'status')) === 'VALID';

        if ($res->successful() && $isValid) {
            return response()->json([
                'ok'      => true,
                'message' => data_get($json, 'message', 'PAN verified successfully'),
                'data'    => [
                    'pan'             => data_get($json, 'pan'),
                    'type'            => data_get($json, 'type'),
                    'reference_id'    => data_get($json, 'reference_id'),
                    'registered_name' => data_get($json, 'registered_name'),
                    'father_name'     => data_get($json, 'father_name'),
                    'valid'           => data_get($json, 'valid'),
                ],
            ]);
        }

        // 5) Error case
        return response()->json([
            'ok'    => false,
            'error' => data_get($json, 'message', 'PAN verification failed'),
            'data'  => $json,
        ], $res->status() ?: 400);

    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'ok'    => false,
            'error' => 'Something went wrong while verifying PAN',
        ], 500);
    }
}




//     public function verifyPan(Request $request)
// {
//     $panNumber = $request->pan_number;

//     $response = Http::withHeaders([
//         'x-client-id'     => env('CASHFREE_CLIENT_ID'),
//         'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),
//         'Content-Type'    => 'application/json'
//     ])->post('https://api.cashfree.com/verification/pan', [
//         'pan' => $panNumber
//     ]);

//     $result = $response->json();
//      dd($result);

//     if (isset($result['status']) && $result['status'] === 'VALID') {
//         return response()->json([
//             'ok'   => true,
//             'data' => $result,
//             'message' => 'PAN verified successfully'
//         ]);
//     }

//     return response()->json([
//         'ok'    => false,
//         'error' => $result['message'] ?? 'Verification failed'
//     ]);
// }



// public function verifyPan(Request $request)
// {
//     $panNumber = $request->pan_number;

//     $response = Http::withHeaders([
//         'x-client-id'     => env('CASHFREE_CLIENT_ID'),  // Add your Cashfree Client ID
//         'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),  // Add your Cashfree Client Secret
//         'Content-Type'    => 'application/json'
//     ])->post('https://api.cashfree.com/verification/pan', [
//         'pan' => $panNumber
//     ]);

//     $result = $response->json();

//     if (isset($result['status']) && $result['status'] === 'VALID') {
//         return response()->json([
//             'ok'   => true,
//             'data' => $result
//         ]);
//     }

//     return response()->json([
//         'ok'    => false,
//         'error' => $result['message'] ?? 'Verification failed'
//     ]);
// }







public function generateOtp(Request $request)
{   
    // dd($request);
    $request->validate(['aadhaar_number' => 'required|digits:12']);
    
    $response = $this->sendOtpToCashfree($request->aadhaar_number);
    //  dd($response);
    
    // If the Cashfree API call was successful, return the actual data
    if ($response['ok']) {
        return response()->json([
            'ok' => true,
            'data' => $response['data'],
            'message' => $response['data']['message'] ?? 'OTP sent successfully'
        ]);
    }
    
    // Handle specific error cases
    $errorMessage = $response['error']['message'] ?? 'Failed to send OTP';
    $statusCode = $response['error']['status'] ?? 400;
    
    // For temporary server issues, suggest retry
    if ($statusCode >= 500 || strpos($errorMessage, 'temporarily unavailable') !== false) {
        $errorMessage = 'Service temporarily unavailable. Please wait a moment and try again.';
        $statusCode = 503; // Service Unavailable
    }
    
    return response()->json([
        'ok' => false,
        'error' => $errorMessage,
        'details' => $response['error'],
        'suggestion' => $statusCode >= 500 ? 'Please try again in a few minutes' : null
    ], $statusCode);
}

public function verifyOtp(Request $request)
{
    $request->validate([
        'ref_id' => 'required|string',
        'otp'    => 'required|digits:6'
    ]);

    $response = $this->verifyOtpWithCashfree($request->ref_id, $request->otp);
    // dd($response);
   
    // If the Cashfree API call was successful, return the actual data
    if ($response['ok']) {
        return response()->json([
            'ok' => true,
            'data' => $response['data'],
            'message' => $response['data']['message'] ?? 'OTP verified successfully'
        ]);
    }
    
    // If there was an error, return the error details
    return response()->json([
        'ok' => false,
        'error' => $response['error']['message'] ?? 'Failed to verify OTP',
        'details' => $response['error']
    ], $response['error']['status'] ?? 400);
}











/**
 * Cashfree पर Aadhaar के लिए OTP भेजो
 *
 * @param string $aadhaarNumber
 * @return array ['ok' => bool, 'data' => array|null, 'error' => array|null]
 */
private function sendOtpToCashfree(string $aadhaarNumber): array
{
    $production = (bool) env('CASHFREE_PRODUCTION', false);

    $url = $production
        ? 'https://api.cashfree.com/verification/offline-aadhaar/otp'
        : 'https://sandbox.cashfree.com/verification/offline-aadhaar/otp';

    $payload = [
        'aadhaar_number' => $aadhaarNumber,
    ];

    return $this->cashfreePost($url, $payload);
}

/**
 * Cashfree पर OTP verify करो
 *
 * @param string $refId
 * @param string $otp
 * @return array ['ok' => bool, 'data' => array|null, 'error' => array|null]
 */
private function verifyOtpWithCashfree(string $refId, string $otp): array
{
    $production = (bool) env('CASHFREE_PRODUCTION', false);

    $url = $production
        ? 'https://api.cashfree.com/verification/offline-aadhaar/verify'
        : 'https://sandbox.cashfree.com/verification/offline-aadhaar/verify';

    $payload = [
        'ref_id' => $refId,
        'otp'    => $otp,
    ];

    return $this->cashfreePost($url, $payload);
}

/**
 * Low-level POST helper for Cashfree
 */



private function cashfreePost(string $url, array $payload): array
{
    // dd($url, $payload);
    $clientId     = env('CASHFREE_CLIENT_ID');
    $clientSecret = env('CASHFREE_CLIENT_SECRET');

    $headers = [
        'Content-Type: application/json',
        'x-client-id: ' . $clientId,
        'x-client-secret: ' . $clientSecret,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT        => 40,
    ]);

    $raw  = curl_exec($ch);
    $err  = curl_errno($ch);
    $msg  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        return [
            'ok'    => false,
            'data'  => null,
            'error' => [
                'type'    => 'network_error',
                'code'    => $err,
                'message' => $msg,
                'status'  => $http,
                'raw'     => $raw,
            ],
        ];
    }

    $json = json_decode($raw, true);
    //  dd($json);
    if ($http < 200 || $http >= 300) {
        // Handle different error response formats from Cashfree
        $errorMessage = 'Unknown error';
        
        if (isset($json['message'])) {
            $errorMessage = $json['message'];
        } elseif (isset($json['error_msg'])) {
            $errorMessage = $json['error_msg'];
        } elseif (isset($json['error']['message'])) {
            $errorMessage = $json['error']['message'];
        }
        
        // Provide user-friendly messages for common errors
        if ($http == 502 || strpos($errorMessage, 'temporarily unavailable') !== false) {
            $errorMessage = 'Cashfree service is temporarily unavailable. Please try again in a few minutes.';
        } elseif ($http == 500) {
            $errorMessage = 'Cashfree server error. Please try again later.';
        } elseif ($http == 429) {
            $errorMessage = 'Too many requests. Please wait a moment and try again.';
        } elseif (strpos($errorMessage, 'Otp generated for this aadhaar') !== false) {
            $errorMessage = 'An OTP was already sent for this Aadhaar number. Please wait before requesting a new OTP or use the existing one.';
        }
        
        return [
            'ok'    => false,
            'data'  => null,
            'error' => [
                'type'    => $json['type']    ?? 'api_error',
                'code'    => $json['code']    ?? 'unknown_error',
                'message' => $errorMessage,
                'status'  => $http,
                'raw'     => $json,
            ],
        ];
    }

    return [
        'ok'    => true,
        'data'  => $json,
        'error' => null,
    ];
    dd([
    'url' => $url,
    'payload' => $payload,
    'http' => $http,
    'response' => $json,
]);
}


public function createPayment($id)
{
    $recharge = Recharge::findOrFail($id);

    $seller = auth('seller')->user();

    $orderId = 'ORDER_' . $recharge->id . '_' . time();

    $response = Http::withHeaders([
        'x-client-id'     => env('CASHFREE_CLIENT_ID'),
        'x-client-secret' => env('CASHFREE_CLIENT_SECRET'),
        'x-api-version'   => '2023-08-01',
        'Content-Type'    => 'application/json',
    ])->post(
        env('CASHFREE_ENV') == 'production'
            ? 'https://api.cashfree.com/pg/orders'
            : 'https://sandbox.cashfree.com/pg/orders',

        [
            "order_id" => $orderId,
            "order_amount" => $recharge->amount,
            "order_currency" => "INR",

            "customer_details" => [
                "customer_id" => (string)$seller->id,
                "customer_name" => $seller->name,
                "customer_email" => $seller->email,
                "customer_phone" => $seller->phone,
            ],

            "order_meta" => [
                "return_url" => route('cashfree.success', [
                    'id' => $recharge->id
                ]) . "?order_id={order_id}"
            ]
        ]
    );

    if (!$response->successful()) {

        return back()->withErrors($response->json());
    }

    $data = $response->json();

    $recharge->update([
        'code' => $orderId
    ]);

    return redirect($data['payment_link']);
}

  

}
