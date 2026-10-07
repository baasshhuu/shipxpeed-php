<?php

// echo 'ascasc';die;

return [



    'xpressbees' => [

        'login_url'       => 'https://shipment.xpressbees.com/api/users/login',


        'service_url'     => 'https://shipment.xpressbees.com/api/courier/serviceability',


        'username'        => env('XPB_USERNAME'),
        'password'        => env('XPB_PASSWORD'),


        'token_ttl'       => 3600,
    ],

    'delhivery' => [
        'service_url' => 'https://track.delhivery.com/c/api/pin-codes/json/',
        'login_url'       => 'https://ltl-clients-api.delhivery.com/ums/login',
    ],

    'shadowfax' => [
        'service_url' => 'https://dale.shadowfax.in/api',
        'api_key'     => env('SHADOWFAX_API_KEY'),
    ],


    'delhivery_b2b' => [
    'base_url' => 'https://ltl-clients-api.delhivery.com/pincode-service',
    // 'token'    => env('DELHIVERY_B2B_TOKEN'),
],



    'delhivery_b2c' => [
    'base_url' => 'https://track.delhivery.com/c/api/pin-codes/json/?parameters',
     'create_url' => 'https://track.delhivery.com/c/api/orders/create/',
     'client_id' => "SHIPXPEED364",
    ],

    // config/courier_services.php

 

    // …
];