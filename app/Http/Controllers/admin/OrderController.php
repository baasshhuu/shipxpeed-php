<?php

namespace App\Http\Controllers\selleradmin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\{Order, Buyer, OrderItem, OrderPackageDetail};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_number' => 'required',
                'unique_order_number' => 'required',
                'payment_type' => 'required',
                'order_amount' => 'required',
                'package_weight' => 'required',
                'package_length' => 'required',
                'package_breadth' => 'required',
                'package_height' => 'required',
                'request_auto_pickup' => 'required',
                'is_rto_different' => 'required',
                'courier_id' => 'nullable',
                'collectable_amount' => 'required',
                'consignee' => 'required',
                'pickup' => 'required',
                'rto' => 'required',
                'order_items' => 'required',
            ]);

            $bearerToken = env('XPRESSBEES_API_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NDYxNjQ2MTUsImp0aSI6IkR5a3d2dFZTR1JvKzdobWp0a2xOZ1RoRDlyUVp3eHc0S1QzeFhaS01PMkE9IiwibmJmIjoxNzQ2MTY0NjE1LCJleHAiOjE3NDYxNzU0MTUsImRhdGEiOnsidXNlcl9pZCI6IjEzNDgxMSIsInBhcmVudF9pZCI6IjAiLCJlbWFpbCI6IlNoaXB4cGVlZEBnbWFpbC5jb20ifX0.8_qE-n3XcGbD00NIRYMwoMT6VMai120MQiUVSFrzJ9IaWR6jUxMqa_UTgJaSSOUK9B64k8MjcPomBLip6gmbHQ');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken
            ])->post('https://shipment.xpressbees.com/api/shipments2', $validated);

            if ($response->successful()) {
                $responseData = $response->json();

                $order = $this->saveOrderToDatabase($validated, $responseData);

                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'data' => $responseData,
                    'order_id' => $order->id,
                    'tracking_number' => $responseData['tracking_number'] ?? null
                ]);
            } else {
                Log::error('XpressBees API Error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create order with XpressBees',
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Order Creation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    protected function saveOrderToDatabase($orderData, $apiResponse)
    {
        $seller = Auth::guard('seller')->user();


        $order = Order::create([
            'seller_id' => $seller->id,
            'order_number' => $orderData['order_number'],
            'unique_order_number' => $orderData['unique_order_number'],
            'payment_type' => $orderData['payment_type'],
            'order_amount' => $orderData['order_amount'],
            'shipping_charges' => $orderData['shipping_charges'] ?? 0,
            'cod_charges' => $orderData['cod_charges'] ?? 0,
            'discount' => $orderData['discount'] ?? 0,
            'collectable_amount' => $orderData['collectable_amount'],
            'status' => 'processing',
            'courier_id' => $orderData['courier_id'] ?? null,
            'tracking_number' => $apiResponse['tracking_number'] ?? null,
            'api_response' => json_encode($apiResponse),
        ]);


        $buyer = Buyer::create([
            'order_id' => $order->id,
            'name' => $orderData['consignee']['name'],
            'phone' => $orderData['consignee']['phone'],
            'address' => $orderData['consignee']['address'],
            'address_2' => $orderData['consignee']['address_2'] ?? null,
            'city' => $orderData['consignee']['city'],
            'state' => $orderData['consignee']['state'],
            'pincode' => $orderData['consignee']['pincode'],
        ]);


        foreach ($orderData['order_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'name' => $item['name'],
                'sku' => $item['sku'] ?? null,
                'quantity' => $item['qty'],
                'price' => $item['price'],
            ]);
        }

        OrderPackageDetail::create([
            'order_id' => $order->id,
            'weight' => $orderData['package_weight'],
            'length' => $orderData['package_length'],
            'breadth' => $orderData['package_breadth'],
            'height' => $orderData['package_height'],
            'package_type' => $orderData['package_type'] ?? 'standard',
        ]);

        return $order;
    }
}
