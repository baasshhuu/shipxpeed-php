<?php
// Seller Control Suite routes (loaded by App\Providers\XpeedServiceProvider).
use App\Http\Controllers\Xpeed\AdminSuiteController;
use App\Http\Controllers\Xpeed\SellerSuiteController;
use App\Http\Controllers\Xpeed\PublicTrackingController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- seller
Route::middleware(['seller'])->prefix('seller/xp')->name('xp.')->controller(SellerSuiteController::class)->group(function () {
    Route::get('ndr', 'ndr')->name('ndr');
    Route::post('ndr/action', 'ndrAction')->name('ndr.action');
    Route::post('ndr/bulk', 'ndrBulk')->name('ndr.bulk');

    Route::get('bulk', 'bulk')->name('bulk');
    Route::post('bulk', 'bulkRun')->name('bulk.run');
    Route::post('bulk/manifest', 'manifest')->name('bulk.manifest');

    Route::get('couriers', 'couriers')->name('couriers');
    Route::post('couriers/prefs', 'saveCourierPrefs')->name('couriers.prefs');
    Route::post('couriers/rules', 'saveRule')->name('couriers.rules');
    Route::post('couriers/rules/{id}/delete', 'deleteRule')->whereNumber('id')->name('couriers.rules.delete');
    Route::post('couriers/rules/{id}/toggle', 'toggleRule')->whereNumber('id')->name('couriers.rules.toggle');
    Route::get('zone-lookup', 'zoneLookup')->name('zone');

    Route::get('reports', 'reports')->name('reports');
    Route::post('reports/download', 'reportDownload')->name('reports.download');
    Route::post('reports/schedule', 'reportSchedule')->name('reports.schedule');
    Route::post('reports/schedule/{id}/delete', 'reportScheduleDelete')->whereNumber('id')->name('reports.schedule.delete');

    Route::get('risk', 'risk')->name('risk');

    Route::get('tracking', 'tracking')->name('tracking');
    Route::post('tracking', 'saveTracking')->name('tracking.save');

    Route::get('protection', 'protection')->name('protection');
    Route::post('protection', 'saveProtection')->name('protection.save');
    Route::post('protection/early-cod', 'earlyCod')->name('protection.earlycod');
    Route::post('protection/claim', 'claim')->name('protection.claim');
    Route::get('protection/claim/{id}/proof/{n}', 'claimProof')->whereNumber('id')->whereNumber('n')->name('protection.claim.proof');
});

// ---------------------------------------------------------------- public (buyer)
Route::get('/t/{awb}', [PublicTrackingController::class, 'show'])->where('awb', '[A-Za-z0-9\-]{6,40}')->middleware('throttle:30,1')->name('xp.track.public');

// ---------------------------------------------------------------- admin
Route::middleware(['auth', 'permission', 'authCheck', 'verified'])->prefix('xp')->name('admin.xp.')->controller(AdminSuiteController::class)->group(function () {
    Route::get('ndr', 'ndr')->name('ndr');
    Route::post('ndr/{id}/retry', 'ndrRetry')->whereNumber('id')->name('ndr.retry');
    Route::post('ndr/{id}/done', 'ndrDone')->whereNumber('id')->name('ndr.done');

    Route::get('courier-performance', 'performance')->name('performance');
    Route::get('rto-risk', 'risk')->name('risk');

    Route::get('services', 'services')->name('services');
    Route::post('services/settings', 'saveSettings')->name('services.settings');
    Route::post('claims/{id}/review', 'reviewClaim')->whereNumber('id')->name('claims.review');
    Route::get('claims/{id}/proof/{n}', 'claimProof')->whereNumber('id')->whereNumber('n')->name('claims.proof');
    Route::post('early-cod/{sellerId}/decide', 'decideEarlyCod')->whereNumber('sellerId')->name('earlycod.decide');
    Route::post('early-cod/{sellerId}/remit', 'remitEarlyCod')->whereNumber('sellerId')->name('earlycod.remit');
    Route::get('early-cod/{sellerId}/export', 'exportEarlyCod')->whereNumber('sellerId')->name('earlycod.export');
});
