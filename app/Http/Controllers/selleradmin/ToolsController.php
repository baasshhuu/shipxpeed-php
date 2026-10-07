<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Models\Recharge;
use App\Models\PriceSetting;
use Illuminate\Support\Facades\Auth;

class ToolsController extends Controller
{

    public function ratecard()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');

     $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }
        return view('sellerdashboard.tools.newratecalculater', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }




    
public function shipmentprice()
{
    $seller = Auth::guard('seller')->user();
    
    // Wallet calculations
    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

    $rateCard = RateCard::where('seller_id', $seller->id)->first();
    $isSellerAllowed = $rateCard && ($seller && ($seller->id == $rateCard->seller_id || $rateCard->seller_id == null));
    
    $pdfLinks = [
        'pdf_10' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_1 ? asset('storage/' . $rateCard->rate_pdf_1) : null,
        'pdf_20' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_2 ? asset('storage/' . $rateCard->rate_pdf_2) : null,
        'pdf_30' => $rateCard && $rateCard->rate_pdf_3 ? asset('storage/' . $rateCard->rate_pdf_3) : null,
    ];

    // Get all price settings at once
    $priceSettings = PriceSetting::where('seller_id', $seller->id)
        ->get()
        ->keyBy('LogisticProvider');

    $priceSettingsXpressBees = PriceSetting::where(['seller_id'=> $seller->id,'LogisticProvider'=>'XpressBees'])
        ->first();
  
$priceSettingsXpressBees_cod_charge = $priceSettingsXpressBees->cod_charge ?? 32;
$priceSettingsXpressBees_cod_charge_parsent = $priceSettingsXpressBees->cod_charge_parsent ?? 2;
   

$adjustShippingRate = function($baseRate, $courierName) use ($priceSettings) {
    if (!is_numeric($baseRate)) return $baseRate;

    $setting = $priceSettings[$courierName] ?? null;

    // अगर shipping_charge null या empty है → 70% बढ़ाओ बेस रेट पर
    if (!$setting || $setting->shipping_charge === null || $setting->shipping_charge === '') {
        $finalRate = $baseRate * 1.70; // 70% बढ़ा के
        return round($finalRate, 2);
    }

    // shipping_charge प्रतिशत मिलने पर उसी प्रतिशत से बढ़ाओ बेस रेट पर
    $shippingChargePct = is_numeric($setting->shipping_charge) 
                         ? floatval($setting->shipping_charge) 
                         : 0;

    $finalRate = $baseRate + ($baseRate * $shippingChargePct / 100);

    return round($finalRate, 2);
};



    // Function to get COD charge as stored (without calculation)
    $getCodCharge = function($courierName) use ($priceSettings) {
        $setting = $priceSettings[$courierName] ?? null;
        return $setting ? ($setting->cod_charge . (strpos($setting->cod_charge, '%') === false ? ' or ' . $setting->cod_charge_parsent . '%' : '')) : 'N/A';
    };


    $applyGst = function($rate) {
    if (!is_numeric($rate)) return $rate;
    $withGst = $rate + ($rate * 0.18);
    return round($withGst, 2);
};


    // Build rate data with adjusted rates
    $rateData = [
  
'Delhivery - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
    'rows' => [
        ['Upto 500 gms', 
            $adjustShippingRate(28.6, 'Delhivery'),
            $adjustShippingRate(31.9, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(38.5, 'Delhivery'),
            $adjustShippingRate(55.0, 'Delhivery'),
            $adjustShippingRate(60.5, 'Delhivery')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(25.3, 'Delhivery'),
            $adjustShippingRate(28.6, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(53.9, 'Delhivery'),
            $adjustShippingRate(59.4, 'Delhivery')
        ],
     
    ]
],



'Delhivery Air - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
    'rows' => [
        ['Upto 500 gms', 
           $adjustShippingRate(30.8, 'Delhivery Air'),
            $adjustShippingRate(35.2, 'Delhivery Air'),
            $adjustShippingRate(52.8, 'Delhivery Air'),
            $adjustShippingRate(56.1, 'Delhivery Air'),
            $adjustShippingRate(67.1, 'Delhivery Air'),
            $adjustShippingRate(73.7, 'Delhivery Air')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(27.5, 'Delhivery Air'),
            $adjustShippingRate(30.8, 'Delhivery Air'),
            $adjustShippingRate(49.5, 'Delhivery Air'),
            $adjustShippingRate(55.0, 'Delhivery Air'),
            $adjustShippingRate(63.8, 'Delhivery Air'),
            $adjustShippingRate(70.4, 'Delhivery Air')
        ],

        // ['COD Charges', '32 or 2%', '', '', '', '', '']
    ]
],

'Delhivery - 250 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
    'rows' => [
        ['First 250 gms',
           $adjustShippingRate(26.4, 'Boxd_1753163038641'),
           $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641')
        ],
        ['Add 250 gms',
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641'),
            $adjustShippingRate(26.4, 'Boxd_1753163038641')
        ],
    ]
],


'Delhivery - 1Kg Surface' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
    'rows' => [
        ['First 1000 gms',
          $adjustShippingRate(36.3, 'Delhivery_1KG'),
            $adjustShippingRate(42.9, 'Delhivery_1KG'),
            $adjustShippingRate(46.2, 'Delhivery_1KG'),
            $adjustShippingRate(48.4, 'Delhivery_1KG'),
            $adjustShippingRate(51.7, 'Delhivery_1KG'),
            $adjustShippingRate(51.7, 'Delhivery_1KG')
        ],
        ['Add 1000 gms',
            $adjustShippingRate(36.3, 'Delhivery_1KG'),
            $adjustShippingRate(42.9, 'Delhivery_1KG'),
            $adjustShippingRate(46.2, 'Delhivery_1KG'),
            $adjustShippingRate(48.4, 'Delhivery_1KG'),
            $adjustShippingRate(51.7, 'Delhivery_1KG'),
            $adjustShippingRate(51.7, 'Delhivery_1KG')
        ],
    ]
],


'Delhivery - Surface 5Kg' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['First 5 Kg',  
            $adjustShippingRate(125.4, 'Delhivery_5kg'),
            $adjustShippingRate(159.5, 'Delhivery_5kg'),
            $adjustShippingRate(222.2, 'Delhivery_5kg'),
            $adjustShippingRate(235.4, 'Delhivery_5kg'),
            $adjustShippingRate(313.5, 'Delhivery_5kg')
        ],
        ['Excess 1 Kg',  
            $adjustShippingRate(25.3, 'Delhivery_5kg'),
            $adjustShippingRate(27.83, 'Delhivery_5kg'),
            $adjustShippingRate(36.3, 'Delhivery_5kg'),
            $adjustShippingRate(42.35, 'Delhivery_5kg'),
            $adjustShippingRate(60.5, 'Delhivery_5kg')
        ],
    ]
],



'Delhivery - Surface 10Kg' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['First 10 Kg',  
            $adjustShippingRate(170.5, 'Delhivery_10kg'),
            $adjustShippingRate(214.5, 'Delhivery_10kg'),
            $adjustShippingRate(297, 'Delhivery_10kg'),
            $adjustShippingRate(314.6, 'Delhivery_10kg'),
            $adjustShippingRate(429, 'Delhivery_10kg')
        ],
        ['Excess 1 Kg',
            $adjustShippingRate(15.4, 'Delhivery_10kg'),
            $adjustShippingRate(19.8, 'Delhivery_10kg'),
            $adjustShippingRate(24.2, 'Delhivery_10kg'),
            $adjustShippingRate(27.5, 'Delhivery_10kg'),
            $adjustShippingRate(39.6, 'Delhivery_10kg')
        ],
    ]
],




