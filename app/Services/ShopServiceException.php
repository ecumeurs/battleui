<?php

namespace App\Services;

/**
 * Domain exception for shop transactions. The `code` constructor arg holds
 * the HTTP status; `reason` carries the machine-readable failure tag for
 * `meta.reason` in the standard envelope.
 *
 * @spec-link [[api_shop_purchase]]
 */
class ShopServiceException extends \RuntimeException
{
    public function __construct(string $message, int $httpStatus, public readonly string $reason)
    {
        parent::__construct($message, $httpStatus);
    }

    public function httpStatus(): int
    {
        return $this->getCode();
    }
}
