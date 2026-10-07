<?php

use App\Http\Controllers\admin\SellerListController;
use App\Http\Controllers\Frontend\CancelShipmentController;
use App\Http\Controllers\Frontend\FormsController;
use App\Http\Controllers\GitTestController;
use App\Http\Controllers\selleradmin\CourierController;
use App\Http\Controllers\selleradmin\InvoiceController;
use App\Http\Controllers\selleradmin\Manifestiation;
use App\Http\Controllers\selleradmin\NdrController;
use App\Http\Controllers\selleradmin\OrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\selleradmin\PickupRequestController;
use App\Http\Controllers\selleradmin\PincodeServiceabilityController;
use App\Http\Controllers\selleradmin\ResourceController;
use App\Http\Controllers\selleradmin\TicketController;
use App\Http\Controllers\selleradmin\ToolsController;
use App\Http\Controllers\selleradmin\ShipmentController;
use App\Http\Controllers\selleradmin\TrackController;
use App\Http\Controllers\selleradmin\WarehosueController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\CityController;
use App\Http\Controllers\FireController;
use App\Http\Controllers\Common\CommonController;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Frontend\SellerAuthController;
use App\Http\Controllers\selleradmin\Dashboard;
use App\Http\Controllers\SellerAdmin\RateCardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SellerAdmin\PaymentController;
use App\Http\Controllers\selleradmin\WeightDispatchingController;
use App\Http\Controllers\selleradmin\OrdersReportsController;
use App\Http\Controllers\selleradmin\CashfreeController;
use App\Http\Controllers\selleradmin\LebalController;
use App\Http\Controllers\selleradmin\ShippingNotificationController;
use App\Http\Controllers\Shopifyconnect\ShopifyController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\selleradmin\ReversepickupController;   


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Website Routes
Route::get('/home', [HomeController::class, 'index'])->name('home');
// Route::patch('fcm-token', [FireController::class, 'updateToken'])->name('fcmToken');
// routes/seller.php (or web.php if guards are not split)

// Route::get('lebal', [Dashboard::class, 'lebal'])->name('lebal');





Route::get('/download-label/{id}', [Dashboard::class, 'downloadLabel'])->name('download.label');

Route::get('/seller/rto/download-excel', [Dashboard::class, 'downloadRtoExcel'])->name('seller.rto.download-excel');


Route::get('/seller/agreement/download', [SellerListController::class, 'download'])
    ->middleware('auth:seller')
    ->name('seller.agreement.download');


Route::get('clear-all', function () {
    Artisan::call('cache:clear');       // Application cache
    Artisan::call('config:clear');      // Config cache
    Artisan::call('route:clear');       // Route cache
    Artisan::call('view:clear');        // View cache
    Artisan::call('event:clear');       // Event cache (if any)
    Artisan::call('storage:link');      // Re-link storage
    Artisan::call('optimize:clear');    // Clears compiled files

    return '<h1>All Caches Cleared Successfully!</h1>';
});


Route::get('/install', 'App\Http\Controllers\Shopifyconnect\ShopifyController@install');
Route::get('/callback', 'App\Http\Controllers\Shopifyconnect\ShopifyController@callback');
Route::get('/products', 'App\Http\Controllers\Shopifyconnect\ShopifyController@getProducts');

// Channel Management Routes
Route::get('/seller/channels', [ShopifyController::class, 'channelList'])->name('seller.channel.list');
Route::get('/seller/channels/add', [ShopifyController::class, 'channelAdd'])->name('seller.channel.add');
Route::get('/seller/channels/seller.shopify.integration', [ShopifyController::class, 'shopifyIntegration'])->name('seller.shopify.integration');
Route::post('/seller/channels/shopify/connect', [ShopifyController::class, 'shopifyConnect'])->name('shopify.connect');
Route::post('/seller/channels/shopify/sync-orders', [ShopifyController::class, 'syncOrders'])->name('shopify.sync.orders');

