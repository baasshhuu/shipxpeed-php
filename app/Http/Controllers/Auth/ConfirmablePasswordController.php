<?php

namespace App\Http\Controllers\Auth;

use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Validation\ValidationException;

class ConfirmablePasswordController extends Controller
{
    public function show(Request $request, $guard = 'web'): View
    {
        $guard  = Helper::getGuardFromURL($request);
        $user   = $request->user($guard);
        return view('auth.confirm-password', compact('guard', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $guard  = Helper::getGuardFromURL($request);
        $user   = $request->user($guard);

        if (!Hash::check($request->password, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());
        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
