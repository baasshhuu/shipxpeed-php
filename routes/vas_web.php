<?php
// Seller › Value Added Services hub (RTO Score, Early COD, Secure Shipments, Delivery Boost, Brand Boost, Notify).
// Loaded at the end of routes/web.php.
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\selleradmin\ValueServicesController;

Route::middleware(['auth:seller'])->group(function () {
    Route::get('/seller/services', [ValueServicesController::class, 'index'])->name('vas.index');
    Route::get('/seller/services/{tab}', [ValueServicesController::class, 'show'])->where('tab', '[a-z\-]+')->name('vas.show');
});
