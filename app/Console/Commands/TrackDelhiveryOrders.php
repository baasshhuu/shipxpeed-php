<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Recharge;
use App\Models\ShippingNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrackDelhiveryOrders extends Command
{
    // protected $signature = 'track:delhivery';
    // protected $description = 'Track Delhivery AWB orders every 5 minutes';

  protected $signature = 'track:delhivery';
  protected $description = 'Track Delhivery and SedoFedEx AWB orders every 5 minutes';



    public function handle()
    {
        Log::channel('scheduler')->info('▶️ Starting courier tracking at ' . now());

        $this->trackDelhivery();
        $this->trackSedoFedex();
        $this->trackBoxd(); // 👈 Boxd tracking
        $this->RTO_Amount();
        $this->trackTekipost();
        $this->cancelled_Amount();

        Log::channel('scheduler')->info('✅ Courier tracking run completed.');
    }

    /**
     * Track Boxd courier orders and update their status
     */
    private function trackBoxd()
    {
        $orders = Order::where(function ($q) {
                $q->whereNull('shipping_status')
                  ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
            })
            ->where(function ($q) {
                $q->whereNull('order_status')
                  ->orWhere('order_status', '!=', 'cancelled');
            })
            ->whereNotNull('awb_number')
            ->where('courier_id', 'boxd')
            ->get();

        foreach ($orders as $order) {
            $response = Http::withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'secretkey' => 'POVHFT',
                'customerid' => 'c1754533690129',
            ])->asForm()->post('https://backend.boxdlogistics.in/vendor/v1/shipment/shipment_tracking', [
                'awb_number' => $order->awb_number,
            ]);

            if (! $response->successful()) {
                // Log::channel('scheduler')->warning("✖ Boxd HTTP {$response->status()} for AWB {$order->awb_number}");
                continue;
            }

            $data = $response->json();

            if (!isset($data['status']) || !$data['status'] || empty($data['shipment_status'])) {
                // Log::channel('scheduler')->warning("✖ Invalid Boxd response for AWB {$order->awb_number}");
                continue;
            }

            $shipmentStatus = strtolower($data['shipment_status']);

            // Map Boxd status to internal status
            $mappedStatus = match ($shipmentStatus) {
                'pickup awaited' => 'manifested',
                'Pickup Scheduled' => 'manifested',
                'In Transit' => 'transit',
                'Picked Up' => 'transit',
                'Out for delivery' => 'out for delivery',
                'Delivered' => 'delivered',
                'Cancelled' => 'cancelled',
                'RTO' => 'rto',
                'NDR' => 'NDR',

                default => $shipmentStatus,
            };

            $previousStatus = $order->shipping_status;
            $statusChanged = $previousStatus !== $mappedStatus;

            $order->shipping_status = $mappedStatus;
            if ($mappedStatus === 'delivered') {
                $order->delivered_date = now()->format('Y-m-d');
            }
            $order->save();

            // Optionally send notifications if status changed
            if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto'])) {
                $this->sendWhatsAppMessage($order, $mappedStatus);
                $this->sendEmailNotification($order, $mappedStatus);
            }

            // Log::channel('scheduler')->info("✔ Boxd AWB {$order->awb_number} updated to {$mappedStatus}");
        }
    }



    public static function getTokentekipost(): ?string
{
    try {
        // 🔹 If token already cached
        if (cache()->has('xpressbees_token')) {
            return cache('xpressbees_token');
        }

        // 🔹 Initialize cURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://app.tekipost.com/api-login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'email' => 'Bashu@shipxpeed.com',
                'password' => 'Shipxpeed@7722',
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // 🔹 Handle cURL errors
        if ($error) {
            \Log::error('XpressBees cURL Error: ' . $error);
            dd('cURL Error: ' . $error);
            return null;
        }

        // 🔹 Decode JSON response
        $data = json_decode($response, true);

        \Log::info('XpressBees API Response', ['response' => $data]);

        // ✅ Corrected: token is inside 'data' key
        if (isset($data['data']['token'])) {
            $token = $data['data']['token'];

            // Cache token for 150 minutes
            cache()->put('xpressbees_token', $token, now()->addMinutes(150));

            return $token;
        } else {
            \Log::warning('XpressBees Token Missing', ['response' => $data]);
            // dd('Token missing: ' . $response);
        }

        return null;
    } catch (\Exception $e) {
        \Log::error('XpressBees Token Exception: ' . $e->getMessage());
        // dd('Exception: ' . $e->getMessage());
        return null;
    }
}



   private function trackTekipost()
{
    // echo 'xxs';die;
    // Get token once (no need to call in loop)
    $token = $this->getTokentekipost();

    $orders = Order::where(function ($q) {
            $q->whereNull('shipping_status')
              ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
        })
        ->where(function ($q) {
            $q->whereNull('order_status')
              ->orWhere('order_status', '!=', 'cancelled');
        })
        ->whereNotNull('awb_number')
        ->where('courier_id', 'tekipost')
        ->get();

    foreach ($orders as $order) {

        $url = "https://app.tekipost.com/api-tracking-details/{$order->awb_number}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
        ])->get($url);

        if (! $response->successful()) {
            Log::channel('scheduler')->warning("✖ HTTP {$response->status()} for AWB {$order->awb_number}");
            continue;
        }


        $data = $response->json();

if (!isset($data['success']) || !$data['success'] || empty($data['data'])) {
    Log::channel('scheduler')->warning("✖ Invalid Tekipost response for AWB {$order->awb_number}");
    continue;
}

