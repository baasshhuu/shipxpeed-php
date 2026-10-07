<?php

namespace App\Http\Controllers\Common;

use Razorpay\Api\Api;
use App\Models\Ledger;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorPayController extends Controller
{
    public static function razorpay(Request $request): JsonResponse
    {
        $razorpay_key       = $request->site_settings['razorpay_key'];
        $razorpay_secret    = $request->site_settings['razorpay_secret'];

        $api = new Api($razorpay_key, $razorpay_secret);
        $orderData  = $api->order->create([
            'receipt'   => Str::random(30),
            'amount'    => $request->amount * 100,
            'currency'  => 'INR'
        ]);

        $data = [
            "key"               => $razorpay_key,
            "amount"            => $request->amount * 100,
            "order_id"          => $orderData['id'],
            "currency"          => 'INR'
        ];
        return response()->json([
            'status'    => true,
            "message"   => "Order Created Successfully.",
            'data'      => $data
        ], 200);
    }

    public static function verify(Request $request): JsonResponse
    {
        $success    = true;
        $error      = "Payment Failed!";
        $razorpay_key       = $request->site_settings['razorpay_key'];
        $razorpay_secret    = $request->site_settings['razorpay_secret'];
        $payment            = null;
        if (empty($request->razorpay_payment_id) === false) {
            $api = new Api($razorpay_key, $razorpay_secret);
            try {
                $attributes = [
                    'razorpay_order_id'     => $request->razorpay_order_id,
                    'razorpay_payment_id'   => $request->razorpay_payment_id,
                    'razorpay_signature'    => $request->razorpay_signature
                ];
                $api->utility->verifyPaymentSignature($attributes);

                $payment = $api->payment->fetch($request->razorpay_payment_id);
            } catch (SignatureVerificationError $e) {
                $success    = false;
                $error      = 'Razorpay Error : ' . $e->getMessage();
            }
        }

        if ($success === true && $payment) {
            $payment = $payment->toArray();

            if ($request->site_settings['is_commision'] == 1) {
                $amount = $payment['amount'] / 100;
            } else {
                $amount = ($payment['amount'] - $payment['fee']) / 100;
            }

            $checkPaymentEntry = Ledger::where('trans_details_json', 'like', '%' . request('razorpay_order_id') . '%')
                ->orWhere('trans_details_json', 'like', '%' . request('razorpay_payment_id') . '%')
                ->first();

            if ($checkPaymentEntry) {
                return response()->json([
                    'status'    => false,
                    'message'   => "Amount Already added to wallet.",
                ]);
            }

            $data = [
                'amount'                => $amount,
                'payment_type'          => 1,
                'payment_method'        => 3,
                'particulars'           => "Online Wallet Load",
                'trans_details_json'    =>  json_encode($payment)
            ];

            if (LedgerController::add($request->user_id, $request->user_type, $data)) {
                return response()->json([
                    'status'    => true,
                    'message'   => 'Ledger Entry Added Successfully',
                ]);
            } else {
                return response()->json([
                    'status'    => false,
                    'message'   => 'Oops..!! There is some error.',
                ]);
            }
        } else {
            return response()->json([
                'status'    => false,
                'message'   => $error,
            ]);
        }
    }
}