// Shopify webhook routes (no authentication needed for webhooks)
Route::post('/webhooks/shopify/orders/create', [ShopifyController::class, 'handleOrderWebhook'])->name('shopify.webhook.order.create');
Route::post('/webhooks/shopify/orders/update', [ShopifyController::class, 'handleOrderWebhook'])->name('shopify.webhook.order.update');

// Shopify store management
Route::get('/seller/shopify/stores', [ShopifyController::class, 'getConnectedStores'])->name('shopify.stores.list');
Route::post('/seller/shopify/stores/{id}/disconnect', [ShopifyController::class, 'disconnectStore'])->name('shopify.stores.disconnect');

// Alternative simpler routes for integration pages
Route::get('/shopify-integration', function () {
    return view('shopify-integration');
})->name('shopify.integration.simple');



// Route::get('clear-all', function () {
//     Artisan::call('cache:clear');
//     Artisan::call('config:clear');
//     Artisan::call('route:clear');
//     Artisan::call('view:clear');
//     Artisan::call('storage:link');
//     return '<h1>Clear All</h1>';
// });
Route::get('/api-docs', function () {
    return view('api-docs');
})->name('seller.api.docs');
Route::get('/download-delivered-orders', [StatusController::class, 'downloadDeliveredOrdersExcel']);

Route::post('/aadhaar/generate', [CashfreeController::class, 'generateOtp'])->name('aadhaar.generate');
Route::post('/aadhaar/verify-otp', [CashfreeController::class, 'verifyOtp'])->name('aadhaar.verify');
Route::post('/verify-pan', [CashfreeController::class, 'verifyPan'])->name('pan.verify');
Route::post('/verify-gst', [CashfreeController::class, 'verifyGST'])->name('gst.verify');
Route::post('/cin-verify', [CashfreeController::class, 'verifyCIN'])->name('cin.verify');
Route::post('/bank-verify', [CashfreeController::class, 'verifyBankAccount'])->name('bank.verify');

Route::post('/submitKyc', [CashfreeController::class, 'submitKyc'])->name('submitKyc');


// Route::post('/aadhaar/generate-otp', [CashfreeController::class, 'generateOtp']);
// Route::post('/aadhaar/verify-otp',   [CashfreeController::class, 'verifyOtp']);


Route::get('/get-current-date', function () {
    return response()->json(['date' => now()->format('d M Y')]);
});

Route::get('/order/delete/{id}', [Dashboard::class, 'deleteOrder'])->name('orderdelete');

Route::post('couriers/cancel', [\App\Http\Controllers\Api\CourierController::class, 'cancelAwb'])
    ->name('courier.cancel.awb');
Route::post('/seller/orders/bulk-cancel', [\App\Http\Controllers\Api\CourierController::class, 'cancelAwb_bulk'])->name('seller.order.bulk-cancel');

// Route::post('couriers/cancel', [CourierController::class, 'cancelAwb'])->name('courier.cancel.awb');
Route::post('/get-courier-info', [Dashboard::class, 'getCourierInfo'])->name('get.courier.info');
Route::post('/get-courier-info-shadowfax', [Dashboard::class, 'getCourierInfo_shadowfax'])->name('get.courier.info.shadowfax');
Route::post('/get-courier-info-delhivery_b2c', [Dashboard::class, 'getCourierInfo_delhivery_b2c'])->name('get.courier.info.delhivery_b2c');
Route::post('/smartship/label', [Dashboard::class, 'getSmartshipLabel'])->name('smartship.label');
Route::post('/tekipost/label', [Dashboard::class, 'getTekipostLabel'])->name('tekipost.label');
Route::post('/parcelx/label', [Dashboard::class, 'getParcelxLabel'])->name('parcelx.label');