'Bluedart Air' => [
    'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['First 500 grams',
            $adjustShippingRate(42.9, 'Boxd_1750498184844'),
            $adjustShippingRate(47.85, 'Boxd_1750498184844'),
            $adjustShippingRate(51.7, 'Boxd_1750498184844'),
            $adjustShippingRate(54.45, 'Boxd_1750498184844'),
            $adjustShippingRate(75.9, 'Boxd_1750498184844')
        ],
        ['Add 500 gms',
            $adjustShippingRate(42.9, 'Boxd_1750498184844'),
            $adjustShippingRate(47.85, 'Boxd_1750498184844'),
            $adjustShippingRate(51.7, 'Boxd_1750498184844'),
            $adjustShippingRate(54.45, 'Boxd_1750498184844'),
            $adjustShippingRate(75.9, 'Boxd_1750498184844')
        ],
    ]
],



'Bluedart Surface' => [
    'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['First 500 grams',
            $adjustShippingRate(28.6, 'Boxd_1746709645240'),
            $adjustShippingRate(31.9, 'Boxd_1746709645240'),
            $adjustShippingRate(38.5, 'Boxd_1746709645240'),
            $adjustShippingRate(40.7, 'Boxd_1746709645240'),
            $adjustShippingRate(58.3, 'Boxd_1746709645240')
        ],
        ['Add 500 gms',
            $adjustShippingRate(28.6, 'Boxd_1746709645240'),
            $adjustShippingRate(31.9, 'Boxd_1746709645240'),
            $adjustShippingRate(38.5, 'Boxd_1746709645240'),
            $adjustShippingRate(40.7, 'Boxd_1746709645240'),
            $adjustShippingRate(58.3, 'Boxd_1746709645240')
        ],
    ]
],



// 'Shadowfax Rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//         ['Additional per 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//     ]
// ],



// 'Ekart rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Surface', 'Zone D Surface', 'Zone E Surface'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//     ]
// ],



// 'Ekart Air' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Air', 'Zone D Air', 'Zone E Air'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//     ]
// ],


'Amazon ATS 500gm' => [
    'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
    'rows' => [
        ['First 500gm',  
            $adjustShippingRate(25, 'Amazon_0.5 KG'),
            $adjustShippingRate(28, 'Amazon_0.5 KG'),
            $adjustShippingRate(32, 'Amazon_0.5 KG'),
            $adjustShippingRate(38, 'Amazon_0.5 KG'),
            $adjustShippingRate(51, 'Amazon_0.5 KG')
        ],
        ['Excess 500gm',  
            $adjustShippingRate(17, 'Amazon_0.5 KG'),
            $adjustShippingRate(19, 'Amazon_0.5 KG'),
            $adjustShippingRate(22, 'Amazon_0.5 KG'),
            $adjustShippingRate(25, 'Amazon_0.5 KG'),
            $adjustShippingRate(30, 'Amazon_0.5 KG')
        ],
    ]
],



'Amazon ATS 2kg' => [
    'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
    'rows' => [
        ['2 Kg',  
            $adjustShippingRate(72, 'Amazon_2 KG'),
            $adjustShippingRate(79, 'Amazon_2 KG'),
            $adjustShippingRate(90, 'Amazon_2 KG'),
            $adjustShippingRate(105, 'Amazon_2 KG'),
            $adjustShippingRate(125, 'Amazon_2 KG')
        ],
        ['Excess 1 Kg',  
            $adjustShippingRate(35, 'Amazon_2 KG'),
            $adjustShippingRate(38, 'Amazon_2 KG'),
            $adjustShippingRate(42, 'Amazon_2 KG'),
            $adjustShippingRate(46, 'Amazon_2 KG'),
            $adjustShippingRate(50, 'Amazon_2 KG')
        ],
    ]
],


    ];

    return view('sellerdashboard.tools.shipmentprice', [
        'priceSettingsXpressBees_cod_charge' => $priceSettingsXpressBees_cod_charge,
        'priceSettingsXpressBees_cod_charge_parsent' => $priceSettingsXpressBees_cod_charge_parsent,

        'seller' => $seller,
        'totalAmount' => $totalAmount,
        'pdfLinks' => $pdfLinks,
        'rateCard' => $rateCard,
        'rateData' => $rateData,
        'priceSettings' => $priceSettings
    ]);
}
   




    
// public function shipmentprice()
// {
//     $seller = Auth::guard('seller')->user();
    
//     // Wallet calculations
//     $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     $rateCard = RateCard::where('seller_id', $seller->id)->first();
//     $isSellerAllowed = $rateCard && ($seller && ($seller->id == $rateCard->seller_id || $rateCard->seller_id == null));
    
//     $pdfLinks = [
//         'pdf_10' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_1 ? asset('storage/' . $rateCard->rate_pdf_1) : null,
//         'pdf_20' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_2 ? asset('storage/' . $rateCard->rate_pdf_2) : null,
//         'pdf_30' => $rateCard && $rateCard->rate_pdf_3 ? asset('storage/' . $rateCard->rate_pdf_3) : null,
//     ];

//     // Get all price settings at once
//     $priceSettings = PriceSetting::where('seller_id', $seller->id)
//         ->get()
//         ->keyBy('LogisticProvider');

//     $priceSettingsXpressBees = PriceSetting::where(['seller_id'=> $seller->id,'LogisticProvider'=>'XpressBees'])
//         ->first();
  
// $priceSettingsXpressBees_cod_charge = $priceSettingsXpressBees->cod_charge ?? 32;
// $priceSettingsXpressBees_cod_charge_parsent = $priceSettingsXpressBees->cod_charge_parsent ?? 2;
   

// $adjustShippingRate = function($baseRate, $courierName) use ($priceSettings) {
//     if (!is_numeric($baseRate)) return $baseRate;

//     $setting = $priceSettings[$courierName] ?? null;

//     // अगर shipping_charge null या empty है → 70% बढ़ाओ बेस रेट पर
//     if (!$setting || $setting->shipping_charge === null || $setting->shipping_charge === '') {
//         $finalRate = $baseRate * 1.70; // 70% बढ़ा के
//         return round($finalRate, 2);
//     }

//     // shipping_charge प्रतिशत मिलने पर उसी प्रतिशत से बढ़ाओ बेस रेट पर
//     $shippingChargePct = is_numeric($setting->shipping_charge) 
//                          ? floatval($setting->shipping_charge) 
//                          : 0;

//     $finalRate = $baseRate + ($baseRate * $shippingChargePct / 100);

//     return round($finalRate, 2);
// };



//     // Function to get COD charge as stored (without calculation)
//     $getCodCharge = function($courierName) use ($priceSettings) {
//         $setting = $priceSettings[$courierName] ?? null;
//         return $setting ? ($setting->cod_charge . (strpos($setting->cod_charge, '%') === false ? ' or ' . $setting->cod_charge_parsent . '%' : '')) : 'N/A';
//     };

//     // Build rate data with adjusted rates
//     $rateData = [
  
