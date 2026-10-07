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

class TrackController extends Controller
{
    // Supported courier providers
    const COURIER_PROVIDERS = [
        'shiprocket' => 'Shiprocket'
    ];
    /**
     * Handle courier webhook for order status updates
     * 
     * @param Request $request
     * @param string $courierProvider
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCourierWebhook(Request $request, $courierProvider = 'shiprocket')
    {
        try {
            // Validate courier provider
            if (!array_key_exists($courierProvider, self::COURIER_PROVIDERS)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unsupported courier provider: ' . $courierProvider
                ], 400);
            }
            
            Log::info(ucfirst($courierProvider) . ' Webhook Received:', [
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
            
            // Extract data based on courier provider
            $extractedData = $this->extractWebhookData($webhookData, $courierProvider);
            
            // Log extracted data for debugging
            Log::info('Extracted webhook data:', [
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
            Log::error(ucfirst($courierProvider) . ' Webhook Error: ' . $e->getMessage(), [
                'courier_provider' => $courierProvider,
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
                'courier_provider' => $courierProvider
            ], 500);
        }
    }
    
    /**
     * Extract webhook data based on courier provider
     * 
     * @param array $webhookData
     * @param string $courierProvider
     * @return array
     */
    private function extractWebhookData($webhookData, $courierProvider)
    {
        // Only handle Shiprocket in this controller
        return $this->extractShiprocketData($webhookData);
    }
    
