<?php

use App\Http\Controllers\admin\BeyondWorkController;
use App\Http\Controllers\admin\BrandController;

use App\Http\Controllers\admin\logisticsController;
use App\Http\Controllers\admin\PriceSettingController;
use App\Http\Controllers\admin\ZonePriceSettingController;

use App\Http\Controllers\admin\ActiveslebsController;

use App\Http\Controllers\admin\CareerController;
use App\Http\Controllers\admin\CareerDetailsController;
use App\Http\Controllers\admin\CleintController;
use App\Http\Controllers\admin\ClientsController;
use App\Http\Controllers\admin\GetInTouchController;
use App\Http\Controllers\admin\InquiriesController;
use App\Http\Controllers\admin\KycManageController;
use App\Http\Controllers\admin\LandingBrandController;
use App\Http\Controllers\admin\PrivacyPolicyController;
use App\Http\Controllers\admin\RateCardController;
use App\Http\Controllers\admin\RechargeController;
use App\Http\Controllers\admin\RefundPolicyController;
use App\Http\Controllers\admin\SellerListController;
use App\Http\Controllers\admin\StaticDataController;
use App\Http\Controllers\admin\TeamSpiritController;
use App\Http\Controllers\admin\TermsConditionController;
use App\Http\Controllers\admin\TestimonialController;
use App\Http\Controllers\admin\TicketCategoryController;
use App\Http\Controllers\admin\WorkCultureController;
use App\Routes\Profile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\CmsController;
use App\Http\Controllers\admin\CityController;
use App\Http\Controllers\admin\HomeController;
use App\Http\Controllers\admin\RolesController;
use App\Http\Controllers\admin\StateController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\admin\SettingController;
use App\Http\Controllers\admin\TermsAndCondition;
use App\Http\Controllers\admin\WeightDispatchingController;
use App\Http\Controllers\admin\CODRemittanceController;
use App\Exports\AllShipmentReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\admin\InvoiceController;
use App\Http\Controllers\admin\Negative_Balance_SellerController;
use App\Http\Controllers\admin\StatusupdateController;
use App\Http\Controllers\admin\RtoamountController;

