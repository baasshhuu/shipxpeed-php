<?php
// COD remittance module (loaded by App\Providers\XpeedServiceProvider after the main route files,
// so these take over the old /seller/cod-remittance and /codremittance pages with the same route names).
use App\Http\Controllers\Cod\AdminCodController;
use App\Http\Controllers\Cod\SellerCodController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- seller
Route::middleware(['seller'])->prefix('seller')->group(function () {
    Route::get('/cod-remittance', [SellerCodController::class, 'index'])->name('seller.cod');
    Route::get('/cod-remittance/export', [SellerCodController::class, 'export'])->name('seller.cod.export');
    Route::post('/cod-remittance/plan', [SellerCodController::class, 'plan'])->name('seller.cod.plan');
});

// ---------------------------------------------------------------- admin
Route::middleware(['auth', 'permission', 'authCheck', 'verified'])->controller(AdminCodController::class)->group(function () {
    Route::get('codremittance', 'index')->name('codremittance.index');
    Route::get('codremittance/export', 'export')->name('codremittance.export');
    Route::get('codremittance/seller/{sellerId}', 'seller')->whereNumber('sellerId')->name('codremittance.seller');
    Route::post('codremittance/mark-paid', 'markPaid')->name('codremittance.markPaid');
    Route::post('codremittance/payout/{id}/resend', 'resend')->whereNumber('id')->name('codremittance.resend');
    Route::post('codremittance/refresh', 'refresh')->name('codremittance.refresh');
});
