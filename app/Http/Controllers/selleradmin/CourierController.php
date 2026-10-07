<?php

namespace App\Http\Controllers\selleradmin;

use App\Models\Recharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\LogisticProvider;
use App\Models\Order;
use App\Models\PriceSetting;

use Illuminate\Support\Facades\Http;

class CourierController extends Controller
{




    public function indexbulk(Request $request, $orderIds = null)
    {
        // Get order IDs from request parameter or route parameter
        if ($orderIds === null) {
            $orderIds = $request->input('order_ids', $request->get('orderIds', []));
        }
        
        // Handle both single ID and comma-separated IDs
        if (is_string($orderIds)) {
            $orderIds = explode(',', $orderIds);
        } elseif (is_numeric($orderIds)) {
            $orderIds = [$orderIds];
        }
        
        $logisticProviders = [];

        if (!empty($orderIds)) {
            try {
                $response = Http::get("https://shipxpeed.com/api/couriers/serviceability/bulk", [
                    'order_ids' => $orderIds  // Pass bulk order IDs
                ]);
                // return $response;

                if ($response->successful()) {
                    $apiData = $response->json();
                    // dd($apiData);
                    if (!empty($apiData['data'])) {
                        $seller = Auth::guard('seller')->user();
                        $sellerId = Auth::guard('seller')->id();

                        // Group data by courierName to avoid duplicates
                        $groupedCouriers = [];

                        foreach ($apiData['data'] as $courier) {
                            if (isset($courier['courierName']) && in_array($courier['courierName'], ['Ekart', 'Delhivery–VPoint'])) {
                                continue;
                            }

                            $courierName = $courier['courierName'];
                            
                            // If this courier hasn't been added yet, or if this has better pricing
                            if (!isset($groupedCouriers[$courierName]) || 
                                ($courier['courierCharge'] ?? 0) < ($groupedCouriers[$courierName]['courierCharge'] ?? PHP_INT_MAX)) {
                                $groupedCouriers[$courierName] = $courier;
                            }
                        }

                        // Process grouped couriers
                        foreach ($groupedCouriers as $courier) {
                            $localProvider = LogisticProvider::where('id', $courier['courierId'])->first();
                            $logisticProviders[] = array_merge($courier, [

                'provider_logo' => match (true) {
                    str_starts_with($courier['courierName'], 'Amazon_') => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                    $courier['courierName'] === 'DTDC-SMART' => 'brands/1750337596_images-removebg-preview.png',
                    $courier['courierName'] === 'Bluedart- Surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
        
                    // ✅ New added conditions
                    $courier['courierName'] === 'Delhivery_5kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Delhivery_10kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Blue Dart_0.5 KG' => 'brands/ad90c5ac-7f72-4f81-bbeb-d73fa6a70be1.jpeg',
                    $courier['courierName'] === 'Delhivery_1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',

                    $courier['courierName'] === 'Delhivery 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Delhivery Air' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Bluedart 500 grams new' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                    $courier['courierName'] === 'Bluedart Air' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                    $courier['courierName'] === 'Amazon 500gm' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                    $courier['courierName'] === 'Ekart_2 KG Fixed' => 'brands/ecart.png',
                    $courier['courierName'] === 'Xpressbee 250gms' => 'brands/Xpressbee.png',
                    $courier['courierName'] === 'Bluedart 2kg surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                    $courier['courierName'] === 'Delhivery_Shiprocket 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Amazon 2kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                    $courier['courierName'] === 'Amazon 1kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                    $courier['courierName'] === 'Ekart 2kg' => 'brands/ecart.png',
                    // tekipost
                    $courier['courierName'] === 'Delhivery 5 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Delhivery 10 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Delhivery 1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Ekart 2 KG Fixed' => 'brands/ecart.png',
                    $courier['courierName'] === 'Amazon 2 KG' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                    $courier['courierName'] === 'Amazon 500 GM' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                    // tekipost
                    $courier['courierName'] === 'Bluedart 1 KG' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                    $courier['courierName'] === 'parcelx_Deliveri 250gm' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                    $courier['courierName'] === 'Ekart500gm_boxd' => 'brands/ecart.png',
                    $courier['courierName'] === 'Bluedartbox_500gm' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Xpressbeepacel 500gm' => 'brands/Xpressbee.png',
                $courier['courierName'] === 'Shreemaruti 500gm' => 'brands/Shreemaruti.jpg',


                    default => $localProvider->logo ?? null,
                },

                                'provider_name' => ($localProvider && in_array($localProvider->name, ['Smartship','Tekipost','Boxd','DTDC','Parcelx','Shiprocket','selloship']))
                                ? match ($courier['courierName']) {
                                    'Bluedart 500 grams new' => 'Bluedart Surface 500gms',
                                    'Bluedart Air' => 'Bluedart Air 500gms',
                                    // 'Delhivery surface' => 'Delhivery 250gms',
                                         'Delhivery 250gms' => 'Delhivery 250gms',
                                         'Bluedart 1 KG' => 'Bluedart 1 KG',

                                         'DTDC Surface 500gm' => 'DTDC Surface 500gm',
                                         'DTDC Surface 1kg' => 'DTDC Surface 1kg',
                                         'DTDC Air' => 'DTDC Air',

                                        'Deliveri 500gm' => 'Delhivery 500gm',
                                         'Amazon 500gm' => 'Amazon 500gm',
                                         'Ekart_2 KG Fixed' => 'Ekart 2 KG',
                                     'parcelx_Deliveri 250gm' => 'Delhivery 250gms',
                                         'Ekart500gm_boxd' => 'Ekart 500 GM',
                                         'Bluedartbox_500gm' => 'BlueDart Air 500 GM',

                                         'Xpressbee 250gms' => 'Xpressbee 250gms',
                                         'Bluedart 2kg surface' => 'Bluedart 2kg surface',
                                         'Delhivery_Shiprocket 250gms' => 'Delhivery_ 250gms',
                                         'Ekart 2kg' => 'Ekart 2KG',
                                         'selloshipEkart2KG' => 'Ekart 2KG NEW',
    // tekipost
                                         'Delhivery 5 KG' => 'Delhivery 5 KG',
                                         'Delhivery 10 KG' => 'Delhivery 10 KG',
                                         'Delhivery 1 KG' => 'Delhivery 1 KG',
                                         'Ekart 2 KG Fixed' => 'Ekart 2 KG Fixed',
                                         'Amazon 2 KG' => 'Amazon 2 KG',
                                         'Amazon 500 GM' => 'Amazon 500 GM',
    // tekipost
                                         'Xpressbeepacel 500gm' => 'Xpressbee 500 GM',
                                                                                 'Shreemaruti 500gm' => 'Shreemaruti 500 GM',

                                    default => $courier['courierName'],
                                }
                                : ($localProvider->name ?? $courier['courierName']),

                                'courierCharge' => $courier['courierCharge'] ?? 0,
                                'gst_amount'    => $courier['gst_amount'] ?? 0,
                                'serviceabilityId'    => $courier['serviceabilityId'] ?? 1,

                            ]);
                        }
                    }

                    // dd($logisticProviders);
                } else {
                    \Log::error('Shipxpeed API error', [
                        'status'   => $response->status(),
                        'body'     => $response->body(),
                        'order_ids' => $orderIds
                    ]); 
                }
            } catch (\Exception $e) {
                \Log::error('Shipxpeed API Exception: ' . $e->getMessage());
            }
        }

        // Seller info
        $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();

        $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
        $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;

        $transactions = Recharge::where([
            'seller_id' => $sellerId,
            'status'    => '1'
        ])->latest()->get();

        $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
            ->where('status', 1)
            ->where('type', 'Credit')
            ->sum('amount');

        $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
            ->where('type', 'Debit')
            ->sum('amount');

        $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        return view('sellerdashboard.courier.bulk_index', compact(
            'transactions',
            'seller',
            'totalAmount',
            'logisticProviders',
            'orderIds',
            'sellerprice'
        ));
    }





