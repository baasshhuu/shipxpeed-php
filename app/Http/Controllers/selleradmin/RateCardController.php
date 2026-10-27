<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use App\Support\ShipRate;
use Illuminate\Http\Request;

/**
 * Seller rate calculator: prices come from the seller's zone rate card
 * (their own rates, else the default rate card) — the same price they are debited when shipping.
 */
class RateCardController extends Controller
{
    public function checkRate(Request $request)
    {
        $seller = auth()->guard('seller')->user();
        if (!$seller || $seller->status != 1) {
            return redirect()->back()->withErrors(['error' => 'You must be logged in as a seller with an active account.']);
        }

        $v = $request->validate([
            'origin' => 'required|digits:6',
            'destination' => 'required|digits:6',
            'payment_type' => 'required|in:cod,prepaid',
            'order_amount' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:1',
            'length' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'originState' => 'nullable|string|max:80',
            'destinationState' => 'nullable|string|max:80',
        ]);

        // Same weight basis as shipping an order (package weight in grams)
        $grams = (float) $v['weight'];

        $rates = ShipRate::quote((int) $seller->id, [
            'origin' => $v['origin'],
            'destination' => $v['destination'],
            'origin_state' => $v['originState'] ?? null,
            'destination_state' => $v['destinationState'] ?? null,
            'payment_type' => $v['payment_type'],
            'order_amount' => (float) $v['order_amount'],
            'weight_grams' => $grams,
            'length' => $v['length'] ?? 10,
            'breadth' => $v['breadth'] ?? 10,
            'height' => $v['height'] ?? 10,
        ]);

        if (!$rates) {
            return redirect()->back()->withInput()->withErrors(['error' => 'No courier is available for this route right now.']);
        }

        return redirect()->back()->withInput()->with([
            'rate_data' => $rates,
            'success' => 'Rates for your account (incl. 18% GST).',
        ]);
    }
}
