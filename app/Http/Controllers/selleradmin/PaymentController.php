<?php

namespace App\Http\Controllers\selleradmin;

use Illuminate\Support\Facades\Session;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\Recharge;
use App\Models\SellerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Order, Buyer, OrderItem, Warehouse, OrderPackageDetail, SellerList};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Helper\Helper;

class PaymentController extends Controller
{

public function redirectToPayU($id)
{
    $recharge = Recharge::findOrFail($id);
    dd($recharge);
    $MERCHANT_KEY = "your_merchant_key";
    $SALT = "your_salt";
    $PAYU_BASE_URL = "https://test.payu.in"; // use secure.payu.in for live

    $txnid = 'TXN_' . $recharge->id . '_' . time();
    $amount = $recharge->amount;
    $firstname = auth('seller')->user()->name;
    $email = auth('seller')->user()->email;
    $phone = auth('seller')->user()->phone ?? '9999999999';
    $productinfo = "Wallet Recharge";
    $successUrl = url('/payu-success?rid=' . $recharge->id);
    $failureUrl = url('/payu-failure?rid=' . $recharge->id);

    // Save txnid in DB if needed
    // $recharge->update(['txnid' => $txnid]);

    $hash_string = $MERCHANT_KEY . "|" . $txnid . "|" . $amount . "|" . $productinfo . "|" . $firstname . "|" . $email . "|||||||||||" . $SALT;
    $hash = strtolower(hash('sha512', $hash_string));

    return view('seller.payu_form', compact(
        'MERCHANT_KEY', 'txnid', 'amount', 'firstname', 'email', 'phone',
        'productinfo', 'successUrl', 'failureUrl', 'hash', 'PAYU_BASE_URL'
    ));
}



    // public function payuRequest(Request $request)
    // {
    //     $MERCHANT_KEY = "a00fed5b3162ffcc54b733ca2d9c7dcd371c3744584c0c02ee0b8d3e0c7a3c3b";
    //     $SALT = "H7fjhDB0DJmk7UwPOSVYmrZv8e0QjlYf";
    //     $PAYU_BASE_URL = "https://test.payu.in"; // For production: https://secure.payu.in

    //     $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
    //     $amount = 100; // ₹100
    //     $firstname = "Test User";
    //     $email = "test@example.com";
    //     $phone = "9999999999";
    //     $productinfo = "Demo Product";
    //     $successUrl = url('/payu-success');
    //     $failureUrl = url('/payu-failure');

    //     // Create hash string
    //     $hash_string = $MERCHANT_KEY . "|" . $txnid . "|" . $amount . "|" . $productinfo . "|" . $firstname . "|" . $email . "|||||||||||" . $SALT;
    //     $hash = strtolower(hash('sha512', $hash_string));

    //     // Pass all data to view
    //     return view('payu_form', compact(
    //         'MERCHANT_KEY', 'txnid', 'amount', 'firstname', 'email', 'phone', 'productinfo',
    //         'successUrl', 'failureUrl', 'hash', 'PAYU_BASE_URL'
    //     ));
    // }



    public function payuSuccess(Request $request)
{
    // Validate and store success response
    return response()->json([
        'status' => 'success',
        'data' => $request->all()
    ]);
}

public function payuFailure(Request $request)
{
    // dd($request);
    // Handle failed transaction
    return response()->json([
        'status' => 'failed',
        'data' => $request->all()
    ]);
}

}