    public function index($id)
{
     
    $firstOrderId = $id ?? null;
    $logisticProviders = [];

    if ($firstOrderId) {
        try {
            $response = Http::get("https://shipxpeed.com/api/couriers/serviceability", [
                'order_id' => $firstOrderId
            ]);
        //  return $response;

            if ($response->successful()) {
                $apiData = $response->json();
            //   dd($apiData);
                if (!empty($apiData['data'])) {
                    // Logged-in seller
                    $seller = Auth::guard('seller')->user();
                    $sellerId = Auth::guard('seller')->id();

                    foreach ($apiData['data'] as $courier) {

                        if (isset($courier['courierName']) && in_array($courier['courierName'], ['Ekart', 'Delhivery–VPoint'])) {
                                    continue;
                                }

                        // DB provider match (for name and logo)
                        $localProvider = LogisticProvider::where('id', $courier['courierId'])->first();
                        $logisticProviders[] = array_merge($courier, [



            'provider_logo' => match (true) {
                str_starts_with($courier['courierName'], 'Amazon_') => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'DTDC-SMART' => 'brands/1750337596_images-removebg-preview.png',
                $courier['courierName'] === 'Bluedart- Surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
    
                // ✅ New added conditions
                $courier['courierName'] === 'Delhivery_5kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Delhivery_10kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Blue Dart_0.5 KG' => 'brands/ad90c5ac-7f72-4f81-bbeb-d73fa6a70be1.jpeg',
                $courier['courierName'] === 'Delhivery_1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',

                $courier['courierName'] === 'Delhivery 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Delhivery Air' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Bluedart 500 grams new' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Bluedart Air' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Amazon 500gm' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'Ekart_2 KG Fixed' => 'brands/ecart.png',
                $courier['courierName'] === 'Xpressbee 250gms' => 'brands/Xpressbee.png',
                $courier['courierName'] === 'Bluedart 2kg surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Delhivery_Shiprocket 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Amazon 2kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'Amazon 1kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'Ekart 2kg' => 'brands/ecart.png',
                // tekipost
                $courier['courierName'] === 'Delhivery 5 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Delhivery 10 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Delhivery 1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Ekart 2 KG Fixed' => 'brands/ecart.png',
                $courier['courierName'] === 'Amazon 2 KG' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'Amazon 500 GM' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'Bluedart 1 KG' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'parcelx_Deliveri 250gm' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Ekart500gm' => 'brands/ecart.png',
                $courier['courierName'] === 'Ekart500gm_boxd' => 'brands/ecart.png',
                    $courier['courierName'] === 'Bluedartbox_500gm' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Xpressbeepacel 500gm' => 'brands/Xpressbee.png',
                $courier['courierName'] === 'Shreemaruti 500gm' => 'brands/Shreemaruti.jpg',

                // tekipost

                default => $localProvider->logo ?? null,
            },


                            'provider_name' => ($localProvider && in_array($localProvider->name, ['Smartship','Tekipost','Boxd','DTDC','Parcelx','Shiprocket','selloship','Ekart']))
                            ? match ($courier['courierName']) {
                                'Bluedart 500 grams new' => 'Bluedart Surface 500gms',
                                'Bluedart Air' => 'Bluedart Air 500gms',
                                // 'Delhivery surface' => 'Delhivery 250gms',
                                     'Delhivery 250gms' => 'Delhivery 250gms',
                                     'Bluedart 1 KG' => 'Bluedart 1 KG',
                                         'Ekart500gm_boxd' => 'Ekart 500 GM',

                                     'DTDC Surface 500gm' => 'DTDC Surface 500gm',
                                     'DTDC Surface 1kg' => 'DTDC Surface 1kg',
                                     'DTDC Air' => 'DTDC Air',

                                    'Deliveri 500gm' => 'Delhivery 500gm',
                                     'Amazon 500gm' => 'Amazon 500gm',
                                     'Ekart_2 KG Fixed' => 'Ekart 2 KG',
                                     'parcelx_Deliveri 250gm' => 'Delhivery 250gms',


                                     'Xpressbee 250gms' => 'Xpressbee 250gms',
                                     'Bluedart 2kg surface' => 'Bluedart 2kg surface',
                                     'Delhivery_Shiprocket 250gms' => 'Delhivery_ 250gms',
                                     'Ekart 2kg' => 'Ekart 2KG',
                                     'selloshipEkart2KG' => 'Ekart 2KG NEW',
// tekipost
                                     'Delhivery 5 KG' => 'Delhivery 5 KG',
                                     'Delhivery 10 KG' => 'Delhivery 10 KG',
                                     'Delhivery 1 KG' => 'Delhivery 1 KG',
                                     'Ekart 2 KG Fixed' => 'Ekart 2 KG Fixed',
                                     'Amazon 2 KG' => 'Amazon 2 KG',
                                     'Amazon 500 GM' => 'Amazon 500 GM',
// tekipost
                                        'Ekart500gm' => 'Ekart 500 GM',
                                                                                 'Bluedartbox_500gm' => 'BlueDart Air 500 GM',


                                                                                 'Xpressbeepacel 500gm' => 'Xpressbee 500 GM',
                                                                                 'Shreemaruti 500gm' => 'Shreemaruti 500 GM',


                                default => $courier['courierName'],
                            }
                            : ($localProvider->name ?? $courier['courierName']),

                            'courierCharge' => $courier['courierCharge'] ?? 0,
                            'gst_amount'    => $courier['gst_amount'] ?? 0,
                            'serviceabilityId'    => $courier['serviceabilityId'] ?? 1,

                        ]);
                    }
                }

                // dd($logisticProviders);
            } else {
                \Log::error('Shipxpeed API error', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'order_id' => $firstOrderId
                ]); 
            }
        } catch (\Exception $e) {
            \Log::error('Shipxpeed API Exception: ' . $e->getMessage());
        }
    }

