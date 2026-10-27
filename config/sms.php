<?php

/*
|--------------------------------------------------------------------------
| SMS OTP (2Factor.in)
|--------------------------------------------------------------------------
| Mobile OTP for seller sign-up and OTP login.
| OTP_CHANNEL=sms      -> codes go by SMS through 2Factor (default when a key is set)
| OTP_CHANNEL=whatsapp -> codes go on WhatsApp through AiSensy (config/whatsapp.php)
|
| Our panel makes the code itself and checks it itself (stored hashed in
| whatsapp_otps); 2Factor only delivers it. Limits (length, expiry, resend wait,
| per-hour cap, wrong tries) come from config/whatsapp.php "otp".
|
| TWOFACTOR_OTP_TEMPLATE: leave empty to use 2Factor's default OTP template
| (sender TFACTR). After DLT approval of your own sender/template, put the
| 2Factor template name here.
*/

return [

    'channel' => env('OTP_CHANNEL', 'sms'),

    'twofactor' => [
        'api_url'  => rtrim(env('TWOFACTOR_API_URL', 'https://2factor.in/API/V1'), '/'),
        'api_key'  => env('TWOFACTOR_API_KEY'),
        'template' => env('TWOFACTOR_OTP_TEMPLATE'),
        'timeout'  => 12,
    ],
];
