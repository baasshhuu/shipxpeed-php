<?php
// app/Http/Controllers/Api/CourierController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\LogisticProvider;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class CourierController extends Controller
{








public function serviceabilitybulk(Request $request)
{
    // Validate incoming order_ids array
    $data = $request->validate([
        'order_ids' => 'required|array',
        'order_ids.*' => 'integer',
    ]);

    $allResults = [];

    // Prepare bulk parameters for all courier services
    $bulkParams = ['order_ids' => $data['order_ids']];

    $results = LogisticProvider::active()
        ->get()
        ->flatMap(function ($prov) use ($bulkParams) {
            $key = "courier.{$prov->code}";

            if (!app()->bound($key)) {
                return [];
            }

            try {
                // Call the bulk method with order_ids array
                $entries = app($key)->getServiceability_bulk($bulkParams);
                
                // Process each entry from bulk response
                return collect($entries)->map(fn($slot) => [
                    'courierId'            => $prov->id,
                    'order_id'             => $slot['order_id'] ?? null,
                    'serviceabilityId'     => $slot['serviceabilityId'] ?? null,
                    'courierName'          => $slot['courierName'] ?? 'N/A',
                    'courierImage'         => $prov->logo,
                    'courierType'          => 1,
                    'courier_cat_id'       => 1,
                    'courier_partner_id'   => $prov->id,
                    'volWeight'            => $slot['volWeight'] ?? 0,
                    'minWeight'            => $slot['minWeight'] ?? 0,
                    'courierCharge'        => $slot['courierCharge'] ?? 0,
                    'freightCharges'       => $slot['freightCharges'] ?? 0,
                    'codCharge'            => $slot['codCharge'] ?? 0,
                    'zone'                 => $slot['zone'] ?? null,
                    'is_document_required' => false,
                ])->toArray();
            } catch (\Exception $e) {
                // Log error but continue with other providers
                Log::error("Error in bulk serviceability for provider {$prov->code}: " . $e->getMessage());
                return [];
            }
        })
        ->filter() // Remove empty arrays
        ->shuffle()
        ->values()
        ->toArray();

    return response()->json([
        'data'         => $results,
        'success'      => true,
        'message'      => 'Bulk Serviceability Fetched Successfully!',
        'responseCode' => 200,
    ]);
}








    public function serviceability(Request $request)
    {
//  return ['dsxsxsxsxs'];
         $data = $request->validate([
            'order_id' => 'required|integer',
        ]);

        $order = Order::findOrFail($data['order_id']);
        // dd($order);
        $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
        $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;


        $params = [
            'origin'       => (int) $consignee['pincode'],
            'destination'  => (int) $pickup['pincode'],
            'payment_type' => $order->payment_type,
            'order_amount' => $order->order_amount,
            'weight'       => $order->package_weight,
            'length'       => $order->package_length,
            'breadth'      => $order->package_breadth,
            'height'       => $order->package_height,
            'order_id'       => $data['order_id'],

        ];


        $results = LogisticProvider::active()
            ->get()
            ->flatMap(function($prov) use ($params, $data) {
                 

                $key = "courier.{$prov->code}";
                if (! app()->bound($key)) {
                    return [];  // no service class, skip
                }
                // dd($key);

                $entries = app($key)->getServiceability($params);
        //  return $entries;
                // Merge each slab with provider + order info
                return collect($entries)->map(fn($slot) => [
                    'courierId'            => $prov->id,
                    'order_id'             => $data['order_id'],
                    'serviceabilityId'     => $slot['serviceabilityId'],
                    'courierName'          => $slot['courierName'],
                    'courierImage'         => $prov->logo,
                    'courierType'          => 1,                  // or derive if you have a column
                    'courier_cat_id'       => 1,                  // your business logic
                    'courier_partner_id'   => $prov->id,
                    'volWeight'            => $slot['volWeight'],
                    'minWeight'            => $slot['minWeight'],
                    'courierCharge'        => $slot['courierCharge'],
                    'freightCharges'       => $slot['freightCharges'],
                    'codCharge'            => $slot['codCharge'],
                    'is_document_required' => false,
                ])->toArray();
            })
            ->shuffle()
            ->values();
            // dd($results);


        // Courier & Rate Manager grouping (new, isolated) - show only seller's assigned sub-account per master courier
        $crmAccounts = \DB::table('courier_accounts')->get()->keyBy('logistic_provider_id');
        $crmChosenPerCourier = [];
        $crmFiltered = [];
        \Log::info('CRM_DEBUG input results', ['count' => count($results), 'raw' => $results, 'seller_id' => $order->seller_id ?? 'NULL']);
        foreach ($results as $entry) {
            $lpId = $entry['courierId'] ?? null;
            if ($lpId && isset($crmAccounts[$lpId])) {
                $masterId = $crmAccounts[$lpId]->courier_id;
                if (!isset($crmChosenPerCourier[$masterId])) {
                    $siblingIds = \DB::table('courier_accounts')->where('courier_id', $masterId)->pluck('id');
                    $assigned = \DB::table('seller_courier_accounts')
                        ->where('seller_id', $order->seller_id)
                        ->whereIn('courier_account_id', $siblingIds)
                        ->where('status', 1)
                        ->first();
                    $chosenAccountId = $assigned ? $assigned->courier_account_id : \DB::table('courier_accounts')->where('courier_id', $masterId)->where('is_default', 1)->value('id');
                    $chosenAccount = $chosenAccountId ? \DB::table('courier_accounts')->where('id', $chosenAccountId)->first() : null;
                    $crmChosenPerCourier[$masterId] = $chosenAccount ? $chosenAccount->logistic_provider_id : null;
                }
                if ($lpId == $crmChosenPerCourier[$masterId]) {
                    $crmFiltered[] = $entry;
                }
            } else {
                $crmFiltered[] = $entry;
            }
        }
        $results = $crmFiltered;
        return response()->json([
            'data'         => $results,
            'success'      => true,
            'message'      => 'Successfully Fetched!!!',
            'responseCode' => 200,
        ]);
    }



    public function reverseServiceability(Request $request)
    {
//  return ['dsxsxsxsxs'];
         $data = $request->validate([
            'order_id' => 'required|integer',
        ]);

        $order = Order::findOrFail($data['order_id']);
        // dd($order);
        $pickup = is_string($order->pickup) ? json_decode($order->pickup, true) : $order->pickup;
        $consignee = is_string($order->consignee) ? json_decode($order->consignee, true) : $order->consignee;


        $params = [
            'origin'       => (int) $consignee['pincode'],
            'destination'  => (int) $pickup['pincode'],
            'payment_type' => $order->payment_type,
            'order_amount' => $order->order_amount,
            'weight'       => $order->package_weight,
            'length'       => $order->package_length,
            'breadth'      => $order->package_breadth,
            'height'       => $order->package_height,
            'order_id'       => $data['order_id'],

        ];


        $results = LogisticProvider::active()
            ->get()
            ->flatMap(function($prov) use ($params, $data) {
                 

                $key = "courier.{$prov->code}";
                if (! app()->bound($key)) {
                    return [];  // no service class, skip
                }
                // dd($key);

                $entries = app($key)->reversegetServiceability($params);
        //  return $entries;
                // Merge each slab with provider + order info
                return collect($entries)->map(fn($slot) => [
                    'courierId'            => $prov->id,
                    'order_id'             => $data['order_id'],
                    'serviceabilityId'     => $slot['serviceabilityId'],
                    'courierName'          => $slot['courierName'],
                    'courierImage'         => $prov->logo,
                    'courierType'          => 1,                  // or derive if you have a column
                    'courier_cat_id'       => 1,                  // your business logic
                    'courier_partner_id'   => $prov->id,
                    'volWeight'            => $slot['volWeight'],
                    'minWeight'            => $slot['minWeight'],
                    'courierCharge'        => $slot['courierCharge'],
                    'freightCharges'       => $slot['freightCharges'],
                    'codCharge'            => $slot['codCharge'],
                    'is_document_required' => false,
                ])->toArray();
            })
            ->shuffle()
            ->values();
            // dd($results);

        return response()->json([
            'data'         => $results,
            'success'      => true,
            'message'      => 'Successfully Fetched!!!',
            'responseCode' => 200,
        ]);
    }





public function cancelAwb(Request $request)
{
    // dd($request);
    $data = $request->validate([
        'courier' => 'required|string',
        'awb'     => 'required|string',
    ]);

    // Handle Delhivery courier variations
    if (in_array($data['courier'], ['delhivery', 'delhivery_air', 'delhivery_b2c'])) {
        $data['courier'] = 'delhivery_b2c';
    }
        if (in_array($data['courier'], ['tekipost', 'amazon_2kg', 'delhivery_5kg','amazon_0_5kg','delhivery_10kg'])) {
        $data['courier'] = 'tekipost';
        }
        if (in_array($data['courier'], ['dtdc', 'dtdc_air', 'dtdc_surface_500gm','dtdc_surface_1kg'])) {
                $data['courier'] = 'dtdc';
            }

        if (in_array($data['courier'], ['boxd', 'delhivery_250gms', 'bluedart_surface_500gms','bluedart_air_500gms'])) {
                $data['courier'] = 'boxd';
            }

    $serviceKey = "courier.{$data['courier']}";

    if (! app()->bound($serviceKey)) {
        return redirect()->back()->with('error', 'Unknown courier');
    }
 
    $result = app($serviceKey)->cancelShipment($data['awb']);
    //   return $result; // For debugging, you can remove this later




    if ($request->courier === 'shiprocket') {
    if ($result && isset($result['status']) && $result['status'] === true) {
        return redirect()->route('seller.courier.Assigned')
            ->with('success', 'courier cancelled successfully! Response Code: ' . ($result['responseCode'] ?? 'N/A'));
    }

    return redirect()->route('seller.courier.Assigned')
        ->with('error', 'cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
}


    if ($request->courier === 'shadowfax') {
        if (!empty($result['status']) && $result['status'] === true) {
        return redirect()->route('seller.courier.Assigned')->with('success', 'shadowfax courier cancelled successfully! AWB: ' . $result['awb_number']);
        }
            return redirect()->route('seller.courier.Assigned')->with('error', 'shadowfax cancellation failed: ');
    }



  if ($request->courier === 'smartship') {
        if ($result && isset($result['status']) && $result['status'] === 1) {
            return redirect()->route('seller.courier.Assigned')->with('success', 'SmartShip courier cancelled successfully! AWB: ' . $data['awb']);
        }

        return redirect()->route('seller.courier.Assigned')->with('error', 'SmartShip cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
    }



if ($request->courier === 'boxd') {
    if ($result && isset($result['status']) && $result['status'] === true) {
        return redirect()->route('seller.courier.Assigned')
            ->with('success', 'courier cancelled successfully! Response Code: ' . ($result['responseCode'] ?? 'N/A'));
    }

    return redirect()->route('seller.courier.Assigned')
        ->with('error', 'Boxd cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
}



if ($request->courier === 'tekipost') {
    if ($result && isset($result['success']) && $result['success'] === true) {
        return redirect()->route('seller.courier.Assigned')
            ->with('success', 'Tekipost courier cancelled successfully! Order ID: ' . ($result['order_id'] ?? 'N/A'));
    }

    return redirect()->route('seller.courier.Assigned')
        ->with('error', 'Tekipost cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
}



  if ($request->courier === 'smartship') {
        if ($result && isset($result['status']) && $result['status'] === 1) {
            return redirect()->route('seller.courier.Assigned')->with('success', 'SmartShip courier cancelled successfully! AWB: ' . $data['awb']);
        }

        return redirect()->route('seller.courier.Assigned')->with('error', 'SmartShip cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
    }


    if ($result && (
        (isset($result['status']) && $result['status'] === true) || 
        (isset($result['responseCode']) && $result['responseCode'] == 200)
        )){

        $order = \App\Models\Order::where('awb_number', $data['awb'])->first();
        if ($order) {
            $order->order_status = 'cancelled';
            $order->save();
        }
        return redirect()->route('seller.courier.Assigned')->with('success', 'Courier cancelled successfully! AWB: ' . $data['awb']);
    }

    return redirect()->route('seller.courier.Assigned')->with('error', 'Courier cancellation failed: ' . ($result['message'] ?? 'Unknown error'));
}







// public function cancelAwb_bulk(Request $request)
// {
//     $data = $request->validate([
//         'order_ids' => 'required|array',
//         'order_ids.*' => 'required|integer|exists:orders,id',
//     ]);

//     // dd($data);

//     $orderIds = $data['order_ids'];

//     $orders = \App\Models\Order::whereIn('id', $orderIds)->get();

//     $successes = [];
//     $failures = [];

//     foreach ($orders as $order) {
//         $awb     = $order->awb_number;
//         $courier = $order->courier_id;
//         $serviceKey = "courier.{$courier}";

//         if (!app()->bound($serviceKey)) {
//             $failures[] = "AWB {$awb}: Unknown courier ({$courier})";
//             continue;
//         }

//         try {
//             $result = app($serviceKey)->cancelShipmentBulk([$awb]);

//             // return $result;
//             if ($courier === 'smartship') {
//                 if ($result && isset($result['status']) && $result['status'] === 1) {
//                     $order->order_status = 'cancelled';
//                     $order->save();
//                     $successes[] = "AWB {$awb}: Smartship cancelled.";
//                 } else {
//                     $failures[] = "AWB {$awb}: Smartship cancellation failed.";
//                 }
//                 continue;
//             }

//             if (
//                 (isset($result['status']) && $result['status'] === true) ||
//                 (isset($result['responseCode']) && $result['responseCode'] == 200)
//             ) {
//                 $order->order_status = 'cancelled';
//                 $order->save();
//                 $successes[] = "AWB {$awb}: Cancelled successfully.";
//             } else {
//                 $failures[] = "AWB {$awb}: Failed to cancel. " . ($result['message'] ?? 'Unknown error');
//             }
//         } catch (\Exception $e) {
//             $failures[] = "AWB {$awb}: Exception occurred - " . $e->getMessage();
//         }
//     }

//     return redirect()->route('seller.order')->with([
//         'success' => implode("\n", $successes),
//         'error'   => implode("\n", $failures),
//     ]);
// }






public function handleXpressbeesWebhook(Request $request)
{
//   dd($request->all());

    $raw = preg_replace('/[\x00-\x1F\x7F]/u', '', $request->getContent());

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('XpressBees Webhook: Invalid JSON', [
            'error' => json_last_error_msg(),
            'raw' => $raw,
        ]);
        return response()->json(['error' => 'Invalid JSON format'], 400);
    }

    // Check if data is an array of objects (multiple)
    if (!is_array($data)) {
        return response()->json(['error' => 'Invalid payload format'], 400);
    }

    // Agar single object hai to usko bhi array bana do for uniform processing
    if (isset($data['awb_number'])) {
        $data = [$data];
    }

    foreach ($data as $shipment) {
        // dd($shipment);
        $trackingNumber = $shipment['awb_number'] ?? null;
        $status = $shipment['status'] ?? null;

        // dd($trackingNumber);
        if (!$trackingNumber || !$status) {
            Log::warning('XpressBees Webhook: Missing fields', ['shipment' => $shipment]);
            continue; // skip this record and continue
        }

        if ($status === 'in transit') {
            // echo "In transit";die;
           $statusrr = 'transit'; 
        }else if ($status === 'out for delivery') {
              $statusrr = 'out for delivery';
        } else if ($status === 'delivered') {
           $statusrr = 'delivered';
           $delivered_date = date('Y-m-d'); // current date
        } else if ($status === 'rto') {
           $statusrr = 'rto';
        }
        else if ($status === 'exception') {
           $statusrr = 'NDR';
        }else if ($status === 'lost') {
           $statusrr = 'NDR';
        }else if ($status === 'damaged') {
           $statusrr = 'NDR';
        }else if ($status === 'rto in transit') {
           $statusrr = 'rto';
        }else if ($status === 'rto lost') {
           $statusrr = 'rto';
        }else if ($status === 'rto damaged') {
           $statusrr = 'rto';
        }else if ($status === 'rto delivered') {
           $statusrr = 'rto';
        }else if ($status === 'booked') {
           $statusrr = 'transit';
        } else {
            $statusrr = $status;
        }

        $order = Order::where('awb_number', $trackingNumber)->first();
        if ($order) {
            $order->shipping_status = $statusrr;
            if (isset($delivered_date)) {
                $order->delivered_date = $delivered_date;
            }
            $order->save();
        }
    }

    return response()->json(['status' => 'success'], 200);
}


// public function handleXpressbeesWebhook(Request $request)
// {
// //   dd($request->all());

//     $raw = preg_replace('/[\x00-\x1F\x7F]/u', '', $request->getContent());

//     $data = json_decode($raw, true);

//     if (json_last_error() !== JSON_ERROR_NONE) {
//         Log::error('XpressBees Webhook: Invalid JSON', [
//             'error' => json_last_error_msg(),
//             'raw' => $raw,
//         ]);
//         return response()->json(['error' => 'Invalid JSON format'], 400);
//     }

//     // Check if data is an array of objects (multiple)
//     if (!is_array($data)) {
//         return response()->json(['error' => 'Invalid payload format'], 400);
//     }

//     // Agar single object hai to usko bhi array bana do for uniform processing
//     if (isset($data['awb_number'])) {
//         $data = [$data];
//     }

//     foreach ($data as $shipment) {
//         // dd($shipment);
//         $trackingNumber = $shipment['awb_number'] ?? null;
//         $status = $shipment['status'] ?? null;

//         // dd($trackingNumber);
//         if (!$trackingNumber || !$status) {
//             Log::warning('XpressBees Webhook: Missing fields', ['shipment' => $shipment]);
//             continue; // skip this record and continue
//         }

//         if ($status === 'in transit') {
//             // echo "In transit";die;
//            $statusrr = 'transit'; 
//         }else if ($status === 'out for delivery') {
//               $statusrr = 'out for delivery';
//         } else if ($status === 'delivered') {
//            $statusrr = 'delivered';
//            $delivered_date = date('Y-m-d'); // current date
//         } else if ($status === 'rto') {
//            $statusrr = 'rto';
//         }
//         else if ($status === 'exception') {
//            $statusrr = 'NDR';
//         }else if ($status === 'lost') {
//            $statusrr = 'NDR';
//         }else if ($status === 'damaged') {
//            $statusrr = 'NDR';
//         }else if ($status === 'rto in transit') {
//            $statusrr = 'rto';
//         }else if ($status === 'rto lost') {
//            $statusrr = 'rto';
//         }else if ($status === 'rto damaged') {
//            $statusrr = 'rto';
//         }else if ($status === 'rto delivered') {
//            $statusrr = 'rto';
//         } else {
//             $statusrr = $status;
//         }

//         $order = Order::where('awb_number', $trackingNumber)->first();
//         if ($order) {
//             $order->shipping_status = $statusrr;
//             if (isset($delivered_date)) {
//                 $order->delivered_date = $delivered_date;
//             }
//             $order->save();
//         }
//     }

//     return response()->json(['status' => 'success'], 200);
// }







    /**
     * Handle incoming webhook payload.
     */
    public function handleOrderStatus(Request $request): JsonResponse
    {
            Log::info('vicky Received handleOrderStatus request', $request->all());

        // Validate incoming payload
        $data = $request->validate([
            'awb_number'              => 'required',
            'order_id'                => 'required',
        
        ]);

   
       $status = $request->status; 

       $statusrr = '';
        if ($status === 'recd_at_rev_hub' || $status === 'recd_at_fwd_dc' || $status === 'recd_at_fwd_hub') {
           $statusrr = 'transit'; 
        }else if ($status === 'ofd') {
              $statusrr = 'out for delivery';
        } else if ($status === 'delivered') {
           $statusrr = 'delivered';
        } else if ($status === 'rts' || $status === 'rts_in_process' || $status === 'rts_d' || $status === 'rts_nd') {
           $statusrr = 'rto';
        } else {
            //    $statusrr = 'rto';
        }

        $order = Order::find($data['order_id']);

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

     if ($order) {
            $order->shipping_status = $statusrr;
            $order->save();
        }

        return response()->json(['message' => 'Order status updated'], 200);
    }







public function handleDelhivery(Request $request): JsonResponse
    {
        // Validate the nested Delhivery structure
        $data = $request->validate([
            'Shipment.Status.Status'           => 'required|string',
            'Shipment.AWB'                     => 'required|string',
        ]);
        
        $awb        = $data['Shipment']['AWB'];
        $status     = $data['Shipment']['Status']['Status'];

        // Try to find your order by reference number or AWB
        $order = Order::where('awb_number', $awb)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }





        if ($status === 'In Transit') {
           $statusrr = 'transit'; 
        }else if ($status === 'Out for delivery') {
              $statusrr = 'out for delivery';
        } else if ($status === 'Delivered') {
           $statusrr = 'delivered';
        } else if ($status === 'RTO') {
           $statusrr = 'rto';
        }else if ($status === 'Cancelled') {
           $statusrr = 'cancelled';
        }else{

        }


     if ($order) {
            $order->shipping_status = $statusrr;
            $order->save();
        }

        return response()->json(['message' => 'Delhivery status updated'], 200);
    }



















}