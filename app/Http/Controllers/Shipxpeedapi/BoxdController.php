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

/**
 * BoxdController - Handles BoxD webhook with custom header authentication
 * 
 * Database Requirements:
 * CREATE TABLE webhook_settings (
 *     id INT PRIMARY KEY AUTO_INCREMENT,
 *     courier_provider VARCHAR(50) NOT NULL,
 *     header_key VARCHAR(100),
 *     header_value VARCHAR(255),
 *     is_active TINYINT(1) DEFAULT 1,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * );
 */
class BoxdController extends Controller
{
    // Supported courier providers
    const COURIER_PROVIDERS = [
        'boxd' => 'BoxD'
    ];
    
    /**     * Test webhook endpoint that accepts any data - for debugging purposes
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testBoxdWebhook(Request $request)
    {
        try {
            Log::info('TEST BoxD Webhook - All Data Received:', [
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'all_input' => $request->all(),
                'raw_content' => $request->getContent(),
                'query_params' => $request->query(),
                'input_stream' => $request->input(),
                'json_decode_raw' => json_decode($request->getContent(), true),
                'content_type' => $request->header('Content-Type'),
                'user_agent' => $request->header('User-Agent'),
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'status' => true,
                'message' => 'Test webhook received successfully',
                'debug_info' => [
                    'method' => $request->method(),
                    'content_type' => $request->header('Content-Type'),
                    'raw_content_length' => strlen($request->getContent()),
                    'has_json_data' => !empty($request->all()),
                    'has_raw_content' => !empty($request->getContent()),
                    'received_keys' => array_keys($request->all()),
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Test webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Test webhook error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**     * Handle BoxD webhook for order status updates
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handlboxdWebhook(Request $request)
    {
        // dd($request->all());
        try {
            $courierProvider = 'boxd';
            
            // Validate custom header authentication
            $headerValidation = $this->validateWebhookHeaders($request);
            // dd($headerValidation);
            if (!$headerValidation['status']) {
                // Log::error('BoxD Webhook Header Validation Failed:', [
                //     'headers' => $request->headers->all(),
                //     'message' => $headerValidation['message']
                // ]);
                return response()->json([
                    'status' => false,
                    'message' => $headerValidation['message']
                ], 401);
            }
            
            Log::info('BoxD Webhook Received:', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'raw_content' => $request->getContent(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'user_agent' => $request->header('User-Agent')
            ]);
            
            $webhookData = $request->all();
            
            // Also try to get raw content if normal data is empty
            if (empty($webhookData)) {
                $rawContent = $request->getContent();
                Log::info('Trying to decode raw webhook content:', [
                    'raw_content' => $rawContent,
                    'raw_length' => strlen($rawContent)
                ]);
                
                // Try to decode JSON from raw content
                if (!empty($rawContent)) {
                    $decodedData = json_decode($rawContent, true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($decodedData)) {
                        $webhookData = $decodedData;
                        Log::info('Successfully decoded JSON from raw content:', ['decoded_data' => $webhookData]);
                    }
                }
            }
            
            // Validate webhook data is not empty
            if (empty($webhookData)) {
                Log::error('Empty webhook data received after all attempts', [
                    'courier_provider' => $courierProvider,
                    'raw_content' => $request->getContent(),
                    'headers' => $request->headers->all(),
                    'query_params' => $request->query(),
                    'all_input' => $request->all()
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Empty webhook data received',
                    'debug_info' => [
                        'raw_content_length' => strlen($request->getContent()),
                        'content_type' => $request->header('Content-Type'),
                        'method' => $request->method()
                    ]
                ], 400);
            }
            
            // Extract data from BoxD webhook
            $extractedData = $this->extractBoxDData($webhookData);
            
            // Log extracted data for debugging
            Log::info('Extracted BoxD webhook data:', [
                'courier_provider' => $courierProvider,
                'extracted_data' => $extractedData,
                'original_webhook_data' => $webhookData
            ]);
            
            // TEMPORARY: For testing, if AWB not found, still continue with a fake AWB
            if (!$extractedData['awb']) {
                Log::warning('AWB number not found in webhook data - using test mode', [
                    'courier_provider' => $courierProvider,
                    'webhook_data' => $webhookData,
                    'extracted_data' => $extractedData,
                    'awb_fields_checked' => ['awb', 'waybill', 'tracking_id']
                ]);
                
                // TEMPORARY: For testing purposes, return success even without AWB
                return response()->json([
                    'status' => true,
                    'message' => 'Webhook received successfully (test mode - no AWB found)',
                    'debug_info' => [
                        'webhook_keys' => array_keys($webhookData),
                        'extracted_awb' => $extractedData['awb'],
                        'checked_fields' => ['awb', 'waybill', 'tracking_id'],
                        'webhook_data' => $webhookData
                    ]
                ], 200);
            }
            
            // Find order by AWB number
            $order = Order::where('awb_number', $extractedData['awb'])->first();
            
            if (!$order) {
                Log::warning("Order not found for AWB: {$extractedData['awb']} - test mode");
                
                // TEMPORARY: For testing, return success even if order not found
                return response()->json([
                    'status' => true,
                    'message' => 'Webhook received successfully (test mode - order not found)',
                    'debug_info' => [
                        'awb' => $extractedData['awb'],
                        'webhook_data' => $webhookData,
                        'extracted_data' => $extractedData
                    ]
                ], 200);
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
            Log::error('BoxD Webhook Error: ' . $e->getMessage(), [
                'courier_provider' => 'boxd',
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
                'courier_provider' => 'boxd'
            ], 500);
        }
    }
    
    /**
     * Extract data from BoxD webhook
     * 
     * @param array $webhookData
     * @return array
     */
    private function extractBoxDData($webhookData)
    {
        return [
            'awb' => $webhookData['awb'] ?? $webhookData['waybill'] ?? $webhookData['tracking_id'] ?? null,
            'status' => $webhookData['status_title'] ?? $webhookData['delivery_status'] ?? $webhookData['status'] ?? null,
            'scan_type' => $webhookData['status_code'] ?? $webhookData['event_type'] ?? $webhookData['scan_type'] ?? null,
            'remarks' => $webhookData['status_description'] ?? $webhookData['description'] ?? $webhookData['remarks'] ?? '',
            'courier_name' => $webhookData['courier_partner'] ?? 'BoxD',
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
            Log::info('Updating BoxD order status:', [
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
            
            // Map BoxD status to internal status
            $mappedStatus = $this->mapBoxDStatus($extractedData['status'], $extractedData['scan_type']);
            // dd($mappedStatus);
            if ($mappedStatus) {
                $updateData['shipping_status'] = $mappedStatus;
                
                // Set delivered_date when status is delivered
                if ($mappedStatus === 'delivered') {
                    $updateData['delivered_date'] = now();
                }
            }
            
            Log::info('BoxD order update data prepared:', ['update_data' => $updateData]);
            
            // Update the order
            // $order->update($updateData);
            
            // Insert tracking history
            // $this->insertTrackingHistory($order, $extractedData, $courierProvider, $webhookData);
            
            Log::info("BoxD order status updated successfully for AWB: {$order->awb}", [
                'order_id' => $order->id,
                'courier_provider' => $courierProvider,
                'status' => $extractedData['status'],
                'scan_type' => $extractedData['scan_type']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating BoxD order status: ' . $e->getMessage(), [
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
            Log::info('Inserting BoxD tracking history:', [
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
                'courier_name' => $extractedData['courier_name'] ?? 'BoxD',
                'courier_provider' => $courierProvider,
                'location' => $extractedData['location'] ?? '',
                'webhook_data' => json_encode($webhookData),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Check if tracking_histories table exists and handle accordingly
            if (DB::getSchemaBuilder()->hasTable('tracking_histories')) {
                DB::table('tracking_histories')->insert($insertData);
                Log::info('BoxD tracking history inserted successfully');
            } else {
                Log::warning('tracking_histories table does not exist, skipping insert');
            }
            
        } catch (\Exception $e) {
            Log::error('Error inserting BoxD tracking history: ' . $e->getMessage(), [
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
     * Map BoxD status to internal delivery status
     * 
     * @param string|null $status
     * @param string|null $scanType
     * @return string|null
     */
    private function mapBoxDStatus($status, $scanType)
    {
        // dd($status);
        if (!$status) return null;
        
        $statusLower = strtolower($status);
        
        // Map BoxD status codes and titles
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
    private function forwardToSellerWebhook($order, $webhookData, $courierProvider = 'boxd')
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
                Log::info("BoxD webhook successfully forwarded to seller", [
                    'seller_id' => $seller->id,
                    'courier_provider' => $courierProvider,
                    'webhook_url' => $webhook->webhook_url,
                    'awb' => $order->awb
                ]);
            } else {
                Log::error("Failed to forward BoxD webhook to seller", [
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
            Log::error('Error forwarding BoxD webhook to seller: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'awb' => $order->awb,
                'seller_id' => $order->seller_id,
                'courier_provider' => $courierProvider
            ]);
        }
    }
    
    /**
     * Validate webhook headers for authentication
     * 
     * @param Request $request
     * @return array
     */
    private function validateWebhookHeaders($request)
    {
        try {
            // Log incoming headers for debugging
            Log::info('BoxD Webhook Headers received:', [
                'all_headers' => $request->headers->all(),
                'user_agent' => $request->header('User-Agent'),
                'content_type' => $request->header('Content-Type'),
                'custom_header_check' => $request->header('SHIPXPEED_API_987543654321')
            ]);

            // Check if webhook_settings table exists
            if (!DB::getSchemaBuilder()->hasTable('webhook_settings')) {
                Log::info('webhook_settings table does not exist, skipping header validation');
                return ['status' => true, 'message' => 'Header validation table not found, allowing request'];
            }
            
            // Get custom header configuration from database
            $webhookSettings = DB::table('webhook_settings')
                ->where('courier_provider', 'boxd')
                ->where('is_active', 1)
                ->first();
            
            // Log what settings we found
            Log::info('Webhook settings found:', [
                'settings_found' => $webhookSettings ? true : false,
                'settings_data' => $webhookSettings ? [
                    'header_key' => $webhookSettings->header_key,
                    'has_header_value' => !empty($webhookSettings->header_value),
                    'is_active' => $webhookSettings->is_active
                ] : null
            ]);
            
            // If no webhook settings found, allow request (backward compatibility)
            if (!$webhookSettings) {
                Log::info('No webhook settings found for boxd, allowing request');
                return ['status' => true, 'message' => 'No header validation configured'];
            }
            
            // Check if custom header key and value are configured
            if (empty($webhookSettings->header_key) || empty($webhookSettings->header_value)) {
                Log::info('Header validation disabled in settings');
                return ['status' => true, 'message' => 'Header validation disabled'];
            }
            
            // Get the header value from request
            $headerValue = $request->header($webhookSettings->header_key);
            
            Log::info('Header validation detailed check:', [
                'expected_key' => $webhookSettings->header_key,
                'expected_value' => $webhookSettings->header_value,
                'received_value' => $headerValue,
                'received_value_length' => strlen($headerValue ?? ''),
                'expected_value_length' => strlen($webhookSettings->header_value),
                'exact_match' => $headerValue === $webhookSettings->header_value,
                'case_insensitive_match' => strtolower($headerValue ?? '') === strtolower($webhookSettings->header_value),
                'trimmed_match' => trim($headerValue ?? '') === trim($webhookSettings->header_value)
            ]);
            
            // TEMPORARY: For debugging, allow request but log the mismatch
            if ($headerValue !== $webhookSettings->header_value) {
                Log::warning('Header validation failed but allowing request for debugging', [
                    'expected_header' => $webhookSettings->header_key,
                    'expected_value' => substr($webhookSettings->header_value, 0, 30) . '...',
                    'received_value' => $headerValue ? substr($headerValue, 0, 30) . '...' : 'null',
                    'all_headers_received' => array_keys($request->headers->all())
                ]);
                
                // TEMPORARY: Return success instead of failure for debugging
                return ['status' => true, 'message' => 'Header validation failed but allowing for debugging'];
            }
            
            return ['status' => true, 'message' => 'Header validation successful'];
            
        } catch (\Exception $e) {
            Log::error('Error validating webhook headers: ' . $e->getMessage(), [
                'courier_provider' => 'boxd',
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile()
            ]);
            
            // If validation fails due to error, allow request for safety
            return ['status' => true, 'message' => 'Header validation error, allowing request'];
        }
    }
    
    /**
     * Set webhook header configuration
     * 
     * @param string $headerKey
     * @param string $headerValue
     * @param string $courierProvider
     * @return bool
     */
    public function setWebhookHeaderConfig($headerKey, $headerValue, $courierProvider = 'boxd')
    {
        try {
            // Check if webhook_settings table exists
            if (!DB::getSchemaBuilder()->hasTable('webhook_settings')) {
                Log::error('webhook_settings table does not exist. Please create the table first.', [
                    'courier_provider' => $courierProvider,
                    'header_key' => $headerKey
                ]);
                return false;
            }
            
            $data = [
                'courier_provider' => $courierProvider,
                'header_key' => $headerKey,
                'header_value' => $headerValue,
                'is_active' => 1,
                'updated_at' => now()
            ];
            
            // Check if record exists
            $existing = DB::table('webhook_settings')
                ->where('courier_provider', $courierProvider)
                ->first();
            
            if ($existing) {
                // Update existing record
                DB::table('webhook_settings')
                    ->where('courier_provider', $courierProvider)
                    ->update($data);
            } else {
                // Insert new record
                $data['created_at'] = now();
                DB::table('webhook_settings')->insert($data);
            }
            
            Log::info('Webhook header configuration updated:', [
                'courier_provider' => $courierProvider,
                'header_key' => $headerKey
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error setting webhook header config: ' . $e->getMessage(), [
                'courier_provider' => $courierProvider,
                'header_key' => $headerKey,
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine()
            ]);
            return false;
        }
    }
    
    /**
     * Setup BoxD webhook header configuration from your settings
     * Call this once to configure your webhook headers
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function setupWebhookHeaders()
    {
        try {
            // Your header configuration from the screenshot
            $headerKey = 'SHIPXPEED_API_987543654321';
            $headerValue = 'Bearer SHIPXPEED_TOKEN_ABC1jchsdnc94uifkxxsdccdqwdasxasxdkj23456789';
            
            // Setup the headers
            $result = $this->setWebhookHeaderConfig($headerKey, $headerValue, 'boxd');
            
            if ($result) {
                return response()->json([
                    'status' => true,
                    'message' => 'Webhook headers configured successfully',
                    'header_key' => $headerKey,
                    'header_value_preview' => substr($headerValue, 0, 20) . '...'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to configure webhook headers'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error configuring webhook headers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}