    // Seller info
    $seller = Auth::guard('seller')->user();
    $sellerId = Auth::guard('seller')->id();

    $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
    $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;

    $transactions = Recharge::where([
        'seller_id' => $sellerId,
        'status'    => '1'
    ])->latest()->get();

    $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

    return view('sellerdashboard.courier.index', compact(
        'transactions',
        'seller',
        'totalAmount',
        'logisticProviders',
        'firstOrderId',
        'sellerprice'
    ));
}




//     public function index($id)
// {
     
//     $firstOrderId = $id ?? null;
//     $logisticProviders = [];

//     if ($firstOrderId) {
//         try {
//             $response = Http::get("https://shipxpeed.com/api/couriers/serviceability", [
//                 'order_id' => $firstOrderId
//             ]);
//          return $response;

//             if ($response->successful()) {
//                 $apiData = $response->json();
//             //   dd($apiData);
//                 if (!empty($apiData['data'])) {
//                     // Logged-in seller
//                     $seller = Auth::guard('seller')->user();
//                     $sellerId = Auth::guard('seller')->id();

//                     foreach ($apiData['data'] as $courier) {

//                         if (isset($courier['courierName']) && in_array($courier['courierName'], ['Ekart', 'Delhivery–VPoint'])) {
//                                     continue;
//                                 }

