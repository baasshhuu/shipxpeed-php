<?php

return [
    // Admin GraphQL API version. 2026-10 is the latest stable as of Oct 2026;
    // Shopify supports each version for 12 months, so bump this once a year.
    'api_version' => env('SHOPIFY_API_VERSION', '2026-10'),

    // Each seller creates their own app in Shopify's Dev Dashboard (custom distribution,
    // no review) and pastes its Client ID + Secret into the panel. They must enter this
    // exact URL under "Allowed redirection URL(s)" in that app. Same URL for every seller.
    'redirect_uri'  => env('SHOPIFY_REDIRECT_URI', rtrim(env('APP_URL', ''), '/') . '/auth/shopify/callback'),
    'app_url'       => env('SHOPIFY_APP_URL', rtrim(env('APP_URL', ''), '/')),

    'scopes' => env('SHOPIFY_SCOPES', implode(',', [
        'read_orders',
        'write_orders',
        'read_fulfillments',
        'write_fulfillments',
        'read_products',
        'read_customers',
        'read_merchant_managed_fulfillment_orders',
        'write_merchant_managed_fulfillment_orders',
        'read_assigned_fulfillment_orders',
        'write_assigned_fulfillment_orders',
    ])),

    // Public tracking page sent to Shopify (and so to the buyer). {awb} is replaced.
    'tracking_url' => env('SHOPIFY_TRACKING_URL', rtrim(env('APP_URL', ''), '/') . '/order-details?awb={awb}'),

    // Base URL Shopify should call for webhooks (must be public HTTPS).
    'webhook_base' => env('SHOPIFY_WEBHOOK_BASE', rtrim(env('APP_URL', ''), '/')),

    // How far back the first sync goes when the seller does not pick a date.
    'default_lookback_days' => 7,
];