// 'Delhivery - 500 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['Upto 500 gms', 
//             $adjustShippingRate(28.6, 'Delhivery'),
//             $adjustShippingRate(31.9, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(38.5, 'Delhivery'),
//             $adjustShippingRate(55.0, 'Delhivery'),
//             $adjustShippingRate(60.5, 'Delhivery')
//         ],
//         ['Additional 500 gms', 
//             $adjustShippingRate(25.3, 'Delhivery'),
//             $adjustShippingRate(28.6, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(53.9, 'Delhivery'),
//             $adjustShippingRate(59.4, 'Delhivery')
//         ],
     
//     ]
// ],



// 'Delhivery Air - 500 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['Upto 500 gms', 
//             $adjustShippingRate(30.8, 'Delhivery Air'),
//             $adjustShippingRate(35.2, 'Delhivery Air'),
//             $adjustShippingRate(52.8, 'Delhivery Air'),
//             $adjustShippingRate(56.1, 'Delhivery Air'),
//             $adjustShippingRate(67.1, 'Delhivery Air'),
//             $adjustShippingRate(73.7, 'Delhivery Air')
//         ],
//         ['Additional 500 gms', 
//             $adjustShippingRate(27.5, 'Delhivery Air'),
//             $adjustShippingRate(30.8, 'Delhivery Air'),
//             $adjustShippingRate(49.5, 'Delhivery Air'),
//             $adjustShippingRate(55.0, 'Delhivery Air'),
//             $adjustShippingRate(63.8, 'Delhivery Air'),
//             $adjustShippingRate(70.4, 'Delhivery Air')
//         ],

//         // ['COD Charges', '32 or 2%', '', '', '', '', '']
//     ]
// ],

// 'Delhivery - 250 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['First 250 gms',
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms')
//         ],
//         ['Add 250 gms',
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms')
//         ],
//     ]
// ],


// 'Delhivery - 1Kg Surface' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['First 1000 gms',
//             $adjustShippingRate(36.3, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(42.9, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(46.2, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(48.4, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface')
//         ],
//         ['Add 1000 gms',
//             $adjustShippingRate(36.3, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(42.9, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(46.2, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(48.4, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface')
//         ],
//     ]
// ],


// 'Delhivery - Surface 5Kg' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 5 Kg',  
//             $adjustShippingRate(125.4, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(159.5, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(222.2, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(235.4, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(313.5, 'Delhivery-Surface5Kg')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(25.3, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(27.83, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(36.3, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(42.35, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(60.5, 'Delhivery-Surface5Kg')
//         ],
//     ]
// ],



// 'Delhivery - Surface 10Kg' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 10 Kg',  
//             $adjustShippingRate(170.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(214.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(297, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(314.6, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(429, 'Delhivery-Surface10Kg')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(15.4, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(19.8, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(24.2, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(27.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(39.6, 'Delhivery-Surface10Kg')
//         ],
//     ]
// ],




// 'Bluedart Air' => [
//     'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500 grams',
//             $adjustShippingRate(42.9, 'Bluedart-Air'),
//             $adjustShippingRate(47.85, 'Bluedart-Air'),
//             $adjustShippingRate(51.7, 'Bluedart-Air'),
//             $adjustShippingRate(54.45, 'Bluedart-Air'),
//             $adjustShippingRate(75.9, 'Bluedart-Air')
//         ],
//         ['Add 500 gms',
//             $adjustShippingRate(42.9, 'Bluedart-Air'),
//             $adjustShippingRate(47.85, 'Bluedart-Air'),
//             $adjustShippingRate(51.7, 'Bluedart-Air'),
//             $adjustShippingRate(54.45, 'Bluedart-Air'),
//             $adjustShippingRate(75.9, 'Bluedart-Air')
//         ],
//     ]
// ],



// 'Bluedart Surface' => [
//     'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500 grams',
//             $adjustShippingRate(28.6, 'Bluedart-Surface'),
//             $adjustShippingRate(31.9, 'Bluedart-Surface'),
//             $adjustShippingRate(38.5, 'Bluedart-Surface'),
//             $adjustShippingRate(40.7, 'Bluedart-Surface'),
//             $adjustShippingRate(58.3, 'Bluedart-Surface')
//         ],
//         ['Add 500 gms',
//             $adjustShippingRate(28.6, 'Bluedart-Surface'),
//             $adjustShippingRate(31.9, 'Bluedart-Surface'),
//             $adjustShippingRate(38.5, 'Bluedart-Surface'),
//             $adjustShippingRate(40.7, 'Bluedart-Surface'),
//             $adjustShippingRate(58.3, 'Bluedart-Surface')
//         ],
//     ]
// ],



// 'Shadowfax Rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//         ['Additional per 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//     ]
// ],



// 'Ekart rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Surface', 'Zone D Surface', 'Zone E Surface'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//     ]
// ],



// 'Ekart Air' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Air', 'Zone D Air', 'Zone E Air'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//     ]
// ],


// 'Amazon ATS 500gm' => [
//     'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
//     'rows' => [
//         ['First 500gm',  
//             $adjustShippingRate(25, 'Amazon'),
//             $adjustShippingRate(28, 'Amazon'),
//             $adjustShippingRate(32, 'Amazon'),
//             $adjustShippingRate(38, 'Amazon'),
//             $adjustShippingRate(51, 'Amazon')
//         ],
//         ['Excess 500gm',  
//             $adjustShippingRate(17, 'Amazon'),
//             $adjustShippingRate(19, 'Amazon'),
//             $adjustShippingRate(22, 'Amazon'),
//             $adjustShippingRate(25, 'Amazon'),
//             $adjustShippingRate(30, 'Amazon')
//         ],
//     ]
// ],



// 'Amazon ATS 2kg' => [
//     'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
//     'rows' => [
//         ['2 Kg',  
//             $adjustShippingRate(72, 'Amazon'),
//             $adjustShippingRate(79, 'Amazon'),
//             $adjustShippingRate(90, 'Amazon'),
//             $adjustShippingRate(105, 'Amazon'),
//             $adjustShippingRate(125, 'Amazon')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(35, 'Amazon'),
//             $adjustShippingRate(38, 'Amazon'),
//             $adjustShippingRate(42, 'Amazon'),
//             $adjustShippingRate(46, 'Amazon'),
//             $adjustShippingRate(50, 'Amazon')
//         ],
//     ]
// ],


//     ];

//     return view('sellerdashboard.tools.shipmentprice', [
//         'priceSettingsXpressBees_cod_charge' => $priceSettingsXpressBees_cod_charge,
//         'priceSettingsXpressBees_cod_charge_parsent' => $priceSettingsXpressBees_cod_charge_parsent,

//         'seller' => $seller,
//         'totalAmount' => $totalAmount,
//         'pdfLinks' => $pdfLinks,
//         'rateCard' => $rateCard,
//         'rateData' => $rateData,
//         'priceSettings' => $priceSettings
//     ]);
// }
   



    
    
// public function shipmentprice()
// {
//     $seller = Auth::guard('seller')->user();
    
//     // Wallet calculations
//     $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     $rateCard = RateCard::where('seller_id', $seller->id)->first();
//     $isSellerAllowed = $rateCard && ($seller && ($seller->id == $rateCard->seller_id || $rateCard->seller_id == null));
    
//     $pdfLinks = [
//         'pdf_10' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_1 ? asset('storage/' . $rateCard->rate_pdf_1) : null,
//         'pdf_20' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_2 ? asset('storage/' . $rateCard->rate_pdf_2) : null,
//         'pdf_30' => $rateCard && $rateCard->rate_pdf_3 ? asset('storage/' . $rateCard->rate_pdf_3) : null,
//     ];

