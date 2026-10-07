<?php

namespace App\Http\Controllers\Shipxpeedapi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\SellerList;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{

        public function createWarehouse(Request $request)
        {
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['success'=>false,'message'=>'Authorization token required'], 401);
            }

            $seller = SellerList::where('api_token', $token)->first();
            if (!$seller) {
                return response()->json(['success'=>false,'message'=>'Invalid or expired token'], 401);
            }

            // ✅ Validation
            $validator = Validator::make($request->all(), [
                'phone'            => 'required|numeric|digits_between:10,15',
                'name'             => 'required|string|max:150',
                'pincode'           => 'required|numeric|digits:6',
                'city'             => 'required|string|max:100',
                'state'            => 'required|string|max:100',
                'country'          => 'required|string|max:100',
                'address'          => 'required|string|max:255',
                'address_two'      => 'nullable|string|max:255',

            ], [
                'phone.required' => 'Phone number is required',
                'phone.numeric'  => 'Phone number must contain only numbers',
                'phone.digits_between' => 'Phone number must be between 10 to 15 digits',
                'city.required'  => 'City is required',
                'name.required'  => 'Warehouse name is required',
                'pincode.required'   => 'Pincode is required',
                'pincode.digits'     => 'Pincode must be 6 digits',
                'address.required'=> 'Address is required',
                'country.required'=> 'Country is required',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success'=>false,
                    'message'=>'Validation errors',
                    'errors'=>$validator->errors()
                ], 422);
            }

            // ✅ Warehouse creation
            $warehouse = new Warehouse();
            $warehouse->seller_id       = $seller->id;
            $warehouse->name            = $request->name;
            $warehouse->phone           = $request->phone;
            $warehouse->pincode         = $request->pincode;
            $warehouse->city            = $request->city;
            $warehouse->state           = $request->state;
            $warehouse->country         = $request->country;
            $warehouse->address_line1   = $request->address;
            $warehouse->address_line2   = $request->address_two ?? '';
            $warehouse->registered_name   = $request->name;
            $warehouse->address_title  = $request->address;
            $warehouse->return_address   = $request->address;
            $warehouse->return_pin           = $request->pincode;
            $warehouse->return_city = $request->city;
            $warehouse->return_state  = $request->state;
            $warehouse->return_country      = $request->country;

            $warehouse->save();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse created successfully!',
                'data'    => $warehouse
            ], 201);
        }
}















