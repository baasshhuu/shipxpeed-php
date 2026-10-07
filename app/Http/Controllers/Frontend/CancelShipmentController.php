<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CancelShipmentController extends Controller
{
    public function checkRate(Request $request)
    {
        $validated = $request->validate([
            'awb' => 'required|string'
        ]);

        $token = env('XPRESSBEES_API_TOKEN');

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])
            ->withoutVerifying()
            ->timeout(30)
            ->post('https://shipment.xpressbees.com/api/shipments2/cancel', $validated);

        \Log::info('XpressBees API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->successful()) {
            return redirect()->back()->with([
                'rate_data' => $response->json()['data'] ?? [],
                'success' => 'Shipment Cancelled successfully!',
            ]);
        } else {
            $errorMessage = $response->json()['message'] ?? 'Failed to cancel shipment.';
            return redirect()->back()->withErrors(['error' => $errorMessage]);
        }

    }

}
