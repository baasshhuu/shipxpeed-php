<?php

namespace App\Http\Controllers\Frontend;

use App\Helper\Helper;
use App\Mail\SellerResetPasswordMail;
use App\Mail\WelcomeMail;
use App\Models\SellerList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Mail\SellerOtpMail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SellerBankDetail;
use App\Services\WhatsApp\AiSensyClient;
use App\Services\WhatsApp\WhatsAppOtpService;
use App\Services\WhatsApp\WhatsAppSettings;
use Illuminate\Support\Facades\Schema;

class SellerAuthController extends Controller
{


    public function showForgotPasswordForm()
    {
        return view('frontend.selleruser.forgot_password');
    }

    // Step 2: Handle sending Reset Link
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:seller_lists,email',
        ]);

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        Mail::to($request->email)->send(new SellerResetPasswordMail($token));

        return back()->with('success', 'We have emailed your password reset link!');
    }

    public function showResetPasswordForm($token)
    {
        return view('frontend.selleruser.reset_password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:seller_lists,email',
            'password' => 'required|confirmed|min:6',
            'token' => 'required'
        ]);

        $resetData = DB::table('password_resets')->where([
            ['email', $request->email],
            ['token', $request->token],
        ])->first();

        if (!$resetData) {
            return back()->with('error', 'Invalid token!');
        }


        DB::table('seller_lists')
            ->where('email', $request->email)
            ->update([
                'password' => bcrypt($request->password)
            ]);

        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect()->route('seller.login')->with('success', 'Your password has been reset successfully!');
    }

    public function sellerregister(Request $request)
    {
        return view('frontend.selleruser.register');
    }

    public function showLoginForm()
    {
        return view('frontend.selleruser.login');
    }


    public function sellerstore(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'user_type'         => 'required',
            'name'              => 'required',
            'email'             => 'required|email|unique:seller_lists,email',
            'phone_number'      => 'required',
            'password'          => 'required',
            'pan_card'          => 'nullable',
            'adhar_card_front'  => 'nullable',
            'adhar_card_back'   => 'nullable',
            'gst_no'            => 'nullable',
            'gst_photo'         => 'nullable',
            'cancel_cheque'     => 'nullable',
        ]);
