<?php
// Shipxpeed API v1 - loaded from routes/api.php (prefix /api). Docs: /developers
use App\Http\Controllers\ApiV1\AccountController;
use App\Http\Controllers\ApiV1\B2bController;
use App\Http\Controllers\ApiV1\B2cController;
use App\Http\Controllers\ApiV1\ReverseController;
use App\Http\Controllers\ApiV1\ShipmentController;
use App\Http\Middleware\ApiV1Auth;
use App\Support\ApiV1\Respond;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // public: health + live courier list (same list the docs page shows)
    Route::get('/', fn () => Respond::ok(['api' => 'Shipxpeed API', 'version' => 'v1', 'docs' => url('/developers')], 'API is up'));
    Route::get('/public/couriers', function () {
        $rows = \App\Support\ApiV1\Couriers::present(\App\Support\ApiV1\Couriers::forSeller(0));
        return Respond::ok(['count' => count($rows), 'couriers' => array_map(fn ($r) => array_intersect_key($r, array_flip(['courier_code', 'name', 'brand', 'type', 'mode', 'logo'])), $rows)]);
    })->middleware('throttle:30,1');

    Route::middleware(ApiV1Auth::class)->group(function () {
        // account
        Route::get('/account', [AccountController::class, 'account']);
        Route::get('/wallet', [AccountController::class, 'wallet']);
        Route::get('/couriers', [AccountController::class, 'couriers']);
        Route::get('/rate-card', [AccountController::class, 'rateCard']);
        Route::get('/pincodes/{pincode}', [AccountController::class, 'pincode']);
        Route::get('/warehouses', [AccountController::class, 'warehouses']);
        Route::post('/warehouses', [AccountController::class, 'createWarehouse']);
        Route::get('/webhooks', [AccountController::class, 'webhooks']);
        Route::post('/webhooks', [AccountController::class, 'saveWebhook']);
        Route::post('/webhooks/test', [AccountController::class, 'testWebhook']);

        // B2C forward
        Route::post('/b2c/rates', [B2cController::class, 'rates']);
        Route::post('/b2c/shipments', [B2cController::class, 'create']);
        Route::post('/b2c/shipments/{id}/book', [B2cController::class, 'book'])->whereNumber('id');

        // reverse pickups
        Route::post('/reverse/rates', [ReverseController::class, 'rates']);
        Route::post('/reverse/shipments', [ReverseController::class, 'create']);

        // B2B
        Route::post('/b2b/rates', [B2bController::class, 'rates']);
        Route::get('/b2b/shipments', [B2bController::class, 'index']);
        Route::post('/b2b/shipments', [B2bController::class, 'create']);
        Route::get('/b2b/shipments/{ref}', [B2bController::class, 'show']);
        Route::post('/b2b/shipments/{ref}/book', [B2bController::class, 'book']);
        Route::post('/b2b/shipments/{ref}/cancel', [B2bController::class, 'cancel']);
        Route::get('/b2b/shipments/{ref}/documents/{type}', [B2bController::class, 'document']);

        // all shipment types
        Route::get('/shipments', [ShipmentController::class, 'index']);
        Route::post('/shipments/cancel', [ShipmentController::class, 'cancel']);
        Route::get('/shipments/{ref}', [ShipmentController::class, 'show']);
        Route::get('/shipments/{awb}/label', [ShipmentController::class, 'label']);
        Route::post('/labels', [ShipmentController::class, 'labels']);
        Route::get('/track/{awb}', [ShipmentController::class, 'track']);
        Route::get('/ndr', [ShipmentController::class, 'ndrList']);
        Route::post('/ndr/action', [ShipmentController::class, 'ndrAction']);
    });

    Route::any('{any}', fn () => Respond::error('Endpoint not found. See ' . url('/developers'), 404, 'not_found'))->where('any', '.*');
});
