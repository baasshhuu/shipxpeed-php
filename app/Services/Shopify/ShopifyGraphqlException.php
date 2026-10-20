<?php

namespace App\Services\Shopify;

use RuntimeException;

class ShopifyGraphqlException extends RuntimeException
{
    public function __construct(string $message, public array|string $errors = [], public ?array $data = null)
    {
        parent::__construct($message);
    }
}