//     // Get all price settings at once
//     $priceSettings = PriceSetting::where('seller_id', $seller->id)
//         ->get()
//         ->keyBy('LogisticProvider');

//     $priceSettingsXpressBees = PriceSetting::where(['seller_id'=> $seller->id,'LogisticProvider'=>'XpressBees'])
//         ->first();
  
// $priceSettingsXpressBees_cod_charge = $priceSettingsXpressBees->cod_charge ?? 32;
// $priceSettingsXpressBees_cod_charge_parsent = $priceSettingsXpressBees->cod_charge_parsent ?? 2;
   

// $adjustShippingRate = function($baseRate, $courierName) use ($priceSettings) {
//     if (!is_numeric($baseRate)) return $baseRate;

//     $setting = $priceSettings[$courierName] ?? null;

//     // अगर shipping_charge null या empty है → 70% बढ़ाओ बेस रेट पर
//     if (!$setting || $setting->shipping_charge === null || $setting->shipping_charge === '') {
//         $finalRate = $baseRate * 1.70; // 70% बढ़ा के
//         return round($finalRate, 2);
//     }

//     // shipping_charge प्रतिशत मिलने पर उसी प्रतिशत से बढ़ाओ बेस रेट पर
//     $shippingChargePct = is_numeric($setting->shipping_charge) 
//                          ? floatval($setting->shipping_charge) 
//                          : 0;

//     $finalRate = $baseRate + ($baseRate * $shippingChargePct / 100);

//     return round($finalRate, 2);
// };



//     // Function to get COD charge as stored (without calculation)
//     $getCodCharge = function($courierName) use ($priceSettings) {
//         $setting = $priceSettings[$courierName] ?? null;
//         return $setting ? ($setting->cod_charge . (strpos($setting->cod_charge, '%') === false ? ' or ' . $setting->cod_charge_parsent . '%' : '')) : 'N/A';
//     };

//     // Build rate data with adjusted rates
//     $rateData = [
  
// 'Delhivery - 500 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['Upto 500 gms', 
//             $adjustShippingRate(28.6, 'Delhivery'),
//             $adjustShippingRate(31.9, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(38.5, 'Delhivery'),
//             $adjustShippingRate(55.0, 'Delhivery'),
//             $adjustShippingRate(60.5, 'Delhivery')
//         ],
//         ['Additional 500 gms', 
//             $adjustShippingRate(25.3, 'Delhivery'),
//             $adjustShippingRate(28.6, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(53.9, 'Delhivery'),
//             $adjustShippingRate(59.4, 'Delhivery')
//         ],
     
//     ]
// ],



// 'Delhivery Air - 500 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['Upto 500 gms', 
//             $adjustShippingRate(30.8, 'Delhivery Air'),
//             $adjustShippingRate(35.2, 'Delhivery Air'),
//             $adjustShippingRate(52.8, 'Delhivery Air'),
//             $adjustShippingRate(56.1, 'Delhivery Air'),
//             $adjustShippingRate(67.1, 'Delhivery Air'),
//             $adjustShippingRate(73.7, 'Delhivery Air')
//         ],
//         ['Additional 500 gms', 
//             $adjustShippingRate(27.5, 'Delhivery Air'),
//             $adjustShippingRate(30.8, 'Delhivery Air'),
//             $adjustShippingRate(49.5, 'Delhivery Air'),
//             $adjustShippingRate(55.0, 'Delhivery Air'),
//             $adjustShippingRate(63.8, 'Delhivery Air'),
//             $adjustShippingRate(70.4, 'Delhivery Air')
//         ],

//         // ['COD Charges', '32 or 2%', '', '', '', '', '']
//     ]
// ],

// 'Delhivery - 250 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['First 250 gms',
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms')
//         ],
//         ['Add 250 gms',
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms')
//         ],
//     ]
// ],


// 'Delhivery - 1Kg Surface' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['First 1000 gms',
//             $adjustShippingRate(36.3, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(42.9, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(46.2, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(48.4, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface')
//         ],
//         ['Add 1000 gms',
//             $adjustShippingRate(36.3, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(42.9, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(46.2, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(48.4, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface')
//         ],
//     ]
// ],


// 'Delhivery - Surface 5Kg' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 5 Kg',  
//             $adjustShippingRate(125.4, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(159.5, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(222.2, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(235.4, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(313.5, 'Delhivery-Surface5Kg')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(25.3, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(27.83, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(36.3, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(42.35, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(60.5, 'Delhivery-Surface5Kg')
//         ],
//     ]
// ],



// 'Delhivery - Surface 10Kg' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 10 Kg',  
//             $adjustShippingRate(170.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(214.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(297, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(314.6, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(429, 'Delhivery-Surface10Kg')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(15.4, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(19.8, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(24.2, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(27.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(39.6, 'Delhivery-Surface10Kg')
//         ],
//     ]
// ],




// 'Bluedart Air' => [
//     'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500 grams',
//             $adjustShippingRate(42.9, 'Bluedart-Air'),
//             $adjustShippingRate(47.85, 'Bluedart-Air'),
//             $adjustShippingRate(51.7, 'Bluedart-Air'),
//             $adjustShippingRate(54.45, 'Bluedart-Air'),
//             $adjustShippingRate(75.9, 'Bluedart-Air')
//         ],
//         ['Add 500 gms',
//             $adjustShippingRate(42.9, 'Bluedart-Air'),
//             $adjustShippingRate(47.85, 'Bluedart-Air'),
//             $adjustShippingRate(51.7, 'Bluedart-Air'),
//             $adjustShippingRate(54.45, 'Bluedart-Air'),
//             $adjustShippingRate(75.9, 'Bluedart-Air')
//         ],
//     ]
// ],



// 'Bluedart Surface' => [
//     'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500 grams',
//             $adjustShippingRate(28.6, 'Bluedart-Surface'),
//             $adjustShippingRate(31.9, 'Bluedart-Surface'),
//             $adjustShippingRate(38.5, 'Bluedart-Surface'),
//             $adjustShippingRate(40.7, 'Bluedart-Surface'),
//             $adjustShippingRate(58.3, 'Bluedart-Surface')
//         ],
//         ['Add 500 gms',
//             $adjustShippingRate(28.6, 'Bluedart-Surface'),
//             $adjustShippingRate(31.9, 'Bluedart-Surface'),
//             $adjustShippingRate(38.5, 'Bluedart-Surface'),
//             $adjustShippingRate(40.7, 'Bluedart-Surface'),
//             $adjustShippingRate(58.3, 'Bluedart-Surface')
//         ],
//     ]
// ],



// 'Shadowfax Rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//         ['Additional per 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//     ]
// ],



// 'Ekart rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Surface', 'Zone D Surface', 'Zone E Surface'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//     ]
// ],



// 'Ekart Air' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Air', 'Zone D Air', 'Zone E Air'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//     ]
// ],


// 'Amazon ATS 500gm' => [
//     'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
//     'rows' => [
//         ['First 500gm',  
//             $adjustShippingRate(25, 'Amazon'),
//             $adjustShippingRate(28, 'Amazon'),
//             $adjustShippingRate(32, 'Amazon'),
//             $adjustShippingRate(38, 'Amazon'),
//             $adjustShippingRate(51, 'Amazon')
//         ],
//         ['Excess 500gm',  
//             $adjustShippingRate(17, 'Amazon'),
//             $adjustShippingRate(19, 'Amazon'),
//             $adjustShippingRate(22, 'Amazon'),
//             $adjustShippingRate(25, 'Amazon'),
//             $adjustShippingRate(30, 'Amazon')
//         ],
//     ]
// ],