//                         // DB provider match (for name and logo)
//                         $localProvider = LogisticProvider::where('id', $courier['courierId'])->first();
//                         $logisticProviders[] = array_merge($courier, [



//             'provider_logo' => match (true) {
//                 str_starts_with($courier['courierName'], 'Amazon_') => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 $courier['courierName'] === 'DTDC-SMART' => 'brands/1750337596_images-removebg-preview.png',
//                 $courier['courierName'] === 'Bluedart- Surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
    
//                 // ✅ New added conditions
//                 $courier['courierName'] === 'Delhivery_5kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Delhivery_10kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Blue Dart_0.5 KG' => 'brands/ad90c5ac-7f72-4f81-bbeb-d73fa6a70be1.jpeg',
//                 $courier['courierName'] === 'Delhivery_1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',

//                 $courier['courierName'] === 'Delhivery 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Delhivery Air' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Bluedart 500 grams new' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
//                 $courier['courierName'] === 'Bluedart Air' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
//                 $courier['courierName'] === 'Amazon 500gm' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 $courier['courierName'] === 'Ekart_2 KG Fixed' => 'brands/ecart.png',
//                 $courier['courierName'] === 'Xpressbee 250gms' => 'brands/Xpressbee.png',
//                 $courier['courierName'] === 'Bluedart 2kg surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
//                 $courier['courierName'] === 'Delhivery_Shiprocket 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Amazon 2kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 $courier['courierName'] === 'Amazon 1kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 $courier['courierName'] === 'Ekart 2kg' => 'brands/ecart.png',
//                 // tekipost
//                 $courier['courierName'] === 'Delhivery 5 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Delhivery 10 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Delhivery 1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Ekart 2 KG Fixed' => 'brands/ecart.png',
//                 $courier['courierName'] === 'Amazon 2 KG' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 $courier['courierName'] === 'Amazon 500 GM' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 // tekipost

