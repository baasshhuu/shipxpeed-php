<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Api\CourierController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\selleradmin\WarehosueController;
use App\Http\Controllers\selleradmin\ShipmentController;
use App\Http\Controllers\selleradmin\CashfreeController;
use App\Http\Controllers\Shipxpeedapi\WarehouseController;
use App\Http\Controllers\Shipxpeedapi\AuthController;
use App\Http\Controllers\Shipxpeedapi\RatecardController;
use App\Http\Controllers\Shipxpeedapi\ServiceabilityController;
use App\Http\Controllers\Shipxpeedapi\CancelshipmentController;
use App\Http\Controllers\Shipxpeedapi\LabelController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Shipxpeedapi\TrackController;
use App\Http\Controllers\Shipxpeedapi\ParcelxController;
use App\Http\Controllers\Shipxpeedapi\TestwebhookController;
use App\Http\Controllers\Shipxpeedapi\BoxdController;
use App\Http\Controllers\Api\ShopifyController;
use App\Http\Controllers\selleradmin\Dashboard;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => "Adiyogi eTally :: Api Working Fine."
    ]);
});

Route::get('/test-xpressbees', function () {
    $token = \App\Helper\Helper::getXpressbeesToken();
    return response()->json(['token' => $token]);
});

// status fatch
// Route::get('/dashboard-data', [App\Http\Controllers\selleradmin\Dashboard::class, 'getDashboardData']);
// Route::get('/revenue-dashboard-data', [App\Http\Controllers\selleradmin\Dashboard::class, 'getRevenueDashboardData']);
Route::get('/send-cod-mai', [App\Http\Controllers\Api\StatusController::class, 'sendCodMail']);

Route::get('/recharge-summary', [App\Http\Controllers\Api\StatusController::class, 'rechargeSummary']);

Route::post('/parcelx/bulk-cancel', [App\Http\Controllers\Api\StatusController::class, 'bulkCancel']);

Route::get('/track/', [App\Http\Controllers\Api\StatusController::class, 'apiTrackDelhivery']);
Route::get('/track/boxd', [App\Http\Controllers\Api\StatusController::class, 'trackBoxd']);
Route::get('/track/tekipost', [App\Http\Controllers\Api\StatusController::class, 'trackTekipost']);
Route::get('/track/trackDTDC', [App\Http\Controllers\Api\StatusController::class, 'trackDTDC']);
Route::get('/sendTransitWhatsAppMessages', [App\Http\Controllers\Api\StatusController::class, 'sendTransitWhatsAppMessages']);
// Route::get('/track/dtdc', [App\Http\Controllers\Api\StatusController::class, 'trackDTDC']);
Route::get('/track/trackBoxdseller', [App\Http\Controllers\Api\StatusController::class, 'trackBoxdseller']);
Route::get('/track/trackParcelX', [App\Http\Controllers\Api\StatusController::class, 'trackParcelX']);
Route::post('/updateColumnValue', [App\Http\Controllers\Api\StatusController::class, 'updateColumnValue']);
Route::get('/track/delhivery', [App\Http\Controllers\Api\StatusController::class, 'apiTrackDelhivery']);
Route::get('/shiprocket/login', [App\Http\Controllers\Api\StatusController::class, 'shiprocketLogin']);
Route::get('/track/trackShadowfax', [App\Http\Controllers\Api\StatusController::class, 'trackShadowfax']);
Route::get('/track/trackShiprocket', [App\Http\Controllers\Api\StatusController::class, 'trackShiprocket']);
Route::post('/process-rto-excel', [StatusController::class, 'processRTOFromExcel']);
Route::post('/update-status-from-excel', [StatusController::class, 'updateStatusFromExcel']);
Route::get('/processrto', [StatusController::class, 'processrto']);
Route::get('/cancelShipment', [StatusController::class, 'cancelShipment']);
Route::delete('/destroy', [StatusController::class, 'destroy']);
Route::get('/orders', [StatusController::class, 'index']);
Route::post('/bulk-cancel-shipment', [StatusController::class, 'bulkCancelShipment']);

// Route::get('/Shipment', [StatusController::class, 'Shipment']);


// status fatch


Route::get('clear-all', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('storage:link');
    return '<h1>Clear All</h1>';
});

Route::get('/track-tekipost', [App\Http\Controllers\Api\CourierController::class, 'trackTekipost']);

// Route::any('{path}', function () {
//     return response()->json([
//         'status'    => false,
//         'message'   => 'Api not found..!!'
//     ], 404);
// })->where('path', '.*');



// Route::get('/get-xpressbees-token', [CourierController::class, 'getXpressBeesTokenuse']);
Route::get('couriers/serviceability', [CourierController::class, 'serviceability']);
Route::get('reverse/couriers/serviceability', [CourierController::class, 'reverseServiceability']);

Route::get('couriers/serviceability/bulk', [CourierController::class, 'serviceabilitybulk']);


Route::post('/xpressbees/webhook', [CourierController::class, 'handleXpressbeesWebhook']);


Route::post('/webhook/shadowfax', [CourierController::class, 'handleOrderStatus']);
Route::post('/webhook/delhivery', [CourierController::class, 'handleDelhivery']);
Route::post('/webhooks', [TrackController::class, 'handleShiprocketWebhook']);
// Route::post('/webhooks/Shiprocket', [TrackController::class, 'handleCourierWebhook']);
// In routes/api.php or routes/web.php
// Route::post('/webhook/Shiprocket', [TrackController::class, 'handleShiprocketWebhook']);
Route::post('/webhook/parcelx', [ParcelxController::class, 'handleParcelXWebhook']);
Route::post('/webhook/seloship', [SeloshipController::class, 'handleSeloshipWebhook']);
Route::post('/webhook/boxd', [BoxdController::class, 'handlboxdWebhook']);

Route::post('/smartship/webhook', [WarehosueController::class, 'smartshipWebhook']);

Route::post('/test-webhook', [TestwebhookController::class, 'getAllwebhookstatus']);



Route::post('/api/shipments/create', [ShipmentController::class, 'createShipmentweb'])->name('api.shipments.create');
Route::get('/api/orders/track', [\App\Http\Controllers\Frontend\TrackController::class, 'trackOrder'])
    ->name('api.orders.track');

Route::get('cashfree', [CashfreeController::class, 'verifyAadhaar']);


// thard party apis 

// ✅ Login API (token generate)
Route::post('/token-generate', [AuthController::class, 'login']);
// ✅ Create Warehouse API (requires Authorization token)
Route::post('/create-warehouse', [WarehouseController::class, 'createWarehouse']);
Route::post('/rate-card', [RatecardController::class, 'checkRate']);
Route::post('/serviceability', [ServiceabilityController::class, 'checkServiceability']);


Route::post('/serviceability/all-providers', [ServiceabilityController::class, 'getAllServiceability']);
Route::post('/create-shipment', [ServiceabilityController::class, 'createShipment']);
Route::post('/assign-order', [ServiceabilityController::class, 'assignOrderAPI']);

Route::post('/cancel-shipment', [CancelshipmentController::class, 'cancelByAwb']);
Route::post('/label-by-awb', [LabelController::class, 'apiGetLabelByAwb']);
Route::post('/ndr-action', [NdrController::class, 'ndrReattempt']);

// Manual tracking route
Route::post('/track-shipment', [TrackController::class, 'trackShipment']);



// thard party apis 










Route::get('/shopify/install', [App\Http\Controllers\Api\ShopifyController::class, 'shopifyInstall']);
Route::get('/shopify/callback', [App\Http\Controllers\Api\ShopifyController::class, 'shopifyCallback']);
