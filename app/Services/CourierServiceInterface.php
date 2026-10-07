<?php
// app/Services/CourierServiceInterface.php

namespace App\Services;

interface CourierServiceInterface
{
    /**
     * @param  string  $pickup
     * @param  string  $destination
     * @return array
     */
public function getServiceability(array $params): array;
}