//                 default => $localProvider->logo ?? null,
//             },


//                             'provider_name' => ($localProvider && in_array($localProvider->name, ['Smartship','Boxd','DTDC','Parcelx','Shiprocket','selloship']))
//                             ? match ($courier['courierName']) {
//                                 'Bluedart 500 grams new' => 'Bluedart Surface 500gms',
//                                 'Bluedart Air' => 'Bluedart Air 500gms',
//                                 // 'Delhivery surface' => 'Delhivery 250gms',
//                                      'Delhivery 250gms' => 'Delhivery 250gms',

//                                      'DTDC Surface 500gm' => 'DTDC Surface 500gm',
//                                      'DTDC Surface 1kg' => 'DTDC Surface 1kg',
//                                      'DTDC Air' => 'DTDC Air',

//                                     'Deliveri 500gm' => 'Delhivery 500gm',
//                                      'Amazon 500gm' => 'Amazon 500gm',
//                                      'Ekart_2 KG Fixed' => 'Ekart 2 KG',

//                                      'Xpressbee 250gms' => 'Xpressbee 250gms',
//                                      'Bluedart 2kg surface' => 'Bluedart 2kg surface',
//                                      'Delhivery_Shiprocket 250gms' => 'Delhivery_ 250gms',
//                                      'Ekart 2kg' => 'Ekart 2KG',
//                                      'selloshipEkart2KG' => 'Ekart 2KG NEW',

//                                 default => $courier['courierName'],
//                             }
//                             : ($localProvider->name ?? $courier['courierName']),

//                             'courierCharge' => $courier['courierCharge'] ?? 0,
//                             'gst_amount'    => $courier['gst_amount'] ?? 0,
//                             'serviceabilityId'    => $courier['serviceabilityId'] ?? 1,

//                         ]);
//                     }
//                 }

//                 // dd($logisticProviders);
//             } else {
//                 \Log::error('Shipxpeed API error', [
//                     'status'   => $response->status(),
//                     'body'     => $response->body(),
//                     'order_id' => $firstOrderId
//                 ]); 
//             }
//         } catch (\Exception $e) {
//             \Log::error('Shipxpeed API Exception: ' . $e->getMessage());
//         }
//     }

//     // Seller info
//     $seller = Auth::guard('seller')->user();
//     $sellerId = Auth::guard('seller')->id();

//     $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
//     $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;

//     $transactions = Recharge::where([
//         'seller_id' => $sellerId,
//         'status'    => '1'
//     ])->latest()->get();

