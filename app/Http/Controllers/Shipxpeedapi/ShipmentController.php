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

class ShipmentController extends Controller
{



    public function createShipment(Request $request)
    {
        //  dd($request);
        try {



        $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success'=>false,'message'=>'Authorization token required'], 401);
            }

            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success'=>false,'message'=>'Invalid or expired token'], 401);
            }
        $sellerid = $seller->id;


            if (!$seller || $seller->status != 1) {
                return redirect()->back()->with('error', 'Unauthorized or inactive seller.');
            }

            $delhivery_b2c = $this->delhivery_b2c($request);
            $delhivery_b2c_air = $this->delhivery_b2c_air($request);
            $order = new \App\Models\Order();

            $order->delhivery_b2c_air        = json_encode($delhivery_b2c_air);
            $order->delhivery_b2c        = json_encode($delhivery_b2c);
            $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
            $order->order_number = 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1);

            $order->seller_id           = $seller->id;
            // $order->awb_number          = $responseData['data']['awb_number'] ?? null;
            $order->courier_id          = $request->courier_id;
            $order->payment_type        = $request->payment_type;
            $order->order_amount        = $request->collectable_amount ?? 0;
            $order->shipping_charges    = $request->shipping_charges ?? 0;
            $order->cod_charges         = $request->cod_charges ?? 0;
            $order->discount            = $request->discount ?? 0;
            $order->collectable_amount  = $request->collectable_amount ?? 0;
            $order->package_type        = $request->package_type;
            $order->package_weight      = $request->package_weight;
            $order->package_length      = $request->package_length;
            $order->package_breadth     = $request->package_breadth;
            $order->package_height      = $request->package_height;
            $order->consignee           = $request->consignee;
            $order->pickup              = $request->pickup;
            $order->rto                 = $request->rto;
            $order->order_items         = $request->order_items;
            $order->save();

            // return redirect()->back()->with('success', 'Shipment created successfully');

            return redirect()->back()->with('success', 'Shipment created successfully. Order ID: ' . $order->id);


            // return redirect()->back()->with('success', 'Shipment created successfully. AWB: ' . ($order->awb_number ?? 'N/A'));
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // dd($e->getMessage());
            return redirect()->back()->with('error', 'API request failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            // dd($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    protected function delhivery_b2c(Request $request)
    {

        $seller = Auth::guard('seller')->user();
        $gst = $seller->gst_no ?? "";
        $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
        // $order->order_number = '#' . ($lastOrder ? ($lastOrder->id + 1) : 1);

        $payload = [
            "shipments" => [
                [
                    "name" => $request['consignee']['name'],
                    "add" => $request['consignee']['address'] . ', ' . $request['consignee']['address_2'],

                    // "add" => $request['consignee']['address'],
                    // "pin" => $request['consignee']['pincode'],
                    "pin" => (int) $request['consignee']['pincode'],

                    "city" => $request['consignee']['city'],
                    "state" => $request['consignee']['state'],
                    "country" => "India",
                    "phone" => $request['consignee']['phone'],
                    "order" => 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1),
                    "payment_mode" => $request['payment_type'], // Prepaid or COD
                    "cod_amount" => (string) $request['collectable_amount'], // If COD
                    "total_amount" => (string) $request['collectable_amount'],
                    "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
                    "hsn_code" => "6403", // You can pass per product if required
                    "quantity" => array_sum(array_column($request['order_items'], 'qty')),
                    "seller_gst_tin" => $gst, // Static or from your config
                    "seller_add" => $request['pickup']['address'],
                    "seller_name" => $request['pickup']['name'],
                    "seller_inv" => $request['unique_order_number'], // or your invoice number
                    "shipment_width" => $request['package_breadth'],
                    "shipment_height" => $request['package_height'],
                    "weight" => $request['package_weight'],
                    "shipping_mode" => "Surface",
                    "address_type" => "home",
                    "waybill" => "",
                    "order_date" => now()->format('Y-m-d'),
                ]
            ],
            "pickup_location" => [
                "name" => $request['pickup']['warehouse_name'], // Must match registered warehouse name exactly
                "add" => $request['pickup']['address'],
                "city" => $request['pickup']['city'],
                "pin_code" => (int) $request['pickup']['pincode'],
                "country" => "India",
                "phone" => $request['pickup']['phone']
            ]
        ];


        return $payload;
    }





    protected function delhivery_b2c_air(Request $request)
    {

        $seller = Auth::guard('seller')->user();
        $gst = $seller->gst_no ?? "";
        $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();

        $payload = [
            "shipments" => [
                [
                    "name" => $request['consignee']['name'],
                    // "add" => $request['consignee']['address'],
                    "add" => $request['consignee']['address'] . ', ' . $request['consignee']['address_2'],

                    // "pin" => $request['consignee']['pincode'],
                    "pin" => (int) $request['consignee']['pincode'],

                    "city" => $request['consignee']['city'],
                    "state" => $request['consignee']['state'],
                    "country" => "India",
                    "phone" => $request['consignee']['phone'],
                    "order" => 'SPX#' . ($lastOrder ? ($lastOrder->id + 1) : 1),
                    "payment_mode" => $request['payment_type'], // Prepaid or COD
                    "cod_amount" => (string) $request['collectable_amount'], // If COD
                    "total_amount" => (string) $request['collectable_amount'],
                    "products_desc" => implode(', ', array_column($request['order_items'], 'name')),
                    "hsn_code" => "6403", // You can pass per product if required
                    "quantity" => array_sum(array_column($request['order_items'], 'qty')),
                    "seller_gst_tin" => $gst, // Static or from your config
                    "seller_add" => $request['pickup']['address'],
                    "seller_name" => $request['pickup']['name'],
                    "seller_inv" => $request['unique_order_number'], // or your invoice number
                    "shipment_width" => $request['package_breadth'],
                    "shipment_height" => $request['package_height'],
                    "weight" => $request['package_weight'],
                    "shipping_mode" => "Express",
                    "address_type" => "home",
                    "waybill" => "",
                    "order_date" => now()->format('Y-m-d'),
                ]
            ],
            "pickup_location" => [
                "name" => $request['pickup']['warehouse_name'], // Must match registered warehouse name exactly
                "add" => $request['pickup']['address'],
                "city" => $request['pickup']['city'],
                "pin_code" => (int) $request['pickup']['pincode'],
                "country" => "India",
                "phone" => $request['pickup']['phone']
            ]
        ];


        return $payload;
    }
}