// 'Amazon ATS 2kg' => [
//     'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
//     'rows' => [
//         ['2 Kg',  
//             $adjustShippingRate(72, 'Amazon'),
//             $adjustShippingRate(79, 'Amazon'),
//             $adjustShippingRate(90, 'Amazon'),
//             $adjustShippingRate(105, 'Amazon'),
//             $adjustShippingRate(125, 'Amazon')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(35, 'Amazon'),
//             $adjustShippingRate(38, 'Amazon'),
//             $adjustShippingRate(42, 'Amazon'),
//             $adjustShippingRate(46, 'Amazon'),
//             $adjustShippingRate(50, 'Amazon')
//         ],
//     ]
// ],


//     ];

//     return view('sellerdashboard.tools.shipmentprice', [
//         'priceSettingsXpressBees_cod_charge' => $priceSettingsXpressBees_cod_charge,
//         'priceSettingsXpressBees_cod_charge_parsent' => $priceSettingsXpressBees_cod_charge_parsent,

//         'seller' => $seller,
//         'totalAmount' => $totalAmount,
//         'pdfLinks' => $pdfLinks,
//         'rateCard' => $rateCard,
//         'rateData' => $rateData,
//         'priceSettings' => $priceSettings
//     ]);
// }
   
    




// public function shipmentprice()
// {
//     $seller = Auth::guard('seller')->user();
    
//     // Wallet calculations
//     $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
//         ->where('status', 1)
//         ->where('type', 'Credit')
//         ->sum('amount');

//     $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
//         ->where('type', 'Debit')
//         ->sum('amount');

//     $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

//     $rateCard = RateCard::where('seller_id', $seller->id)->first();
//     $isSellerAllowed = $rateCard && ($seller && ($seller->id == $rateCard->seller_id || $rateCard->seller_id == null));
    
//     $pdfLinks = [
//         'pdf_10' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_1 ? asset('storage/' . $rateCard->rate_pdf_1) : null,
//         'pdf_20' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_2 ? asset('storage/' . $rateCard->rate_pdf_2) : null,
//         'pdf_30' => $rateCard && $rateCard->rate_pdf_3 ? asset('storage/' . $rateCard->rate_pdf_3) : null,
//     ];

//     // Get all price settings at once
//     $priceSettings = PriceSetting::where('seller_id', $seller->id)
//         ->get()
//         ->keyBy('LogisticProvider');

//     $priceSettingsXpressBees = PriceSetting::where(['seller_id'=> $seller->id,'LogisticProvider'=>'XpressBees'])
//         ->first();
  
// $priceSettingsXpressBees_cod_charge = $priceSettingsXpressBees->cod_charge ?? 32;
// $priceSettingsXpressBees_cod_charge_parsent = $priceSettingsXpressBees->cod_charge_parsent ?? 2;
   

// $adjustShippingRate = function($baseRate, $courierName) use ($priceSettings) {
//     if (!is_numeric($baseRate)) return $baseRate;

//     $setting = $priceSettings[$courierName] ?? null;

//     // अगर shipping_charge null या empty है → 70% बढ़ाओ बेस रेट पर
//     if (!$setting || $setting->shipping_charge === null || $setting->shipping_charge === '') {
//         $finalRate = $baseRate * 1.70; // 70% बढ़ा के
//         return round($finalRate, 2);
//     }

//     // shipping_charge प्रतिशत मिलने पर उसी प्रतिशत से बढ़ाओ बेस रेट पर
//     $shippingChargePct = is_numeric($setting->shipping_charge) 
//                          ? floatval($setting->shipping_charge) 
//                          : 0;

//     $finalRate = $baseRate + ($baseRate * $shippingChargePct / 100);

//     return round($finalRate, 2);
// };



//     // Function to get COD charge as stored (without calculation)
//     $getCodCharge = function($courierName) use ($priceSettings) {
//         $setting = $priceSettings[$courierName] ?? null;
//         return $setting ? ($setting->cod_charge . (strpos($setting->cod_charge, '%') === false ? ' or ' . $setting->cod_charge_parsent . '%' : '')) : 'N/A';
//     };

//     // Build rate data with adjusted rates
//     $rateData = [
  
// 'Delhivery - 500 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['Upto 500 gms', 
//             $adjustShippingRate(28.6, 'Delhivery'),
//             $adjustShippingRate(31.9, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(38.5, 'Delhivery'),
//             $adjustShippingRate(55.0, 'Delhivery'),
//             $adjustShippingRate(60.5, 'Delhivery')
//         ],
//         ['Additional 500 gms', 
//             $adjustShippingRate(25.3, 'Delhivery'),
//             $adjustShippingRate(28.6, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(37.4, 'Delhivery'),
//             $adjustShippingRate(53.9, 'Delhivery'),
//             $adjustShippingRate(59.4, 'Delhivery')
//         ],
     
//     ]
// ],



// 'Delhivery Air - 500 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['Upto 500 gms', 
//             $adjustShippingRate(30.8, 'Delhivery Air'),
//             $adjustShippingRate(35.2, 'Delhivery Air'),
//             $adjustShippingRate(52.8, 'Delhivery Air'),
//             $adjustShippingRate(56.1, 'Delhivery Air'),
//             $adjustShippingRate(67.1, 'Delhivery Air'),
//             $adjustShippingRate(73.7, 'Delhivery Air')
//         ],
//         ['Additional 500 gms', 
//             $adjustShippingRate(27.5, 'Delhivery Air'),
//             $adjustShippingRate(30.8, 'Delhivery Air'),
//             $adjustShippingRate(49.5, 'Delhivery Air'),
//             $adjustShippingRate(55.0, 'Delhivery Air'),
//             $adjustShippingRate(63.8, 'Delhivery Air'),
//             $adjustShippingRate(70.4, 'Delhivery Air')
//         ],

//         // ['COD Charges', '32 or 2%', '', '', '', '', '']
//     ]
// ],

// 'Delhivery - 250 gms' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['First 250 gms',
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms')
//         ],
//         ['Add 250 gms',
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms'),
//             $adjustShippingRate(26.4, 'Delhivery-250gms')
//         ],
//     ]
// ],


// 'Delhivery - 1Kg Surface' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
//     'rows' => [
//         ['First 1000 gms',
//             $adjustShippingRate(36.3, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(42.9, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(46.2, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(48.4, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface')
//         ],
//         ['Add 1000 gms',
//             $adjustShippingRate(36.3, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(42.9, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(46.2, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(48.4, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface'),
//             $adjustShippingRate(51.7, 'Delhivery-1KgSurface')
//         ],
//     ]
// ],


// 'Delhivery - Surface 5Kg' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 5 Kg',  
//             $adjustShippingRate(125.4, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(159.5, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(222.2, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(235.4, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(313.5, 'Delhivery-Surface5Kg')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(25.3, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(27.83, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(36.3, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(42.35, 'Delhivery-Surface5Kg'),
//             $adjustShippingRate(60.5, 'Delhivery-Surface5Kg')
//         ],
//     ]
// ],



// 'Delhivery - Surface 10Kg' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 10 Kg',  
//             $adjustShippingRate(170.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(214.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(297, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(314.6, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(429, 'Delhivery-Surface10Kg')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(15.4, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(19.8, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(24.2, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(27.5, 'Delhivery-Surface10Kg'),
//             $adjustShippingRate(39.6, 'Delhivery-Surface10Kg')
//         ],
//     ]
// ],