//     $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     return view('sellerdashboard.courier.index', compact(
//         'transactions',
//         'seller',
//         'totalAmount',
//         'logisticProviders',
//         'firstOrderId',
//         'sellerprice'
//     ));
// }








    public function reverseindex($id)
{
     
    $firstOrderId = $id ?? null;
    $logisticProviders = [];

    if ($firstOrderId) {
        try {
            $response = Http::get("https://shipxpeed.com/api/reverse/couriers/serviceability", [
                'order_id' => $firstOrderId
            ]);
        //  return $response;

            if ($response->successful()) {
                $apiData = $response->json();
            //   dd($apiData);
                if (!empty($apiData['data'])) {
                    // Logged-in seller
                    $seller = Auth::guard('seller')->user();
                    $sellerId = Auth::guard('seller')->id();

                    foreach ($apiData['data'] as $courier) {

                        if (isset($courier['courierName']) && in_array($courier['courierName'], ['Ekart', 'Delhivery–VPoint'])) {
                                    continue;
                                }

                        // DB provider match (for name and logo)
                        $localProvider = LogisticProvider::where('id', $courier['courierId'])->first();
                        $logisticProviders[] = array_merge($courier, [



            'provider_logo' => match (true) {
                str_starts_with($courier['courierName'], 'Amazon_') => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'DTDC-SMART' => 'brands/1750337596_images-removebg-preview.png',
                $courier['courierName'] === 'Bluedart- Surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
    
                // ✅ New added conditions
                $courier['courierName'] === 'Delhivery_5kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Delhivery_10kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Blue Dart_0.5 KG' => 'brands/ad90c5ac-7f72-4f81-bbeb-d73fa6a70be1.jpeg',
                $courier['courierName'] === 'Delhivery_1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',

                $courier['courierName'] === 'Delhivery 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Delhivery Air' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Bluedart 500 grams new' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Bluedart Air' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Amazon 500gm' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'Ekart_2 KG Fixed' => 'brands/ecart.png',
                $courier['courierName'] === 'Xpressbee 250gms' => 'brands/Xpressbee.png',
                $courier['courierName'] === 'Bluedart 2kg surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                $courier['courierName'] === 'Delhivery_Shiprocket 250gms' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
                $courier['courierName'] === 'Amazon 2kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
                $courier['courierName'] === 'Amazon 1kg' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',

                default => $localProvider->logo ?? null,
            },


                            'provider_name' => ($localProvider && in_array($localProvider->name, ['Smartship', 'Tekipost','Boxd','DTDC','Parcelx','Shiprocket']))
                            ? match ($courier['courierName']) {
                                'Bluedart 500 grams new' => 'Bluedart Surface 500gms',
                                'Bluedart Air' => 'Bluedart Air 500gms',
                                // 'Delhivery surface' => 'Delhivery 250gms',
                                     'Delhivery 250gms' => 'Delhivery 250gms',

                                     'DTDC Surface 500gm' => 'DTDC Surface 500gm',
                                     'DTDC Surface 1kg' => 'DTDC Surface 1kg',
                                     'DTDC Air' => 'DTDC Air',

                                    'Deliveri 500gm' => 'Delhivery 500gm',
                                     'Amazon 500gm' => 'Amazon 500gm',
                                     'Ekart_2 KG Fixed' => 'Ekart 2 KG',

                                     'Xpressbee 250gms' => 'Xpressbee 250gms',
                                     'Bluedart 2kg surface' => 'Bluedart 2kg surface',
                                     'Delhivery_Shiprocket 250gms' => 'Delhivery_ 250gms',

                                default => $courier['courierName'],
                            }
                            : ($localProvider->name ?? $courier['courierName']),

                            'courierCharge' => $courier['courierCharge'] ?? 0,
                            'gst_amount'    => $courier['gst_amount'] ?? 0,
                            'serviceabilityId'    => $courier['serviceabilityId'] ?? 1,

                        ]);
                    }
                }

                // dd($logisticProviders);
            } else {
                \Log::error('Shipxpeed API error', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'order_id' => $firstOrderId
                ]); 
            }
        } catch (\Exception $e) {
            \Log::error('Shipxpeed API Exception: ' . $e->getMessage());
        }
    }

    // Seller info
    $seller = Auth::guard('seller')->user();
    $sellerId = Auth::guard('seller')->id();

    $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
    $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;

    $transactions = Recharge::where([
        'seller_id' => $sellerId,
        'status'    => '1'
    ])->latest()->get();

    $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

    return view('sellerdashboard.reverse.index', compact(
        'transactions',
        'seller',
        'totalAmount',
        'logisticProviders',
        'firstOrderId',
        'sellerprice'
    ));
}












//     public function index($id)
// {
     
//     $firstOrderId = $id ?? null;
//     $logisticProviders = [];

//     if ($firstOrderId) {
//         try {
//             $response = Http::get("https://shipxpeed.com/api/couriers/serviceability", [
//                 'order_id' => $firstOrderId
//             ]);
//         //  return $response;

//             if ($response->successful()) {
//                 $apiData = $response->json();
//             //   dd($apiData);
//                 if (!empty($apiData['data'])) {
//                     // Logged-in seller
//                     $seller = Auth::guard('seller')->user();
//                     $sellerId = Auth::guard('seller')->id();

//                     foreach ($apiData['data'] as $courier) {

//                         if (isset($courier['courierName']) && in_array($courier['courierName'], ['Ekart', 'Delhivery–VPoint'])) {
//                                     continue;
//                                 }