// ✅ Get current status directly
$statusName = $data['data']['status_name'] ?? '';

// ✅ Map Tekipost status to your internal system status
$mappedStatus = match (strtolower($statusName)) {
    'in transit'       => 'transit',
    'dispatched'       => 'out for delivery',
    'delivered'        => 'delivered',
    'not picked'       => 'rto',
    'manifested'       => 'manifested',
    'cancelled'        => 'cancelled',
    'NDR'              => 'ndr',
    'rto delivered'    => 'rto delivered',
    default            => strtolower($statusName),
};

// ✅ Check and update order
$previousStatus = $order->shipping_status;
$statusChanged = $previousStatus !== $mappedStatus;

$order->shipping_status = $mappedStatus;

if ($mappedStatus === 'delivered' || $mappedStatus === 'rto delivered') {
    $order->delivered_date = $data['data']['delivery_date'] ?? now()->format('Y-m-d');
}

$order->save();

// ✅ Optional notifications
if ($statusChanged && in_array($mappedStatus, ['transit', 'out for delivery', 'delivered', 'rto', 'rto delivered'])) {
    $this->sendWhatsAppMessage($order, $mappedStatus);
    $this->sendEmailNotification($order, $mappedStatus);
}

Log::channel('scheduler')->info("✔ Tekipost AWB {$order->awb_number} updated to {$mappedStatus}");


    }
}

// private function trackTekipost()
// {
//     $token = $this->getTokentekipost();

//     $orders = Order::where(function ($q) {
//             $q->whereNull('shipping_status')
//               ->orWhereNotIn('shipping_status', ['delivered', 'rto']);
//         })
//         ->where(function ($q) {
//             $q->whereNull('order_status')
//               ->orWhere('order_status', '!=', 'cancelled');
//         })
//         ->whereNotNull('awb_number')
//         ->where('courier_id', 'tekipost')
//         ->get();

//     foreach ($orders as $order) {

//         $url = "https://app.tekipost.com/api-tracking-details/{$order->awb_number}";

//         $response = Http::withHeaders([
//             'Authorization' => "Bearer {$token}",
//             'Content-Type'  => 'application/json',
//         ])->get($url);

//         if (! $response->successful()) {
//             Log::channel('scheduler')->warning("✖ HTTP {$response->status()} for AWB {$order->awb_number}");
//             continue;
//         }

//         $data = $response->json();

//         if (!isset($data['success']) || !$data['success'] || empty($data['data'])) {
//             Log::channel('scheduler')->warning("✖ Invalid Tekipost response for AWB {$order->awb_number}");
//             continue;
//         }

//         $statusName = $data['data']['status_name'] ?? '';

//         $mappedStatus = match (strtolower($statusName)) {
//             'in transit'       => 'transit',
//             'dispatched'       => 'out for delivery',
//             'delivered'        => 'delivered',
//             'not picked'       => 'rto',
//             'manifested'       => 'manifested',
//             'cancelled'        => 'cancelled',
//             'ndr'              => 'ndr',
//             'rto delivered'    => 'rto delivered',
//             default            => strtolower($statusName),
//         };

//         $previousShipping = $order->shipping_status;
//         $previousAdmin = $order->admin_status;

//         // ✅ Delivered & RTO Delivered → admin_status
//         if (in_array($mappedStatus, ['delivered'])) {

//             $statusChanged = $previousAdmin !== $mappedStatus;

//             $order->admin_status = $mappedStatus;
//             $order->delivered_date = $data['data']['delivery_date'] ?? now()->format('Y-m-d');

//         } else {
//             // ✅ All others → shipping_status
//             $statusChanged = $previousShipping !== $mappedStatus;
//             $order->shipping_status = $mappedStatus;
//         }

//         $order->save();

//         // 🔔 Send notification only when status changes
//         if ($statusChanged && in_array($mappedStatus, [
//                 'transit', 'out for delivery', 'delivered', 'rto', 'rto delivered'
//             ])) {
//             $this->sendWhatsAppMessage($order, $mappedStatus);
//             $this->sendEmailNotification($order, $mappedStatus);
//         }

//         Log::channel('scheduler')->info("✔ Tekipost AWB {$order->awb_number} updated to {$mappedStatus}");
//     }
// }




