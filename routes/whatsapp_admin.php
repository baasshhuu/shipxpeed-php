<?php
// WhatsApp (AiSensy) admin routes. Loaded from routes/admin.php only when the
// deploy couldn't merge them in directly (server copy differs). Same middleware as admin.
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\WhatsAppController;

if (!Route::has('admin.whatsapp.index')) {
    Route::middleware(['auth', 'permission', 'authCheck', 'verified'])->group(function () {
        Route::get('whatsapp', [WhatsAppController::class, 'index'])->name('admin.whatsapp.index');
        Route::post('whatsapp/settings', [WhatsAppController::class, 'saveSettings'])->name('admin.whatsapp.settings');
        Route::post('whatsapp/seller/{id}', [WhatsAppController::class, 'saveSeller'])->whereNumber('id')->name('admin.whatsapp.seller');
        Route::post('whatsapp/test', [WhatsAppController::class, 'test'])->name('admin.whatsapp.test');
        Route::post('whatsapp/resend/{id}', [WhatsAppController::class, 'resend'])->whereNumber('id')->name('admin.whatsapp.resend');
        Route::post('whatsapp/ndr/{id}/done', [WhatsAppController::class, 'ndrDone'])->whereNumber('id')->name('admin.whatsapp.ndr.done');
    });
}