//                         // DB provider match (for name and logo)
//                         $localProvider = LogisticProvider::where('id', $courier['courierId'])->first();
//                         $logisticProviders[] = array_merge($courier, [



//             'provider_logo' => match (true) {
//                 str_starts_with($courier['courierName'], 'Amazon_') => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 $courier['courierName'] === 'DTDC-SMART' => 'brands/1750337596_images-removebg-preview.png',
//                 $courier['courierName'] === 'Bluedart- Surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
    
//                 // ✅ New added conditions
//                 $courier['courierName'] === 'Delhivery_5kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Delhivery_10kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Blue Dart_0.5 KG' => 'brands/ad90c5ac-7f72-4f81-bbeb-d73fa6a70be1.jpeg',
//                 $courier['courierName'] === 'Delhivery_1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',

//                 $courier['courierName'] === 'Delhivery surface' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Delhivery Air' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Bluedart 500 grams new' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
//                 $courier['courierName'] === 'Bluedart Air' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
//                 default => $localProvider->logo ?? null,
//             },


//                             'provider_name' => ($localProvider && in_array($localProvider->name, ['Smartship', 'Tekipost','Boxd']))
//                             ? $courier['courierName']
//                             : ($localProvider->name ?? $courier['courierName']),

//                             'courierCharge' => $courier['courierCharge'] ?? 0,
//                             'gst_amount'    => $courier['gst_amount'] ?? 0,
//                             'serviceabilityId'    => $courier['serviceabilityId'] ?? 1,

//                         ]);
//                     }
//                 }

//                 // dd($logisticProviders);
//             } else {
//                 \Log::error('Shipxpeed API error', [
//                     'status'   => $response->status(),
//                     'body'     => $response->body(),
//                     'order_id' => $firstOrderId
//                 ]);
//             }
//         } catch (\Exception $e) {
//             \Log::error('Shipxpeed API Exception: ' . $e->getMessage());
//         }
//     }

//     // Seller info
//     $seller = Auth::guard('seller')->user();
//     $sellerId = Auth::guard('seller')->id();

//     $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
//     $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;

//     $transactions = Recharge::where([
//         'seller_id' => $sellerId,
//         'status'    => '1'
//     ])->latest()->get();

//     $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     return view('sellerdashboard.courier.index', compact(
//         'transactions',
//         'seller',
//         'totalAmount',
//         'logisticProviders',
//         'firstOrderId',
//         'sellerprice'
//     ));
// }



    
//     public function index($id)
// {
     
//     $firstOrderId = $id ?? null;
//     $logisticProviders = [];

//     if ($firstOrderId) {
//         try {
//             $response = Http::get("https://shipxpeed.com/api/couriers/serviceability", [
//                 'order_id' => $firstOrderId
//             ]);
//          return $response;

//             if ($response->successful()) {
//                 $apiData = $response->json();
//             //   dd($apiData);
//                 if (!empty($apiData['data'])) {
//                     // Logged-in seller
//                     $seller = Auth::guard('seller')->user();
//                     $sellerId = Auth::guard('seller')->id();

//                     foreach ($apiData['data'] as $courier) {

//                         if (isset($courier['courierName']) && in_array($courier['courierName'], ['Ekart', 'Delhivery–VPoint'])) {
//                                     continue;
//                                 }

//                         // DB provider match (for name and logo)
//                         $localProvider = LogisticProvider::where('id', $courier['courierId'])->first();
//                         $logisticProviders[] = array_merge($courier, [



//             'provider_logo' => match (true) {
//                 str_starts_with($courier['courierName'], 'Amazon_') => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg',
//                 $courier['courierName'] === 'DTDC-SMART' => 'brands/1750337596_images-removebg-preview.png',
//                 $courier['courierName'] === 'Bluedart- Surface' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
                
//                 // ✅ New added conditions
//                 $courier['courierName'] === 'Delhivery_5kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Delhivery_10kg' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 $courier['courierName'] === 'Blue Dart_0.5 KG' => 'brands/ad90c5ac-7f72-4f81-bbeb-d73fa6a70be1.jpeg',
//                 $courier['courierName'] === 'Delhivery_1 KG' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg',
//                 default => $localProvider->logo ?? null,
//             },



