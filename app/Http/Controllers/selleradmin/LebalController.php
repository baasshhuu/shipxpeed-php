<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Models\LabelSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Recharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\LogisticProvider;
use App\Models\Order;
use App\Models\PriceSetting;

use Illuminate\Support\Facades\Http;
class LebalController extends Controller
{
  
    

    public function index()
    {

         $seller = Auth::guard('seller')->user();
        $sellerId = Auth::guard('seller')->id();



        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            // $totalAmount = Recharge::where(['seller_id' => $seller->id,'status' => '1'])->sum('amount'); 
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;

        }
        // echo 'asasc';die;
        $sellerId = auth()->guard('seller')->id();
        $settings = LabelSetting::where('seller_id', $sellerId)->first();

        return view('sellerdashboard.custom-label', compact('settings', 'totalAmount', 'seller'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'label_type' => 'required|in:standard,thermal',
        ]);

        $sellerId = auth()->guard('seller')->id();
        
        // Find existing settings or create new
        $settings = LabelSetting::where('seller_id', $sellerId)->first();
        if (!$settings) {
            $settings = new LabelSetting();
            $settings->seller_id = $sellerId;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Create seller-logos directory in public folder if it doesn't exist
            $uploadPath = public_path('seller-logos');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete old logo if exists
            if ($settings->logo_path && file_exists(public_path($settings->logo_path))) {
                unlink(public_path($settings->logo_path));
            }
            
            // Generate unique filename
            $file = $request->file('logo');
            $fileName = time() . '_' . $sellerId . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Move file to public/seller-logos directory
            $file->move($uploadPath, $fileName);
            
            // Save relative path (without public_path)
            $settings->logo_path = 'seller-logos/' . $fileName;
        }

        // Update boolean fields (checkboxes)
        $settings->show_logo = $request->has('show_logo') ? 1 : 0;
        $settings->show_support_contact = $request->has('show_support_contact') ? 1 : 0;
        $settings->hide_prepaid_amount = $request->has('hide_prepaid_amount') ? 1 : 0;
        $settings->hide_customer_mobile = $request->has('hide_customer_mobile') ? 1 : 0;
        $settings->hide_gst_number = $request->has('hide_gst_number') ? 1 : 0;
        $settings->hide_return_address_line_1 = $request->has('hide_return_address_line_1') ? 1 : 0;
        $settings->hide_return_address_line_2 = $request->has('hide_return_address_line_2') ? 1 : 0;
        $settings->hide_return_city_state_pincode = $request->has('hide_return_city_state_pincode') ? 1 : 0;
        $settings->hide_return_mobile_number = $request->has('hide_return_mobile_number') ? 1 : 0;
        $settings->hide_return_contact_name = $request->has('hide_return_contact_name') ? 1 : 0;
        $settings->hide_sku = $request->has('hide_sku') ? 1 : 0;
        $settings->hide_product = $request->has('hide_product') ? 1 : 0;
        $settings->hide_discount = $request->has('hide_discount') ? 1 : 0;
        $settings->hide_qty = $request->has('hide_qty') ? 1 : 0;
        $settings->hide_amount = $request->has('hide_amount') ? 1 : 0;
        
        // Update label type and size
        $settings->label_type = $request->label_type;
        $settings->label_size = $request->label_type == 'thermal' ? '4x6' : '8x11';

        $settings->save();

        return redirect()->route('seller.custom-label.index')
                        ->with('success', 'Label settings updated successfully!');
    }
}