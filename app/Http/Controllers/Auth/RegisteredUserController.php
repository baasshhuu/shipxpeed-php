<?php

namespace App\Http\Controllers\Auth;

use App\Models\Company;
use Illuminate\View\View;
use App\Rules\CheckUnique;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\SendWelComeEmail;
use Illuminate\Support\Carbon;
use App\Models\RegistrationOtp;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', new CheckUnique('companies')],
            'mobile'        => ['required', 'digits:10', new CheckUnique('companies'), 'regex:' . config('constant.phoneRegExp')],
            'password'      => ['required', 'string', 'min:8', 'confirmed', Rules\Password::defaults()],
            'otp'           => ['required', 'numeric', 'digits:6'],
        ], [
            'mobile.regex'  => "Please enter valid indian mobile number."
        ]);

        $checkOtp = RegistrationOtp::firstWhere(['mobile' => $request->mobile, 'otp' => $request->otp]);
        if (!$checkOtp) {
            throw ValidationException::withMessages([
                'otp' => 'Incorrect OTP..!!',
            ]);
        }

        if ($checkOtp && Carbon::now()->isAfter($checkOtp->expire_at)) {
            throw ValidationException::withMessages([
                'otp' => 'Your OTP has been expired',
            ]);
        }

        $user = Company::create([
            'slug'                  => Str::uuid(),
            'name'                  => $request->name,
            'email'                 => $request->email,
            'mobile'                => $request->mobile,
            'status'                => 1,
            'image'                 => 'admin/avatar.png',
            'registor_from'         => 2,
            'password'              => Hash::make($request->password),
        ]);

        // event(new Registered($user));

        RegistrationOtp::where('mobile', $request->mobile)->delete();
        SendWelComeEmail::dispatch($user, $request->site_settings);
        return to_route('loginPage', 'company')->withSuccess("Successfully Registered, Please Login..!!");
    }
}