// 'Bluedart Air' => [
//     'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500 grams',
//             $adjustShippingRate(42.9, 'Bluedart-Air'),
//             $adjustShippingRate(47.85, 'Bluedart-Air'),
//             $adjustShippingRate(51.7, 'Bluedart-Air'),
//             $adjustShippingRate(54.45, 'Bluedart-Air'),
//             $adjustShippingRate(75.9, 'Bluedart-Air')
//         ],
//         ['Add 500 gms',
//             $adjustShippingRate(42.9, 'Bluedart-Air'),
//             $adjustShippingRate(47.85, 'Bluedart-Air'),
//             $adjustShippingRate(51.7, 'Bluedart-Air'),
//             $adjustShippingRate(54.45, 'Bluedart-Air'),
//             $adjustShippingRate(75.9, 'Bluedart-Air')
//         ],
//     ]
// ],



// 'Bluedart Surface' => [
//     'headers' => ['Slabs', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500 grams',
//             $adjustShippingRate(28.6, 'Bluedart-Surface'),
//             $adjustShippingRate(31.9, 'Bluedart-Surface'),
//             $adjustShippingRate(38.5, 'Bluedart-Surface'),
//             $adjustShippingRate(40.7, 'Bluedart-Surface'),
//             $adjustShippingRate(58.3, 'Bluedart-Surface')
//         ],
//         ['Add 500 gms',
//             $adjustShippingRate(28.6, 'Bluedart-Surface'),
//             $adjustShippingRate(31.9, 'Bluedart-Surface'),
//             $adjustShippingRate(38.5, 'Bluedart-Surface'),
//             $adjustShippingRate(40.7, 'Bluedart-Surface'),
//             $adjustShippingRate(58.3, 'Bluedart-Surface')
//         ],
//     ]
// ],



// 'Shadowfax Rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
//     'rows' => [
//         ['First 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//         ['Additional per 500g',
//             $adjustShippingRate(30.8, 'Shadowfax'),
//             $adjustShippingRate(34.1, 'Shadowfax'),
//             $adjustShippingRate(42.9, 'Shadowfax'),
//             $adjustShippingRate(47.3, 'Shadowfax'),
//             $adjustShippingRate(55, 'Shadowfax')
//         ],
//     ]
// ],



// 'Ekart rate' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Surface', 'Zone D Surface', 'Zone E Surface'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart'),
//             $adjustShippingRate(32.67, 'Ekart'),
//             $adjustShippingRate(38.72, 'Ekart'),
//             $adjustShippingRate(43.56, 'Ekart'),
//             $adjustShippingRate(47.19, 'Ekart')
//         ],
//     ]
// ],



// 'Ekart Air' => [
//     'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C Air', 'Zone D Air', 'Zone E Air'],
//     'rows' => [
//         ['0–500 gms',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//         ['500 gms thereafter',
//             $adjustShippingRate(27.83, 'Ekart-Air'),
//             $adjustShippingRate(32.67, 'Ekart-Air'),
//             $adjustShippingRate(58.08, 'Ekart-Air'),
//             $adjustShippingRate(70.18, 'Ekart-Air'),
//             $adjustShippingRate(78.65, 'Ekart-Air')
//         ],
//     ]
// ],


// 'Amazon ATS 500gm' => [
//     'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
//     'rows' => [
//         ['First 500gm',  
//             $adjustShippingRate(25, 'Amazon'),
//             $adjustShippingRate(28, 'Amazon'),
//             $adjustShippingRate(32, 'Amazon'),
//             $adjustShippingRate(38, 'Amazon'),
//             $adjustShippingRate(51, 'Amazon')
//         ],
//         ['Excess 500gm',  
//             $adjustShippingRate(17, 'Amazon'),
//             $adjustShippingRate(19, 'Amazon'),
//             $adjustShippingRate(22, 'Amazon'),
//             $adjustShippingRate(25, 'Amazon'),
//             $adjustShippingRate(30, 'Amazon')
//         ],
//     ]
// ],



// 'Amazon ATS 2kg' => [
//     'headers' => ['Weight', 'Type', 'A', 'B', 'C', 'D', 'E'],
//     'rows' => [
//         ['2 Kg',  
//             $adjustShippingRate(72, 'Amazon'),
//             $adjustShippingRate(79, 'Amazon'),
//             $adjustShippingRate(90, 'Amazon'),
//             $adjustShippingRate(105, 'Amazon'),
//             $adjustShippingRate(125, 'Amazon')
//         ],
//         ['Excess 1 Kg',  
//             $adjustShippingRate(35, 'Amazon'),
//             $adjustShippingRate(38, 'Amazon'),
//             $adjustShippingRate(42, 'Amazon'),
//             $adjustShippingRate(46, 'Amazon'),
//             $adjustShippingRate(50, 'Amazon')
//         ],
//     ]
// ],


//     ];

//     return view('sellerdashboard.tools.shipmentprice', [
//         'priceSettingsXpressBees_cod_charge' => $priceSettingsXpressBees_cod_charge,
//         'priceSettingsXpressBees_cod_charge_parsent' => $priceSettingsXpressBees_cod_charge_parsent,

//         'seller' => $seller,
//         'totalAmount' => $totalAmount,
//         'pdfLinks' => $pdfLinks,
//         'rateCard' => $rateCard,
//         'rateData' => $rateData,
//         'priceSettings' => $priceSettings
//     ]);
// }






    
public function shipmentpriceold()
{
    $seller = Auth::guard('seller')->user();
    
    // Wallet calculations
    $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('type', 'Credit')
        ->sum('amount');

    $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
        ->where('type', 'Debit')
        ->sum('amount');

    $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

    $rateCard = RateCard::where('seller_id', $seller->id)->first();
    $isSellerAllowed = $rateCard && ($seller && ($seller->id == $rateCard->seller_id || $rateCard->seller_id == null));
    
    $pdfLinks = [
        'pdf_10' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_1 ? asset('storage/' . $rateCard->rate_pdf_1) : null,
        'pdf_20' => $isSellerAllowed && $rateCard && $rateCard->rate_pdf_2 ? asset('storage/' . $rateCard->rate_pdf_2) : null,
        'pdf_30' => $rateCard && $rateCard->rate_pdf_3 ? asset('storage/' . $rateCard->rate_pdf_3) : null,
    ];

    // Get all price settings at once
    $priceSettings = PriceSetting::where('seller_id', $seller->id)
        ->get()
        ->keyBy('LogisticProvider');

    $priceSettingsXpressBees = PriceSetting::where(['seller_id'=> $seller->id,'LogisticProvider'=>'XpressBees'])
        ->first();
  
$priceSettingsXpressBees_cod_charge = $priceSettingsXpressBees->cod_charge ?? 32;
$priceSettingsXpressBees_cod_charge_parsent = $priceSettingsXpressBees->cod_charge_parsent ?? 2;
   

$adjustShippingRate = function($baseRate, $courierName) use ($priceSettings) {
    if (!is_numeric($baseRate)) return $baseRate;

    $setting = $priceSettings[$courierName] ?? null;

    // अगर shipping_charge null या empty है → 70% बढ़ाओ बेस रेट पर
    if (!$setting || $setting->shipping_charge === null || $setting->shipping_charge === '') {
        $finalRate = $baseRate * 1.70; // 70% बढ़ा के
        return round($finalRate, 2);
    }

    // shipping_charge प्रतिशत मिलने पर उसी प्रतिशत से बढ़ाओ बेस रेट पर
    $shippingChargePct = is_numeric($setting->shipping_charge) 
                         ? floatval($setting->shipping_charge) 
                         : 0;

    $finalRate = $baseRate + ($baseRate * $shippingChargePct / 100);

    return round($finalRate, 2);
};



    // Function to get COD charge as stored (without calculation)
    $getCodCharge = function($courierName) use ($priceSettings) {
        $setting = $priceSettings[$courierName] ?? null;
        return $setting ? ($setting->cod_charge . (strpos($setting->cod_charge, '%') === false ? ' or ' . $setting->cod_charge_parsent . '%' : '')) : 'N/A';
    };

    // Build rate data with adjusted rates
    $rateData = [
       
'XpressBees - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 500 gms', 
            $adjustShippingRate(22, 'XpressBees'),
            $adjustShippingRate(25, 'XpressBees'),
            $adjustShippingRate(34, 'XpressBees'),
            $adjustShippingRate(37, 'XpressBees'),
            $adjustShippingRate(46, 'XpressBees')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(12, 'XpressBees'),
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(20, 'XpressBees'),
            $adjustShippingRate(24, 'XpressBees'),
            $adjustShippingRate(27, 'XpressBees')
        ],
        ['RTO 500 gms', 
            $adjustShippingRate(22, 'XpressBees'),
            $adjustShippingRate(25, 'XpressBees'),
            $adjustShippingRate(34, 'XpressBees'),
            $adjustShippingRate(37, 'XpressBees'),
            $adjustShippingRate(46, 'XpressBees')
        ],
        ['RTO Additional 500 gms', 
            $adjustShippingRate(12, 'XpressBees'),
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(20, 'XpressBees'),
            $adjustShippingRate(24, 'XpressBees'),
            $adjustShippingRate(27, 'XpressBees')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '']
    ]
],





