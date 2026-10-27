<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Support\CourierBrand;
use App\Support\ShipRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard "Calculate your shipping rates" card.
 * Same prices as the Rate calculator and Ship Now (seller's own rate card, else the default card, incl. GST).
 */
class QuickRateController extends Controller
{
    public function __invoke(Request $request)
    {
        $seller = auth()->guard('seller')->user();
        if (!$seller) {
            return response()->json(['ok' => false, 'message' => 'Please log in again.'], 401);
        }

        $v = $request->validate([
            'origin' => 'required|digits:6',
            'destination' => 'required|digits:6',
            'weight' => 'nullable|numeric|min:1|max:100000',
            'payment_type' => 'nullable|in:cod,prepaid',
            'order_amount' => 'nullable|numeric|min:0',
        ]);

        $cod = ($v['payment_type'] ?? 'prepaid') === 'cod';
        $grams = (float) ($v['weight'] ?? 500);

        try {
            $rows = ShipRate::quote((int) $seller->id, [
                'origin' => $v['origin'],
                'destination' => $v['destination'],
                'payment_type' => $cod ? 'cod' : 'prepaid',
                'order_amount' => (float) ($v['order_amount'] ?? 0),
                'weight_grams' => $grams,
            ]);
        } catch (\Throwable $e) {
            Log::warning('QuickRate failed: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Could not fetch rates right now. Please try again.'], 500);
        }

        $zone = null;
        $out = [];
        foreach ($rows as $r) {
            $name = CourierBrand::cleanName($r['name'] ?? 'Courier');
            $zone = $zone ?? ($r['zone'] ?? null);
            $out[] = [
                'name' => $name,
                'logo' => asset(CourierBrand::sellerLogo($name)),
                'zone' => $r['zone'] ?? null,
                'total' => round((float) $r['total_charges'], 2),
                'cod' => round((float) ($r['cod_charges'] ?? 0), 2),
                'weight' => (float) ($r['chargeable_weight'] ?? $grams),
            ];
        }

        return response()->json([
            'ok' => true,
            'zone' => $zone,
            'rates' => $out,
            'message' => $out ? null : 'No courier is available for this route right now.',
        ]);
    }
}
