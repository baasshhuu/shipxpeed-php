<?php
// WhatsApp (AiSensy) seller + buyer routes. Loaded from routes/web.php only when the
// deploy couldn't merge them in directly (server copy differs).
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\SellerAuthController;
use App\Http\Controllers\Frontend\NdrResponseController;
use App\Http\Controllers\selleradmin\WhatsAppAlertsController;

if (!Route::has('seller.whatsapp.alerts')) {
    Route::post('/seller/verify-otp/whatsapp-resend', [SellerAuthController::class, 'resendSignupWhatsappOtp'])->middleware('throttle:6,1')->name('seller.verify.otp.wa.resend');
    Route::post('/seller-login/otp/send', [SellerAuthController::class, 'sendLoginOtp'])->middleware('throttle:6,1')->name('seller.login.otp.send');
    Route::post('/seller-login/otp/verify', [SellerAuthController::class, 'verifyLoginOtp'])->middleware('throttle:10,1')->name('seller.login.otp.verify');

    Route::middleware(['auth:seller'])->group(function () {
        Route::get('/seller/whatsapp-alerts', [WhatsAppAlertsController::class, 'index'])->name('seller.whatsapp.alerts');
        Route::post('/seller/whatsapp-alerts', [WhatsAppAlertsController::class, 'update'])->name('seller.whatsapp.alerts.update');
    });

    Route::get('/ndr/{token}', [NdrResponseController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->name('ndr.respond');
    Route::post('/ndr/{token}', [NdrResponseController::class, 'store'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:10,1')->name('ndr.respond.store');
}
