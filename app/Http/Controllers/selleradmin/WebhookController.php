<?php

namespace App\Http\Controllers\selleradmin;

use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\RateCard;
use App\Models\Recharge;
use App\Models\SellerBankDetail;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Order, Buyer, OrderItem, Warehouse, OrderPackageDetail, SellerList};
use App\Models\SellerAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Helper\Helper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class WebhookController extends Controller
{
    /**
     * Display a listing of webhooks
     */
    public function index()
    {
    $seller = Auth::guard('seller')->user();
        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
                }


        $seller_id = Auth::guard('seller')->id();
        $webhooks = Webhook::where('seller_id', $seller_id)
                          ->orderBy('created_at', 'desc')
                          ->paginate(10);
        
        return view('webhook.index', compact('webhooks', 'totalAmount', 'seller'));
    }

    /**
     * Show the form for creating a new webhook
     */
    public function create()
    {


        $seller = Auth::guard('seller')->user();
        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
                }

        return view('webhook.create', compact('totalAmount', 'seller'));
    }

    /**
     * Store a newly created webhook
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url',
            'status' => 'required|in:active,inactive'
        ]);
//   echo 'xsxsx';die;
        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        if($request->status == 'active'){
     $stat = '1';
        }else{
     $stat = '0';

        };

        try {
   
            $webhook = new Webhook();
            $webhook->seller_id = Auth::guard('seller')->id();
            $webhook->webhook_url = $request->webhook_url;
            $webhook->status = $stat;
            $webhook->save();

            // Session::flash('success', 'Webhook created successfully!');
            return redirect()->route('seller.webhooks.index');
        } catch (Exception $e) {
            // dd($e->getMessage())
            // Log::error('Webhook creation failed: ' . $e->getMessage());
            Session::flash('error', 'Failed to create webhook. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show the form for editing a webhook
     */
    public function edit($id)
    {

          $seller = Auth::guard('seller')->user();
        $totalAmount = 0;
        if ($seller && $seller->status == 1) {
            $sellerRechargeAmount = Recharge::where('seller_id', $seller->id)
                ->where('status', 1)
                ->where('type', 'Credit')
                ->sum('amount');

            $sellerUsedAmount = Recharge::where('seller_id', $seller->id)
                ->where('type', 'Debit')
                ->sum('amount');

            $totalAmount = $sellerRechargeAmount - $sellerUsedAmount;
                }
        $seller_id = Auth::guard('seller')->id();
        $webhook = Webhook::where('seller_id', $seller_id)
                         ->where('id', $id)
                         ->firstOrFail();
        
        return view('webhook.edit', compact('webhook', 'totalAmount', 'seller'));
    }

    /**
     * Update the specified webhook
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }
  if($request->status == 'active'){
     $stat = '1';
        }else{
     $stat = '0';

        };
        try {
            $seller_id = Auth::guard('seller')->id();
            $webhook = Webhook::where('seller_id', $seller_id)
                             ->where('id', $id)
                             ->firstOrFail();

            $webhook->webhook_url = $request->webhook_url;
            $webhook->status = $stat;
            $webhook->save();

            Session::flash('success', 'Webhook updated successfully!');
            return redirect()->route('seller.webhooks.index');
        } catch (Exception $e) {
            Log::error('Webhook update failed: ' . $e->getMessage());
            Session::flash('error', 'Failed to update webhook. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete a webhook
     */
    public function destroy($id)
    {
        try {
            $seller_id = Auth::guard('seller')->id();
            $webhook = Webhook::where('seller_id', $seller_id)
                             ->where('id', $id)
                             ->firstOrFail();

            $webhook->delete();

            Session::flash('success', 'Webhook deleted successfully!');
            return redirect()->route('seller.webhooks.index');
        } catch (Exception $e) {
            Log::error('Webhook deletion failed: ' . $e->getMessage());
            Session::flash('error', 'Failed to delete webhook. Please try again.');
            return redirect()->back();
        }
    }

    /**
     * Test a webhook URL
     */
    public function test($id)
    {
        try {
            $seller_id = Auth::guard('seller')->id();
            $webhook = Webhook::where('seller_id', $seller_id)
                             ->where('id', $id)
                             ->firstOrFail();

            // Sample test data
            $testData = [
                'event' => 'test',
                'timestamp' => now()->toISOString(),
                'seller_id' => $seller_id,
                'data' => [
                    'message' => 'This is a test webhook call',
                    'status' => 'success'
                ]
            ];

            $response = Http::timeout(30)
                           ->withHeaders([
                               'Content-Type' => 'application/json',
                               'X-Webhook-Source' => 'Seller-Admin'
                           ])
                           ->post($webhook->webhook_url, $testData);

            if ($response->successful()) {
                Session::flash('success', 'Webhook test successful! Response code: ' . $response->status());
            } else {
                Session::flash('error', 'Webhook test failed! Response code: ' . $response->status());
            }
        } catch (Exception $e) {
            Log::error('Webhook test failed: ' . $e->getMessage());
            Session::flash('error', 'Webhook test failed: ' . $e->getMessage());
        }

        return redirect()->route('seller.webhooks.index');
    }

    /**
     * Send webhook notification for order status updates
     */
    public function sendOrderWebhook($order, $event)
    {
        try {
            $seller_id = $order->seller_id;
            $webhooks = Webhook::where('seller_id', $seller_id)
                              ->where('status', 'active')
                              ->get();

            foreach ($webhooks as $webhook) {
                $data = [
                    'event' => $event,
                    'timestamp' => now()->toISOString(),
                    'order_id' => $order->id,
                    'awb_number' => $order->awb_number,
                    'status' => $order->status,
                    'data' => [
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'delivery_address' => $order->delivery_address,
                        'current_status' => $order->status,
                        'updated_at' => $order->updated_at->toISOString()
                    ]
                ];

                // Send webhook asynchronously
                $this->sendWebhookAsync($webhook->webhook_url, $data);
            }
        } catch (Exception $e) {
            Log::error('Failed to send order webhook: ' . $e->getMessage());
        }
    }

    /**
     * Send webhook asynchronously
     */
    private function sendWebhookAsync($url, $data)
    {
        try {
            Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'Seller-Admin'
                ])
                ->post($url, $data);
        } catch (Exception $e) {
            Log::error('Webhook delivery failed for URL: ' . $url . ' - ' . $e->getMessage());
        }
    }

    /**
     * Toggle webhook status
     */
    public function toggleStatus($id)
    {
        try {
            $seller_id = Auth::guard('seller')->id();
            $webhook = Webhook::where('seller_id', $seller_id)
                             ->where('id', $id)
                             ->firstOrFail();

            $webhook->status = $webhook->status === 'active' ? 'inactive' : 'active';
            $webhook->save();

            Session::flash('success', 'Webhook status updated successfully!');
        } catch (Exception $e) {
            Log::error('Webhook status toggle failed: ' . $e->getMessage());
            Session::flash('error', 'Failed to update webhook status.');
        }

        return redirect()->route('seller.webhooks.index');
    }
}