'XpressBees - 1 Kg' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 1 Kgs', 
            $adjustShippingRate(34, 'XpressBees'),
            $adjustShippingRate(38, 'XpressBees'),
            $adjustShippingRate(53, 'XpressBees'),
            $adjustShippingRate(59, 'XpressBees'),
            $adjustShippingRate(69, 'XpressBees')
        ],
        ['Additional 1 Kgs', 
            $adjustShippingRate(27, 'XpressBees'),
            $adjustShippingRate(27, 'XpressBees'),
            $adjustShippingRate(30, 'XpressBees'),
            $adjustShippingRate(32, 'XpressBees'),
            $adjustShippingRate(36, 'XpressBees')
        ],
        ['RTO 1 Kgs', 
            $adjustShippingRate(34, 'XpressBees'),
            $adjustShippingRate(38, 'XpressBees'),
            $adjustShippingRate(53, 'XpressBees'),
            $adjustShippingRate(59, 'XpressBees'),
            $adjustShippingRate(69, 'XpressBees')
        ],
        ['RTO Additional 1 Kgs', 
            $adjustShippingRate(27, 'XpressBees'),
            $adjustShippingRate(27, 'XpressBees'),
            $adjustShippingRate(30, 'XpressBees'),
            $adjustShippingRate(32, 'XpressBees'),
            $adjustShippingRate(36, 'XpressBees')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '']
    ]
],




'XpressBees - 2 Kg' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 2 Kgs', 
            $adjustShippingRate(59, 'XpressBees'),
            $adjustShippingRate(69, 'XpressBees'),
            $adjustShippingRate(78, 'XpressBees'),
            $adjustShippingRate(86, 'XpressBees'),
            $adjustShippingRate(99, 'XpressBees')
        ],
        ['Additional 1 Kgs', 
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(19, 'XpressBees'),
            $adjustShippingRate(22, 'XpressBees'),
            $adjustShippingRate(27, 'XpressBees')
        ],
        ['RTO 2 Kgs', 
            $adjustShippingRate(59, 'XpressBees'),
            $adjustShippingRate(69, 'XpressBees'),
            $adjustShippingRate(78, 'XpressBees'),
            $adjustShippingRate(86, 'XpressBees'),
            $adjustShippingRate(99, 'XpressBees')
        ],
        ['RTO Additional 1 Kgs', 
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(19, 'XpressBees'),
            $adjustShippingRate(22, 'XpressBees'),
            $adjustShippingRate(27, 'XpressBees')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '']
    ]
],



'XpressBees - 5 Kg' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 5 Kgs', 
            $adjustShippingRate(95, 'XpressBees'),
            $adjustShippingRate(108, 'XpressBees'),
            $adjustShippingRate(120, 'XpressBees'),
            $adjustShippingRate(142, 'XpressBees'),
            $adjustShippingRate(163, 'XpressBees')
        ],
        ['Additional 1 Kg', 
            $adjustShippingRate(14, 'XpressBees'),
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(19, 'XpressBees'),
            $adjustShippingRate(22, 'XpressBees')
        ],
        ['RTO 5 Kgs', 
            $adjustShippingRate(95, 'XpressBees'),
            $adjustShippingRate(108, 'XpressBees'),
            $adjustShippingRate(120, 'XpressBees'),
            $adjustShippingRate(142, 'XpressBees'),
            $adjustShippingRate(163, 'XpressBees')
        ],
        ['RTO Additional 1 Kg', 
            $adjustShippingRate(14, 'XpressBees'),
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(19, 'XpressBees'),
            $adjustShippingRate(22, 'XpressBees')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '']
    ]
],


'XpressBees - 10 Kg' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 10 Kgs', 
            $adjustShippingRate(154, 'XpressBees'),
            $adjustShippingRate(171, 'XpressBees'),
            $adjustShippingRate(188, 'XpressBees'),
            $adjustShippingRate(209, 'XpressBees'),
            $adjustShippingRate(269, 'XpressBees')
        ],
        ['Additional 1 Kgs', 
            $adjustShippingRate(14, 'XpressBees'),
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(22, 'XpressBees')
        ],
        ['RTO 10 Kgs', 
            $adjustShippingRate(154, 'XpressBees'),
            $adjustShippingRate(171, 'XpressBees'),
            $adjustShippingRate(188, 'XpressBees'),
            $adjustShippingRate(209, 'XpressBees'),
            $adjustShippingRate(269, 'XpressBees')
        ],
        ['RTO Additional 1 Kgs', 
            $adjustShippingRate(14, 'XpressBees'),
            $adjustShippingRate(15, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(17, 'XpressBees'),
            $adjustShippingRate(22, 'XpressBees')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '']
    ]
],


'XpressBees Air - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 500 gms', 
            $adjustShippingRate(24, 'XpressBees Air'),
            $adjustShippingRate(29, 'XpressBees Air'),
            $adjustShippingRate(45, 'XpressBees Air'),
            $adjustShippingRate(48, 'XpressBees Air'),
            $adjustShippingRate(53, 'XpressBees Air')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(15, 'XpressBees Air'),
            $adjustShippingRate(15, 'XpressBees Air'),
            $adjustShippingRate(32, 'XpressBees Air'),
            $adjustShippingRate(37, 'XpressBees Air'),
            $adjustShippingRate(44, 'XpressBees Air')
        ],
        ['RTO 500 gms', 
            $adjustShippingRate(24, 'XpressBees Air'),
            $adjustShippingRate(29, 'XpressBees Air'),
            $adjustShippingRate(45, 'XpressBees Air'),
            $adjustShippingRate(48, 'XpressBees Air'),
            $adjustShippingRate(53, 'XpressBees Air')
        ],
        ['RTO Additional 500 gms', 
            $adjustShippingRate(15, 'XpressBees Air'),
            $adjustShippingRate(15, 'XpressBees Air'),
            $adjustShippingRate(32, 'XpressBees Air'),
            $adjustShippingRate(37, 'XpressBees Air'),
            $adjustShippingRate(44, 'XpressBees Air')
        ],
        // ['COD Charges', '42 or 2%', '', '', '', '']
    ]
],




