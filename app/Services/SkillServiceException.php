<?php

namespace App\Services;

/**
 * Domain exception for skill inventory operations.
 *
 * @spec-link [[entity_character_skill_inventory]]
 */
class SkillServiceException extends \RuntimeException
{
    public const ERR_SKILL_SLOT_FULL          = 'ERR_SKILL_SLOT_FULL';
    public const ERR_SKILL_NOT_OWNED          = 'ERR_SKILL_NOT_OWNED';
    public const ERR_SKILL_NOT_EQUIPPED       = 'ERR_SKILL_NOT_EQUIPPED';
    public const ERR_GENERATOR_UNREACHABLE    = 'ERR_GENERATOR_UNREACHABLE';
    public const ERR_GRADE_OUT_OF_WINDOW     = 'ERR_GRADE_OUT_OF_WINDOW';

    public function __construct(string $message, int $httpStatus, public readonly string $reason)
    {
        parent::__construct($message, $httpStatus);
    }

    public function httpStatus(): int
    {
        return $this->getCode();
    }
}