Route::get('/seller-agreement', [SellerListController::class, 'fetchAgreement'])->name('seller.agreement.fetch');
Route::get('/happy-new-year', [Dashboard::class, 'happynewyear'])->name('seller.happynewyear');

Route::get('/push-notificaiton', [FireController::class, 'index'])->name('push-notificaiton');
Route::get('/git-test', [GitTestController::class, 'index'])->name('git.test');
Route::post('/store-token', [FireController::class, 'storeToken'])->name('store.token');
Route::post('/send-web-notification', [FireController::class, 'sendWebNotification'])->name('send.web-notification');

Route::get('test', [CommonController::class, 'test'])->name('test');
Route::get('{guard}', fn($guard) => redirect($guard == 'admin' ? url('admin/login') : url("/$guard/login")))->whereIn('guard', ['super-admin']);
Route::redirect('admin/dashboard', '/dashboard');


Route::middleware(['authCheck'])->group(function () {


    Route::post('get-cities', [CityController::class, 'get_cities'])->name('cities.list');
    Route::post('upload-image', action: [CommonController::class, 'upload_image'])->name('upload_image');
    Route::get('get-user-list-filter', [CommonController::class, 'get_user_list_filter'])->name('get_user_list_filter');
});

Route::get('/seller-register', [SellerAuthController::class, 'sellerregister'])->name('register.get');
Route::post('/seller-register', [SellerAuthController::class, 'sellerstore'])->name('register.add');
Route::post('/seller/kyc/complete', [SellerAuthController::class, 'completeKyc'])->name('seller.kyc.complete');



Route::get('/seller/verify-otp', function () {
    return view('email.verify_otp');
})->name('seller.verify.otp.view');

Route::post('/seller/verify-otp', [SellerAuthController::class, 'verifyOtp'])->name('seller.verify.otp');


// Route::get('/seller/verify-otp', function () {
//     return view('email.verify_otp');
// })->name('seller.verify.otp');

// Route::post('/seller/verify-otp', [SellerAuthController::class, 'verifyOtp'])->name('seller.verify.otp');


Route::get('/seller-login', function () {
    return view('frontend.selleruser.login');
})->name('seller.login');

Route::post('/seller-login', [SellerAuthController::class, 'login'])->name('seller.login');
// Forgot Password routes
Route::get('seller/forgot-password', [SellerAuthController::class, 'showForgotPasswordForm'])->name('seller.password.request');
Route::post('seller/forgot-password', [SellerAuthController::class, 'sendResetLinkEmail'])->name('seller.password.email');
Route::get('seller/reset-password/{token}', [SellerAuthController::class, 'showResetPasswordForm'])->name('seller.reset.password.form');
Route::post('seller/reset-password', [SellerAuthController::class, 'resetPassword'])->name('seller.reset.password');
Route::post('/seller/update-profile', [Dashboard::class, 'updateProfile'])->name('seller.update-profile');
Route::post('/seller/update-image', [Dashboard::class, 'updateImage'])->name('seller.update-image');

// Webhook Management Routes
Route::get('/seller/webhooks', [\App\Http\Controllers\selleradmin\WebhookController::class, 'index'])->name('seller.webhooks.index');
Route::get('/seller/webhooks/create', [\App\Http\Controllers\selleradmin\WebhookController::class, 'create'])->name('seller.webhooks.create');
Route::post('/seller/webhooks', [\App\Http\Controllers\selleradmin\WebhookController::class, 'store'])->name('seller.webhooks.store');
Route::get('/seller/webhooks/{id}/edit', [\App\Http\Controllers\selleradmin\WebhookController::class, 'edit'])->name('seller.webhooks.edit');
Route::put('/seller/webhooks/{id}', [\App\Http\Controllers\selleradmin\WebhookController::class, 'update'])->name('seller.webhooks.update');
Route::delete('/seller/webhooks/{id}', [\App\Http\Controllers\selleradmin\WebhookController::class, 'destroy'])->name('seller.webhooks.destroy');
Route::get('/seller/webhooks/{id}/test', [\App\Http\Controllers\selleradmin\WebhookController::class, 'test'])->name('seller.webhooks.test');
Route::get('/seller/webhooks/{id}/toggle', [\App\Http\Controllers\selleradmin\WebhookController::class, 'toggleStatus'])->name('seller.webhooks.toggle');

