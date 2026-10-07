<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;

class PasswordResetLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware('mail');
    }

    public function create($guard = 'web'): View
    {
        return view('auth.passwords.email', ['guard' => $guard]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'     => ['required', 'email'],
            'login_as'  => ['required'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.

        //Check if the user exists
        $user  = null;
        switch ($request->login_as) {
            default:
                $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            return back()->withErrors(['email' => trans('User does not exist')]);
        }

        //Create Password Reset Token
        $token = Str::random(64);
        DB::table('password_resets')->where(['email' => $request->email])->delete();
        DB::table('password_resets')->insert([
            'email'         => $request->email,
            'guard'         => $request->login_as,
            'token'         => bcrypt($token),
            'created_at'    => Carbon::now()
        ]);

        //Get the token just created above
        if ($this->sendResetEmail($user, $token) == true) {
            return back()->withSuccess(trans('A reset link has been sent to your email address.'));
        } else {
            return back()->withInput($request->only('email'))->withError(trans('A Network Error occurred. Please try again.'));
        }
    }

    private function sendResetEmail($user, $token)
    {
        try {
            Mail::send('email.reset-password', [
                'name'          => $user->name,
                'reset_url'     => route('password.reset', ['token' => $token, 'email' => $user->email]),
            ], function ($message) use ($user) {
                $message->subject('Reset Password Request');
                $message->to($user->email);
            });

            return true;
        } catch (\Exception $e) {
            return Str::limit($e->getMessage());
        }
    }
}
