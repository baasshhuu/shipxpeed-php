<?php

use App\Http\Controllers\Frontend\CompanyController;
use App\Http\Controllers\Frontend\FeaturesController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\RateCardController as FrontendRateCardController;
use App\Http\Controllers\Frontend\ResourcesController;

use App\Http\Controllers\selleradmin\ToolsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ndr-follow-up', [FeaturesController::class, 'index'])->name('ndr.follow.ups');
Route::get('/ltl-shipments', [FeaturesController::class, 'ltlshipments'])->name('ltl.shipments');
Route::get('/hyperlocal', [FeaturesController::class, 'hyperlocal'])->name('hyperlocal');
Route::get('/about', [CompanyController::class, 'about'])->name('about');
Route::get('/contact', [CompanyController::class, 'contact'])->name('contact');
Route::get('/life-shipxpeed', [CompanyController::class, 'lifeshipxpeed'])->name('life-shipxpeed');
Route::get('/rate-calculater', [CompanyController::class, 'ratecalculater'])->name('ratecalculater');
Route::get('/price', [CompanyController::class, 'price'])->name('price');
Route::get('/privacy_policies', [ResourcesController::class, 'privacypolicy'])->name('privacy_policies');
Route::get('/refund_policies', [ResourcesController::class, 'refundpolicy'])->name('refund_policies');
Route::get('/terms-condition', [ResourcesController::class, 'termsandcondition'])->name('termsandcondition');
Route::get('/track-order', [CompanyController::class, 'trackorder'])->name('track-order');
Route::get('/career', [CompanyController::class, 'career'])->name('career');
Route::post('/career-details', [CompanyController::class, 'storeCareerDetails'])->name('careerdetails');
Route::get('/rate-cards', [ToolsController::class, 'ratecard'])->name('ratecards');
Route::get('/shipment-price', [ToolsController::class, 'shipmentprice'])->name('shipmentprice');
Route::get('/activity-log', [ToolsController::class, 'activitylog'])->name('activitylog');
Route::get('/courier-manage', [ToolsController::class, 'couriermanage'])->name('couriermanage');
Route::get('/tools-report', [ToolsController::class, 'reports'])->name('tools.report');
Route::get('/track-orders', [ToolsController::class, 'trackorder'])->name('track.order');
Route::get('/weight-discrepancy', [ToolsController::class, 'weightdiscrepancy'])->name('weightdiscrepancy');
Route::get('/weight-discrepancy', [ToolsController::class, 'weightdiscrepancy'])->name('weightdiscrepancy');
Route::post('/seller/rate-calculate', [\App\Http\Controllers\Frontend\RateCardController::class, 'checkRate'])->name('seller.check.rate');
Route::post('/cancel-shipment', [\App\Http\Controllers\Frontend\CancelShipmentController::class, 'index'])->name('seller.cancel.shipment');
Route::post('/shipment-cancel', [\App\Http\Controllers\Frontend\CancelShipmentController::class, 'index'])->name('seller.shipment.cancel');
Route::get('/shipment-cancel', [HomeController::class, 'cancelshipment'])->name('cancelshipment');
