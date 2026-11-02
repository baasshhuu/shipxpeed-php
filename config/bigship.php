<?php

/*
|--------------------------------------------------------------------------
| Bigship (Unified Outbound API) - used for domestic B2B shipments
|--------------------------------------------------------------------------
| Credentials are normally saved from Admin > Bigship B2B (stored encrypted in
| app_settings). Values in .env override the admin page:
|
|   BIGSHIP_USERNAME=<bigship login email>
|   BIGSHIP_PASSWORD=<bigship login password>
|   BIGSHIP_ACCESS_KEY=<Bigship > API Integration > Access Key>
|
| Bigship's Unified Outbound API has no international endpoints, so only domestic B2B is wired here.
*/

return [
    'base_url' => env('BIGSHIP_BASE_URL', 'https://api.bigship.direct/'),

    'username'   => env('BIGSHIP_USERNAME'),
    'password'   => env('BIGSHIP_PASSWORD'),
    'access_key' => env('BIGSHIP_ACCESS_KEY'),

    'timeout' => (int) env('BIGSHIP_TIMEOUT', 45),

    // Seller price = Bigship total x (1 + markup%) + GST. Admin page can override markup.
    'markup_percent' => (float) env('BIGSHIP_MARKUP_PERCENT', 20),
    'gst_percent'    => (float) env('BIGSHIP_GST_PERCENT', 18),

    // segment_type used when registering pickup warehouses (doc: domestic_b2b / local);
    // the service falls back to "local" if Bigship rejects the first one.
    'warehouse_segment' => env('BIGSHIP_WAREHOUSE_SEGMENT', 'domestic_b2b'),

    // Minimum wallet balance kept after booking (same rule as the B2C couriers)
    'min_wallet' => (float) env('BIGSHIP_MIN_WALLET', 150),

    // E-way bill needed at or above this invoice value
    'ewaybill_threshold' => 50000,
];
