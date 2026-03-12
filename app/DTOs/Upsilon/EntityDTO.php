<?php

namespace App\DTOs\Upsilon;

/**
 * @spec-link [[battleui_api_dtos]]
 */
class EntityDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public int $hp,
        public int $maxHp,
        public int $attack,
        public int $defense,
        public int $move,
        public int $maxMove,
        public ?PositionDTO $position = null
    ) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'hp' => $this->hp,
            'max_hp' => $this->maxHp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'move' => $this->move,
            'max_move' => $this->maxMove,
        ];

        if ($this->position !== null) {
            $data['position'] = $this->position->toArray();
        }

        return $data;
    }
}
