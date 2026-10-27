<?php

/*
|--------------------------------------------------------------------------
| WhatsApp (AiSensy) — OTP + buyer shipment alerts
|--------------------------------------------------------------------------
| Everything here can be overridden from the admin panel (WhatsApp settings
| page, stored in app_settings). Values below are the defaults.
|
| AiSensy side: create + get approved one template per event, then create an
| "API Campaign" for each template and set it Live. The campaign NAME goes
| below (or on the admin page).
*/

return [

    'api_url' => env('AISENSY_API_URL', 'https://backend.aisensy.com/campaign/t1/api/v2'),

    // AiSensy › Manage › API Key. Prefer .env; the admin page can also store it.
    'api_key' => env('AISENSY_API_KEY'),

    'timeout' => 12,

    // Public base URL used in links inside messages (tracking + NDR response page)
    'public_url' => rtrim(env('WHATSAPP_PUBLIC_URL', 'https://shipxpeed.tech'), '/'),

    'otp' => [
        // AiSensy API campaign for an Authentication-category template: "{{1}} is your verification code." + copy-code button
        'campaign'        => env('AISENSY_OTP_CAMPAIGN', 'shipxpeed_otp'),
        'length'          => 6,
        'ttl_minutes'     => 10,
        'max_attempts'    => 5,   // wrong codes before the code is burned
        'resend_seconds'  => 45,  // wait before another code can be sent to the same number
        'max_per_hour'    => 6,   // codes per number per hour
    ],

    /*
     | Buyer shipment alerts.
     | format "full"  = new templates, 6 body variables:
     |   {{1}} buyer name {{2}} seller brand {{3}} order no. {{4}} courier {{5}} AWB {{6}} link (NDR = response link)
     | format "basic" = the older approved templates, 3 body variables: {{1}} buyer name {{2}} order no. {{3}} AWB
     | campaign = AiSensy API campaign name ('' = not set up yet, nothing sent)
     | rate = default charge (₹) to the seller per message; admin can change it and set per-seller rates.
     | Defaults use the 4 API campaigns already live on the AiSensy account (free plan can't add new ones).
     */
    'events' => [
        'booked'           => ['label' => 'Order shipped (AWB assigned)', 'campaign' => '',                      'format' => 'full',  'rate' => 2],
        'picked_up'        => ['label' => 'Picked up',                    'campaign' => '',                      'format' => 'full',  'rate' => 0],
        'in_transit'       => ['label' => 'In transit',                   'campaign' => 'intransitorder',        'format' => 'basic', 'rate' => 2],
        'out_for_delivery' => ['label' => 'Out for delivery',             'campaign' => 'out_for_delivery_test', 'format' => 'basic', 'rate' => 2],
        'ndr'              => ['label' => 'Delivery failed (NDR)',        'campaign' => 'ndrqorders',            'format' => 'basic', 'rate' => 2],
        'delivered'        => ['label' => 'Delivered',                    'campaign' => 'deliveredorder',        'format' => 'basic', 'rate' => 0],
        'rto_initiated'    => ['label' => 'Return to origin (RTO)',       'campaign' => '',                      'format' => 'full',  'rate' => 2],
        'rto_delivered'    => ['label' => 'RTO delivered to seller',      'campaign' => '',                      'format' => 'full',  'rate' => 2],
        'cancelled'        => ['label' => 'Cancelled',                    'campaign' => '',                      'format' => 'full',  'rate' => 2],
        'lost'             => ['label' => 'Shipment lost / damaged',      'campaign' => '',                      'format' => 'full',  'rate' => 2],
    ],

    // NDR response link stays valid this many days
    'ndr_link_days' => 5,
];
