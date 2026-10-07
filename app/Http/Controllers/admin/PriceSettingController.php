<?php

namespace App\Http\Controllers\admin;

use App\Models\Brand;
use App\Models\Cms;
use App\Helper\Helper;
use App\Models\Testimonial;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSetting;
use App\Models\SellerList;
use App\Models\LogisticProvider;


class PriceSettingController extends Controller
{
    


public function add()
    {
        $SellerList = SellerList::all();
        
        $selectedSeller = null;
        $existingPrices = [];
        
        if(request()->has('seller_id')) {
            $selectedSeller = SellerList::find(request('seller_id'));
            
            if($selectedSeller) {
                // Get existing prices for this seller
                $prices = PriceSetting::where('seller_id', $selectedSeller->id)->get();
                
                foreach($prices as $price) {
                    $existingPrices[$price->LogisticProvider] = [
                        'shipping_charge' => $price->shipping_charge,
                        'cod_charge' => $price->cod_charge,
                        'cod_charge_percent' => $price->cod_charge_parsent,
                        'fixed_courier_price' => $price->fixed_courier_price
                    ];
                }
            }
        }
        // dd($existingPrices);
        return view('pricesetting.add', compact(
            'SellerList',
            'selectedSeller',
            'existingPrices'
        ));
    }

    
    public function save(Request $request)
{
    $validated = $request->validate([
        'seller_id' => ['required'],
    ]);

    // Process each courier service
    $courierServices = [
        // 'XpressBees' => [
        //     'shipping' => $request->xpressbees_shipping ?? 70,
        //     'cod' => $request->xpressbees_cod ?? 32,
        //     'cod_percent' => $request->xpressbees_cod_percent ?? 2

        // ],
        // 'XpressBees Air' => [
        //     'shipping' => $request->xpressbees_air_shipping ?? 70,
        //     'cod' => $request->xpressbees_air_cod ?? 32,
        //     'cod_percent' => $request->xpressbees_air_cod_percent ?? 2
        // ],
        'Delhivery' => [
            'shipping' => $request->delhivery_shipping ?? 70,
            'cod' => $request->delhivery_cod ?? 32,
            'cod_percent' => $request->delhivery_cod_percent ?? 2,
            'fixed_courier_price' => $request->delhivery_fixed_price ?? 2

        ],
        'Delhivery Air' => [
            'shipping' => $request->delhivery_air_shipping ?? 70,
            'cod' => $request->delhivery_air_cod ?? 32,
            'cod_percent' => $request->delhivery_air_cod_percent ?? 2,
            'fixed_courier_price' => $request->delhivery_air_fixed_price ?? 2

        ],
        'Blue Dart' => [
            'shipping' => $request->bluedart_shipping ?? 70,
            'cod' => $request->bluedart_cod ?? 32,
            'cod_percent' => $request->bluedart_cod_percent ?? 2,
                        'fixed_courier_price' => $request->bluedart_fixed_price ?? 2

        ],
        'DTDC' => [
            'shipping' => $request->dtdc_shipping ?? 70,
            'cod' => $request->dtdc_cod ?? 32,
            'cod_percent' => $request->dtdc_cod_percent ?? 2,
                                    'fixed_courier_price' => $request->dtdc_fixed_price ?? 2

        ],

        'Amazon_0.5 KG' => [
            'shipping' => $request->amazon_0_5kg_shipping ?? 70,
            'cod' => $request->amazon_0_5kg_cod ?? 32,
            'cod_percent' => $request->amazon_0_5kg_cod_percent ?? 2,
                                                'fixed_courier_price' => $request->amazon_0_5kg_fixed_price ?? 2

        ],

        'Amazon_2 KG' => [
            'shipping' => $request->amazon_2_kg_shipping ?? 70,
            'cod' => $request->amazon_2_kg_cod ?? 32,
            'cod_percent' => $request->amazon_2_kg_cod_percent ?? 2,
            'fixed_courier_price' => $request->amazon_2_kg_fixed_price ?? 2

        ],




        'Ekart_2 KG Fixed' => [
            'shipping' => $request->Ekart_2kg_shipping ?? 70,
            'cod' => $request->Ekart_2kg_cod ?? 32,
            'cod_percent' => $request->Ekart_2kg_cod_percent ?? 2,
            'fixed_courier_price' => $request->Ekart_2kg_fixed_price ?? 2

        ],



        'Blue Dart_0.5 KG' => [
            'shipping' => $request->blue_dart_0_5_kg_shipping ?? 70,
            'cod' => $request->blue_dart_0_5_kg_cod ?? 32,
            'cod_percent' => $request->blue_dart_0_5_kg_cod_percent ?? 2,
             'fixed_courier_price' => $request->blue_dart_0_5_kg_fixed_price ?? 2

        ],

        'Delhivery_5kg' => [
            'shipping' => $request->delhivery_5kg_shipping ?? 70,
            'cod' => $request->delhivery_5kg_cod ?? 32,
            'cod_percent' => $request->delhivery_5kg_cod_percent ?? 2,
                         'fixed_courier_price' => $request->delhivery_5kg_fixed_price ?? 2

        ],

        
        'Delhivery_10kg' => [
            'shipping' => $request->delhivery_10kg_shipping ?? 70,
            'cod' => $request->delhivery_10kg_cod ?? 32,
            'cod_percent' => $request->delhivery_10kg_cod_percent ?? 2,
            'fixed_courier_price' => $request->delhivery_10kg_fixed_price ?? 2

        ],
        'Delhivery_1KG' => [
            'shipping' => $request->delhivery_1kg_shipping ?? 70,
            'cod' => $request->delhivery_1kg_cod ?? 32,
            'cod_percent' => $request->delhivery_1kg_cod_percent ?? 2,
            'fixed_courier_price' => $request->delhivery_1kg_fixed_price ?? 2

        ],
        'Boxd_1753163038641' => [
            'shipping' => $request->boxd_1753163038641_shipping ?? 70,
            'cod' => $request->boxd_1753163038641_cod ?? 32,
            'cod_percent' => $request->boxd_1753163038641_cod_percent ?? 2,
                                    'fixed_courier_price' => $request->boxd_1753163038641_fixed_price ?? 2

        ],
        'Boxd_1750498184844' => [
            'shipping' => $request->boxd_1750498184844_shipping ?? 70,
            'cod' => $request->boxd_1750498184844_cod ?? 32,
            'cod_percent' => $request->boxd_1750498184844_cod_percent ?? 2,
                 'fixed_courier_price' => $request->boxd_1750498184844_fixed_price ?? 2

        ],
        'Boxd_1746709645240' => [
            'shipping' => $request->boxd_1746709645240_shipping ?? 70,
            'cod' => $request->boxd_1746709645240_cod ?? 32,
            'cod_percent' => $request->boxd_1746709645240_cod_percent ?? 2,
                             'fixed_courier_price' => $request->boxd_1746709645240_fixed_price ?? 2

        ],
        'Boxd_1753178335262' => [
            'shipping' => $request->boxd_1753178335262_shipping ?? 70,
            'cod' => $request->boxd_1753178335262_cod ?? 32,
            'cod_percent' => $request->boxd_1753178335262_cod_percent ?? 2,
                                         'fixed_courier_price' => $request->boxd_1753178335262_fixed_price ?? 2

        ],

 

           'parcel_x_Delhivery' => [
            'shipping' => $request->parcel_x_Delhivery_shipping ?? 70,
            'cod' => $request->parcel_x_Delhivery_cod ?? 32,
            'cod_percent' => $request->parcel_x_Delhivery_cod_percent ?? 2,
            'fixed_courier_price' => $request->parcel_x_Delhivery_fixed_price ?? 2

           ],
            'parcel_x_Amazon' => [
            'shipping' => $request->parcel_x_Amazon_shipping ?? 70,
            'cod' => $request->parcel_x_Amazon_cod ?? 32,
            'cod_percent' => $request->parcel_x_Amazon_cod_percent ?? 2,
            'fixed_courier_price' => $request->parcel_x_Amazon_fixed_price ?? 2

            ],
                        'parcel_x_Amazon_2kg' => [
            'shipping' => $request->parcel_x_Amazon_2kg_shipping ?? 70,
            'cod' => $request->parcel_x_Amazon_2kg_cod ?? 32,
            'cod_percent' => $request->parcel_x_Amazon_2kg_cod_percent ?? 2,
            'fixed_courier_price' => $request->parcel_x_Amazon_2kg_fixed_price ?? 2
            ],
                        'parcel_x_Amazon_1kg' => [
            'shipping' => $request->parcel_x_Amazon_1kg_shipping ?? 70,
            'cod' => $request->parcel_x_Amazon_1kg_cod ?? 32,
            'cod_percent' => $request->parcel_x_Amazon_1kg_cod_percent ?? 2,
            'fixed_courier_price' => $request->parcel_x_Amazon_1kg_fixed_price ?? 2
            ],
         'Shadowfax' => [
            'shipping' => $request->shadowfax_shipping ?? 70,
            'cod' => $request->shadowfax_cod ?? 32,
            'cod_percent' => $request->shadowfax_cod_percent ?? 2,
            'fixed_courier_price' => $request->shadowfax_fixed_price ?? 2
         ],

            'shiprocket_Xpressbee' => [
            'shipping' => $request->shiprocket_xpressbee_shipping ?? 70,
            'cod' => $request->shiprocket_xpressbee_cod ?? 32,
            'cod_percent' => $request->shiprocket_xpressbee_cod_percent ?? 2,
            'fixed_courier_price' => $request->shiprocket_xpressbee_fixed_price ?? 2
            ],

                        'shiprocket_Delhivery' => [
            'shipping' => $request->shiprocket_delhivery_shipping ?? 70,
            'cod' => $request->shiprocket_delhivery_cod ?? 32,
            'cod_percent' => $request->shiprocket_delhivery_cod_percent ?? 2,
            'fixed_courier_price' => $request->shiprocket_delhivery_fixed_price ?? 2
            ],
                                    'shiprocket_Bluedart' => [
            'shipping' => $request->shiprocket_bluedart_shipping ?? 70,
            'cod' => $request->shiprocket_bluedart_cod ?? 32,
            'cod_percent' => $request->shiprocket_bluedart_cod_percent ?? 2,
            'fixed_courier_price' => $request->shiprocket_bluedart_fixed_price ?? 2
            ],

    ];

    foreach ($courierServices as $courierName => $charges) {
        // Check if record exists
        $existing = PriceSetting::where('seller_id', $validated['seller_id'])
            ->where('LogisticProvider', $courierName)
            ->first();

        $data = [
            'seller_id' => $validated['seller_id'],
            'LogisticProvider' => $courierName,
            'shipping_charge' => $charges['shipping'],
            'cod_charge' => $charges['cod'],
            'cod_charge_parsent' => $charges['cod_percent'],
            'fixed_courier_price' => $charges['fixed_courier_price'],
        ];

        if ($existing) {
            // Update existing record
            $existing->update($data);
        } else {
            // Create new record
            PriceSetting::create($data);
        }
    }

    return to_route('pricesetting.add')->withSuccess('Price settings updated successfully!');
}


}
