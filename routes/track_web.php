<?php
// Public order tracking + buyer email updates.
// Loaded at the end of routes/web.php so it replaces the old /order-details page.
use App\Http\Controllers\Frontend\PublicTrackController;
use Illuminate\Support\Facades\Route;

Route::get('/order-details', [PublicTrackController::class, 'show'])->middleware('throttle:60,1')->name('order.details');
Route::post('/track-order', [PublicTrackController::class, 'legacy'])->name('order.track');
Route::post('/order-details/notify', [PublicTrackController::class, 'subscribe'])->middleware('throttle:6,1')->name('order.details.notify');
Route::get('/order-details/unsubscribe/{token}', [PublicTrackController::class, 'unsubscribe'])->where('token', '[A-Za-z0-9]{20,64}')->name('order.details.unsubscribe');
