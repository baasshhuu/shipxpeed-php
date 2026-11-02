<?php
// Domestic B2B shipments (Bigship): seller pages + admin settings.
// Loaded from the end of routes/web.php.
use App\Http\Controllers\admin\BigshipController;
use App\Http\Controllers\selleradmin\B2bOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:seller'])->prefix('seller/b2b-orders')->name('seller.b2b.')->group(function () {
    Route::get('/', [B2bOrderController::class, 'index'])->name('orders');
    Route::get('/create', [B2bOrderController::class, 'create'])->name('order');
    Route::post('/quote', [B2bOrderController::class, 'quote'])->middleware('throttle:30,1')->name('order.quote');
    Route::post('/', [B2bOrderController::class, 'store'])->middleware('throttle:20,1')->name('order.store');
    Route::get('/{id}', [B2bOrderController::class, 'show'])->whereNumber('id')->name('order.show');
    Route::get('/{id}/rates', [B2bOrderController::class, 'draftRates'])->whereNumber('id')->middleware('throttle:30,1')->name('order.rates');
    Route::post('/{id}/book', [B2bOrderController::class, 'book'])->whereNumber('id')->middleware('throttle:20,1')->name('order.book');
    Route::post('/{id}/cancel', [B2bOrderController::class, 'cancel'])->whereNumber('id')->name('order.cancel');
    Route::post('/{id}/track', [B2bOrderController::class, 'track'])->whereNumber('id')->middleware('throttle:20,1')->name('order.track');
    Route::get('/{id}/document/{type}', [B2bOrderController::class, 'document'])->whereNumber('id')->name('order.document');
    Route::get('/{id}/file/{which}', [B2bOrderController::class, 'file'])->whereNumber('id')->name('order.file');
});

Route::middleware(['auth', 'permission', 'authCheck', 'verified'])->prefix('bigship')->name('admin.bigship.')->group(function () {
    Route::get('/', [BigshipController::class, 'index'])->name('index');
    Route::post('/settings', [BigshipController::class, 'saveSettings'])->name('settings');
    Route::post('/courier/{id}', [BigshipController::class, 'courier'])->whereNumber('id')->name('courier');
    Route::post('/rate-test', [BigshipController::class, 'rateTest'])->name('rate-test');
    Route::get('/log/{id}', [BigshipController::class, 'logShow'])->whereNumber('id')->name('log');
});
