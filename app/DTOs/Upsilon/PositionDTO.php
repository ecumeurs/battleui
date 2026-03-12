<?php

namespace App\DTOs\Upsilon;

/**
 * @spec-link [[battleui_api_dtos]]
 */
class PositionDTO
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}
