<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;

Route::middleware(['guest'])
    ->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::get('login', fn () => to_route('loginPage', ['guard' => 'admin']))->name('login');
        Route::get('{guard}/login', [AuthenticatedSessionController::class, 'create'])->whereIn('guard', ['admin'])->name('loginPage');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('{guard}/password/reset', [PasswordResetLinkController::class, 'create'])
            ->whereIn('guard', ['admin'])
            ->name('forget.password');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
    });

Route::middleware(['authCheck'])->group(function () {
    Route::prefix('{guard}')->group(function () {
        Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    })->whereIn('guard', ['admin']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
