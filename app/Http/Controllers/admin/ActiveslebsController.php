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
use App\Models\ActicvSleb;


class ActiveslebsController extends Controller
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
                    $prices = ActicvSleb::where('seller_id', $selectedSeller->id)->get();
                    
                    foreach($prices as $price) {
                        $existingPrices[$price->LogisticProvider] = [
                            // 'shipping_charge' => $price->shipping_charge,
                            // 'cod_charge' => $price->cod_charge,
                            // 'cod_charge_percent' => $price->cod_charge_parsent,
                            // 'fixed_courier_price' => $price->fixed_courier_price,
                            'status' => $price->status ?? 1
                        ];
                    }
                }
            }
            // dd($existingPrices);
            return view('admin.Activeslebs.index', compact(
                'SellerList',
                'selectedSeller',
                'existingPrices'
            ));
        }



public function updateStatus(Request $request)
{
    $request->validate([
        'seller_id' => 'required',
        'courier'   => 'required|string',
        'status'    => 'required|in:0,1'
    ]);

    $status = (string)$request->status; // convert to string for ENUM

    // Handle bulk update for all sellers
    if ($request->seller_id === 'all') {
        $allSellers = SellerList::all();
        
        foreach ($allSellers as $seller) {
            $existing = ActicvSleb::where('seller_id', $seller->id)
                ->where('LogisticProvider', $request->courier)
                ->first();

            if ($existing) {
                $existing->update([
                    'status' => $status
                ]);
            } else {
                ActicvSleb::create([
                    'seller_id'            => $seller->id,
                    'LogisticProvider'     => $request->courier,
                    'status'               => $status,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Courier status updated for all sellers successfully'
        ]);
    }

    // Handle single seller update
    $existing = ActicvSleb::where('seller_id', $request->seller_id)
        ->where('LogisticProvider', $request->courier)
        ->first();

    if ($existing) {
        $existing->update([
            'status' => $status
        ]);
    } else {
        ActicvSleb::create([
            'seller_id'            => $request->seller_id,
            'LogisticProvider'     => $request->courier,
            'status'               => $status,
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Courier status updated successfully'
    ]);
}



}
