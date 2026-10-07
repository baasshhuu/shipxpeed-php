<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Models\SellerList;

class StatusupdateController extends Controller
{
    /**
     * @var Order
     */
    protected $order;



public function getOrderspage()
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
        return view('admin/Status/Statusupdate', compact(
            'SellerList',
            'selectedSeller',
            'existingPrices'
        ));
    }


    public function getOrders(Request $request)
    {     
        // If the endpoint is opened directly in the browser (not an AJAX/JSON request),
        // redirect the user to the form page where they can select a seller.
        if (! $request->ajax() && ! $request->expectsJson()) {
            return Redirect::route('get.orders.page');
        }

        try {
            $sellerId = $request->get('seller_id');
            
            if (!$sellerId) {
                // If the endpoint is visited directly (no seller selected yet),
                // return an empty orders array with 200 so the UI can handle it
                // gracefully instead of showing a 400 error.
                return response()->json(['orders' => [], 'message' => 'No seller selected'], 200);
            }

            $orders = Order::where('seller_id', $sellerId)
                        ->where('admin_status', 'delivered')
                        ->select('id', 'order_number', 'order_amount', 'created_at')
                        ->get();
            
            return response()->json([
                'orders' => $orders,
                'message' => $orders->count() > 0 ? 'Orders found successfully' : 'No orders found'
            ]);
        } catch (\Exception $e) {
            Log::error('Order retrieval failed: ' . $e->getMessage());
            return response()->json(['orders' => [], 'message' => 'An error occurred while fetching orders'], 500);
        }
    }

    public function updateStatusall(Request $request): RedirectResponse
    {
        $orderIds = $request->get('order_ids', []);
        
        if (empty($orderIds)) {
            return redirect()->back()->with('error', 'Please select at least one order');
        }

        DB::beginTransaction();
        try {
            Order::whereIn('id', $orderIds)
                 ->update([
                     'shipping_status' => 'delivered',
                     'admin_status' => null
                 ]);

            DB::commit();
            return redirect()->back()->with('success', 'Orders updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Order update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update orders');
        }
    }
}