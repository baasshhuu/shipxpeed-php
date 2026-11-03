<?php

/*
|--------------------------------------------------------------------------
| ShipCorrect courier aggregator (https://shipcorrect.com)
|--------------------------------------------------------------------------
| Seller panel > API Documentation gives the secret API key. Every request
| sends it in the X-API-KEY header (and in the body where the docs ask).
| Keep the real key in .env only (or save it in Admin > ShipCorrect).
|
|   SHIPCORRECT_API_KEY=<secret api key>
|   SHIPCORRECT_MARKUP_PERCENT=20     # used only when no zone rate card exists
*/

return [
    'base_url' => rtrim(env('SHIPCORRECT_BASE_URL', 'https://shipcorrect.com'), '/'),

    'api_key' => env('SHIPCORRECT_API_KEY'),

    'timeout' => (int) env('SHIPCORRECT_TIMEOUT', 40),

    // Seller price when admin has not set a zone rate card for a ShipCorrect courier:
    // ShipCorrect price before GST x (1 + markup%) + GST
    'markup_percent' => (float) env('SHIPCORRECT_MARKUP_PERCENT', 20),
    'gst_percent'    => (float) env('SHIPCORRECT_GST_PERCENT', 18),

    // Seller whose zone rate card is used when a seller has none of their own.
    'default_rate_seller_id' => (int) env('SHIPCORRECT_DEFAULT_RATE_SELLER_ID', env('RATE_CARD_DEFAULT_SELLER_ID', 14)),

    // Prefix used for rate-card / seller-activation keys, e.g. "ShipCorrect_Ekart_Surface".
    'rate_key_prefix' => 'ShipCorrect_',

    /*
     | Couriers ShipCorrect accepts in book_shipment_b2c.php (courier_name).
     | Rate API names ("Ekart Surface 0.5kg") are matched to these by dropping the weight suffix.
     */
    'couriers' => [
        'EKART SURFACE',
        'DELHIVERY SURFACE',
        'DELHIVERY AIR',
        'DTDC SURFACE',
        'DTDC AIR',
        'SHADOWFAX SURFACE',
        'BLUEDART SURFACE',
        'BLUEDART AIR',
        'BLUEDART EXPRESS',
        'XPRESSBEES SURFACE',
        'XPRESSBEES AIR',
    ],
];
