<?php

namespace App\Http\Controllers\selleradmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class TrackController extends Controller
{
    const TRACK_BASE_URL = 'https://shipment.xpressbees.com/';
    const TRACK_ENDPOINT = '/api/track';

    public function trackOrder(Request $request)
    {
        $request->validate([
            'awb_number' => 'required|string',
        ]);

        try {
            $client = new Client([
                'base_uri' => self::TRACK_BASE_URL,
                'verify' => false,
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getApiToken(),
                    'Accept'        => 'application/json',
                ],
            ]);

            $response = $client->post(self::TRACK_ENDPOINT, [
                'json' => [
                    'awb_number' => $request->input('awb_number'),
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            return response()->json([
                'success' => true,
                'message' => 'Tracking info retrieved',
                'data' => $body
            ]);
        } catch (RequestException $e) {
            Log::error('Track Order API Error', [
                'message' => $e->getMessage(),
                'request' => $e->getRequest() ? (string) $e->getRequest()->getBody() : null,
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getApiToken()
    {
        // You can reuse the same token logic as ShipmentController
        return env('XPRESSBEES_API_TOKEN', 'your-default-token');
    }
}