'Delhivery - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
    'rows' => [
        ['Upto 500 gms', 
            $adjustShippingRate(28.6, 'Delhivery'),
            $adjustShippingRate(31.9, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(38.5, 'Delhivery'),
            $adjustShippingRate(55.0, 'Delhivery'),
            $adjustShippingRate(60.5, 'Delhivery')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(25.3, 'Delhivery'),
            $adjustShippingRate(28.6, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(53.9, 'Delhivery'),
            $adjustShippingRate(59.4, 'Delhivery')
        ],
        ['RTO 500 gms', 
            $adjustShippingRate(28.6, 'Delhivery'),
            $adjustShippingRate(31.9, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(38.5, 'Delhivery'),
            $adjustShippingRate(55.0, 'Delhivery'),
            $adjustShippingRate(60.5, 'Delhivery')
        ],
        ['RTO Additional 500 gms', 
            $adjustShippingRate(25.3, 'Delhivery'),
            $adjustShippingRate(28.6, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(37.4, 'Delhivery'),
            $adjustShippingRate(53.9, 'Delhivery'),
            $adjustShippingRate(59.4, 'Delhivery')
        ],
    ]
],



'Delhivery Air - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E', 'Zone F'],
    'rows' => [
        ['Upto 500 gms', 
            $adjustShippingRate(30.8, 'Delhivery Air'),
            $adjustShippingRate(35.2, 'Delhivery Air'),
            $adjustShippingRate(52.8, 'Delhivery Air'),
            $adjustShippingRate(56.1, 'Delhivery Air'),
            $adjustShippingRate(67.1, 'Delhivery Air'),
            $adjustShippingRate(73.7, 'Delhivery Air')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(27.5, 'Delhivery Air'),
            $adjustShippingRate(30.8, 'Delhivery Air'),
            $adjustShippingRate(49.5, 'Delhivery Air'),
            $adjustShippingRate(55.0, 'Delhivery Air'),
            $adjustShippingRate(63.8, 'Delhivery Air'),
            $adjustShippingRate(70.4, 'Delhivery Air')
        ],
        ['RTO 500 gms', 
            $adjustShippingRate(30.8, 'Delhivery Air'),
            $adjustShippingRate(35.2, 'Delhivery Air'),
            $adjustShippingRate(52.8, 'Delhivery Air'),
            $adjustShippingRate(56.1, 'Delhivery Air'),
            $adjustShippingRate(67.1, 'Delhivery Air'),
            $adjustShippingRate(73.7, 'Delhivery Air')
        ],
        ['RTO Additional 500 gms', 
            $adjustShippingRate(27.5, 'Delhivery Air'),
            $adjustShippingRate(30.8, 'Delhivery Air'),
            $adjustShippingRate(49.5, 'Delhivery Air'),
            $adjustShippingRate(55.0, 'Delhivery Air'),
            $adjustShippingRate(63.8, 'Delhivery Air'),
            $adjustShippingRate(70.4, 'Delhivery Air')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '', '']
    ]
],



'Blue Dart Surface - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 500 gms', 
            $adjustShippingRate(28, 'Blue Dart'),
            $adjustShippingRate(32, 'Blue Dart'),
            $adjustShippingRate(42, 'Blue Dart'),
            $adjustShippingRate(44, 'Blue Dart'),
            $adjustShippingRate(61, 'Blue Dart')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(28, 'Blue Dart'),
            $adjustShippingRate(32, 'Blue Dart'),
            $adjustShippingRate(42, 'Blue Dart'),
            $adjustShippingRate(44, 'Blue Dart'),
            $adjustShippingRate(61, 'Blue Dart')
        ],
        ['RTO 500 gms', 
            $adjustShippingRate(28, 'Blue Dart'),
            $adjustShippingRate(30, 'Blue Dart'),
            $adjustShippingRate(40, 'Blue Dart'),
            $adjustShippingRate(44, 'Blue Dart'),
            $adjustShippingRate(61, 'Blue Dart')
        ],
        ['RTO Additional 500 gms', 
            $adjustShippingRate(26, 'Blue Dart'),
            $adjustShippingRate(30, 'Blue Dart'),
            $adjustShippingRate(36, 'Blue Dart'),
            $adjustShippingRate(44, 'Blue Dart'),
            $adjustShippingRate(61, 'Blue Dart')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '']
    ]
],



'DTDC Surface - 500 gms' => [
    'headers' => ['Slab', 'Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    'rows' => [
        ['Upto 500 gms', 
            $adjustShippingRate(28, 'DTDC'),
            $adjustShippingRate(32, 'DTDC'),
            $adjustShippingRate(38, 'DTDC'),
            $adjustShippingRate(38, 'DTDC'),
            $adjustShippingRate(50, 'DTDC')
        ],
        ['Additional 500 gms', 
            $adjustShippingRate(24, 'DTDC'),
            $adjustShippingRate(28, 'DTDC'),
            $adjustShippingRate(30, 'DTDC'),
            $adjustShippingRate(32, 'DTDC'),
            $adjustShippingRate(45, 'DTDC')
        ],
        ['RTO 500 gms', 
            $adjustShippingRate(28, 'DTDC'),
            $adjustShippingRate(32, 'DTDC'),
            $adjustShippingRate(38, 'DTDC'),
            $adjustShippingRate(38, 'DTDC'),
            $adjustShippingRate(50, 'DTDC')
        ],
        ['RTO Additional 500 gms', 
            $adjustShippingRate(24, 'DTDC'),
            $adjustShippingRate(28, 'DTDC'),
            $adjustShippingRate(30, 'DTDC'),
            $adjustShippingRate(32, 'DTDC'),
            $adjustShippingRate(45, 'DTDC')
        ],
        // ['COD Charges', '32 or 2%', '', '', '', '']
    ]
],


        // [Continue for all other couriers...]
    ];

    return view('sellerdashboard.tools.shipmentprice', [
        'priceSettingsXpressBees_cod_charge' => $priceSettingsXpressBees_cod_charge,
        'priceSettingsXpressBees_cod_charge_parsent' => $priceSettingsXpressBees_cod_charge_parsent,

        'seller' => $seller,
        'totalAmount' => $totalAmount,
        'pdfLinks' => $pdfLinks,
        'rateCard' => $rateCard,
        'rateData' => $rateData,
        'priceSettings' => $priceSettings
    ]);
}













    public function activitylogs()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.tools.activitylogs', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }

    public function couriermanage()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.tools.couriermanage', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }

    public function reports()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.tools.reports', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }
    public function trackorder()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.tools.trackorder', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }

    public function weightdiscrepancy()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.tools.weightdiscrepancy', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }

    public function shipmentpricelist()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.tools.shipmentpricelist', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }

    public function activitylog()
    {
        $seller = Auth::guard('seller')->user();

        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $totalAmount = Recharge::where('seller_id', $seller->id)->sum('amount');
        }
        return view('sellerdashboard.tools.shipmentpricelist', [
            'seller' => $seller,
            'totalAmount' => $totalAmount
        ]);
    }



}
