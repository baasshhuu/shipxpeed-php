<?php
// Copy to lead-config.php on the server (never commit the real file).
// At least one of SMTP or webhook must be set, otherwise the form reports "not delivered".
return [
    'allowed_origins' => ['https://shipxpeed.tech', 'https://www.shipxpeed.tech'],

    // Where leads are emailed
    'to_email' => 'support@shipxpeed.com',

    // SMTP sender. For Gmail use an App Password (Google Account > Security > App passwords).
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 465,                 // 465 = SSL, 587 = STARTTLS
        'user' => 'REPLACE_WITH_SENDER_ADDRESS',
        'pass' => 'REPLACE_WITH_APP_PASSWORD',
        'from' => 'REPLACE_WITH_SENDER_ADDRESS',
    ],

    // Optional CRM webhook (Zoho, HubSpot, Make, n8n…). Leave empty to disable.
    'webhook_url' => '',
    'webhook_headers' => [],           // e.g. ['Authorization: Bearer xxx']

    // Private storage for rate-limit counters and a CSV copy of leads. Must NOT be web-accessible.
    'storage_dir' => '/var/lib/shipxpeed-leads',
    'rate_salt' => 'REPLACE_WITH_RANDOM_STRING',
    'rate_window_sec' => 600,
    'rate_max' => 5,
];
