<?php
// app/Services/DelhiveryService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DelhiveryService implements CourierServiceInterface
{


    /**
     * @param  array  $params  // origin, destination, payment_type, order_amount, weight, length, breadth, height
     * @return array           // a list of normalized entries
     */

    protected function fetchAuthToken(): string
    {
        $cfg      = config('courier_services.delhivery');
        // dd($cfg);
        $cacheKey = 'xpressbees.token';

        //return Cache::remember($cacheKey, $cfg['token_ttl'], function() use ($cfg) {
            $resp = Http::post($cfg['login_url'], [
                'email' => "SHIPXPEED3645B2B",
                'password' => "Welcome@123",
            ]);

            $json = $resp->json();
            if (! $resp->successful() || empty($json['data'])) {
                throw new \RuntimeException("XpressBees login failed: {$resp->body()}");
            }
        dd( $json['jwt']);
            return $json['data'];
        //});
    }



    public function getServiceability(array $params): array
    {
        // dd($params);
        $cfg = config('courier_services.delhivery');

        $response = Http::get($cfg['service_url'], [
            'pickup_postcode'   => $params['origin'],
            'delivery_postcode' => $params['destination'],
            //  'token'             => "67f98dd67376fb9655edbdb0ad29027c90e12f88",
            'token'             => $cfg['token'],
        ]);

        if (! $response->successful()) {
            return [];
        }

        $json = $response->json();

        // If not serviceable, return empty
        if (empty($json['serviceable']) || ! $json['serviceable']) {
            return [];
        }

        // Delhivery serviceability returns only boolean; we create a single slab entry
        return [[
            'serviceabilityId' => $params['origin'] . '_' . $params['destination'],
            'courierName'      => 'Delhivery',
            'courierCharge'    => 0.0,
            'freightCharges'   => 0.0,
            'codCharge'        => ($params['payment_type'] === 'cod' ? 0.0 : 0.0),
            'minWeight'        => $params['weight'],
            'volWeight'        => $params['weight'],
        ]];
    }
}