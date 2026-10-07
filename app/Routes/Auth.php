<?php

namespace App\Routes;

use Illuminate\Support\Facades\Route;

class Auth
{
    // Profile Routes Group
    public static function routes()
    {

        // // Authentication Routes...
        // Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        // Route::post('login', [LoginController::class, 'login']);
        // Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        // // Registration Routes...
        // Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        // Route::post('register', [RegisterController::class, 'register']);

        // // Password Reset Routes...
        // Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        // Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        // Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        // Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

        // // Confirm Password (added in v6.2)
        // Route::get('password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
        // Route::post('password/confirm', [ConfirmPasswordController::class, 'confirm']);

        // // Email Verification Routes...
        // Route::get('email/verify', [VerificationController::class, 'show'])->name('verification.notice');
        // Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify'); // v6.x
        // /* Route::get('email/verify/{id}', [VerificationController::class, 'verify'])->name('verification.verify'); // v5.x */
        // Route::get('email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
    }
}
