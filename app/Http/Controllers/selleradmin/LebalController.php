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

        $cfg = \App\Support\Label\LabelRenderer::config($settings);

        return view('sellerdashboard.custom-label', compact('settings', 'cfg', 'totalAmount', 'seller'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo'              => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'label_type'        => 'required|in:standard,thermal',
            'product_name_trim' => 'nullable|integer|min:10|max:250',
            'sku_trim'          => 'nullable|integer|min:4|max:64',
            'max_products'      => 'nullable|integer|min:1|max:20',
            'tax_rate'          => 'nullable|numeric|min:0|max:28',
        ]);

        $sellerId = auth()->guard('seller')->id();
        $settings = LabelSetting::firstOrNew(['seller_id' => $sellerId]);

        if ($request->hasFile('logo')) {
            $uploadPath = public_path('seller-logos');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if ($settings->logo_path && is_file(public_path($settings->logo_path))) {
                @unlink(public_path($settings->logo_path));
            }
            $file = $request->file('logo');
            $fileName = time() . '_' . $sellerId . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $file->move($uploadPath, $fileName);
            $settings->logo_path = 'seller-logos/' . $fileName;
        }
        if ($request->boolean('remove_logo') && $settings->logo_path) {
            @unlink(public_path($settings->logo_path));
            $settings->logo_path = null;
        }

        $cfg = [];
        foreach (\App\Support\Label\LabelRenderer::DEFAULTS as $k => $default) {
            if (is_bool($default)) {
                $cfg[$k] = $request->boolean($k);
            } elseif ($request->filled($k)) {
                $cfg[$k] = is_int($default) ? (int) $request->input($k) : $request->input($k);
            } else {
                $cfg[$k] = $default;
            }
        }
        $cfg['label_type'] = $request->label_type;
        $cfg = \App\Support\Label\LabelRenderer::config(['config' => $cfg]);

        // keep legacy columns in sync
        $settings->label_type           = $cfg['label_type'];
        $settings->label_size           = $cfg['label_type'] === 'thermal' ? '4x6' : '8x11';
        $settings->show_logo            = $cfg['show_logo'] ? 1 : 0;
        $settings->hide_customer_mobile = $cfg['show_customer_mobile'] ? 0 : 1;
        $settings->hide_prepaid_amount  = $cfg['show_invoice_value_prepaid'] ? 0 : 1;
        $settings->hide_sku             = $cfg['show_sku'] ? 0 : 1;
        $settings->hide_product         = $cfg['show_product_name'] ? 0 : 1;
        $settings->hide_qty             = $cfg['show_qty'] ? 0 : 1;
        $settings->hide_amount          = $cfg['show_item_price'] ? 0 : 1;
        $settings->show_support_contact = $cfg['show_support_contact'] ? 1 : 0;
        if (\Illuminate\Support\Facades\Schema::hasColumn('label_settings', 'config')) {
            $settings->config = json_encode($cfg);
        }
        $settings->save();

        return redirect()->route('seller.custom-label.index')->with('success', 'Label settings saved.');
    }
}
