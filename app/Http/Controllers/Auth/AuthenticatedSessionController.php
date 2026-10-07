<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['guest'])->except('destroy');
    }

    public function create($guard = 'web'): View
    {
        return view('auth.login', ['guard' => $guard]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if ($redirectTo = $request->authenticate()) {
            $request->session()->regenerate();
            return redirect()->intended($redirectTo);
        }

        return back()->withError("Invalid Login Credential..!!")->withInput($request->only('email', 'login_as', 'remember'));
    }

    public static function destroy(Request $request): RedirectResponse
    {

        auth('web')->logout();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Session::forget('locked');
        return to_route('home');
    }
}