//                             'provider_name' => ($localProvider && in_array($localProvider->name, ['Smartship', 'Tekipost']))
//                         ? $courier['courierName']
//                         : ($localProvider->name ?? $courier['courierName']),


//                             'courierCharge' => $courier['courierCharge'] ?? 0,
//                             'gst_amount'    => $courier['gst_amount'] ?? 0,
//                             'serviceabilityId'    => $courier['serviceabilityId'] ?? 1,

//                         ]);
//                     }
//                 }

//                 // dd($logisticProviders);
//             } else {
//                 \Log::error('Shipxpeed API error', [
//                     'status'   => $response->status(),
//                     'body'     => $response->body(),
//                     'order_id' => $firstOrderId
//                 ]);
//             }
//         } catch (\Exception $e) {
//             \Log::error('Shipxpeed API Exception: ' . $e->getMessage());
//         }
//     }

//     // Seller info
//     $seller = Auth::guard('seller')->user();
//     $sellerId = Auth::guard('seller')->id();

//     $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
//     $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;

//     $transactions = Recharge::where([
//         'seller_id' => $sellerId,
//         'status'    => '1'
//     ])->latest()->get();

//     $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     return view('sellerdashboard.courier.index', compact(
//         'transactions',
//         'seller',
//         'totalAmount',
//         'logisticProviders',
//         'firstOrderId',
//         'sellerprice'
//     ));
// }

  
//     public function index($id)
// {
     
//     $firstOrderId = $id ?? null;
//     $logisticProviders = [];

//     if ($firstOrderId) {
//         try {
//             $response = Http::get("https://shipxpeed.com/api/couriers/serviceability", [
//                 'order_id' => $firstOrderId
//             ]);
//         //  return $response;

//             if ($response->successful()) {
//                 $apiData = $response->json();
//             //   dd($apiData);
//                 if (!empty($apiData['data'])) {
//                     // Logged-in seller
//                     $seller = Auth::guard('seller')->user();
//                     $sellerId = Auth::guard('seller')->id();

//                     foreach ($apiData['data'] as $courier) {
//                          if (isset($courier['courierName']) && $courier['courierName'] === 'Ekart') {
//                                     continue; // Skip Ekart
//                                 }
//                         // DB provider match (for name and logo)
//                         $localProvider = LogisticProvider::where('id', $courier['courierId'])->first();
//                         $logisticProviders[] = array_merge($courier, [
//                             'provider_logo' => match ($courier['courierName']) {
//                                 'DTDC-SMART'         => 'brands/1750337596_images-removebg-preview.png',
//                                 'Bluedart- Surface'  => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png',
//                                 default              => $localProvider->logo ?? null,
//                             },
//                             // 'provider_logo' => $localProvider->logo ?? null,
//                             'provider_name' => $localProvider->name ?? $courier['courierName'], 
//                             'courierCharge' => $courier['courierCharge'] ?? 0,
//                             'gst_amount'    => $courier['gst_amount'] ?? 0,
//                             'serviceabilityId'    => $courier['serviceabilityId'] ?? 1,

//                         ]);
//                     }
//                 }

//                 // dd($logisticProviders);
//             } else {
//                 \Log::error('Shipxpeed API error', [
//                     'status'   => $response->status(),
//                     'body'     => $response->body(),
//                     'order_id' => $firstOrderId
//                 ]);
//             }
//         } catch (\Exception $e) {
//             \Log::error('Shipxpeed API Exception: ' . $e->getMessage());
//         }
//     }

//     // Seller info
//     $seller = Auth::guard('seller')->user();
//     $sellerId = Auth::guard('seller')->id();

//     $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
//     $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;

//     $transactions = Recharge::where([
//         'seller_id' => $sellerId,
//         'status'    => '1'
//     ])->latest()->get();

//     $sellerRechargeAmount = Recharge::where('seller_id', $sellerId)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $sellerId)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     return view('sellerdashboard.courier.index', compact(
//         'transactions',
//         'seller',
//         'totalAmount',
//         'logisticProviders',
//         'firstOrderId',
//         'sellerprice'
//     ));
// }



  

}