private function cancelled_Amount()
{
    try {
        // ℹ️ Find RTO orders where refund hasn't been processed yet
        $orders = Order::where('shipping_status', 'cancelled')
                      ->where(function($query) {
                          $query->where('order_cancelled_amount', '0')
                                ->orWhereNull('order_cancelled_amount');
                      })
                      ->whereNotNull('seller_id')
                      ->whereNotNull('seller_amount_walate')
                      ->where('seller_amount_walate', '>', 0)
                    //  ->where('order_status', '!=', 'cancelled')
                      ->whereNull('order_status')
                      ->get();

        Log::channel('scheduler')->info("🔍 Found {$orders->count()} RTO orders to process");

        if ($orders->isEmpty()) {
            Log::channel('scheduler')->info("ℹ️ No RTO orders found for processing");
            return;
        }

        foreach ($orders as $order) {
            try {
                $creditAmount = $order->seller_amount_walate ?? 0;
                
                if ($creditAmount <= 0) {
                    Log::channel('scheduler')->warning("⚠️ Skipping order {$order->id} - Invalid credit amount: {$creditAmount}");
                    continue;
                }

                        // ℹ️ Check seller's fixed_price before creating recharge entry
                $seller = DB::table('seller_lists')->where('id', $order->seller_id)->first();

                if (!$seller || $seller->fixed_price == 0 || is_null($seller->fixed_price)) {
                    Log::channel('scheduler')->info("⚠️ Skipping RTO debit for order {$order->id} - Seller fixed_price is 0 or null");
                    
                    // Still mark as processed to avoid reprocessing
                    $order->order_cancelled_amount = 1;
                    $order->save();
                    continue;
                }


                // 💰 Credit seller wallet
                $recharge = Recharge::create([
                    'seller_id' => $order->seller_id,
                    'type'      => 'Debit',
                    'amount'    => $creditAmount,
                    'status'    => 1,
                    'description' => 'cancelled for partnrs Debit for order:'
                ]);

                // ✅ Mark recharge as done
                $order->order_cancelled_amount = 1;
                $order->order_status = 'cancelled';
                $order->save();

                Log::channel('scheduler')->info("✔ RTO credit ₹{$creditAmount} processed for seller_id: {$order->seller_id}, order: " . ($order->order_number ?? $order->id));
                
            } catch (\Exception $e) {
                Log::channel('scheduler')->error("❌ Failed RTO credit for order {$order->id}: " . $e->getMessage());
                Log::channel('scheduler')->error("❌ Stack trace: " . $e->getTraceAsString());
            }
        }
        
        Log::channel('scheduler')->info("✅ RTO_Amount processing completed");
       
    } catch (\Exception $e) {
        Log::channel('scheduler')->error('❌ RTO_Amount function error: ' . $e->getMessage());
        Log::channel('scheduler')->error('❌ Stack trace: ' . $e->getTraceAsString());
    }

}



private function RTO_Amount()
{
    try {
        // ℹ️ Find RTO orders where refund hasn't been processed yet
        $orders = Order::where('shipping_status', 'rto')
                      ->where(function($query) {
                          $query->where('rto_amount', '0')
                                ->orWhereNull('rto_amount');
                      })
                      ->whereNotNull('seller_id')
                      ->whereNotNull('seller_amount_walate')
                      ->where('seller_amount_walate', '>', 0)
                      ->get();

        // Log::channel('scheduler')->info("🔍 Found {$orders->count()} RTO orders to process");

        if ($orders->isEmpty()) {
            // Log::channel('scheduler')->info("ℹ️ No RTO orders found for processing");
            return;
        }

        foreach ($orders as $order) {
            try {
                $creditAmount = $order->seller_amount_walate ?? 0;
                
                if ($creditAmount <= 0) {
                    // Log::channel('scheduler')->warning("⚠️ Skipping order {$order->id} - Invalid credit amount: {$creditAmount}");
                    continue;
                }

                        // ℹ️ Check seller's fixed_price before creating recharge entry
                $seller = DB::table('seller_lists')->where('id', $order->seller_id)->first();

                if (!$seller || $seller->fixed_price == 0 || is_null($seller->fixed_price)) {
                    // Log::channel('scheduler')->info("⚠️ Skipping RTO debit for order {$order->id} - Seller fixed_price is 0 or null");
                    
                    // Still mark as processed to avoid reprocessing
                    $order->rto_amount = 1;
                    $order->save();
                    continue;
                }


                // 💰 Credit seller wallet
                $recharge = Recharge::create([
                    'seller_id' => $order->seller_id,
                    'type'      => 'Debit',
                    'amount'    => $creditAmount,
                    'status'    => 1,
                    'description' => 'RTO Debit for order: ' . ($order->order_number ?? $order->id)
                ]);

                // ✅ Mark recharge as done
                $order->rto_amount = 1;
                $order->save();

                // Log::channel('scheduler')->info("✔ RTO credit ₹{$creditAmount} processed for seller_id: {$order->seller_id}, order: " . ($order->order_number ?? $order->id));
                
            } catch (\Exception $e) {
                // Log::channel('scheduler')->error("❌ Failed RTO credit for order {$order->id}: " . $e->getMessage());
                // Log::channel('scheduler')->error("❌ Stack trace: " . $e->getTraceAsString());
            }
        }
        
        // Log::channel('scheduler')->info("✅ RTO_Amount processing completed");
       
    } catch (\Exception $e) {
        // Log::channel('scheduler')->error('❌ RTO_Amount function error: ' . $e->getMessage());
        // Log::channel('scheduler')->error('❌ Stack trace: ' . $e->getTraceAsString());
    }

}
 






// / rivate function RTO_Amount()
// / 
// /    try {
// /        // ℹ️ Find RTO orders where refund hasn't been processed yet
// /        $orders = Order::where('shipping_status', 'rto')
// /                      ->where(function($query) {
// /                          $query->where('rto_amount', '0')
// /                                ->orWhereNull('rto_amount');
// /                      })
// /                      ->whereNotNull('seller_id')
// /                      ->whereNotNull('seller_amount_walate')
// /                      ->where('seller_amount_walate', '>', 0)
// /                      ->get();

// /        Log::channel('scheduler')->info("🔍 Found {$orders->count()} RTO orders to process");

// /        if ($orders->isEmpty()) {
// /            Log::channel('scheduler')->info("ℹ️ No RTO orders found for processing");
// /            return;
// /        }

// /        foreach ($orders as $order) {
// /            try {
// /                $creditAmount = $order->seller_amount_walate ?? 0;
                
// /                if ($creditAmount <= 0) {
// /                    Log::channel('scheduler')->warning("⚠️ Skipping order {$order->id} - Invalid credit amount: {$creditAmount}");
// /                    continue;
// /                }

