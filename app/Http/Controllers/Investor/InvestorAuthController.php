<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class InvestorAuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('investor.login');
    }

    /**
     * Handle login request  
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('investor')->attempt($credentials)) {
            return redirect()->route('investor.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials provided.'])->withInput();
    }

    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        if (Auth::guard('investor')->check()) {
            return redirect()->route('investor.dashboard');
        }
        
        return view('investor.register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:investors,email',
            'phone' => 'required|string|max:15',
            'company_name' => 'nullable|string|max:255',
            'investment_amount' => 'required|numeric|min:0',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $investor = Investor::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'investment_amount' => $request->investment_amount,
                'password' => Hash::make($request->password),
                // 'status' => 0, // Pending approval
                'email_verified_at' => Carbon::now(),
            ]);

            Session::flash('success', 'Registration successful! Please wait for admin approval.');
            return redirect()->route('investor.login');

        } catch (\Exception $e) {
            // dd($e->getMessage());
            return back()->withErrors(['email' => 'Registration failed. Please try again.'])->withInput();
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::guard('investor')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Session::flash('success', 'You have been logged out successfully.');
        return redirect()->route('investor.login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('investor.forgot-password');
    }

    /**
     * Handle forgot password request
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:Investor,email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Here you can implement email sending logic
        // For now, just show success message
        Session::flash('success', 'Password reset link has been sent to your email.');
        return back();
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm($token)
    {
        return view('investor.auth.reset-password', compact('token'));
    }

    /**
     * Handle reset password request
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:investors,email',
            'password' => 'required|string|min:6|confirmed',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Here you can implement token validation logic
        // For now, just update the password
        $investor = Investor::where('email', $request->email)->first();
        
        if ($investor) {
            $investor->update([
                'password' => Hash::make($request->password)
            ]);

            Session::flash('success', 'Password reset successfully. You can now login.');
            return redirect()->route('investor.login');
        }

        return back()->withErrors(['email' => 'Invalid reset token.']);
    }

    /**
     * Show investor profile
     */
    public function profile()
    {
        return view('investor.auth.profile');
    }

    /**
     * Update investor profile
     */
    public function updateProfile(Request $request)
    {
        $investor = Auth::guard('investor')->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'company_name' => 'nullable|string|max:255',
            'investment_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $investor->update($request->only([
            'name', 'phone', 'company_name', 'investment_amount'
        ]));

        Session::flash('success', 'Profile updated successfully.');
        return back();
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $investor = Auth::guard('investor')->user();
        
        if (!Hash::check($request->current_password, $investor->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $investor->update([
            'password' => Hash::make($request->password)
        ]);

        Session::flash('success', 'Password changed successfully.');
        return back();
    }
}