Route::post('/create-shipment', action: [ShipmentController::class, 'createShipment'])->name('order.shipment');
Route::post('/create-shipment-shopify', [ShipmentController::class, 'createShipmentshopify'])->name('order.shipment.shopify');

Route::post('/create-shipment-shopify', action: [ShipmentController::class, 'createShipmentshopify'])->name('order.shipment.shopify');
Route::get('/order/edit/{id}', [ShipmentController::class, 'orderedit'])->name('seller.orderedit');
Route::post('/order/update/{id}', [ShipmentController::class, 'orderupdate'])->name('seller.orderupdate');
Route::post('/track-order', [TrackController::class, 'trackOrder'])->name('order.track');
Route::post('/seller/rate-check', [RateCardController::class, 'checkRate'])->name('seller.check.rate');
Route::post('cancelshipment', [CancelShipmentController::class, 'checkRate'])->name('cancel.shipment');

// AWB Search API Routes
Route::get('/api/search-awb', [Dashboard::class, 'searchAwb'])->name('api.search.awb');
Route::get('/api/order-details/{awb}', [Dashboard::class, 'getOrderDetails'])->name('api.order.details');

Route::get('/order-details', [\App\Http\Controllers\Frontend\TrackController::class, 'trackOrder'])
    ->name('order.details');
Route::get('/seller-order-details', [\App\Http\Controllers\Frontend\AdmintrackController::class, 'trackOrder'])
    ->name('seller.order.details');


Route::post('/assign-courier', action: [ShipmentController::class, 'assignCourier'])->name('assign.Courier');
Route::post('/assign-courier-bulk', action: [ShipmentController::class, 'assignCourier_bulk'])->name('assign.Courier.Bulk');






// WhatsApp Marketing System Routes
// Route::get('/SendWhatsApp-index', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'index'])->name('SendWhatsApp.index');
// Route::get('/SendWhatsApp-create', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'create'])->name('SendWhatsApp.create');
// Route::post('/SendWhatsApp-store', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'store'])->name('SendWhatsApp.store');
// Route::post('/SendWhatsApp-upload-excel', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'uploadExcel'])->name('SendWhatsApp.upload-excel');
// Route::get('/SendWhatsApp-download-sample', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'downloadSampleExcel'])->name('SendWhatsApp.download-sample');
// Route::get('/SendWhatsApp-get-numbers', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'getNumbers'])->name('SendWhatsApp.get-numbers');
// Route::delete('/SendWhatsApp-{id}', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'destroy'])->name('SendWhatsApp.destroy');
// Route::post('/SendWhatsApp-send-message', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'sendMessage'])->name('SendWhatsApp.send-message');

// Route::get('/SendWhatsApp-index', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'index'])->name('SendWhatsApp.index');
// Route::get('/SendWhatsApp-create', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'create'])->name('SendWhatsApp.create');
// Route::post('/SendWhatsApp-store', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'store'])->name('SendWhatsApp.store');
// Route::post('/SendWhatsApp-upload-excel', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'uploadExcel'])->name('SendWhatsApp.upload-excel');
// Route::get('/SendWhatsApp-get-numbers', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'getNumbers'])->name('SendWhatsApp.get-numbers');
// Route::delete('/SendWhatsApp-{id}', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'destroy'])->name('SendWhatsApp.destroy');
// Route::post('/SendWhatsApp-send-message', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'sendMessage'])->name('SendWhatsApp.send-message');




Route::post('/seller/orders/bulk-label-download', [Dashboard::class, 'bulkLabelDownload'])->name('seller.order.bulk-label-download');