// /                // 💰 Credit seller wallet
// /                $recharge = Recharge::create([
// /                    'seller_id' => $order->seller_id,
// /                    'type'      => 'Debit',
// /                    'amount'    => $creditAmount,
// /                    'status'    => 1,
// /                    'description' => 'RTO Debit for order: ' . ($order->order_number ?? $order->id)
// /                ]);

// /                // ✅ Mark recharge as done
// /                $order->rto_amount = 1;
// /                $order->save();

// /                Log::channel('scheduler')->info("✔ RTO credit ₹{$creditAmount} processed for seller_id: {$order->seller_id}, order: " . ($order->order_number ?? $order->id));
                
// /            } catch (\Exception $e) {
// /                Log::channel('scheduler')->error("❌ Failed RTO credit for order {$order->id}: " . $e->getMessage());
// /                Log::channel('scheduler')->error("❌ Stack trace: " . $e->getTraceAsString());
// /            }
// /        }
        
// /        Log::channel('scheduler')->info("✅ RTO_Amount processing completed");
        
// /    } catch (\Exception $e) {
// /        Log::channel('scheduler')->error('❌ RTO_Amount function error: ' . $e->getMessage());
// /        Log::channel('scheduler')->error('❌ Stack trace: ' . $e->getTraceAsString());
// /    }
// / 







    private function trackDelhivery()
    {
       
        $apiToken = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';

        $orders = Order::where(function($q) {
                            $q->whereNull('shipping_status')
                              ->orWhereNotIn('shipping_status', ['delivered','rto']);
                        })
                        ->where(function($q) {
                            $q->whereNull('order_status')
                              ->orWhere('order_status', '!=', 'cancelled');
                        })
                        ->whereNotNull('awb_number')
                        ->where('courier_id', 'delhivery_b2c')
                        ->get();

        foreach ($orders as $order) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Token $apiToken",
            ])->get('https://track.delhivery.com/api/v2/packages/json/', [
                'waybill' => $order->awb_number,
                'ref_ids' => '',
            ]);

            if (! $response->successful()) {
                Log::channel('scheduler')->warning("✖ HTTP {$response->status()} for AWB {$order->awb_number}");
                continue;
            }

            $data = $response->json();
            $shipment = $data['ShipmentData'][0]['Shipment'] ?? null;

            if ($shipment && isset($shipment['Status']['Status'])) {

                $newStatus = $shipment['Status']['Status'];

                $statusrr = match ($newStatus) {
                    'In Transit' => 'transit',
                    'Out for delivery' => 'out for delivery',
                    'Delivered' => 'delivered',
                    'RTO' => 'rto',
                    'Dispatched' => 'transit',
                    'Ready for pickup' => 'Assign',

                    default => $newStatus,
                };

                Log::channel('scheduler')->info("ℹ Delhivery raw status for AWB {$order->awb_number}: {$newStatus}");

                // Check if status has changed to prevent duplicate messages
                $previousStatus = $order->shipping_status;
                $statusChanged = $previousStatus !== $statusrr;

                $order->shipping_status = $statusrr;
                if ($statusrr === 'delivered') {
                    $order->delivered_date = date('Y-m-d');
                }
                $order->save();

                // Send WhatsApp message only if status changed
                if ($statusChanged) {
                    $this->sendWhatsAppMessage($order, $statusrr);
                 $this->sendEmailNotification($order, $statusrr);
                }

                Log::channel('scheduler')
                    ->info("✔ Delhivery AWB {$order->awb_number} updated to {$newStatus}");
            }
        }
    }

//     private function trackDelhivery()
// {
//     $apiToken = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';

//     $orders = Order::where(function($q) {
//                         $q->whereNull('shipping_status')
//                           ->orWhereNotIn('shipping_status', ['delivered','rto']);
//                     })
//                     ->where(function($q) {
//                         $q->whereNull('order_status')
//                           ->orWhere('order_status', '!=', 'cancelled');
//                     })
//                     ->whereNotNull('awb_number')
//                     ->where('courier_id', 'delhivery_b2c')
//                     ->get();

//     foreach ($orders as $order) {
//         $response = Http::withHeaders([
//             'Content-Type' => 'application/json',
//             'Authorization' => "Token $apiToken",
//         ])->get('https://track.delhivery.com/api/v2/packages/json/', [
//             'waybill' => $order->awb_number,
//             'ref_ids' => '',
//         ]);

//         if (! $response->successful()) {
//             Log::channel('scheduler')->warning("✖ HTTP {$response->status()} for AWB {$order->awb_number}");
//             continue;
//         }

//         $data = $response->json();
//         $shipment = $data['ShipmentData'][0]['Shipment'] ?? null;

//         if ($shipment && isset($shipment['Status']['Status'])) {

//             $newStatus = $shipment['Status']['Status'];

//             $statusrr = match ($newStatus) {
//                 'In Transit' => 'transit',
//                 'Out for delivery' => 'out for delivery',
//                 'Delivered' => 'delivered',
//                 'RTO' => 'rto',
//                 'Dispatched' => 'transit',
//                 'Ready for pickup' => 'Assign',
//                 default => $newStatus,
//             };

//             Log::channel('scheduler')->info("ℹ Delhivery raw status for AWB {$order->awb_number}: {$newStatus}");

//             // Check existing status
//             $previousShipping = $order->shipping_status;
//             $previousAdmin = $order->admin_status;

