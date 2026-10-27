<?php

// Seller › Notify (buyer Email & SMS). WhatsApp has its own config (config/whatsapp.php).
return [
    // Charge to the seller wallet per buyer email actually sent (₹). 0 = free.
    'email_rate' => (float) env('NOTIFY_EMAIL_RATE', 0.50),

    // SMS: stays off until an SMS gateway (DLT-approved templates) is connected.
    'sms_enabled' => (bool) env('NOTIFY_SMS_ENABLED', false),
    'sms_rate'    => (float) env('NOTIFY_SMS_RATE', 0.25),
];
