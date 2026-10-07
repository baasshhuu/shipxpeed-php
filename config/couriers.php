<?php

return [
     'xpressbees' => \App\Services\XpressBeesService::class,
    // 'delhivery'  => \App\Services\DelhiveryService::class,
     'shadowfax'  => \App\Services\ShadowfaxService::class,
    //  'delhivery_b2b' => \App\Services\DelhiveryB2BService::class,
     'delhivery_b2c' => \App\Services\DelhiveryB2CService::class,
     'delhivery_b2c_Express' => \App\Services\DelhiveryB2CServiceExpress::class,
     'smartship' => \App\Services\SmartshipService::class,
     'ekart' => \App\Services\EkartService::class,
     'tekipost' => \App\Services\TekipostService::class,
     'boxd' => \App\Services\BoxdService::class,
     'dtdc' => \App\Services\DtdcService::class,
     'parcelx' => \App\Services\ParcelxService::class,
     'jiffy' => \App\Services\JiffyService::class,
     'shiprocket' => \App\Services\ShiprocketService::class,
     'selloship' => \App\Services\selloshipService::class,

    'delhivery_zapdeal' => \App\Services\DelhiveryZapdealService::class,
    // …add as you onboard more
];