    /**
     * Extract data from Shiprocket webhook
     * 
     * @param array $webhookData
     * @return array
     */
    private function extractShiprocketData($webhookData)
    {
        try {
            Log::info('Processing Shiprocket webhook data:', ['webhook_keys' => array_keys($webhookData)]);
            
            // Get the latest scan for additional details
            $latestScan = null;
            if (isset($webhookData['scans']) && is_array($webhookData['scans']) && !empty($webhookData['scans'])) {
                $latestScan = end($webhookData['scans']);
                Log::info('Latest scan found:', ['latest_scan' => $latestScan]);
            }
            
            $extractedData = [
                'awb' => $webhookData['awb'] ?? $webhookData['tracking_number'] ?? null,
                'status' => $webhookData['current_status'] ?? $webhookData['shipment_status'] ?? $webhookData['status'] ?? null,
                'scan_type' => $webhookData['current_status_id'] ?? $webhookData['shipment_status_id'] ?? $webhookData['scan_type'] ?? null,
                'remarks' => $latestScan['activity'] ?? $webhookData['remarks'] ?? $webhookData['comment'] ?? '',
                'courier_name' => $webhookData['courier_name'] ?? 'Shiprocket',
                'location' => $latestScan['location'] ?? $webhookData['location'] ?? '',
                'timestamp' => $webhookData['current_timestamp'] ?? ($latestScan['date'] ?? null) ?? $webhookData['timestamp'] ?? now()->toISOString(),
                'order_id' => $webhookData['order_id'] ?? null,
                'sr_order_id' => $webhookData['sr_order_id'] ?? null,
                'channel_order_id' => $webhookData['channel_order_id'] ?? null,
                'channel' => $webhookData['channel'] ?? null,
                'etd' => $webhookData['etd'] ?? null,
                'scans' => $webhookData['scans'] ?? [],
                'pod_status' => $webhookData['pod_status'] ?? null,
                'is_return' => $webhookData['is_return'] ?? 0
            ];
// dd($extractedData);
            Log::info('Shiprocket data extracted successfully:', ['extracted' => $extractedData]);
            return $extractedData;
            
        } catch (\Exception $e) {
            Log::error('Error extracting Shiprocket data: ' . $e->getMessage(), [
                'webhook_data' => $webhookData,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return basic extraction as fallback
            return [
                'awb' => $webhookData['awb'] ?? null,
                'status' => $webhookData['current_status'] ?? $webhookData['status'] ?? null,
                'scan_type' => $webhookData['current_status_id'] ?? null,
                'remarks' => '',
                'courier_name' => $webhookData['courier_name'] ?? 'Shiprocket',
                'location' => '',
                'timestamp' => $webhookData['current_timestamp'] ?? now()->toISOString(),
                'order_id' => $webhookData['order_id'] ?? null,
                'sr_order_id' => null,
                'channel_order_id' => $webhookData['channel_order_id'] ?? null,
                'channel' => $webhookData['channel'] ?? null,
                'etd' => $webhookData['etd'] ?? null,
                'scans' => $webhookData['scans'] ?? [],
                'pod_status' => null,
                'is_return' => 0
            ];
        }
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
            Log::info('Updating order status:', [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'extracted_data' => $extractedData,
                'courier_provider' => $courierProvider
            ]);
            
            $updateData = [
                'updated_at' => now(),
                'courier_provider' => $courierProvider
            ];
            
            if (!empty($extractedData['status'])) {
                $updateData['status'] = $extractedData['status'];
                $updateData['last_status'] = $extractedData['status'];
            }
            
            if (!empty($extractedData['scan_type'])) {
                $updateData['scan_type'] = $extractedData['scan_type'];
            }
            
            if (!empty($extractedData['remarks'])) {
                $updateData['remarks'] = $extractedData['remarks'];
            }
            
            // Map courier status to internal status
            $mappedStatus = $this->mapCourierStatus($extractedData['status'], $extractedData['scan_type'], $courierProvider);
            if ($mappedStatus) {
                $updateData['delivery_status'] = $mappedStatus;
            }
            
            Log::info('Order update data prepared:', ['update_data' => $updateData]);
            
            // Update the order
            $order->update($updateData);
            
            // Insert tracking history
            $this->insertTrackingHistory($order, $extractedData, $courierProvider, $webhookData);
            
            Log::info("Order status updated successfully for AWB: {$order->awb}", [
                'order_id' => $order->id,
                'courier_provider' => $courierProvider,
                'status' => $extractedData['status'],
                'scan_type' => $extractedData['scan_type']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating order status: ' . $e->getMessage(), [
                'order_id' => $order->id ?? 'unknown',
                'awb' => $order->awb ?? 'unknown',
                'courier_provider' => $courierProvider,
                'extracted_data' => $extractedData,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't throw exception, just log it
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
            Log::info('Inserting tracking history:', [
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
                'courier_name' => $extractedData['courier_name'] ?? 'Unknown',
                'courier_provider' => $courierProvider,
                'location' => $extractedData['location'] ?? '',
                'webhook_data' => json_encode($webhookData),
                'created_at' => now(),
                'updated_at' => now()
            ];            
            // Check if tracking_histories table exists and handle accordingly
            if (DB::getSchemaBuilder()->hasTable('tracking_histories')) {
                DB::table('tracking_histories')->insert($insertData);
                Log::info('Tracking history inserted successfully');
            } else {
                Log::warning('tracking_histories table does not exist, skipping insert');
            }
            
        } catch (\Exception $e) {
            Log::error('Error inserting tracking history: ' . $e->getMessage(), [
                'order_id' => $order->id ?? 'unknown',
                'awb' => $order->awb ?? 'unknown',
                'courier_provider' => $courierProvider,
                'insert_data' => $insertData ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't throw exception, just log it
            return false;
        }
    }
    
    /**
     * Map courier status to internal delivery status
     * 
     * @param string|null $status
     * @param string|null $scanType
     * @param string $courierProvider
     * @return string|null
     */
    private function mapCourierStatus($status, $scanType, $courierProvider)
    {
        if (!$status) return null;
        
        switch ($courierProvider) {
            case 'shiprocket':
                return $this->mapShiprocketStatus($status, $scanType);
            case 'parcelx':
                return $this->mapParcelXStatus($status, $scanType);
            case 'seloship':
                return $this->mapSeloshipStatus($status, $scanType);
            default:
                return $this->mapShiprocketStatus($status, $scanType);
        }
    }
    
    /**
     * Map Shiprocket status to internal delivery status
     * 
     * @param string|null $status
     * @param string|null $scanType
     * @return string|null
     */
    private function mapShiprocketStatus($status, $scanType)
    {
        if (!$status) return null;
        
        $statusLower = strtolower($status);
        $scanTypeLower = strtolower($scanType ?? '');
        
        // Map Shiprocket statuses
        $statusMap = [
            'delivered' => 'delivered',
            'out for delivery' => 'out_for_delivery',
            'in transit' => 'in_transit',
            'picked up' => 'picked_up',
            'manifested' => 'manifested',
            'manifest generated' => 'manifested',
            'shipped' => 'in_transit',
            'rto initiated' => 'rto_initiated',
            'rto delivered' => 'rto_delivered',
            'cancelled' => 'cancelled',
            'lost' => 'lost',
            'damaged' => 'damaged',
            'undelivered' => 'undelivered'
        ];
        
        // Map by status ID if available
        $statusIdMap = [
            '5' => 'manifested',        // MANIFEST GENERATED
            '6' => 'in_transit',        // SHIPPED
            '7' => 'delivered',         // DELIVERED
            '8' => 'delivered',         // DELIVERED
            '18' => 'in_transit',       // IN TRANSIT
            '20' => 'in_transit',       // IN TRANSIT (current_status_id)
            '42' => 'picked_up',        // PICKED UP
            '9' => 'rto_initiated',     // RTO INITIATED
            '10' => 'rto_delivered'     // RTO DELIVERED
        ];
        
        // First try status ID mapping
        if ($scanType && isset($statusIdMap[$scanType])) {
            return $statusIdMap[$scanType];
        }
        
        return $statusMap[$statusLower] ?? 'in_transit';
    }

    
    /**
     * Forward webhook data to seller's webhook URL
     * 
     * @param Order $order
     * @param array $webhookData
     * @param string $courierProvider
     */
    private function forwardToSellerWebhook($order, $webhookData, $courierProvider = 'Shipxpeed')
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
            $sellerWebhookData = [
                'awb' => $order->awb,
                'order_id' => $order->order_id ?? $order->id,
                'status' => $webhookData['current_status'] ?? $webhookData['shipment_status'] ?? $webhookData['status'] ?? $webhookData['status_title'] ?? $webhookData['delivery_status'],
                'scan_type' => $webhookData['current_status_id'] ?? $webhookData['shipment_status_id'] ?? $webhookData['status_code'] ?? $webhookData['scan_type'] ?? $webhookData['event_type'] ?? null,
                'remarks' => $webhookData['remarks'] ?? $webhookData['comment'] ?? $webhookData['description'] ?? $webhookData['status_description'] ?? '',
                // 'courier_name' => $webhookData['courier_name'] ?? $webhookData['courier_partner'] ?? self::COURIER_PROVIDERS[$courierProvider],
                'courier_provider' => $courierProvider,
                // 'location' => $webhookData['location'] ?? $webhookData['city'] ?? $webhookData['status_location'] ?? '',
                // 'timestamp' => $webhookData['current_timestamp'] ?? $webhookData['event_date'] ?? now()->toISOString(),
                // 'etd' => $webhookData['etd'] ?? null,
                'original_webhook_data' => $webhookData
            ];
            
            // Send webhook to seller
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->post($webhook->webhook_url, $sellerWebhookData);
            
            if ($response->successful()) {
                Log::info("Webhook successfully forwarded to seller", [
                    'seller_id' => $seller->id,
                    'courier_provider' => $courierProvider,
                    'webhook_url' => $webhook->webhook_url,
                    'awb' => $order->awb
                ]);
            } else {
                Log::error("Failed to forward webhook to seller", [
                    'seller_id' => $seller->id,
                    'courier_provider' => $courierProvider,
                    'webhook_url' => $webhook->webhook_url,
                    'awb' => $order->awb,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error forwarding webhook to seller: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'seller_id' => $order->seller_id,
                'courier_provider' => $courierProvider
            ]);
        }
    }
    
    /**
     * Shiprocket webhook handler (default)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleShiprocketWebhook(Request $request)
    {
        return $this->handleCourierWebhook($request, 'shiprocket');
    }
    
    /**
     * Add a new courier provider support (Shiprocket only in this controller)
     * For other providers, use their respective controllers
     * 
     * @param string $providerKey
     * @param string $providerName
     * @return bool
     */
    public function addCourierProvider($providerKey, $providerName)
    {
        if (!array_key_exists($providerKey, self::COURIER_PROVIDERS)) {
            // In a real application, you might want to store this in database
            // For now, it would need to be manually added to the COURIER_PROVIDERS constant
            Log::info("New courier provider added: {$providerKey} - {$providerName}");
            return true;
        }
        return false;
    }
    
    /**
     * Get list of supported courier providers (Shiprocket only in this controller)
     * 
     * @return array
     */
    public function getSupportedCourierProviders()
    {
        return self::COURIER_PROVIDERS;
    }
    
    /**
     * Manual tracking function for specific AWB
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackShipment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'awb' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $awb = $request->awb;
        
        try {
            // Find order by AWB
            $order = Order::where('awb_number', $awb)->first();
            
            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found for AWB: ' . $awb
                ], 404);
            }
            
            // Get tracking history
            $trackingHistory = DB::table('tracking_histories')
                ->where('awb', $awb)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'status' => true,
                'data' => [
                    'order' => $order,
                    'courier_provider' => $order->courier_provider ?? 'shiprocket',
                    'tracking_history' => $trackingHistory,
                    'supported_providers' => self::COURIER_PROVIDERS
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error tracking shipment: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}



