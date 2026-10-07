<?php

namespace App\Http\Controllers\Shipxpeedapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\PriceSetting;
use App\Models\SellerList;
use App\Models\LogisticProvider;
use App\Models\Order;
use App\Models\Recharge;
use Illuminate\Support\Facades\Log;

class TestwebhookController extends Controller
{
    // Supported courier providers


        public function getAllwebhookstatus(Request $request)
    {
        try {
            // Get request data
            $webhookData = $request->all();
            
            // Get AWB from request
            $awb = $request->awb;
            
            if (!$awb) {
                return response()->json([
                    'status' => false,
                    'message' => 'AWB number not found in request'
                ], 400);
            }
            
            // Find order by AWB
            $order = Order::where('awb_number', $awb)->first();
            
            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found for AWB: ' . $awb
                ], 404);
            }

            // Prepare response data (same as forwardToSellerWebhook)
            $sellerWebhookData = [
                'awb' => $order->awb_number,
                'order_id' => $order->order_id ?? $order->id,
                'status' => $webhookData['status_title'] ?? $webhookData['status'] ?? $webhookData['delivery_status'] ?? '',
                'scan_type' => $webhookData['status_code'] ?? $webhookData['event_type'] ?? $webhookData['scan_type'] ?? null,
                'remarks' => $webhookData['status_description'] ?? $webhookData['description'] ?? $webhookData['remarks'] ?? '',
                'courier_provider' => 'Shipxpeed',
            ];

            return response()->json($sellerWebhookData);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}