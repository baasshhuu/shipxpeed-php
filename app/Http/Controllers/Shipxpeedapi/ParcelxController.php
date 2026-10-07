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

class ParcelxController extends Controller
{
    // Supported courier providers
    const COURIER_PROVIDERS = [
        'parcelx' => 'ParcelX'
    ];
    
    /**
     * Handle ParcelX webhook for order status updates
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleParcelXWebhook(Request $request)
    {
        try {
            $courierProvider = 'parcelx';
            
            Log::info('ParcelX Webhook Received:', [
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
            
            // Extract data from ParcelX webhook
            $extractedData = $this->extractParcelXData($webhookData);
            
            // Log extracted data for debugging
            Log::info('Extracted ParcelX webhook data:', [
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
            Log::error('ParcelX Webhook Error: ' . $e->getMessage(), [
                'courier_provider' => 'parcelx',
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
                'courier_provider' => 'parcelx'
            ], 500);
        }
    }
    
    /**
     * Extract data from ParcelX webhook
     * 
     * @param array $webhookData
     * @return array
     */
    private function extractParcelXData($webhookData)
    {
        return [
            'awb' => $webhookData['awb'] ?? $webhookData['waybill'] ?? $webhookData['tracking_id'] ?? null,
            'status' => $webhookData['status_title'] ?? $webhookData['delivery_status'] ?? $webhookData['status'] ?? null,
            'scan_type' => $webhookData['status_code'] ?? $webhookData['event_type'] ?? $webhookData['scan_type'] ?? null,
            'remarks' => $webhookData['status_description'] ?? $webhookData['description'] ?? $webhookData['remarks'] ?? '',
            'courier_name' => $webhookData['courier_partner'] ?? 'ParcelX',
            'location' => $webhookData['status_location'] ?? $webhookData['location'] ?? $webhookData['city'] ?? '',
            'timestamp' => $webhookData['event_date'] ?? $webhookData['event_time'] ?? $webhookData['timestamp'] ?? now()->toISOString(),
            'client_order_id' => $webhookData['client_order_id'] ?? null
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
            Log::info('Updating ParcelX order status:', [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'extracted_data' => $extractedData,
                'courier_provider' => $courierProvider
            ]);
            
            $updateData = [
                'updated_at' => now(),
                // 'courier_provider' => $courierProvider
            ];
            
            // if (!empty($extractedData['status'])) {
            //     // $updateData['status'] = $extractedData['status'];
            //     $updateData['last_status'] = $extractedData['status'];
            // }
            
            // if (!empty($extractedData['scan_type'])) {
            //     $updateData['scan_type'] = $extractedData['scan_type'];
            // }
            
            // if (!empty($extractedData['remarks'])) {
            //     $updateData['remarks'] = $extractedData['remarks'];
            // }
            
            // Map ParcelX status to internal status
            $mappedStatus = $this->mapParcelXStatus($extractedData['status'], $extractedData['scan_type']);
            // dd($mappedStatus);
            if ($mappedStatus) {
                $updateData['shipping_status'] = $mappedStatus;
                
                // Set delivered_date when status is delivered
                if ($mappedStatus === 'delivered') {
                    $updateData['delivered_date'] = now();
                }
            }
            
            Log::info('ParcelX order update data prepared:', ['update_data' => $updateData]);
            
            // Update the order
            // $order->update($updateData);
            
            // Insert tracking history
            // $this->insertTrackingHistory($order, $extractedData, $courierProvider, $webhookData);
            
            Log::info("ParcelX order status updated successfully for AWB: {$order->awb}", [
                'order_id' => $order->id,
                'courier_provider' => $courierProvider,
                'status' => $extractedData['status'],
                'scan_type' => $extractedData['scan_type']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating ParcelX order status: ' . $e->getMessage(), [
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
            Log::info('Inserting ParcelX tracking history:', [
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
                'courier_name' => $extractedData['courier_name'] ?? 'ParcelX',
                'courier_provider' => $courierProvider,
                'location' => $extractedData['location'] ?? '',
                'webhook_data' => json_encode($webhookData),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Check if tracking_histories table exists and handle accordingly
            if (DB::getSchemaBuilder()->hasTable('tracking_histories')) {
                DB::table('tracking_histories')->insert($insertData);
                Log::info('ParcelX tracking history inserted successfully');
            } else {
                Log::warning('tracking_histories table does not exist, skipping insert');
            }
            
        } catch (\Exception $e) {
            Log::error('Error inserting ParcelX tracking history: ' . $e->getMessage(), [
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
     * Map ParcelX status to internal delivery status
     * 
     * @param string|null $status
     * @param string|null $scanType
     * @return string|null
     */
    private function mapParcelXStatus($status, $scanType)
    {
        if (!$status) return null;
        
        $statusLower = strtolower($status);
        
        // Map ParcelX status codes and titles
        $statusMap = [
            'delivered' => 'delivered',
            'out_for_delivery' => 'out_for_delivery',
            'out for delivery' => 'out_for_delivery',
            'in_transit' => 'in_transit',
            'in transit' => 'in_transit',
            'picked' => 'picked_up',
            'picked_up' => 'picked_up',
            'pickup' => 'picked_up',
            'manifested' => 'manifested',
            'manifest' => 'manifested',
            'rto_initiated' => 'rto_initiated',
            'return_to_origin' => 'rto_initiated',
            'rto_delivered' => 'rto_delivered',
            'cancelled' => 'cancelled',
            'lost' => 'lost',
            'damaged' => 'damaged',
            'undelivered' => 'undelivered',
            'not picked' => 'manifested'
        ];
        
        // If status found in mapping, return mapped value, otherwise return original status
        return $statusMap[$statusLower] ?? $status;
    }
    
    /**
     * Forward webhook data to seller's webhook URL
     * 
     * @param Order $order
     * @param array $webhookData
     * @param string $courierProvider
     */
    private function forwardToSellerWebhook($order, $webhookData, $courierProvider = 'parcelx')
    {
        // echo 'cscs';die;
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
            $sellerWebhookData = [
                'awb' => $order->awb_number,
                'order_id' => $order->order_id ?? $order->id,
                'status' => $webhookData['status_title'] ?? $webhookData['status'] ?? $webhookData['delivery_status'],
                'scan_type' => $webhookData['status_code'] ?? $webhookData['event_type'] ?? $webhookData['scan_type'] ?? null,
                'remarks' => $webhookData['status_description'] ?? $webhookData['description'] ?? $webhookData['remarks'] ?? '',
                'courier_provider' => 'Shipxpeed',
                // 'original_webhook_data' => $webhookData
            ];
            
            //  dd($sellerWebhookData);
            // Send webhook to seller
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->post($webhook->webhook_url, $sellerWebhookData);
                //   dd($response);
            if ($response->successful()) {
                Log::info("ParcelX webhook successfully forwarded to seller", [
                    'seller_id' => $seller->id,
                    'courier_provider' => $courierProvider,
                    'webhook_url' => $webhook->webhook_url,
                    'awb' => $order->awb
                ]);
            } else {
                Log::error("Failed to forward ParcelX webhook to seller", [
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
            Log::error('Error forwarding ParcelX webhook to seller: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'seller_id' => $order->seller_id,
                'courier_provider' => $courierProvider
            ]);
        }
    }
}