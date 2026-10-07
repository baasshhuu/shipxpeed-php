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

class SeloshipController extends Controller
{
    // Supported courier providers
    const COURIER_PROVIDERS = [
        'seloship' => 'Seloship'
    ];
    
    /**
     * Handle Seloship webhook for order status updates
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleSeloshipWebhook(Request $request)
    {
        // echo 'dxsxs';die;
        // dd($request->all());
        try {
            $courierProvider = 'seloship';
            Log::info('Seloship Webhook Received:', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'raw_content' => $request->getContent()
            ]);
            
            $webhookData = $request->all();
            
            // Validate webhook data is not empty
            if (empty($webhookData)) {
                Log::error('Empty webhook data received', [
                    'courier_provider' => $courierProvider,
                    'raw_content' => $request->getContent()
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Empty webhook data received'
                ], 400);
            }
            
            // Check if waybillDetails exists in the webhook
            if (!isset($webhookData['waybillDetails'])) {
                Log::error('waybillDetails not found in Seloship webhook', [
                    'courier_provider' => $courierProvider,
                    'webhook_data' => $webhookData
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'waybillDetails not found in webhook data'
                ], 400);
            }

            // Extract data from Seloship webhook
            $extractedData = $this->extractSeloshipData($webhookData);
            
            // Log extracted data for debugging
            Log::info('Extracted Seloship webhook data:', [
                'courier_provider' => $courierProvider,
                'extracted_data' => $extractedData
            ]);
            
            if (!$extractedData['awb']) {
                return response()->json([
                    'status' => false,
                    'message' => 'AWB number not found in webhook data'
                ], 400);
            }
            
            // Find order by AWB number
            $order = Order::where('awb_number', $extractedData['awb'])->first();
            
            if (!$order) {
                Log::warning("Order not found for AWB: {$extractedData['awb']}");
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found for AWB: ' . $extractedData['awb']
                ], 404);
            }
            
            // Update order status in database
            $this->updateOrderStatus($order, $extractedData, $courierProvider, $webhookData);
            
            // Find seller and forward webhook to seller's URL
            $this->forwardToSellerWebhook($order, $webhookData, $courierProvider);
            
            return response()->json([
                'status' => true,
                'message' => 'Webhook processed successfully',
                'courier_provider' => $courierProvider,
                'awb' => $extractedData['awb'],
                'order_id' => $order->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Seloship Webhook Error: ' . $e->getMessage(), [
                'courier_provider' => 'seloship',
                'request_headers' => $request->headers->all(),
                'request_body' => $request->all(),
                'raw_content' => $request->getContent(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return more specific error for debugging
            return response()->json([
                'status' => false,
                'message' => 'Webhook processing failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'courier_provider' => 'seloship'
            ], 500);
        }
    }
    
    /**
     * Extract data from Seloship webhook
     * 
     * @param array $webhookData
     * @return array
     */
    private function extractSeloshipData($webhookData)
    {
        // Extract data from waybillDetails structure
        $waybillDetails = $webhookData['waybillDetails'] ?? [];
        
        return [
            'awb' => $waybillDetails['waybill'] ?? $webhookData['awb'] ?? $webhookData['tracking_number'] ?? null,
            'status' => $waybillDetails['currentStatus'] ?? $webhookData['status'] ?? null,
            'scan_type' => $webhookData['Status'] ?? null,
            'remarks' => $webhookData['message'] ?? $webhookData['remarks'] ?? '',
            'courier_name' => 'Seloship',
            'location' => $waybillDetails['location'] ?? '',
            'timestamp' => $waybillDetails['statusDate'] ?? now()->toISOString(),
            'client_order_id' => $webhookData['order_id'] ?? null
        ];
    }
    
