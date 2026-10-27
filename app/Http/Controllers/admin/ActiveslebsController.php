<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ActicvSleb;
use App\Models\SellerList;
use App\Support\CourierAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Admin > Price Setting > Courier Status.
 * Shows only integrated couriers. "All Sellers (default)" sets each courier's default;
 * picking a seller lets you switch a courier on/off just for them.
 */
class ActiveslebsController extends Controller
{
    public function add()
    {
        if (request('refresh')) {
            Cache::forget('ratecard_keys_shipclues');
            Cache::forget('ratecard_keys_fship');
        }

        $SellerList = SellerList::orderBy('name')->get();
        $sellerParam = request('seller_id', 'all');
        $selectedSeller = $sellerParam !== 'all' ? SellerList::find($sellerParam) : null;
        if (!$selectedSeller) {
            $sellerParam = 'all';
        }

        $groups = CourierAccess::groups();
        $overrides = $selectedSeller ? CourierAccess::sellerOverrides($selectedSeller->id) : [];

        // How many sellers have their own switch per courier (shown in default mode).
        $customCounts = ActicvSleb::whereIn('LogisticProvider', CourierAccess::keys())
            ->selectRaw('LogisticProvider, COUNT(DISTINCT seller_id) as n')
            ->groupBy('LogisticProvider')->pluck('n', 'LogisticProvider')->all();

        return view('admin.Activeslebs.index', compact(
            'SellerList', 'selectedSeller', 'sellerParam', 'groups', 'overrides', 'customCounts'
        ));
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'seller_id' => 'required',
            'courier'   => 'required|string|max:120',
            'status'    => 'required|in:0,1',
        ]);

        $courier = $request->courier;
        if (!in_array($courier, CourierAccess::keys(), true)) {
            return response()->json(['success' => false, 'message' => 'This courier is not integrated.'], 422);
        }
        $on = (string) $request->status === '1';

        // Default for every seller: save it and drop per-seller switches so it truly applies to all.
        if ($request->seller_id === 'all') {
            CourierAccess::setDefault($courier, $on);
            $cleared = ActicvSleb::where('LogisticProvider', $courier)->count();
            ActicvSleb::where('LogisticProvider', $courier)->delete();

            return response()->json([
                'success' => true,
                'message' => ($on ? 'Live' : 'Off') . ' for all sellers' . ($cleared ? " ({$cleared} custom switch(es) reset)" : ''),
            ]);
        }

        $seller = SellerList::find($request->seller_id);
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Seller not found.'], 404);
        }

        ActicvSleb::where('seller_id', $seller->id)->where('LogisticProvider', $courier)->delete();
        ActicvSleb::create([
            'seller_id'        => $seller->id,
            'LogisticProvider' => $courier,
            'status'           => $on ? '1' : '0',
        ]);

        return response()->json(['success' => true, 'message' => ($on ? 'Live' : 'Off') . ' for ' . $seller->name]);
    }

    /** Remove a seller's own switch so the courier follows the default again. */
    public function resetStatus(Request $request): JsonResponse
    {
        $request->validate(['seller_id' => 'required|integer', 'courier' => 'required|string|max:120']);

        ActicvSleb::where('seller_id', $request->seller_id)->where('LogisticProvider', $request->courier)->delete();

        return response()->json([
            'success' => true,
            'default' => CourierAccess::isDefaultOn($request->courier),
            'message' => 'Now follows the default',
        ]);
    }
}
