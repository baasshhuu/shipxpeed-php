<?php

return [

    // Cashfree Secure ID (KYC) — existing keys
    'app_id' => env('CASHFREE_APP_ID'),

    'secret_key' => env('CASHFREE_SECRET_KEY'),

    'environment' => env('CASHFREE_ENV', 'sandbox'),

    // Cashfree Payment Gateway — seller wallet recharge (separate keys from KYC)
    'pg' => [
        'app_id'      => env('CASHFREE_PG_APP_ID'),
        'secret_key'  => env('CASHFREE_PG_SECRET_KEY'),
        'environment' => env('CASHFREE_PG_ENV', 'sandbox'), // sandbox | production
        'min_amount'  => env('WALLET_RECHARGE_MIN', 1),
    ],

];
