<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $token          = $request->route()->parameter('token');
        $token_data     = DB::table('password_resets')->where([
            'email'     => $request->email,
        ])->first();

        if (!$token_data) {
            return to_route('loginPage', 'company');
        }

        $loginUrl = route('loginPage', $token_data->guard == 'web' ? 'admin' : $token_data->guard);
        return view('auth.passwords.reset', ['token' => $token, 'email' => $request->email, 'loginUrl' => $loginUrl]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token'     => ['required'],
            'email'     => ['required', 'email'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $token_data = DB::table('password_resets')->where([
            'email'         => $request->email,
            // 'token'         => $request->token,
        ])->first();

        if (!$token_data) {
            return back()->withErrors(['email' => trans('User does not exist')]);
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::broker($token_data->guard)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password'          => Hash::make($request->password),
                    'remember_token'    => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
            ? to_route('loginPage', $token_data->guard == 'web' ? 'admin' : $token_data->guard)->withSuccess(__($status))
            : back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
