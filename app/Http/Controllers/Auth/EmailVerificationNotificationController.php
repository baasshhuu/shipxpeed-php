<?php

namespace App\Http\Controllers\Auth;

use App\Helper\Helper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\UserVerification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;

class EmailVerificationNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('mail');
    }

    public function store(Request $request): RedirectResponse
    {
        $guard  = Helper::getGuardFromURL($request);
        $user   = $request->user($guard);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        // $user->sendEmailVerificationNotification();
        $res = $this->sendMail($user, $request->site_settings);
        if ($res == true) {
            return back()->withSuccess(' A new verification link has been sent to the email address you provided during registration.');
        } else {
            return back()->withError($res);
        }
    }

    protected function sendMail($user, $site_settings)
    {
        try {
            Mail::to($user->email)->send(new UserVerification($user, $site_settings));
            return true;
        } catch (\Exception $e) {

            return Str::limit($e->getMessage());
        }
    }
}
