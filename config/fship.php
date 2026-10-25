<?php

/*
|--------------------------------------------------------------------------
| Fship courier aggregator (https://fship.in)
|--------------------------------------------------------------------------
| API guide v1.2.3.3. Every request carries the client key in the
| "signature" header. Production key: Fship dashboard > Settings >
| API Details > Client Key. Keep the real key in .env only.
|
|   FSHIP_ENV=production            # or "staging" to use the Fship sandbox
|   FSHIP_API_KEY=<client key>
|   FSHIP_MARKUP_PERCENT=20         # used only when no zone rate card exists
*/

return [
    'env' => env('FSHIP_ENV', 'production'),

    'base_url' => env('FSHIP_ENV', 'production') === 'staging'
        ? env('FSHIP_STAGING_URL', 'https://capi-qc.fship.in')
        : env('FSHIP_BASE_URL', 'https://capi.fship.in'),

    // Public sandbox key published in Fship's own API guide (staging only).
    'api_key' => env('FSHIP_ENV', 'production') === 'staging'
        ? env('FSHIP_STAGING_KEY', '085c36066064af83c66b9dbf44d190d40feec79f437bc1c1cb')
        : env('FSHIP_API_KEY'),

    'timeout' => (int) env('FSHIP_TIMEOUT', 30),

    // Seller price when admin has not set a zone rate card for a Fship courier:
    // (Fship freight [+ COD charge]) x (1 + markup%) + GST
    'markup_percent' => (float) env('FSHIP_MARKUP_PERCENT', 20),
    'gst_percent'    => (float) env('FSHIP_GST_PERCENT', 18),

    // Fship rejects orders without a buyer email; used when the order has none.
    'fallback_email' => env('FSHIP_FALLBACK_EMAIL', 'support@shipxpeed.com'),

    // Register the courier pickup right after the AWB is created.
    'auto_pickup' => (bool) env('FSHIP_AUTO_PICKUP', true),

    // Seller whose zone rate card is used when a seller has none of their own
    // (same convention as the other aggregators in this panel).
    'default_rate_seller_id' => (int) env('FSHIP_DEFAULT_RATE_SELLER_ID', 14),

    // Prefix used for rate-card / seller-activation keys, e.g. "Fship_DTDC_Surface".
    'rate_key_prefix' => 'Fship_',
];
