<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SellerList;

class CourierRateManagerController extends Controller
{
    public function index()
    {
        $sellers = SellerList::select('id','name','email')->orderBy('name')->get();
        $couriers = \DB::table('couriers')->where('status',1)->orderBy('name')->get();
        return view('courier-rate-manager.index', compact('sellers','couriers'));
    }

    public function getCourierAccounts(Request $request)
    {
        $accounts = \DB::table('courier_accounts')
            ->where('courier_id', $request->courier_id)
            ->where('status', 1)
            ->get();
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function getSellerAssignment(Request $request)
    {
        $assignment = \DB::table('seller_courier_accounts as sca')
            ->join('courier_accounts as ca', 'ca.id', '=', 'sca.courier_account_id')
            ->where('sca.seller_id', $request->seller_id)
            ->where('ca.courier_id', $request->courier_id)
            ->where('sca.status', 1)
            ->select('sca.*', 'ca.name as account_name')
            ->first();
        return response()->json(['success' => true, 'data' => $assignment]);
    }

    public function assignSeller(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|integer',
            'courier_account_id' => 'required|integer',
        ]);

        $account = \DB::table('courier_accounts')->where('id', $request->courier_account_id)->first();
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'Courier account not found'], 404);
        }

        \DB::beginTransaction();
        try {
            $siblingIds = \DB::table('courier_accounts')->where('courier_id', $account->courier_id)->pluck('id');
            \DB::table('seller_courier_accounts')
                ->where('seller_id', $request->seller_id)
                ->whereIn('courier_account_id', $siblingIds)
                ->update(['status' => 0, 'updated_at' => now()]);

            $existing = \DB::table('seller_courier_accounts')
                ->where('seller_id', $request->seller_id)
                ->where('courier_account_id', $request->courier_account_id)
                ->first();

            if ($existing) {
                \DB::table('seller_courier_accounts')->where('id', $existing->id)->update([
                    'status' => 1,
                    'courier_slab_id' => $request->courier_slab_id ?? $existing->courier_slab_id,
                    'updated_at' => now(),
                ]);
            } else {
                \DB::table('seller_courier_accounts')->insert([
                    'seller_id' => $request->seller_id,
                    'courier_account_id' => $request->courier_account_id,
                    'courier_slab_id' => $request->courier_slab_id,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Seller assigned to courier account successfully']);
        } catch (\Throwable $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSlabs(Request $request)
    {
        $slabs = \DB::table('courier_slabs')
            ->where('courier_account_id', $request->courier_account_id)
            ->where('status', 1)
            ->get();
        return response()->json(['success' => true, 'data' => $slabs]);
    }

    public function getSlabRates(Request $request)
    {
        $rates = \DB::table('courier_slab_rates')
            ->where('courier_slab_id', $request->courier_slab_id)
            ->get();
        return response()->json(['success' => true, 'data' => $rates]);
    }

    public function saveSlab(Request $request)
    {
        $request->validate([
            'courier_account_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'rates' => 'required|array',
        ]);

        \DB::beginTransaction();
        try {
            $slabId = \DB::table('courier_slabs')->insertGetId([
                'courier_account_id' => $request->courier_account_id,
                'name' => $request->name,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->rates as $zone => $rate) {
                \DB::table('courier_slab_rates')->insert([
                    'courier_slab_id' => $slabId,
                    'zone' => $zone,
                    'cod_price' => $rate['cod_price'] ?? 0,
                    'cod_fix_price' => $rate['cod_fix_price'] ?? 0,
                    'prepaid_price' => $rate['prepaid_price'] ?? 0,
                    'prepaid_fix_price' => $rate['prepaid_fix_price'] ?? 0,
                    'cod_charge_percent' => $rate['cod_charge_percent'] ?? 0,
                    'rto_credit' => $rate['rto_credit'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Slab created successfully', 'slab_id' => $slabId]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