// dd($validator->errors());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $waPhone = AiSensyClient::normalizePhone($request->phone_number);
        if (!$waPhone) {
            return response()->json(['errors' => ['phone_number' => ['Enter a valid 10-digit Indian mobile number.']]], 422);
        }
        if ($this->sellersByPhone($waPhone)->isNotEmpty()) {
            return response()->json(['errors' => ['phone_number' => ['This mobile number is already registered. Log in instead.']]], 422);
        }

        $panCardPath      = $request->hasFile('pan_card')         ? Helper::saveFile($request->file('pan_card'), 'seller') : null;
        $adharFrontPath   = $request->hasFile('adhar_card_front') ? Helper::saveFile($request->file('adhar_card_front'), 'seller') : null;
        $adharBackPath    = $request->hasFile('adhar_card_back')  ? Helper::saveFile($request->file('adhar_card_back'), 'seller') : null;
        $gstPhotoPath     = $request->hasFile('gst_photo')        ? Helper::saveFile($request->file('gst_photo'), 'seller') : null;
        $cancelChequePath = $request->hasFile('cancel_cheque')    ? Helper::saveFile($request->file('cancel_cheque'), 'seller') : null;

        $otp = rand(100000, 999999);


        Session::put('seller_temp_data', [
            'user_type'         => $request->user_type,
            'name'              => $request->name,
            'email'             => $request->email,
            'phone_number'      => $request->phone_number,
            'password'          => Hash::make($request->password),
            'pan_card'          => $panCardPath,
            'adhar_card_front'  => $adharFrontPath,
            'adhar_card_back'   => $adharBackPath,
            'gst_no'            => $request->gst_no,
            'gst_photo'         => $gstPhotoPath,
            'cancel_cheque'     => $cancelChequePath,
            'otp'               => $otp
        ]);

        // Mobile OTP (SMS or WhatsApp): verify the mobile number as part of sign-up
        $waRequired = false;
        if (WhatsAppSettings::otpEnabled()) {
            $waRes = app(WhatsAppOtpService::class)->send('signup', $waPhone, $request->name, $request->ip());
            if (!$waRes['ok']) {
                Session::forget('seller_temp_data');
                return response()->json(['errors' => ['phone_number' => [$waRes['message']]]], 422);
            }
            $waRequired = true;
        }
        Session::put('seller_temp_data.wa_phone', $waPhone);
        Session::put('seller_temp_data.wa_required', $waRequired);

        Mail::to($request->email)->send(new SellerOtpMail($otp));

        return response()->json([
            'message' => $waRequired
                ? 'Codes sent to your email and ' . (WhatsAppSettings::otpChannel() === 'sms' ? 'mobile (SMS)' : 'WhatsApp') . '. Please verify to complete registration.'
                : 'OTP sent to your email. Please verify to complete registration.',
            'redirect_url' => route('seller.verify.otp.view')
        ]);
    }
    public function verifyOtp(Request $request)
    {
       
        $enteredOtp = $request->otp;
        $sessionData = Session::get('seller_temp_data');

        if (!$sessionData) {
            return redirect()->back()->with('error', 'Session expired. Please register again.');
        }

        if ((string) $enteredOtp !== (string) $sessionData['otp']) {
            return redirect()->back()->with('error', 'Wrong email code. Please try again.');
        }

        $phoneVerified = false;
        if (!empty($sessionData['wa_required'])) {
            $wa = app(WhatsAppOtpService::class)->verify('signup', $sessionData['wa_phone'], (string) $request->mobile_otp);
            if (!$wa['ok']) {
                return redirect()->back()->with('error', $wa['message']);
            }
            $phoneVerified = true;
        }

        // Save seller data
        $seller = new SellerList();
        $seller->user_type         = $sessionData['user_type'];
        $seller->name              = $sessionData['name'];
        $seller->email             = $sessionData['email'];
        $seller->phone_number      = !empty($sessionData['wa_phone']) ? substr($sessionData['wa_phone'], 3) : $sessionData['phone_number'];
        $seller->password          = $sessionData['password'];
        $seller->pan_card          = $sessionData['pan_card'];
        $seller->adhar_card_front  = $sessionData['adhar_card_front'];
        $seller->adhar_card_back   = $sessionData['adhar_card_back'];
        $seller->gst_no            = $sessionData['gst_no'];
        $seller->gst_photo         = $sessionData['gst_photo'];
        $seller->cancel_cheque     = $sessionData['cancel_cheque'];
        $seller->status            = 0;
        $seller->kyc_status        = 0;
        if ($phoneVerified && Schema::hasColumn($seller->getTable(), 'phone_verified_at')) {
            $seller->phone_verified_at = now();
        }
        $seller->save();

        Session::forget('seller_temp_data');


        $site_settings = [
            'application_name' => 'Shipxpeed',
            'logo' => 'path/to/logo.png',
        ];

        // Send Welcome Email
        Mail::to($seller->email)->send(new WelcomeMail($seller->name, $site_settings));

        return redirect()->route('seller.login')->with('success', 'Registration completed successfully!');
    }



    public function completeKyc(Request $request)
{
    // dd($request);
    $validator = Validator::make($request->all(), [
        'bank_name'             => 'required',
        'bank_account_verified' => 'required',
        'gst_verified_name'     => 'nullable',
        'pan_verified_name'     => 'required',
        'account_number'        => 'required',
        'ifsc_code'             => 'required',
        'gst_number'            => 'nullable',
        'pan_number'            => 'required',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput()
            ->with('error', 'Please correct the errors in the form.');
    }

    try {
        $authSeller = Auth::guard('seller')->user();

        if (!$authSeller) {
            return redirect()->back()->with('error', 'Unauthorized or seller not found.');
        }

        $seller = SellerList::find($authSeller->id);

        if (!$seller) {
            return redirect()->back()->with('error', 'Seller not found in database.');
        }

        // Optional: Check if bank details already exist to prevent duplicate entry
        SellerBankDetail::updateOrCreate(
            ['seller_id' => $seller->id], // assumes there's a seller_id column
            [
                'account_number'      => $request->account_number,
                'account_holder_name' => $request->bank_name,
                'ifsc_code'           => $request->ifsc_code,
            ]
        );

        $seller->update([
            'bank_account_verified' => $request->bank_account_verified,
            'gst_verified_name'     => $request->gst_verified_name,
            'pan_verified_name'     => $request->pan_verified_name,
            'account_number'        => $request->account_number,
            'ifsc_code'             => $request->ifsc_code,
            'bank_name'             => $request->bank_name,
            'pan_number'            => $request->pan_number,
            'gst_no'                => $request->gst_number,
            'status'                => 1,
            'kyc_status'            => 1,
        ]);

        return redirect()->back()->with('success', 'KYC completed successfully.');
    } catch (\Exception $e) {
        // dd($e->getMessage());
        \Log::error('KYC Error: ' . $e->getMessage());

        return redirect()->back()
            ->with('error', 'Something went wrong while completing KYC.');
    }
}





// public function completeKyc(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'bank_name'             => 'required',
//         'bank_account_verified' => 'required',
//         'gst_verified_name'     => 'nullable',
//         'pan_verified_name'     => 'required',
//         'account_number'        => 'required',
//         'ifsc_code'             => 'required',
//         'gst_number'            => 'nullable',
//         'pan_number'            => 'required',
//     ]);

//     if ($validator->fails()) {
//         return redirect()->back()
//             ->withErrors($validator)
//             ->withInput()
//             ->with('error', 'Please correct the errors in the form.');
//     }

//     try {
//         $seller = Auth::guard('seller')->user();

//         if (!$seller) {
//             return redirect()->back()->with('error', 'Unauthorized or seller not found.');
//         }

//         $seller = SellerList::find($seller->id);

//         if (!$seller) {
//             return redirect()->back()->with('error', 'Seller not found in database.');
//         }


    

//         $seller->bank_account_verified = $request->bank_account_verified;
//         $seller->gst_verified_name     = $request->gst_verified_name;
//         $seller->pan_verified_name     = $request->pan_verified_name;
//         $seller->account_number        = $request->account_number;
//         $seller->ifsc_code             = $request->ifsc_code;
//         $seller->bank_name             = $request->bank_name;
//         $seller->pan_number                = $request->pan_number;
//         $seller->gst_no                = $request->gst_number;
//         $seller->status            = 1;
//         $seller->kyc_status            = 1;
//         $seller->save();

//         return redirect()->back()->with('success', 'KYC completed successfully.');
//     } catch (\Exception $e) {
//         // . $e->getMessage()
//         return redirect()->back()
//             ->with('error', 'Something went wrong: ' );
//     }
// }



//     public function completeKyc(Request $request)
//     {
//         // dd($request);
//         $validator = Validator::make($request->all(), [
//             'bank_name'         => 'required',
//             'bank_account_verified'          => 'required',
//             'gst_verified_name'  => 'nullable',
//             'pan_verified_name'   => 'required',
//             'account_number'            =>'required',
//             'ifsc_code'         => 'required',
//             'gst_number'     => 'nullable',
//             'pan_number'            => 'required',
         

//         ]);

//          if ($validator->fails()) {
//         return redirect()->back()
//             ->withErrors($validator)
//             ->withInput()
//             ->with('error', 'Please correct the errors in the form.');
//          }
// try {
//         $seller = Auth::guard('seller')->user();

//         if (!$seller) {
//             return redirect()->back()->with('error', 'Unauthorized or seller not found.');
//         }

//         $sellerId = Auth::guard('seller')->id();
//         $seller = SellerList::find($request->sellerId);

//         $seller->bank_account_verified = $request->bank_account_verified;
//         $seller->gst_verified_name = $request->gst_verified_name;
//         $seller->pan_verified_name = $request->pan_verified_name;
//         $seller->account_number = $request->account_number;
//         $seller->ifsc_code      = $request->ifsc_code;
//         $seller->bank_name      = $request->bank_name;
//         $seller->pan_no         = $request->pan_number;
//         $seller->kyc_status     = 1; // Mark KYC as complete
//         $seller->gst_no = $request->gst_number;
//         $seller->kyc_status = 1; // Mark KYC as complete
//         $seller->save();


//              return redirect()->back()->with('success', 'KYC completed successfully.');
//     } catch (\Exception $e) {
//         return redirect()->back()
//             ->with('error', 'Something went wrong: ' . $e->getMessage());
//     }

//     }



    // public function completeKyc(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'seller_id'         => 'required|exists:seller_lists,id',
    //         'pan_card'          => 'nullable|file',
    //         'adhar_card_front'  => 'nullable|file',
    //         'adhar_card_back'   => 'nullable|file',
    //         'gst_no'            => 'nullable|string',
    //         'gst_photo'         => 'nullable|file',
    //         'cancel_cheque'     => 'nullable|file',



    //         'kyc_type'            => 'nullable',
    //         'ie_Code'            => 'nullable',
    //         'ie_photo'            => 'nullable',
    //         'ad_Code'            => 'nullable',
    //         'ad_photo'            => 'nullable',

    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $seller = SellerList::find($request->seller_id);


        
    //     if ($request->hasFile('ie_photo')) {
    //         $seller->ie_photo = Helper::saveFile($request->file('ie_photo'), 'seller');
    //     }
        
    //     if ($request->hasFile('ad_photo')) {
    //         $seller->ad_photo = Helper::saveFile($request->file('ad_photo'), 'seller');
    //     }


    //     if ($request->hasFile('pan_card')) {
    //         $seller->pan_card = Helper::saveFile($request->file('pan_card'), 'seller');
    //     }
    //     if ($request->hasFile('adhar_card_front')) {
    //         $seller->adhar_card_front = Helper::saveFile($request->file('adhar_card_front'), 'seller');
    //     }
    //     if ($request->hasFile('adhar_card_back')) {
    //         $seller->adhar_card_back = Helper::saveFile($request->file('adhar_card_back'), 'seller');
    //     }
    //     if ($request->hasFile('gst_photo')) {
    //         $seller->gst_photo = Helper::saveFile($request->file('gst_photo'), 'seller');
    //     }
    //     if ($request->hasFile('cancel_cheque')) {
    //         $seller->cancel_cheque = Helper::saveFile($request->file('cancel_cheque'), 'seller');
    //     }

    //     $seller->gst_no = $request->gst_no;
    //     $seller->kyc_status = 1; // Mark KYC as complete
    //     $seller->save();

    //     return response()->json([
    //         'message' => 'KYC completed successfully.',
    //     ]);
    // }





    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('seller')->attempt($credentials)) {
            $request->session()->regenerate();


            if ($request->ajax()) {
                return response()->json(['redirect' => route('seller.dashboard')]);
            }

            return redirect()->route('seller.dashboard');
        }


        if ($request->ajax()) {
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }

        return back()->with('error', 'Invalid email or password');
    }

    /** Resend the sign-up WhatsApp code. */
    public function resendSignupWhatsappOtp(Request $request)
    {
        $data = Session::get('seller_temp_data');
        if (!$data || empty($data['wa_phone'])) {
            return response()->json(['message' => 'Session expired. Please register again.'], 419);
        }
        $res = app(WhatsAppOtpService::class)->send('signup', $data['wa_phone'], $data['name'] ?? 'Seller', $request->ip());
        return response()->json(['message' => $res['message'], 'retry_after' => $res['retry_after'] ?? null], $res['ok'] ? 200 : 429);
    }

    /** Login with WhatsApp OTP, step 1: send the code to a registered mobile. */
    public function sendLoginOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);
        if (!WhatsAppSettings::otpEnabled()) {
            return response()->json(['message' => 'Mobile OTP login is not available right now. Please log in with email.'], 503);
        }
        $phone = AiSensyClient::normalizePhone($request->phone);
        if (!$phone) {
            return response()->json(['errors' => ['phone' => ['Enter a valid 10-digit mobile number.']]], 422);
        }
        $sellers = $this->sellersByPhone($phone);
        if ($sellers->isEmpty()) {
            return response()->json(['errors' => ['phone' => ['No seller account uses this mobile number.']]], 422);
        }
        if ($sellers->count() > 1) {
            return response()->json(['errors' => ['phone' => ['More than one account uses this number. Please log in with email.']]], 422);
        }
        $res = app(WhatsAppOtpService::class)->send('login', $phone, $sellers->first()->name ?? 'Seller', $request->ip());
        return response()->json(['message' => $res['message'], 'retry_after' => $res['retry_after'] ?? null], $res['ok'] ? 200 : 429);
    }

    /** Login with WhatsApp OTP, step 2: check the code and sign in. */
    public function verifyLoginOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20', 'otp' => 'required|string|max:8']);
        $phone = AiSensyClient::normalizePhone($request->phone);
        $sellers = $phone ? $this->sellersByPhone($phone) : collect();
        if ($sellers->count() !== 1) {
            return response()->json(['message' => 'Account not found for this number.'], 422);
        }
        $res = app(WhatsAppOtpService::class)->verify('login', $phone, trim($request->otp));
        if (!$res['ok']) {
            return response()->json(['errors' => ['otp' => [$res['message']]]], 422);
        }

        $seller = $sellers->first();
        if (Schema::hasColumn($seller->getTable(), 'phone_verified_at') && empty($seller->phone_verified_at)) {
            $seller->phone_verified_at = now();
            $seller->save();
        }
        Auth::guard('seller')->login($seller);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('seller.dashboard')]);
    }

    /** Sellers whose stored mobile matches (stored as 10 digits, +91…, 91…, with spaces etc.). */
    private function sellersByPhone(string $normalized)
    {
        $ten = substr($normalized, -10);
        return SellerList::where('phone_number', 'like', '%' . $ten)->get()
            ->filter(fn ($s) => AiSensyClient::normalizePhone($s->phone_number) === $normalized)
            ->values();
    }

    public function logout(Request $request)
    {
        Auth::guard('seller')->logout(); // or Auth::logout() if you're not using guards
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('seller.login')->with('success', 'You have been logged out.');
    }





// public function sendOtp(Request $request)
// {
//     $request->validate([
//         'email' => 'required|email|unique:users,email',
//     ]);

//     $otp = rand(100000, 999999);
//     session([
//         'otp' => $otp,
//         'registration_data' => $request->all()
//     ]);

//     Mail::to($request->email)->send(new SignupOtpMail($otp));

//     return response()->json(['message' => 'OTP sent to your email.']);
// }




// public function verifyOtp(Request $request)
// {
//     if ($request->otp == session('otp')) {
//         $data = session('registration_data');
//         $user = User::create([
//             'name' => $data['name'],
//             'email' => $data['email'],
//             'phone_number' => $data['phone_number'],
//             'user_type' => $data['user_type'],
//             'password' => bcrypt($data['password']),
//         ]);

//         // Optionally send welcome mail
//         Mail::to($user->email)->send(new WelcomeMail($user));

//         session()->forget(['otp', 'registration_data']);

//         return response()->json(['message' => 'Registration successful!']);
//     }

//     return response()->json(['error' => 'Invalid OTP.'], 422);
// }


}
