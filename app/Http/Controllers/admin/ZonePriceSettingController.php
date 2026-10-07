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
use App\Models\ZonePriceSetting;
use App\Models\SellerList;
use App\Models\LogisticProvider;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ZonePriceImport;
use App\Exports\ZonePriceTemplateExport;

class ZonePriceSettingController extends Controller
{
    public function add()
    {
        $SellerList = SellerList::all();
        // $LogisticProviders = LogisticProvider::all();
        
    // 🔥 Static Logistic Providers Array (NO DB Query)
    $LogisticProviders = [
 
// Delhivery
"Delhivery",
"Delhivery_Air",
// Delhivery
// Shadowfax
"Shadowfax",
// Shadowfax
// selloship
"Ekart2KG_selloship",
// selloship
// DTDC
"DTDC_Surface_500gm",
"DTDC_Surface_1kg",
"DTDC_Air",
// DTDC
// tekipost
'tekipost_Delhivery_5kg', 
'tekipost_Delhivery_10kg', 
'tekipost_Delhivery_1_KG', 
'tekipost_Ekart_2_KG_Fixed', 
'tekipost_Amazon_2_kg', 
'tekipost_Amazon_500_GM',
// tekipost
// parcelx
"Parcel_X_Delhivery",
"Parcel_X_Amazon",
"Parcel_X_Amazon_1KG",
"Parcel_X_Amazon_2KG",
"Parcel_X_Delhivery_250gm",
// parcelx
// Shiprocket
"Shiprocket_Xpressbee",
"Shiprocket_Delhivery",
"Shiprocket_Bluedart",
"shiprocket_Bluedart_1kg",
// Shiprocket
"Ekart500gm",
"Ekart500gm_boxd",
"boxd_bluedart_500gm",
"parcel_x_Xpressbee",
"parcel_x_Shreemaruti",


    ];
        $selectedSeller = null;
        $existingPrices = [];
        
        if(request()->has('seller_id')) {
            $selectedSeller = SellerList::find(request('seller_id'));
            
            if($selectedSeller) {
                // Get existing zone prices for this seller
                $existingPrices = ZonePriceSetting::where('seller_id', $selectedSeller->id)->get();
            }
        }
        
        return view('admin.zonepricesetting.zoneprice_add_new', compact(
            'SellerList',
            'LogisticProviders',
            'selectedSeller',
            'existingPrices'
        ));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'seller_id' => ['required'],
            'zone' => ['required', 'string'],
            'LogisticProvider' => ['required'],
            'cod_price' => ['required', 'numeric', 'min:0'],
            'cod_fix_price' => ['required', 'numeric', 'min:0'],
            'prepaid_price' => ['required', 'numeric', 'min:0'],
            'prepaid_fix_price' => ['required', 'numeric', 'min:0'],
            'cod_charge_parsent' => ['required', 'numeric', 'min:0', 'max:100'],
                        'rto_credit' => ['nullable', 'numeric', 'min:0'],

        ]);
    //   dd($validated);
        // Check if record exists
        $existing = ZonePriceSetting::where('seller_id', $validated['seller_id'])
            ->where('zone', $validated['zone'])
            ->where('LogisticProvider', $validated['LogisticProvider'])
            ->first();

        $data = [
            'seller_id' => $validated['seller_id'],
            'zone' => $validated['zone'],
            'LogisticProvider' => $validated['LogisticProvider'],
            'cod_price' => $validated['cod_price'],
            'cod_fix_price' => $validated['cod_fix_price'],
            'prepaid_price' => $validated['prepaid_price'],
            'prepaid_fix_price' => $validated['prepaid_fix_price'],
            'cod_charge_parsent' => $validated['cod_charge_parsent'],
            'rto_credit' => $validated['rto_credit'],
            'status' => 1,
        ];

        if ($existing) {
            // Update existing record
            $existing->update($data);
            $message = 'Zone price setting updated successfully!';
        } else {
            // Create new record
            ZonePriceSetting::create($data);
            $message = 'Zone price setting added successfully!';
        }

        return to_route('zone.pricesetting.add')->withSuccess($message);
    }

    public function uploadExcel(Request $request)
    {
        $validated = $request->validate([
            'seller_id' => ['required'],
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

     try {
            Excel::import(new ZonePriceImport($validated['seller_id']), $validated['excel_file']);
            return back()->withSuccess('Excel data uploaded successfully!');
        } catch (\Exception $e) {
            return back()->withError('Error uploading file: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $query = ZonePriceSetting::with(['seller']);
        
        // Filter by seller if selected
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }
        
        $zonePrices = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Preserve filter parameters in pagination links
        $zonePrices->appends($request->query());
        
        return view('admin.zonepricesetting.zone_price_index', compact('zonePrices'));
    }

    public function edit($id)
    {
        $zonePriceSetting = ZonePriceSetting::findOrFail($id);
        $SellerList = SellerList::all();
        // $LogisticProviders = LogisticProvider::all();
         $LogisticProviders = [
// Delhivery
"Delhivery",
"Delhivery_Air",
// Delhivery
// Shadowfax
"Shadowfax",
// Shadowfax
// selloship
"Ekart2KG_selloship",
// selloship
// DTDC
"DTDC_Surface_500gm",
"DTDC_Surface_1kg",
"DTDC_Air",
// DTDC
// tekipost
'tekipost_Delhivery_5kg', 
'tekipost_Delhivery_10kg', 
'tekipost_Delhivery_1_KG', 
'tekipost_Ekart_2_KG_Fixed', 
'tekipost_Amazon_2_kg', 
'tekipost_Amazon_500_GM',
// tekipost
// parcelx
"Parcel_X_Delhivery",
"Parcel_X_Amazon",
"Parcel_X_Amazon_1KG",
"Parcel_X_Amazon_2KG",
"Parcel_X_Delhivery_250gm",
// parcelx
// Shiprocket
"Shiprocket_Xpressbee",
"Shiprocket_Delhivery",
"Shiprocket_Bluedart",
"shiprocket_Bluedart_1kg",
// Shiprocket
"Ekart500gm",
'Ekart500gm_boxd',

    ];

        return view('admin.zonepricesetting.zone_price_edit', compact('zonePriceSetting', 'SellerList', 'LogisticProviders'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'seller_id' => ['required'],
            'zone' => ['required', 'string'],
            'LogisticProvider' => ['required'],
            'cod_price' => ['required', 'numeric', 'min:0'],
            'cod_fix_price' => ['required', 'numeric', 'min:0'],
            'prepaid_price' => ['required', 'numeric', 'min:0'],
            'prepaid_fix_price' => ['required', 'numeric', 'min:0'],
            'cod_charge_parsent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $zonePriceSetting = ZonePriceSetting::findOrFail($id);
        $zonePriceSetting->update($validated);

        return to_route('zone.price.index')->withSuccess('Zone price setting updated successfully!');
    }

    public function delete($id)
    {
        $zonePriceSetting = ZonePriceSetting::findOrFail($id);
        $zonePriceSetting->delete();

        return back()->withSuccess('Zone price setting deleted successfully!');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ZonePriceTemplateExport, 'zone_price_template.xlsx');
    }


}