    /**
     * Update order status in database
     * 
     * @param Order $order
     * @param array $extractedData
     * @param string $courierProvider
     * @param array $webhookData
     */
    private function updateOrderStatus($order, $extractedData, $courierProvider, $webhookData)
    {
        try {
            Log::info('Updating Seloship order status:', [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'extracted_data' => $extractedData,
                'courier_provider' => $courierProvider
            ]);
            
            $updateData = [
                'updated_at' => now(),
            ];

        
      
            
            // Map Seloship status to internal status
            $mappedStatus = $this->mapSeloshipStatus($extractedData['status'], $extractedData['scan_type']);

            if ($mappedStatus) {
                $updateData['shipping_status'] = $mappedStatus;
                
                // Set delivered_date when status is delivered
                if ($mappedStatus === 'delivered') {
                    $updateData['delivered_date'] = now();
                }
            }
            Log::info('Seloship order update data prepared:', ['update_data' => $updateData]);
            
            // Update the order
            // $order->update($updateData);
            
            // Insert tracking history
            // $this->insertTrackingHistory($order, $extractedData, $courierProvider, $webhookData);
            
            Log::info("Seloship order status updated successfully for AWB: {$order->awb}", [
                'order_id' => $order->id,
                'courier_provider' => $courierProvider,
                'status' => $extractedData['status'],
                'scan_type' => $extractedData['scan_type']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating Seloship order status: ' . $e->getMessage(), [
                'order_id' => $order->id ?? 'unknown',
                'awb' => $order->awb ?? 'unknown',
                'courier_provider' => $courierProvider,
                'extracted_data' => $extractedData,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Insert tracking history record
     * 
     * @param Order $order
     * @param array $extractedData
     * @param string $courierProvider
     * @param array $webhookData
     */
    private function insertTrackingHistory($order, $extractedData, $courierProvider, $webhookData)
    {
        try {
            Log::info('Inserting Seloship tracking history:', [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'courier_provider' => $courierProvider
            ]);
            
            $insertData = [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'status' => $extractedData['status'] ?? null,
                'scan_type' => $extractedData['scan_type'] ?? null,
                'remarks' => $extractedData['remarks'] ?? '',
                'courier_name' => $extractedData['courier_name'] ?? 'Seloship',
                'courier_provider' => $courierProvider,
                'location' => $extractedData['location'] ?? '',
                'webhook_data' => json_encode($webhookData),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Check if tracking_histories table exists and handle accordingly
            if (DB::getSchemaBuilder()->hasTable('tracking_histories')) {
                DB::table('tracking_histories')->insert($insertData);
                Log::info('Seloship tracking history inserted successfully');
            } else {
                Log::warning('tracking_histories table does not exist, skipping insert');
            }
            
        } catch (\Exception $e) {
            Log::error('Error inserting Seloship tracking history: ' . $e->getMessage(), [
                'order_id' => $order->id ?? 'unknown',
                'awb' => $order->awb ?? 'unknown',
                'courier_provider' => $courierProvider,
                'insert_data' => $insertData ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Map Seloship status to internal delivery status
     * 
     * @param string|null $status
     * @param string|null $scanType
     * @return string|null
     */





    private function mapSeloshipStatus($status, $scanType)
    {
        if (!$status) return null;
        
        $statusLower = strtolower($status);
        
        // Map Seloship status codes and titles
        $statusMap = [
            'delivered' => 'delivered',
            'out for delivery' => 'out_for_delivery',
            'out_for_delivery' => 'out_for_delivery',
            'in transit' => 'in_transit',
            'in_transit' => 'in_transit',
            'picked up' => 'picked_up',
            'picked_up' => 'picked_up',
            'pickup' => 'picked_up',
            'manifested' => 'manifested',
            'manifest' => 'manifested',
            'return to origin initiated' => 'rto_initiated',
            'rto_initiated' => 'rto_initiated',
            'return to origin delivered' => 'rto_delivered',
            'rto_delivered' => 'rto_delivered',
            'cancelled' => 'cancelled',
            'lost' => 'lost',
            'damaged' => 'damaged',
            'undelivered' => 'undelivered'
        ];
        
        return $statusMap[$statusLower] ?? $status;
    }
    
    /**
     * Forward webhook data to seller's webhook URL
     * 
     * @param Order $order
     * @param array $webhookData
     * @param string $courierProvider
     */
    private function forwardToSellerWebhook($order, $webhookData, $courierProvider = 'seloship')
    {
        try {
            // Get seller information
            $seller = SellerList::find($order->seller_id);
            
            if (!$seller) {
                Log::info("Seller not found for seller ID: {$order->seller_id}");
                return;
            }
            
            // Get webhook URL from Webhook table
            $webhook = DB::table('webhooks')
                ->where('seller_id', $order->seller_id)
                ->where('status', 1)
                ->first();
            
            if (!$webhook || !$webhook->webhook_url) {
                Log::info("No active webhook URL found for seller ID: {$order->seller_id}");
                return;
            }
            
            // Prepare data to send to seller
            $waybillDetails = $webhookData['waybillDetails'] ?? [];
            $sellerWebhookData = [
                'awb' => $order->awb_number,
                'order_id' => $order->order_id ?? $order->id,
                'status' => $waybillDetails['currentStatus'] ?? $webhookData['Status'] ?? '',
                'scan_type' => $webhookData['Status'] ?? null,
                'remarks' => $webhookData['message'] ?? $webhookData['remarks'] ?? '',
                'timestamp' => $waybillDetails['statusDate'] ?? null,
                'courier_provider' => 'Shipxpeed',
                // 'original_webhook_data' => $webhookData
            ];
            
            // Send webhook to seller
            // dd($sellerWebhookData);
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->post($webhook->webhook_url, $sellerWebhookData);
            //  dd($response);
            if ($response->successful()) {
                Log::info("Seloship webhook successfully forwarded to seller", [
                    'seller_id' => $seller->id,
                    'courier_provider' => $courierProvider,
                    'webhook_url' => $webhook->webhook_url,
                    'awb' => $order->awb
                ]);
            } else {
                Log::error("Failed to forward Seloship webhook to seller", [
                    'seller_id' => $seller->id,
                    'courier_provider' => $courierProvider,
                    'webhook_url' => $webhook->webhook_url,
                    'awb' => $order->awb,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            // dd($e->getMessage());
            Log::error('Error forwarding Seloship webhook to seller: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'seller_id' => $order->seller_id,
                'courier_provider' => $courierProvider
            ]);
        }
    }
}