//             // If Delivered → Save to admin_status
//             if ($statusrr === 'delivered') {
//                 $statusChanged = $previousAdmin !== 'delivered';
//                 $order->admin_status = 'delivered';
//                 $order->delivered_date = date('Y-m-d');

//             } else {
//                 // All other statuses → Save to shipping_status
//                 $statusChanged = $previousShipping !== $statusrr;
//                 $order->shipping_status = $statusrr;
//             }

//             $order->save();

//             // Send notification only when changed
//             if ($statusChanged) {
//                 $this->sendWhatsAppMessage($order, $statusrr);
//                 $this->sendEmailNotification($order, $statusrr);
//             }

//             Log::channel('scheduler')
//                 ->info("✔ Delhivery AWB {$order->awb_number} updated to {$newStatus}");
//         }
//     }
// }


    /**
     * Send WhatsApp message via Aisensy API
     */
    private function sendWhatsAppMessage($order, $status)
    {
        try {
            // Check if WhatsApp notifications are enabled for this seller
            $whatsappNotification = ShippingNotification::where('seller_id', $order->seller_id)
                ->where('notification_type', 'whatsapp')
                ->where('order_status', $status)
                ->where('enabled', '1')
                ->first();

            if (!$whatsappNotification) {
                Log::channel('scheduler')->info("✖ WhatsApp notification disabled for seller {$order->seller_id} - Status: {$status}");
                return;
            }
            $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;

            // // Skip if customer phone is not available
            // if (empty($order->customer_phone)) {
            //     Log::channel('scheduler')->warning("✖ No phone number for order {$order->order_number}");
            //     return;
            // }

            $customerName = $consignee->name ?? 'Customer';
            $orderNumber = $order->order_number ?? 'N/A';
            $awbNumber = $order->awb_number ?? 'N/A';
            $phoneNumber = $consignee->phone ?? 'N/A';
            // $customerName = 'vicky';
            // $orderNumber = '12345';
            // $awbNumber = 'AWB12345';
            // $phoneNumber = '7357169546';

            // Ensure phone number starts with +91
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+91' . ltrim($phoneNumber, '+91');
            }

            // Determine campaign and template parameters based on status
            $campaignData = $this->getWhatsAppCampaignData($status, $customerName, $orderNumber, $awbNumber);
            
            if (!$campaignData) {
                return; // Skip if no campaign configured for this status
            }

            $payload = [
                "apiKey" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY4OTMwNmIxNTk2NTBhMGMwYmEyNmM1NyIsIm5hbWUiOiJTSElQWFBFRUQgTE9HSVNUSUNTICIsImFwcE5hbWUiOiJBaVNlbnN5IiwiY2xpZW50SWQiOiI2ODkzMDZiMTU5NjUwYTBjMGJhMjZjNTIiLCJhY3RpdmVQbGFuIjoiRlJFRV9GT1JFVkVSIiwiaWF0IjoxNzU0NDY1OTY5fQ.KrQxCgEKxGJLJ6KCLk6vmNrmakhhqnM19ycdHjskf84",
                "campaignName" => $campaignData['campaign'],
                "destination" => $phoneNumber,
                "userName" => $customerName,
                "templateParams" => $campaignData['params']
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://backend.aisensy.com/campaign/t1/api/v2', $payload);

            if ($response->successful()) {
                Log::channel('scheduler')->info("✔ WhatsApp message sent for order {$orderNumber} - Status: {$status}");
                
                // Deduct ₹1 from seller's account for successful WhatsApp message
                try {
                    Recharge::create([
                        'seller_id' => $order->seller_id,
                        'type' => 'Debit',
                        'amount' => 1.00,
                        'status' => 1,
                        'description' => "WhatsApp notification charge for order: {$orderNumber}"
                    ]);
                    Log::channel('scheduler')->info("✔ ₹1 deducted from seller {$order->seller_id} for WhatsApp notification");
                } catch (\Exception $e) {
                    Log::channel('scheduler')->error("❌ Failed to deduct WhatsApp charge for seller {$order->seller_id}: " . $e->getMessage());
                }
            } else {
                Log::channel('scheduler')->warning("✖ Failed to send WhatsApp message for order {$orderNumber} - HTTP {$response->status()}");
            }

        } catch (\Exception $e) {
            Log::channel('scheduler')->error("❌ WhatsApp message error for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Get campaign data based on shipping status
     */
    private function getWhatsAppCampaignData($status, $customerName, $orderNumber, $awbNumber)
    {
        return match ($status) {
            'transit' => [
                'campaign' => 'intransitorder',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            'out for delivery' => [
                'campaign' => 'out_for_delivery_test',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            'delivered' => [
                'campaign' => 'deliveredorder',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            'rto' => [
                'campaign' => 'ndrqorders',
                'params' => [$customerName, $orderNumber, $awbNumber]
            ],
            default => null // No message for other statuses
        };
    }

    /**
     * Send Email notification for order status updates
     */
    private function sendEmailNotification($order, $status)
    {
        try {
            // Check if Email notifications are enabled for this seller
            $emailNotification = ShippingNotification::where('seller_id', $order->seller_id)
                ->where('notification_type', 'email')
                ->where('order_status', $status)
                ->where('enabled', 1)
                ->first();

            if (!$emailNotification) {
                Log::channel('scheduler')->info("✖ Email notification disabled for seller {$order->seller_id} - Status: {$status}");
                return;
            }

            // Skip if customer email is not available
            // if (empty($order->customer_email)) {
            //     Log::channel('scheduler')->warning("✖ No email address for order {$order->order_number}");
            //     return;
            // }
            $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;

            $customerName = $consignee['name'] ?? 'Customer';
            $orderNumber = $order->order_number ?? 'N/A';
            $awbNumber = $order->awb_number ?? 'N/A';
            $customerEmail = $consignee['email'] ?? 'N/A';

            // Get email content based on status
            $emailData = $this->getEmailContent($status, $customerName, $orderNumber, $awbNumber);
            
            if (!$emailData) {
                return; // Skip if no email configured for this status
            }

            // Send email using Laravel's Mail facade
            Mail::send([], [], function ($message) use ($customerEmail, $customerName, $emailData) {
                $message->to($customerEmail, $customerName)
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
            });

            Log::channel('scheduler')->info("✔ Email sent for order {$orderNumber} - Status: {$status}");
            
            // Deduct ₹0.50 from seller's account for successful Email
            try {
                Recharge::create([
                    'seller_id' => $order->seller_id,
                    'type' => 'Debit',
                    'amount' => 0.50,
                    'status' => 1,
                    'description' => "Email notification charge for order: {$orderNumber}"
                ]);
                Log::channel('scheduler')->info("✔ ₹0.50 deducted from seller {$order->seller_id} for Email notification");
            } catch (\Exception $e) {
                Log::channel('scheduler')->error("❌ Failed to deduct Email charge for seller {$order->seller_id}: " . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::channel('scheduler')->error("❌ Email sending error for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Get email content based on shipping status
     */
    private function getEmailContent($status, $customerName, $orderNumber, $awbNumber)
    {
        return match ($status) {
            'transit' => [
                'subject' => "📦 Your Order is in Transit - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #2563eb; margin-bottom: 20px;'>🚚 Your Order is On the Move!</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                Great news! Your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) is currently in transit 🛣️ and moving closer to your city 🏙️.
                            </p>
                            
                            <div style='background-color: #e0f2fe; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #0277bd; font-weight: bold;'>📌 Order Details:</p>
                                <p style='margin: 5px 0; color: #0277bd;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #0277bd;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #0277bd;'>Status: In Transit</p>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                Stay tuned — we'll keep you posted until it reaches you! 💌
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for choosing our service!
                            </p>
                        </div>
                    </div>
                "
            ],
            'out for delivery' => [
                'subject' => "🚛 Out for Delivery - Your Order Arrives Today! - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #ff9800; margin-bottom: 20px;'>🚛 Out for Delivery!</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                Exciting news! Your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) is out for delivery and will arrive today! 🎉
                            </p>
                            
                            <div style='background-color: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #f57c00; font-weight: bold;'>📦 Delivery Information:</p>
                                <p style='margin: 5px 0; color: #f57c00;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #f57c00;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #f57c00;'>Status: Out for Delivery</p>
                                <p style='margin: 5px 0; color: #f57c00;'>Expected: Today</p>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                Please ensure someone is available to receive the package. Our delivery partner will contact you shortly! 📞
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for choosing our service!
                            </p>
                        </div>
                    </div>
                "
            ],
            'delivered' => [
                'subject' => "✅ Order Delivered Successfully! - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #4caf50; margin-bottom: 20px;'>🎁 Order Delivered Successfully!</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                Great news! Your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) has been delivered successfully! 🎁
                            </p>
                            
                            <div style='background-color: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #2e7d32; font-weight: bold;'>✅ Delivery Confirmation:</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>Status: Delivered</p>
                                <p style='margin: 5px 0; color: #2e7d32;'>Delivered On: " . date('d M Y') . "</p>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                We hope you enjoy your purchase! 💜 Thank you for trusting us to deliver your orders safely and on time.
                            </p>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 15px;'>
                                If you have any questions about your order, please feel free to contact our customer support.
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for choosing our service! We look forward to serving you again.
                            </p>
                        </div>
                    </div>
                "
            ],
            'rto' => [
                'subject' => "❌ Delivery Attempt Unsuccessful - {$orderNumber}",
                'body' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <h2 style='color: #f44336; margin-bottom: 20px;'>❌ Delivery Attempt Unsuccessful</h2>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 15px;'>Hi <strong>{$customerName}</strong>,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                We tried delivering your order <strong>{$orderNumber}</strong> (AWB: <strong>{$awbNumber}</strong>) today but it was unsuccessful 😔.
                            </p>
                            
                            <div style='background-color: #ffebee; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #c62828; font-weight: bold;'>📋 Order Details:</p>
                                <p style='margin: 5px 0; color: #c62828;'>Order ID: {$orderNumber}</p>
                                <p style='margin: 5px 0; color: #c62828;'>AWB No.: {$awbNumber}</p>
                                <p style='margin: 5px 0; color: #c62828;'>Status: Delivery Failed</p>
                            </div>
                            
                            <div style='background-color: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 0; color: #f57c00; font-weight: bold;'>Possible reasons:</p>
                                <ul style='margin: 10px 0; color: #f57c00; padding-left: 20px;'>
                                    <li>📍 Wrong or incomplete address</li>
                                    <li>📞 Phone number not reachable</li>
                                    <li>🚪 Customer not available at delivery location</li>
                                </ul>
                            </div>
                            
                            <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                                Please contact our customer support team to reschedule delivery or update your delivery information.
                            </p>
                            
                            <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                            <p style='font-size: 12px; color: #999; text-align: center;'>
                                Thank you for your patience. We'll resolve this issue quickly!
                            </p>
                        </div>
                    </div>
                "
            ],
            default => null // No email for other statuses
        };
    }








    // private function trackDelhivery()
    // {
       
    //     $apiToken = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';

    //     $orders = Order::where(function($q) {
    //                         $q->whereNull('shipping_status')
    //                           ->orWhereNotIn('shipping_status', ['delivered','rto']);
    //                     })
    //                     ->where(function($q) {
    //                         $q->whereNull('order_status')
    //                           ->orWhere('order_status', '!=', 'cancelled');
    //                     })
    //                     ->whereNotNull('awb_number')
    //                     ->where('courier_id', 'delhivery_b2c')
    //                     ->get();

    //     foreach ($orders as $order) {
    //         $response = Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //             'Authorization' => "Token $apiToken",
    //         ])->get('https://track.delhivery.com/api/v2/packages/json/', [
    //             'waybill' => $order->awb_number,
    //             'ref_ids' => '',
    //         ]);

    //         if (! $response->successful()) {
    //             Log::channel('scheduler')->warning("✖ HTTP {$response->status()} for AWB {$order->awb_number}");
    //             continue;
    //         }

    //         $data = $response->json();
    //         $shipment = $data['ShipmentData'][0]['Shipment'] ?? null;

    //         if ($shipment && isset($shipment['Status']['Status'])) {




    //             $newStatus = $shipment['Status']['Status'];

    //             $statusrr = match ($newStatus) {
    //                 'In Transit' => 'transit',
    //                 'Out for delivery' => 'out for delivery',
    //                 'Delivered' => 'delivered',
    //                 'RTO' => 'rto',
    //                  'Dispatched' => 'transit',

    //                 default => $newStatus,
    //             };

    //             Log::channel('scheduler')->info("ℹ Delhivery raw status for AWB {$order->awb_number}: {$newStatus}");


    //         $order->shipping_status = $statusrr;
    //         if ($statusrr === 'Delivered') {
    //             $order->delivered_date = date('Y-m-d');
    //         }
    //         $order->save();
    //             // $order->update(['shipping_status' => $statusrr]);

    //             Log::channel('scheduler')
    //                 ->info("✔ Delhivery AWB {$order->awb_number} updated to {$newStatus}");
    //         }
    //     }
    // }


    private function trackSedoFedex()
{
    $sedoApiToken = 'fec1949bfc737bd52df914d18673e27b67a7f92d'; // Replace with real token
    $baseUrl = "https://private-anon-9a4bb70501-sfxreversepickupsellerdelivery.apiary-mock.com/api/v4/clients/requests"; // Replace with actual URL base

    $orders = Order::where(function($q) {
                        $q->whereNull('shipping_status')
                          ->orWhereNotIn('shipping_status', ['delivered','rto']);
                    })
                    ->where(function($q) {
                        $q->whereNull('order_status')
                          ->orWhere('order_status', '!=', 'cancelled');
                    })
                    ->whereNotNull('awb_number')
                    ->where('courier_id', 'shadowfax')
                    ->get();

    foreach ($orders as $order) {
        $clientRequestId = $order->awb_number;

        $response = Http::withHeaders([
            'Authorization' => "Token $sedoApiToken",
            'Accept' => 'application/json',
        ])
        ->timeout(30)
        ->get("{$baseUrl}/{$clientRequestId}");

        // Log response for debugging
        Log::channel('scheduler')->info("📦 SedoFedEx API Response for AWB {$clientRequestId}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if (! $response->successful()) {
            Log::channel('scheduler')->warning("✖ SedoFedEx HTTP {$response->status()} for AWB {$clientRequestId}");
            continue;
        }

        $data = $response->json();

        $newStatus = $data['status'] ?? null;

        if ($newStatus) {
            $statusrr = match (strtolower($newStatus)) {
                'recd_at_rev_hub' => 'transit',
                'recd_at_fwd_dc' => 'transit',
                'recd_at_fwd_hub' => 'transit',

                'ofd' => 'out for delivery',
                'delivered' => 'delivered',

                'rts' => 'rto',
                'rts_in_process' => 'rto',

                'rts_d' => 'rto',
                'rts_nd' => 'rto',
      
                default => $newStatus,
            };

            // Check if status has changed to prevent duplicate messages
            $previousStatus = $order->shipping_status;
            $statusChanged = $previousStatus !== $statusrr;

            $order->shipping_status = $statusrr;
            if ($statusrr === 'delivered') {
                $order->delivered_date = date('Y-m-d');
            }
            $order->save();

            // Send WhatsApp message only if status changed
            if ($statusChanged) {
                // $this->sendWhatsAppMessage($order, $statusrr);
                // $this->sendEmailNotification($order, $statusrr);
            }

            Log::channel('scheduler')->info("✔ SedoFedEx AWB {$clientRequestId} updated to {$newStatus}");
        } else {
            Log::channel('scheduler')->warning("✖ SedoFedEx status missing for AWB {$clientRequestId}");
        }

        // Optional: log full history (if exists)
        if (!empty($data['pickup_request_state_histories'])) {
            foreach ($data['pickup_request_state_histories'] as $event) {
                $state = $event['state'] ?? 'N/A';
                $desc = $event['state_description'] ?? 'N/A';
                $time = $event['created_at'] ?? 'N/A';

                Log::channel('scheduler')->info("📍 SedoFedEx History [{$clientRequestId}]: {$state} at {$time} - {$desc}");
            }
        }
    }
}


//     public function handle()
//     {
//         Log::channel('scheduler')->info('▶️ Starting courier tracking at ' . now());

//         $this->trackDelhivery();
//         $this->trackSedoFedex();

//         Log::channel('scheduler')->info('✅ Courier tracking run completed.');
//     }

//     private function trackDelhivery()
//     {
//         $apiToken = 'a6cd5bb955fddcb41757ec23ee92cf62b6650607';

//         $orders = Order::where(function($q) {
//                             $q->whereNull('shipping_status')
//                               ->orWhereNotIn('shipping_status', ['delivered','rto']);
//                         })
//                         ->where(function($q) {
//                             $q->whereNull('order_status')
//                               ->orWhere('order_status', '!=', 'cancelled');
//                         })
//                         ->whereNotNull('awb_number')
//                         ->where('courier_id', 'delhivery_b2c')
//                         ->get();

//         foreach ($orders as $order) {
//             $response = Http::withHeaders([
//                 'Content-Type' => 'application/json',
//                 'Authorization' => "Token $apiToken",
//             ])->get('https://track.delhivery.com/api/v1/packages/json/', [
//                 'waybill' => $order->awb_number,
//                 'ref_ids' => '',
//             ]);

//             if (! $response->successful()) {
//                 Log::channel('scheduler')->warning("✖ HTTP {$response->status()} for AWB {$order->awb_number}");
//                 continue;
//             }

//             $data = $response->json();
//             $shipment = $data['ShipmentData'][0]['Shipment'] ?? null;

//             if ($shipment && isset($shipment['Status']['Status'])) {
//                 $newStatus = $shipment['Status']['Status'];

//                 $statusrr = match ($newStatus) {
//                     'In Transit' => 'transit',
//                     'Out for delivery' => 'out for delivery',
//                     'Delivered' => 'delivered',
//                     'RTO' => 'rto',
//                     default => $newStatus,
//                 };

//             $order->shipping_status = $statusrr;
//             if ($statusrr === 'Delivered') {
//                 $order->delivered_date = date('Y-m-d');
//             }
//             $order->save();
//                 // $order->update(['shipping_status' => $statusrr]);

//                 Log::channel('scheduler')
//                     ->info("✔ Delhivery AWB {$order->awb_number} updated to {$newStatus}");
//             }
//         }
//     }


//     private function trackSedoFedex()
// {
//     $sedoApiToken = 'fec1949bfc737bd52df914d18673e27b67a7f92d'; // Replace with real token
//     $baseUrl = "https://private-anon-9a4bb70501-sfxreversepickupsellerdelivery.apiary-mock.com/api/v4/clients/requests"; // Replace with actual URL base

//     $orders = Order::where(function($q) {
//                         $q->whereNull('shipping_status')
//                           ->orWhereNotIn('shipping_status', ['delivered','rto']);
//                     })
//                     ->where(function($q) {
//                         $q->whereNull('order_status')
//                           ->orWhere('order_status', '!=', 'cancelled');
//                     })
//                     ->whereNotNull('awb_number')
//                     ->where('courier_id', 'shadowfax')
//                     ->get();

//     foreach ($orders as $order) {
//         $clientRequestId = $order->awb_number;

//         $response = Http::withHeaders([
//             'Authorization' => "Token $sedoApiToken",
//             'Accept' => 'application/json',
//         ])
//         ->timeout(30)
//         ->get("{$baseUrl}/{$clientRequestId}");

//         // Log response for debugging
//         Log::channel('scheduler')->info("📦 SedoFedEx API Response for AWB {$clientRequestId}", [
//             'status' => $response->status(),
//             'body' => $response->body(),
//         ]);

//         if (! $response->successful()) {
//             Log::channel('scheduler')->warning("✖ SedoFedEx HTTP {$response->status()} for AWB {$clientRequestId}");
//             continue;
//         }

//         $data = $response->json();

//         $newStatus = $data['status'] ?? null;




//         if ($newStatus) {
//             $statusrr = match (strtolower($newStatus)) {
//                 'recd_at_rev_hub' => 'transit',
//                 'recd_at_fwd_dc' => 'transit',
//                 'recd_at_fwd_hub' => 'transit',

//                 'ofd' => 'out for delivery',
//                 'delivered' => 'delivered',

//                 'rts' => 'rto',
//                 'rts_in_process' => 'rto',

//                 'rts_d' => 'rto',
//                 'rts_nd' => 'rto',
      
//                 default => $newStatus,
//             };

//             $order->shipping_status = $statusrr;
//             if ($statusrr === 'delivered') {
//                 $order->delivered_date = date('Y-m-d');
//             }
//             $order->save();

//             // $order->update(['shipping_status' => $statusrr]);

//             Log::channel('scheduler')->info("✔ SedoFedEx AWB {$clientRequestId} updated to {$newStatus}");
//         } else {
//             Log::channel('scheduler')->warning("✖ SedoFedEx status missing for AWB {$clientRequestId}");
//         }

//         // Optional: log full history (if exists)
//         if (!empty($data['pickup_request_state_histories'])) {
//             foreach ($data['pickup_request_state_histories'] as $event) {
//                 $state = $event['state'] ?? 'N/A';
//                 $desc = $event['state_description'] ?? 'N/A';
//                 $time = $event['created_at'] ?? 'N/A';

//                 Log::channel('scheduler')->info("📍 SedoFedEx History [{$clientRequestId}]: {$state} at {$time} - {$desc}");
//             }
//         }
//     }
// }


}