/*
|--------------------------------------------------------------------------
| Web Routes For Admin
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Admin & Sub-Admin Routes
Route::middleware(['auth', 'permission', 'authCheck', 'verified'])->group(function () {

    // Courier & Rate Manager (new isolated module)
    Route::get('courier-rate-manager', [\App\Http\Controllers\admin\CourierRateManagerController::class, 'index'])->name('courier.rate.manager');
    Route::get('courier-rate-manager/accounts', [\App\Http\Controllers\admin\CourierRateManagerController::class, 'getCourierAccounts'])->name('courier.rate.manager.accounts');
    Route::get('courier-rate-manager/seller-assignment', [\App\Http\Controllers\admin\CourierRateManagerController::class, 'getSellerAssignment'])->name('courier.rate.manager.seller.assignment');
    Route::post('courier-rate-manager/assign-seller', [\App\Http\Controllers\admin\CourierRateManagerController::class, 'assignSeller'])->name('courier.rate.manager.assign.seller');
    Route::get('courier-rate-manager/slabs', [\App\Http\Controllers\admin\CourierRateManagerController::class, 'getSlabs'])->name('courier.rate.manager.slabs');
    Route::get('courier-rate-manager/slab-rates', [\App\Http\Controllers\admin\CourierRateManagerController::class, 'getSlabRates'])->name('courier.rate.manager.slab.rates');
    Route::post('courier-rate-manager/save-slab', [\App\Http\Controllers\admin\CourierRateManagerController::class, 'saveSlab'])->name('courier.rate.manager.save.slab');
    Route::get('courier-master', [\App\Http\Controllers\admin\CourierMasterController::class, 'index'])->name('courier.master.index');
    Route::post('courier-master', [\App\Http\Controllers\admin\CourierMasterController::class, 'store'])->name('courier.master.store');
    Route::post('courier-master/{id}/update', [\App\Http\Controllers\admin\CourierMasterController::class, 'update'])->name('courier.master.update');

    Profile::routes();
    Route::get('dashboard', [HomeController::class, 'index'])->name('dashboard');
        Route::get('/shipment-report', [HomeController::class, 'shipment_report'])->name('shipment.report');

        Route::get('/shipment-report/export', function (\Illuminate\Http\Request $request) {
            return Excel::download(new AllShipmentReportExport($request), 'shipment-report.xlsx');
        })->name('shipment.report.export');



Route::get('/SendWhatsApp-index', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'index'])->name('SendWhatsApp.index');
Route::get('/SendWhatsApp-create', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'create'])->name('SendWhatsApp.create');
Route::post('/SendWhatsApp-store', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'store'])->name('SendWhatsApp.store');
Route::post('/SendWhatsApp-upload-excel', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'uploadExcel'])->name('SendWhatsApp.upload-excel');
Route::get('/SendWhatsApp-download-sample', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'downloadSampleExcel'])->name('SendWhatsApp.download-sample');
Route::get('/SendWhatsApp-get-numbers', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'getNumbers'])->name('SendWhatsApp.get-numbers');
Route::delete('/SendWhatsApp-{id}', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'destroy'])->name('SendWhatsApp.destroy');
Route::post('/SendWhatsApp-send-message', [\App\Http\Controllers\admin\SendWhatsAppMessageController::class, 'sendMessage'])->name('SendWhatsApp.send-message');

    // ----------------------- Order Status Update Routes ----------------------------------------------------
    Route::get('/order-status-upload', [HomeController::class, 'orderStatusUploadForm'])->name('order.status.upload.form');
    Route::post('/order-status-upload', [HomeController::class, 'uploadOrderStatus'])->name('order.status.upload');
    Route::get('/order-status-template', [HomeController::class, 'downloadOrderStatusTemplate'])->name('order.status.template');
    Route::get('/search-orders', [HomeController::class, 'searchOrders'])->name('order.search');
    // ----------------------- Role Routes ----------------------------------------------------
    Route::controller(RolesController::class)->name('roles')->group(function () {
        Route::get('roles', 'index')->middleware('isAllow:102,can_view');
        Route::post('roles', 'save')->middleware('isAllow:102,can_add');
        Route::put('roles', 'update')->middleware('isAllow:102,can_edit');
        Route::delete('roles', 'delete')->middleware('isAllow:102,can_delete');
        Route::get('roles/permission/{id}', 'permission')->name('.permission.view')->middleware('isAllow:102,can_edit');
        Route::put('roles/permission', 'permission_update')->name('.permission.update')->middleware('isAllow:102,can_edit');
    });

    // ----------------------- Admin and Sub Admin Routes ----------------------------------------------------
    Route::controller(UsersController::class)->group(function () {
        Route::get('users', 'index')->name('users')->middleware('isAllow:103,can_view');
        Route::get('users/add', 'add')->name('users.add')->middleware('isAllow:103,can_add');
        Route::post('users/add', 'save')->name('users.add')->middleware('isAllow:103,can_add');
        Route::get('users/{slug}', 'edit')->name('users.edit')->middleware('isAllow:103,can_edit');
        Route::post('users/{slug}', 'update')->name('users.edit')->middleware('isAllow:103,can_edit');
        Route::delete('users', 'delete')->name('users')->middleware('isAllow:103,can_delete');
        Route::get('users/permission/{id}', 'permission')->name('users.permission.view')->middleware('isAllow:103,can_edit');
        Route::put('users/permission', 'permission_update')->name('users.permission.update')->middleware('isAllow:103,can_edit');
    });

    // ----------------------- States Routes ----------------------------------------------------
    Route::controller(StateController::class)->name('states')->group(function () {
        Route::get('states', 'index')->middleware('isAllow:105,can_view');
        Route::post('states', 'save')->middleware('isAllow:105,can_add');
        Route::put('states', 'update')->middleware('isAllow:105,can_edit');
        Route::delete('states', 'delete')->middleware('isAllow:105,can_delete');
    });

    // ----------------------- City Routes ----------------------------------------------------
    Route::controller(CityController::class)->name('cities')->group(function () {
        Route::get('cities', 'index')->middleware('isAllow:106,can_view');
        Route::post('cities', 'save')->middleware('isAllow:106,can_add');
        Route::put('cities', 'update')->middleware('isAllow:106,can_edit');
        Route::delete('cities', 'delete')->middleware('isAllow:106,can_delete');
    });

    // ----------------------- CMS Routes ----------------------------------------------------
    Route::controller(CmsController::class)->group(function () {
        Route::get('cms', 'index')->name('cms')->middleware('isAllow:104,can_view');
        Route::get('cms/add', 'add')->name('cms.add')->middleware('isAllow:104,can_add');
        Route::post('cms/add', 'save')->name('cms.add')->middleware('isAllow:104,can_add');
        Route::get('cms/{id}', 'edit')->name('cms.edit')->middleware('isAllow:104,can_edit');
        Route::post('cms', 'slug')->name('cms.slug')->middleware('isAllow:104,can_edit');
        Route::post('cms/{id}', 'update')->name('cms.edit')->middleware('isAllow:104,can_edit');
        Route::delete('cms', 'delete')->name('cms')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Seller List Routes ----------------------------------------------------
    Route::controller(SellerListController::class)->group(function () {
        Route::get('seller-list', 'index')->name('seller-list')->middleware('isAllow:104,can_view');
        Route::get('seller-list/add', 'add')->name('seller-list.add')->middleware('isAllow:104,can_add');
        Route::post('seller-list/add', 'save')->name('seller-list.add')->middleware('isAllow:104,can_add');
        Route::get('seller-list/{id}', 'edit')->name('seller-list.edit')->middleware('isAllow:104,can_edit');
        Route::post('seller-list', 'slug')->name('seller-list.slug')->middleware('isAllow:104,can_edit');
        Route::post('seller-list/{id}', 'update')->name('seller-list.edit')->middleware('isAllow:104,can_edit');
        Route::delete('seller-list', 'delete')->name('seller-list')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Seller List Routes ----------------------------------------------------
    //         Route::post('/recharges/change-status', [RechargeController::class, 'changeStatus'])
    // ->middleware('auth') // Ensure user is authenticated
    // ->name('recharges.changeStatus');
        Route::post('/recharges/change-status', [RechargeController::class, 'changeStatus'])->name('recharges.changeStatus');

    Route::controller(RechargeController::class)->group(function () {
        // Route::get('/recharges/change-status', 'changeStatus')->name('recharges.changeStatus')->middleware('isAllow:107,can_view');
        Route::get('recharges', 'index')->name('recharges')->middleware('isAllow:107,can_view');
        Route::get('recharges/add', 'add')->name('recharges.add')->middleware('isAllow:107,can_add');
        Route::post('recharges/add', 'save')->name('recharges.add')->middleware('isAllow:107,can_add');
        Route::get('recharges/{id}', 'edit')->name('recharges.edit')->middleware('isAllow:107,can_edit');
        Route::post('recharges', 'slug')->name('recharges.slug')->middleware('isAllow:107,can_edit');
        Route::post('recharges/{id}', 'update')->name('recharges.edit')->middleware('isAllow:107,can_edit');
        Route::delete('recharges', 'delete')->name('recharges')->middleware('isAllow:107,can_delete');

        Route::get('balance', 'indexRecharge')->name('balance')->middleware('isAllow:107,can_view');
        Route::get('/recharge/export', 'exportRecharge')->name('recharge.export');



    });

    // ----------------------- Inquiries List Routes ----------------------------------------------------
    Route::controller(InquiriesController::class)->group(function () {
        Route::get('inquiries', 'index')->name('inquiries')->middleware('isAllow:104,can_view');
        Route::get('inquiries/add', 'add')->name('inquiries.add')->middleware('isAllow:104,can_add');
        Route::post('inquiries/add', 'save')->name('inquiries.add')->middleware('isAllow:104,can_add');
        Route::get('inquiries/{id}', 'edit')->name('inquiries.edit')->middleware('isAllow:104,can_edit');
        Route::post('inquiries', 'slug')->name('inquiries.slug')->middleware('isAllow:104,can_edit');
        Route::post('inquiries/{id}', 'update')->name('inquiries.edit')->middleware('isAllow:104,can_edit');
        Route::delete('inquiries', 'delete')->name('inquiries')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Testimonials List Routes ----------------------------------------------------
    Route::controller(TestimonialController::class)->group(function () {
        Route::get('testimonial', 'index')->name('testimonial')->middleware('isAllow:104,can_view');
        Route::get('testimonial/add', 'add')->name('testimonial.add')->middleware('isAllow:104,can_add');
        Route::post('testimonial/add', 'save')->name('testimonial.add')->middleware('isAllow:104,can_add');
        Route::get('testimonial/{id}', 'edit')->name('testimonial.edit')->middleware('isAllow:104,can_edit');
        Route::post('testimonial', 'slug')->name('testimonial.slug')->middleware('isAllow:104,can_edit');
        Route::post('testimonial/{id}', 'update')->name('testimonial.edit')->middleware('isAllow:104,can_edit');
        Route::delete('testimonial', 'delete')->name('testimonial')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Get In Toch Routes ----------------------------------------------------
    Route::controller(GetInTouchController::class)->group(function () {
        Route::get('get-in-touch', 'index')->name('get-in-touch')->middleware('isAllow:104,can_view');
        Route::get('get-in-touch/add', 'add')->name('get-in-touch.add')->middleware('isAllow:104,can_add');
        Route::post('get-in-touch/add', 'save')->name('get-in-touch.add')->middleware('isAllow:104,can_add');
        Route::get('get-in-touch/{id}', 'edit')->name('get-in-touch.edit')->middleware('isAllow:104,can_edit');
        Route::post('get-in-touch', 'slug')->name('get-in-touch.slug')->middleware('isAllow:104,can_edit');
        Route::post('get-in-touch/{id}', 'update')->name('get-in-touch.edit')->middleware('isAllow:104,can_edit');
        Route::delete('get-in-touch', 'delete')->name('get-in-touch')->middleware('isAllow:104,can_delete');
    });

     // ----------------------- Privacy Policy List Routes ----------------------------------------------------
     Route::controller(PrivacyPolicyController::class)->group(function () {
        Route::get('privacy-policy', 'index')->name('privacy-policy')->middleware('isAllow:104,can_view');
        Route::get('privacy-policy/add', 'add')->name('privacy-policy.add')->middleware('isAllow:104,can_add');
        Route::post('privacy-policy/add', 'save')->name('privacy-policy.add')->middleware('isAllow:104,can_add');
        Route::get('privacy-policy/{id}', 'edit')->name('privacy-policy.edit')->middleware('isAllow:104,can_edit');
        Route::post('privacy-policy', 'slug')->name('privacy-policy.slug')->middleware('isAllow:104,can_edit');
        Route::post('privacy-policy/{id}', 'update')->name('privacy-policy.edit')->middleware('isAllow:104,can_edit');
        Route::delete('privacy-policy', 'delete')->name('privacy-policy')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Refund Policy Routes ----------------------------------------------------
    Route::controller(RefundPolicyController::class)->group(function () {
        Route::get('refund-policy', 'index')->name('refund-policy')->middleware('isAllow:104,can_view');
        Route::get('refund-policy/add', 'add')->name('refund-policy.add')->middleware('isAllow:104,can_add');
        Route::post('refund-policy/add', 'save')->name('refund-policy.add')->middleware('isAllow:104,can_add');
        Route::get('refund-policy/{id}', 'edit')->name('refund-policy.edit')->middleware('isAllow:104,can_edit');
        Route::post('refund-policy', 'slug')->name('refund-policy.slug')->middleware('isAllow:104,can_edit');
        Route::post('refund-policy/{id}', 'update')->name('refund-policy.edit')->middleware('isAllow:104,can_edit');
        Route::delete('refund-policy', 'delete')->name('refund-policy')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Clients Routes ----------------------------------------------------
    Route::controller(RefundPolicyController::class)->group(function () {
        Route::get('client', 'index')->name('refund-policy')->middleware('isAllow:104,can_view');
        Route::get('client/add', 'add')->name('refund-policy.add')->middleware('isAllow:104,can_add');
        Route::post('client/add', 'save')->name('refund-policy.add')->middleware('isAllow:104,can_add');
        Route::get('client/{id}', 'edit')->name('client.edit')->middleware('isAllow:104,can_edit');
        Route::post('client', 'slug')->name('client.slug')->middleware('isAllow:104,can_edit');
        Route::post('client/{id}', 'update')->name('client.edit')->middleware('isAllow:104,can_edit');
        Route::delete('client', 'delete')->name('client')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Brands List Routes ----------------------------------------------------
    Route::controller(BrandController::class)->group(function () {
        Route::get('brands', 'index')->name('brands')->middleware('isAllow:104,can_view');
        Route::get('brands/add', 'add')->name('brands.add')->middleware('isAllow:104,can_add');
        Route::post('brands/add', 'save')->name('brands.add')->middleware('isAllow:104,can_add');
        Route::get('brands/{id}', 'edit')->name('brands.edit')->middleware('isAllow:104,can_edit');
        Route::post('brands', 'slug')->name('brands.slug')->middleware('isAllow:104,can_edit');
        Route::post('brands/{id}', 'update')->name('brands.edit')->middleware('isAllow:104,can_edit');
        Route::delete('brands', 'delete')->name('brands')->middleware('isAllow:104,can_delete');
    });
    
    
     Route::controller(ActiveslebsController::class)->group(function () {
        Route::get('active-slebs-add', 'add')->name('active.slebs.add')->middleware('isAllow:104,can_add');
        Route::post('pricesetting/status-update', 'updateStatus')->name('pricesetting.courier.update')->middleware('isAllow:104,can_add');


    });
        // ----------------------- logistics List Routes ----------------------------------------------------
    Route::controller(logisticsController::class)->group(function () {
        Route::get('logistics', 'index')->name('logistics');
        Route::get('logistics/add', 'add')->name('logistics.add')->middleware('isAllow:104,can_add');
        Route::post('logistics/data/add', 'save')->name('logistics.data.add')->middleware('isAllow:104,can_add');
        Route::get('logistics/{id}', 'edit')->name('logistics.edit')->middleware('isAllow:104,can_edit');
        Route::post('logistics', 'slug')->name('logistics.slug')->middleware('isAllow:104,can_edit');
        Route::post('logistics/{id}', 'update')->name('logistics.edit')->middleware('isAllow:104,can_edit');
        Route::delete('logistics', 'delete')->name('logistics')->middleware('isAllow:104,can_delete');
        // Route::delete('logistics', 'delete')->name('logistics')->middleware('isAllow:104,can_delete');
    });
     Route::match(['get', 'post'], 'logistics/status/{id}/{status}', [logisticsController::class, 'status'])->name('logistics.status');
    Route::match(['get', 'post'], 'logistics/delete/{id}', [logisticsController::class, 'destroy'])->name('logistics.delete');




        // ----------------------- logistics List Routes ----------------------------------------------------
    Route::controller(PriceSettingController::class)->group(function () {
        Route::get('pricesetting', 'index')->name('pricesetting');
        Route::get('pricesetting/add', 'add')->name('pricesetting.add')->middleware('isAllow:104,can_add');
        Route::post('pricesetting/data/add', 'save')->name('pricesetting.data.add')->middleware('isAllow:104,can_add');
        Route::get('pricesetting/{id}', 'edit')->name('pricesetting.edit')->middleware('isAllow:104,can_edit');
        Route::post('pricesetting', 'slug')->name('pricesetting.slug')->middleware('isAllow:104,can_edit');
        //  Route::post('pricesetting/{id}', 'update')->name('pricesetting.update')->middleware('isAllow:104,can_edit');
        // Route::delete('price/setting/delete/{id}', 'delete')->name('pricesetting.price.delete')->middleware('isAllow:104,can_delete');
        // Route::delete('logistics', 'delete')->name('logistics')->middleware('isAllow:104,can_delete');
         Route::post('pricesetting/update/{id}', 'update')->name('pricesetting.price.update')->middleware('isAllow:104,can_edit');
        Route::delete('price-setting-price-delete/{id}', 'delete')->name('price.setting.price.delete')->middleware('isAllow:104,can_delete');

    });




        Route::controller(ZonePriceSettingController::class)->group(function () {
            Route::get('zone-price-index', 'index')->name('zone.price.index')->middleware('isAllow:104,can_add');
            Route::get('zone-price-add', 'add')->name('zone.pricesetting.add')->middleware('isAllow:104,can_add');
             Route::post('zone-price-save', 'save')->name('zone.pricesetting.save')->middleware('isAllow:104,can_add');
             Route::post('zone-price-upload', 'uploadExcel')->name('zone.pricesetting.upload.excel')->middleware('isAllow:104,can_add');
            Route::get('zone-price-download-template', 'downloadTemplate')->name('zone.pricesetting.download.template')->middleware('isAllow:104,can_add');
             Route::get('zone-pricesetting-edit/{id}', 'edit')->name('zone.pricesetting.edit')->middleware('isAllow:104,can_edit');
         Route::post('zone-pricesetting-update/{id}', 'update')->name('zone.pricesetting.update')->middleware('isAllow:104,can_edit');

        // Route::get('zone-pricesetting-add', 'add')->name('zone.pricesetting.add')->middleware('isAllow:104,can_add');
        // Route::post('zone-pricesetting-data-add', 'save')->name('zone.pricesetting.data.add')->middleware('isAllow:104,can_add');
     
    });

     Route::match(['get', 'post'], 'pricesetting/status/{id}/{status}', [logisticsController::class, 'status'])->name('pricesetting.status');
    Route::match(['get', 'post'], 'pricesetting/delete/{id}', [logisticsController::class, 'destroy'])->name('pricesetting.delete');

    Route::get('get-orders-page', [StatusupdateController::class, 'getOrderspage'])->name('get.orders.page');
    Route::get('get-orders', [StatusupdateController::class, 'getOrders'])->name('get.orders');
    Route::post('update-status-all', [StatusupdateController::class, 'updateStatusall'])->name('update.status.all');

   Route::get('get-rto-page', [RtoamountController::class, 'getrtopage'])->name('get.rto.page');
    Route::get('get-rto', [RtoamountController::class, 'getrtoOrders'])->name('get.rto');

// Route::post('update-status', [StatusupdateController::class, 'updateStatus'])->name('update.status.seller');






        Route::controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'index')->name('invoices');
        Route::get('invoices/add', 'add')->name('invoices.add')->middleware('isAllow:104,can_add');
        Route::post('invoices/data/add', 'save')->name('invoices.data.add')->middleware('isAllow:104,can_add');
        Route::get('invoices/{id}', 'edit')->name('invoices.edit')->middleware('isAllow:104,can_edit');
        Route::post('invoices', 'slug')->name('invoices.slug')->middleware('isAllow:104,can_edit');
         Route::post('invoices/update/{id}', 'update')->name('invoices.update')->middleware('isAllow:104,can_edit');
        Route::delete('invoices/delete/{id}', 'delete')->name('invoices.delete')->middleware('isAllow:104,can_delete');
    });
    
    // Invoice specific routes
    Route::get('all/seller/monthly/report/{month}', [InvoiceController::class, 'sellermonthly'])->name('all.seller.monthly.report');
    Route::get('all/seller/invoice/pdf', [InvoiceController::class, 'downloadInvoice'])->name('all.seller.invoice.pdf');
    Route::get('all/seller/invoice/bulk-download/{month}', [InvoiceController::class, 'bulkDownloadInvoices'])->name('all.seller.invoice.bulk.download');
    Route::post('all/seller/invoice/bulk-selected/{month}', [InvoiceController::class, 'bulkDownloadSelectedInvoices'])->name('all.seller.invoice.bulk.selected');
    Route::get('/admin/seller-invoice/download', [InvoiceController::class, 'downloadInvoice'])
        ->name('admin.seller.invoice.pdf');

     // ----------------------- Landing Brands List Routes ----------------------------------------------------
     Route::controller(LandingBrandController::class)->group(function () {
        Route::get('landing-brands', 'index')->name('landing-brands')->middleware('isAllow:104,can_view');
        Route::get('landing-brands/add', 'add')->name('landing-brands.add')->middleware('isAllow:104,can_add');
        Route::post('landing-brands/add', 'save')->name('landing-brands.add')->middleware('isAllow:104,can_add');
        Route::get('landing-brands/{id}', 'edit')->name('landing-brands.edit')->middleware('isAllow:104,can_edit');
        Route::post('landing-brands', 'slug')->name('landing-brands.slug')->middleware('isAllow:104,can_edit');
        Route::post('landing-brands/{id}', 'update')->name('landing-brands.edit')->middleware('isAllow:104,can_edit');
        Route::delete('landing-brands', 'delete')->name('landing-brands')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Career List Routes ----------------------------------------------------
    Route::controller(CareerController::class)->group(function () {
        Route::get('careers', 'index')->name('careers')->middleware('isAllow:104,can_view');
        Route::get('careers/add', 'add')->name('careers.add')->middleware('isAllow:104,can_add');
        Route::post('careers/add', 'save')->name('careers.add')->middleware('isAllow:104,can_add');
        Route::get('careers/{id}', 'edit')->name('careers.edit')->middleware('isAllow:104,can_edit');
        Route::post('careers', 'slug')->name('careers.slug')->middleware('isAllow:104,can_edit');
        Route::post('careers/{id}', 'update')->name('careers.edit')->middleware('isAllow:104,can_edit');
        Route::delete('careers', 'delete')->name('careers')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Career List Routes ----------------------------------------------------
    Route::controller(TicketCategoryController::class)->group(function () {
        Route::get('careers', 'index')->name('careers')->middleware('isAllow:104,can_view');
        Route::get('careers/add', 'add')->name('careers.add')->middleware('isAllow:104,can_add');
        Route::post('careers/add', 'save')->name('careers.add')->middleware('isAllow:104,can_add');
        Route::get('careers/{id}', 'edit')->name('careers.edit')->middleware('isAllow:104,can_edit');
        Route::post('careers', 'slug')->name('careers.slug')->middleware('isAllow:104,can_edit');
        Route::post('careers/{id}', 'update')->name('careers.edit')->middleware('isAllow:104,can_edit');
        Route::delete('careers', 'delete')->name('careers')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Career List Routes ----------------------------------------------------
    Route::controller(RateCardController::class)->group(function () {
        Route::get('rate-card', 'index')->name('rate-card')->middleware('isAllow:104,can_view');
        Route::get('rate-card/add', 'add')->name('rate-card.add')->middleware('isAllow:104,can_add');
        Route::post('rate-card/add', 'save')->name('rate-card.add')->middleware('isAllow:104,can_add');
        Route::get('rate-card/{id}', 'edit')->name('rate-card.edit')->middleware('isAllow:104,can_edit');
        Route::post('rate-card', 'slug')->name('rate-card.slug')->middleware('isAllow:104,can_edit');
        Route::post('rate-card/{id}', 'update')->name('rate-card.edit')->middleware('isAllow:104,can_edit');
        Route::delete('rate-card', 'delete')->name('rate-card')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Career Details List Routes ----------------------------------------------------
    Route::controller(CareerDetailsController::class)->group(function () {
        Route::get('careers-details', 'index')->name('careers-details')->middleware('isAllow:104,can_view');
        Route::get('careers-details/add', 'add')->name('careers-details.add')->middleware('isAllow:104,can_add');
        Route::post('careers-details/add', 'save')->name('careers-details.add')->middleware('isAllow:104,can_add');
        Route::get('careers-details/{id}', 'edit')->name('careers-details.edit')->middleware('isAllow:104,can_edit');
        Route::post('careers-details', 'slug')->name('careers-details.slug')->middleware('isAllow:104,can_edit');
        Route::post('careers-details/{id}', 'update')->name('careers-details.edit')->middleware('isAllow:104,can_edit');
        Route::delete('careers-details', 'delete')->name('careers-details')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Work Culture List Routes ----------------------------------------------------
    Route::controller(WorkCultureController::class)->group(function () {
        Route::get('work-culture', 'index')->name('work-culture')->middleware('isAllow:104,can_view');
        Route::get('work-culture/add', 'add')->name('work-culture.add')->middleware('isAllow:104,can_add');
        Route::post('work-culture/add', 'save')->name('work-culture.add')->middleware('isAllow:104,can_add');
        Route::get('work-culture/{id}', 'edit')->name('work-culture.edit')->middleware('isAllow:104,can_edit');
        Route::post('work-culture', 'slug')->name('work-culture.slug')->middleware('isAllow:104,can_edit');
        Route::post('work-culture/{id}', 'update')->name('work-culture.edit')->middleware('isAllow:104,can_edit');
        Route::delete('work-culture', 'delete')->name('work-culture')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Career List Routes ----------------------------------------------------
    Route::controller(BeyondWorkController::class)->group(function () {
        Route::get('beyond-work', 'index')->name('beyond-work')->middleware('isAllow:104,can_view');
        Route::get('beyond-work/add', 'add')->name('beyond-work.add')->middleware('isAllow:104,can_add');
        Route::post('beyond-work/add', 'save')->name('beyond-work.add')->middleware('isAllow:104,can_add');
        Route::get('beyond-work/{id}', 'edit')->name('beyond-work.edit')->middleware('isAllow:104,can_edit');
        Route::post('beyond-work', 'slug')->name('beyond-work.slug')->middleware('isAllow:104,can_edit');
        Route::post('beyond-work/{id}', 'update')->name('beyond-work.edit')->middleware('isAllow:104,can_edit');
        Route::delete('beyond-work', 'delete')->name('beyond-work')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- team spirits Routes ----------------------------------------------------
    Route::controller(TeamSpiritController::class)->group(function () {
        Route::get('team-spirit', 'index')->name('team-spirit')->middleware('isAllow:104,can_view');
        Route::get('team-spirit/add', 'add')->name('team-spirit.add')->middleware('isAllow:104,can_add');
        Route::post('team-spirit/add', 'save')->name('team-spirit.add')->middleware('isAllow:104,can_add');
        Route::get('team-spirit/{id}', 'edit')->name('team-spirit.edit')->middleware('isAllow:104,can_edit');
        Route::post('team-spirit', 'slug')->name('team-spirit.slug')->middleware('isAllow:104,can_edit');
        Route::post('team-spirit/{id}', 'update')->name('team-spirit.edit')->middleware('isAllow:104,can_edit');
        Route::delete('team-spirit', 'delete')->name('team-spirit')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Inquiries List Routes ----------------------------------------------------
    Route::controller(TermsConditionController::class)->group(function () {
        Route::get('terms-and-condition', 'index')->name('terms-and-condition')->middleware('isAllow:104,can_view');
        Route::get('terms-and-condition/add', 'add')->name('terms-and-condition.add')->middleware('isAllow:104,can_add');
        Route::post('terms-and-condition/add', 'save')->name('terms-and-condition.add')->middleware('isAllow:104,can_add');
        Route::get('terms-and-condition/{id}', 'edit')->name('terms-and-condition.edit')->middleware('isAllow:104,can_edit');
        Route::post('terms-and-condition', 'slug')->name('terms-and-condition.slug')->middleware('isAllow:104,can_edit');
        Route::post('terms-and-condition/{id}', 'update')->name('terms-and-condition.edit')->middleware('isAllow:104,can_edit');
        Route::delete('terms-and-condition', 'delete')->name('terms-and-condition')->middleware('isAllow:104,can_delete');
    });



        // ----------------------- Inquiries List Routes ----------------------------------------------------
    Route::controller(WeightDispatchingController::class)->group(function () {
        Route::get('weight-dispatching-index', 'index')->name('weight.dispatching.index')->middleware('isAllow:104,can_view');
        Route::get('weight-dispatching-create', 'create')->name('weight.dispatching.create')->middleware('isAllow:104,can_view');
          Route::post('import-weight-dispute', 'importWeightDisputes')->name('import.weight.dispute')->middleware('isAllow:104,can_view');
         Route::post('weight-submit', 'submit')->name('weight.submit')->middleware('isAllow:104,can_view');
        Route::get('weight-dispatching-download', 'downloadExcel')->name('weight.dispatching.download')->middleware('isAllow:104,can_view');

    });

        // Route::get('/codremittance', [CODRemittanceController::class, 'index'])->name('codremittance.index');
        // Route::post('/codremittance/mark-paid', [CODRemittanceController::class, 'markAsPaid'])->name('codremittance.markPaid');
        // Route::get('/codremittance/download', [CODRemittanceController::class, 'download'])->name('codremittance.download');

    Route::controller(CODRemittanceController::class)->group(function () {
        Route::get('codremittance', 'index')->name('codremittance.index');
        Route::get('codremittance-create', 'create')->name('codremittance.create');
         Route::get('codremittance/export', 'export')->name('codremittance.export');
         Route::post('codremittance/upload', 'upload')->name('codremittance.upload');

    });

// ----------------------- Negative Balance Sellers Routes ----------------------------------------------------
Route::controller(Negative_Balance_SellerController::class)->group(function () {
    Route::get('negative-balance-sellers', 'index')->name('negative-balance.index')->middleware('isAllow:104,can_view');
    Route::get('negative-balance-sellers/export-orders', 'exportOrders')->name('negative-balance.export-orders')->middleware('isAllow:104,can_view');
    Route::post('negative-balance-sellers/upload-excel', 'uploadExcel')->name('negative-balance.upload-excel')->middleware('isAllow:104,can_edit');
    Route::get('negative-balance-sellers/add-money', 'addMoney')->name('negative-balance.add-money')->middleware('isAllow:104,can_add');
    Route::post('negative-balance-sellers/store-money', 'storeMoney')->name('negative-balance.store-money')->middleware('isAllow:104,can_add');
    Route::get('negative-balance-sellers/get-seller-orders', 'getSellerOrders')->name('negative-balance.get-seller-orders')->middleware('isAllow:104,can_view');
});



Route::get('/admin/negative-balance-sellers', [Negative_Balance_SellerController::class, 'index'])
    ->name('negative-balance.index');
    // ----------------------- Inquiries List Routes ----------------------------------------------------
    Route::controller(StaticDataController::class)->group(function () {
        Route::get('kyc-manage', 'kycmanage')->name('kyc-manage')->middleware('isAllow:104,can_view');
        Route::post('update-kyc-status', 'update_kyc_status')->name('update-kyc-status')->middleware('isAllow:104,can_view');

        Route::get('profile-manage', 'profilemanage')->name('profile-manage')->middleware('isAllow:104,can_view');
        Route::post('update-status', 'update_status')->name('update-status')->middleware('isAllow:104,can_view');

    });


    Route::any('setting/{id}', [SettingController::class, 'setting'])->name('setting')->middleware('isAllow:101,can_view');
    Route::get('database-backup', [SettingController::class, 'database_backup'])->name('database_backup')->middleware('isAllow:101,can_view');
    Route::get('server-control', [SettingController::class, 'serverControl'])->name('server-control')->middleware('isAllow:101,can_view');
    Route::post('server-control', [SettingController::class, 'serverControlSave'])->name('server-control')->middleware('isAllow:101,can_view');




    Route::controller(TicketCategoryController::class)->group(function () {
        Route::get('ticket', 'ticketindex')->name('ticket')->middleware('isAllow:104,can_view');
        Route::patch('/tickets/{ticket}/update-status', [TicketCategoryController::class, 'updateStatus'])
    ->name('tickets.update-status');
        // Route::get('ticket/add', 'add')->name('careers.add')->middleware('isAllow:104,can_add');
        // Route::post('careers/add', 'save')->name('careers.add')->middleware('isAllow:104,can_add');
        // Route::get('careers/{id}', 'edit')->name('careers.edit')->middleware('isAllow:104,can_edit');
        // Route::post('careers', 'slug')->name('careers.slug')->middleware('isAllow:104,can_edit');
        // Route::post('careers/{id}', 'update')->name('careers.edit')->middleware('isAllow:104,can_edit');
        // Route::delete('careers', 'delete')->name('careers')->middleware('isAllow:104,can_delete');
    });

});
