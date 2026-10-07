<?php
   
namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\PriceSetting;

class RateCardController extends Controller
{

public function checkRate(Request $request)
{
    // dd($request);
    $seller = auth()->guard('seller')->user();
    if (!$seller || $seller->status != 1) {
        return redirect()->back()->withErrors(['error' => 'You must be logged in as a seller with an active account.']);
    }
    
    $sellerId = Auth::guard('seller')->id();
    $PriceSetting = PriceSetting::where('seller_id', $sellerId)->first();
    // Seller shipping charge %; default 30 agar nahi mila
    $sellerprice = $PriceSetting ? $PriceSetting->shipping_charge : 30;
    
    $validated = $request->validate([
        'origin' => 'required|string',
        'destination' => 'required|string',
        'payment_type' => 'required|in:cod,prepaid',
        'order_amount' => 'required|string',
        'weight' => 'required|string',
        'length' => 'required|string',
        'breadth' => 'required|string',
        'height' => 'required|string',
    ]);

    $token = env('XPRESSBEES_API_TOKEN');
    // dd($token);

    $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])
        ->withoutVerifying()
        ->timeout(30)
        ->post('https://shipment.xpressbees.com/api/courier/serviceability', $validated);

    \Log::info('XpressBees API Response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);

    if ($response->successful()) {
        $data = $response->json()['data'] ?? [];

        // Convert percentage to multiplier
        $multiplier = 1 + ($sellerprice / 100);
        // dd($multiplier);

        // Apply seller % markup dynamically
        $updatedData = collect($data)->map(function ($item) use ($multiplier) {
            $item['freight_charges'] = round($item['freight_charges'] * 2);
            $item['cod_charges'] = round($item['cod_charges'] *  2);
            $item['total_charges'] = round($item['total_charges'] * $multiplier, 2);
            return $item;
        })->toArray();

        return redirect()->back()->with([
            'rate_data' => $updatedData,
            'success' => 'Rate fetched with markup successfully!',
        ]);
    } else {
        return redirect()->back()->withErrors(['error' => 'Failed to fetch rate.']);
    }
}


}