// Custom Label Routes
Route::get('/seller/orders/{id}/custom-label', [Dashboard::class, 'generateCustomLabel'])->name('seller.order.custom-label');
Route::post('/seller/orders/bulk-custom-labels', [Dashboard::class, 'generateBulkCustomLabels'])->name('seller.order.bulk-custom-labels');
Route::get('/seller/orders/{id}/custom-label/download', [Dashboard::class, 'downloadCustomLabel'])->name('seller.order.custom-label.download');
Route::post('/seller/orders/bulk-custom-labels/download', [Dashboard::class, 'downloadBulkCustomLabels'])->name('seller.order.bulk-custom-labels.download');

 

Route::middleware(['seller'])->prefix('seller')->group(function () {


Route::get('/custom-label', [LebalController::class, 'index'])->name('seller.custom-label.index');
Route::post('/custom-label', [LebalController::class, 'store'])->name('seller.custom-label.store');

// Custom Label Generation Routes with new layout support
// Route::get('/orders/{id}/custom-label', [Dashboard::class, 'generateCustomLabel'])->name('seller.order.custom-label');
// Route::post('/orders/bulk-custom-labels', [Dashboard::class, 'generateBulkCustomLabels'])->name('seller.order.bulk-custom-labels');
// Route::get('/orders/{id}/custom-label/download', [Dashboard::class, 'downloadCustomLabel'])->name('seller.order.custom-label.download');
// Route::post('/orders/bulk-custom-labels/download', [Dashboard::class, 'downloadBulkCustomLabels'])->name('seller.order.bulk-custom-labels.download');

    Route::get('/shipment/report', [OrdersReportsController::class, 'shipment_report'])->name('seller.shipment.report');
    Route::get('/shipment-report/download', [OrdersReportsController::class, 'shipment_report_download'])->name('shipment.report.download');



    Route::get('/dashboard', [Dashboard::class, 'index'])->name('seller.dashboard');
    Route::get('/happy-new-year', [Dashboard::class, 'happynewyear'])->name('seller.happynewyear');



    Route::get('/seller/order-status', [Dashboard::class, 'orderstatus'])->name('seller.orderstatus');
    Route::post('/seller-logout', [SellerAuthController::class, 'logout'])->name('seller.logout');
    Route::get('/order', [Dashboard::class, 'order'])->name('seller.order');
    // Route::get('/help', [Dashboard::class, 'index'])->name('seller.help');
    Route::get('/orders/export-excel', [Dashboard::class, 'exportOrdersExcel'])->name('seller.orders.export-excel');
    Route::get('/orders/export-assigned-excel', [Dashboard::class, 'exportAssignedOrdersExcel'])->name('seller.orders.export-assigned-excel');
        Route::post('/orders/update-warehouse', [Dashboard::class, 'updateWarehouseBulk'])
            ->name('seller.orders.update-warehouse');


        Route::get('/reverse-order', [ReversepickupController::class, 'reverse_order'])->name('seller.reverse.order');
        Route::get('/reverse-order-add', [ReversepickupController::class, 'reverseorderadd'])->name('seller.reverseorderadd');


 Route::get('/seller/orders/download-template', [Dashboard::class, 'downloadExcelTemplate'])->name('seller.orders.download-template');
Route::post('/seller/orders/import', [Dashboard::class, 'importOrders'])->name('seller.orders.import');

    // Route::post('/seller/courier/bulk', [Dashboard::class, 'bulkShip'])->name('seller.courier.bulk');

    Route::get('/courier/Assigned', [Dashboard::class, 'index_Assigned'])->name('seller.courier.Assigned');
    Route::post('/orders/clone', [Dashboard::class, 'clone'])->name('seller.orders.clone');

    Route::get('/courier/Cancelled', [Dashboard::class, 'Cancelled_order'])->name('seller.courier.Cancelled');
    Route::get('/courier/all', [Dashboard::class, 'all_order'])->name('seller.courier.all');
    Route::get('/courier/other', [Dashboard::class, 'index_other'])->name('seller.courier.other');
    Route::get('/orders/track', [Dashboard::class, 'trackOrder'])->name('seller.order.track');
    
    // Weight Discrepancy Routes
    Route::get('/weight/discrepancy', [WeightDispatchingController::class, 'index'])->name('seller.weight.discrepancy');
    Route::get('/weight/discrepancy/excel', [WeightDispatchingController::class, 'downloadExcel'])->name('seller.weight.discrepancy.excel');
    Route::post('/weight/discrepancy/submit', [WeightDispatchingController::class, 'submit'])->name('seller.weight.discrepancy.submit');

    Route::get('/courier/In-Transit', [Dashboard::class, 'InTransit'])->name('seller.courier.InTransit');
    Route::get('/courier/Out-For-Delivery', [Dashboard::class, 'OutForDelivery'])->name('seller.courier.OutForDelivery');
    Route::get('/courier/Delivered', [Dashboard::class, 'Delivered'])->name('seller.courier.Delivered');
    Route::get('/courier/NDR', [Dashboard::class, 'NDR'])->name('seller.courier.NDR');
    Route::get('/courier/RTO', [Dashboard::class, 'RTO'])->name('seller.courier.RTO');


    Route::get('/ndr', [NdrController::class, 'index'])->name('seller.ndr');
    
    Route::get('/ndr/create', [NdrController::class, 'create'])->name('seller.ndr.create');
    Route::get('/ndr/create', [NdrController::class, 'create'])->name('seller.ndr.create');
    Route::post('/ndr/store', [NdrController::class, 'store'])->name('seller.ndr.store');
    Route::get('/pickup-request/create', [PickupRequestController::class, 'index'])->name('pickuprequest.create');
    Route::post('/pickup-request/store', [PickupRequestController::class, 'store'])->name('pickuprequest.store');
    Route::get('/pincode/service', [PincodeServiceabilityController::class, 'index'])->name('pincode.service');
    Route::get('/pincode/check', [PincodeServiceabilityController::class, 'pincodeservice'])->name('pincode.check');
    Route::get('/order-add', [Dashboard::class, 'orderadd'])->name('seller.orderadd');
    Route::middleware(['auth:seller'])->group(function () {
        Route::post('/orders', [\App\Http\Controllers\selleradmin\OrderController::class, 'store'])
            ->name('api.orders.create');
    });



    // Edit Order Routes




    // Route::get('channels/channel-list', [ShopifyController::class, 'channelList'])->name('seller.channel.list');
    // Route::get('channels/channel-add', [ShopifyController::class, 'channelAdd'])->name('seller.channel.add');

    // Route::get('shopify-integration', [ShopifyController::class, 'shopifyIntegration'])->name('seller.shopify.integration');

    // Route::get('/woocommerce-integration', [ShopifyController::class, 'woocommerceIntegration'])->name('seller.woocommerce.integration');

    // Route::get('/magento-integration', [ShopifyController::class, 'magentoIntegration'])->name('seller.magento.integration');

    // Route::get('/bigcommerce-integration', [ShopifyController::class, 'bigcommerceIntegration'])->name('seller.bigcommerce.integration');



    // Route::get('/ticket', [\App\Http\Controllers\selleradmin\TicketController::class, 'index'])
            // ->name('seller.ticket.get');
    Route::post('/seller/agreement/accept', [Dashboard::class, 'acceptAgreement'])->name('seller.agreement.accept');
    Route::post('/warehouses', [Dashboard::class, 'storeWarehouse'])->name('warehouses.store');
    Route::get('/seller-profile', [Dashboard::class, 'profile'])->name('profile.get');
    Route::get('/view-profile', [Dashboard::class, 'showprofile'])->name('profile.view');
    Route::get('/ticket', [TicketController::class, 'index'])->name('seller.ticket.get');
    Route::get('/ticket/add', [TicketController::class, 'add'])->name('seller.ticket.add');
    Route::post('/seller/tickets/store', [TicketController::class, 'store'])->name('seller.tickets.store');
    Route::post('/seller/profile/update', [Dashboard::class, 'updateProfile'])->name('seller.update.profile');
    Route::post('/seller/update-address', [Dashboard::class, 'updateAddress'])->name('seller.update.address');
    Route::post('/seller/update-bank', [Dashboard::class, 'updateBank'])->name('seller.update.bank');
    Route::post('/change-password', [Dashboard::class, 'changePassword'])->name('seller.change.password');
    Route::get('/ndr', [ResourceController::class, 'index'])->name('seller.ndr');
    Route::get('/passbook', [ResourceController::class, 'passbook'])->name('seller.passbook');
    Route::get('/cod-remittance', [ResourceController::class, 'cod'])->name('seller.cod');
    Route::get('/help', [ResourceController::class, 'help'])->name('seller.help');
    Route::get('/api', [ResourceController::class, 'api'])->name('seller.api');
    Route::get('/recharge', [ResourceController::class, 'recharge'])->name('seller.recharge');
    Route::get('/shipping-charge', [ResourceController::class, 'shippingcharge'])->name('seller.shippingcharge');
    Route::get('/all-charges', [ResourceController::class, 'allcharges'])->name('seller.allcharges');
    Route::get('/invoice', [ResourceController::class, 'invoice'])->name('seller.invoice');
    Route::get('/invoice/show', [InvoiceController::class, 'index'])->name('seller.invoice.show');
    Route::get('/invoice/print', [InvoiceController::class, 'printableView'])->name('invoice.print');


    Route::get('/warehouse', [WarehosueController::class, 'index'])->name('seller.warehouse');

    Route::get('/warehouse/create', [WarehosueController::class, 'add'])->name('seller.warehouse.create');
    Route::get('/warehouse/index', [WarehosueController::class, 'indexwarehouse'])->name('seller.warehouse.index');
    Route::get('/warehouse/edit/{id}', [WarehosueController::class, 'indexwarehouseedit'])->name('seller.warehouse.edit');
    Route::post('/warehouse/update{id}', [WarehosueController::class, 'updateWarehouse'])->name('seller.warehouse.update');
   Route::match(['get', 'post'], '/warehouse/delete{id}', [WarehosueController::class, 'deleteWarehouse'])->name('seller.warehouse.delete');



    Route::get('/invoice/show', [InvoiceController::class, 'index'])->name('seller.invoice.show');
    Route::post('/warehouse/create', [WarehosueController::class, 'createWarehouse'])->name('seller.warehouse.create');
    Route::get('/manifestiation', [Manifestiation::class, 'index'])->name('seller.manifestiation');
    Route::post('/manifest/create', [Manifestiation::class, 'createManifest'])->name('seller.manifest.create');

    Route::get('/courier/{id}', [CourierController::class, 'index'])->name('seller.courier');
    Route::get('reverse-courier/{id}', [CourierController::class, 'reverseindex'])->name('seller.reverse.courier');

     Route::post('/seller/courier/bulk', [CourierController::class, 'indexbulk'])->name('seller.courier.bulk');


    Route::get('/invoice/create', [InvoiceController::class, 'add'])->name('seller.invoice.add');
    Route::get('/credit-note', [ResourceController::class, 'creditnote'])->name('seller.creditnote');
    Route::get('/shippment-price', [ToolsController::class, 'shipmentpricelist'])->name('seller.shipmentpricelist');
    Route::get('/activity-logs', [ToolsController::class, 'activitylog'])->name('seller.activitylog');
    Route::get('/track-order', [ResourceController::class, 'trackorder'])->name('seller.trackorder');
    Route::get('/rate-cards', [ToolsController::class, 'ratecard'])->name('seller.ratecards');


    Route::get('/seller/monthly/report/{month}', [InvoiceController::class, 'sellermonthly'])->name('seller.monthly.report');
    Route::get('seller/invoice/pdf', [InvoiceController::class, 'downloadInvoice'])->name('seller.invoice.pdf');

    Route::get('/track-order-in', [ResourceController::class, 'trackorderin'])->name('seller.trackorder.in');




    Route::get('/xpressbees/ndr', [Dashboard::class, 'fetchNdrData'])->name('seller.xpressbees.ndr');




    Route::get('/weight-discrepancy', [WeightDispatchingController::class, 'index'])->name('seller.weight.discrepancy');


});
// Notification routes
Route::get('/notifications', [ShippingNotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/toggle/{id}', [ShippingNotificationController::class, 'toggle'])->name('notifications.toggle');
Route::post('/notifications/update-template/{id}', [ShippingNotificationController::class, 'updateTemplate'])->name('notifications.updateTemplate');
Route::post('/notifications/update', [ShippingNotificationController::class, 'update'])->name('notifications.update');




Route::middleware(['auth'])->group(function () {
    Route::get('impersonate/seller/{id}', [\App\Http\Controllers\admin\SellerImpersonateController::class, 'loginAsSeller'])->name('seller.impersonate');
});
Route::post('/recharge', [Dashboard::class, 'save'])->name('recharge.add');

Route::get('/seller/recharge/payu/{id}', [Dashboard::class, 'redirectToPayU'])->name('seller.recharge.payu');

// WhatsApp Message Routes
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::prefix('send-whatsapp-message')->name('send-whatsapp-message.')->group(function () {
   
    });
});
// In web.php
// Route::get('/payu-success/{id}', [Dashboard::class, 'payuSuccess'])->name('payment.success');
// Route::get('/payu-failure/{id}', [Dashboard::class, 'payuFailure'])->name('payment.failure');


