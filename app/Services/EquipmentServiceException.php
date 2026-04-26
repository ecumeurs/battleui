<?php

namespace App\Services;

/**
 * Domain exception for equipment operations.
 *
 * @spec-link [[upsilonapi:api_equipment_management]]
 */
class EquipmentServiceException extends \RuntimeException
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
