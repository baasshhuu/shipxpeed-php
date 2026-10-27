<?php
// Seller › Notify (WhatsApp + Email & SMS buyer communication).
// Loaded at the end of routes/web.php so it replaces the old /notifications page.
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\selleradmin\NotifyController;

Route::middleware(['auth:seller'])->group(function () {
    Route::get('/notifications', [NotifyController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/settings', [NotifyController::class, 'save'])->name('notifications.settings');
    Route::get('/notifications/preview/{channel}/{event}', [NotifyController::class, 'preview'])->name('notifications.preview');
    Route::post('/notifications/test', [NotifyController::class, 'test'])->middleware('throttle:6,1')->name('notifications.test');
});