// Route::get('/pesa-payment-success/{id}', [Dashboard::class, 'payuSuccess_pesa'])->name('pesa.payu.success');
// Route::get('/pesa-payment-failure/{id}', [Dashboard::class, 'payuFailure_pesa'])->name('pesa.payu.failure');


// Route::get('/pesa-payment-success-new/{id}', [Dashboard::class, 'payuSuccess_pesa'])->name('pesa.payu.success.new');
// Route::get('/pesa-payment-failure-new/{id}', [Dashboard::class, 'payuFailure_pesa'])->name('pesa.payu.failure.new');
Route::match(['get', 'post'], '/pesa-payment-success-new/{id}', [Dashboard::class, 'payuSuccess_pesa'])->name('pesa.payu.success.new');
Route::match(['get', 'post'], '/pesa-payment-failure-new/{id}', [Dashboard::class, 'payuFailure_pesa'])->name('pesa.payu.failure.new');

// Route::get('pay-u-money',   [Dashboard::class,'payUMoneyView']);
// Route::post('pay-u-response',[Dashboard::class,'payuSuccess'])
//      ->name('payment.success');
// Route::get('pay-u-cancel',   [Dashboard::class,'payuFailure'])
//      ->name('payment.failure');

Route::post('/get-in-touch', [FormsController::class, 'sellerstore'])->name('getintouch.store');
Route::post('/inquiries', [FormsController::class, 'inquiries'])->name('inquiries.store');
Route::get('impersonate/leave', function () {
    $adminId = session('admin_impersonator_id');
    if ($adminId) {
        Auth::guard('web')->loginUsingId($adminId);
        Session::forget('admin_impersonator_id');
        return redirect()->route('admin.dashboard')->with('success', 'Returned to admin account.');
    }
    return redirect()->route('login');
})->name('impersonate.leave');



Route::fallback(function () {
    abort(404);
});
