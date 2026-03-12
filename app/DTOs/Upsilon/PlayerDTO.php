<?php

namespace App\DTOs\Upsilon;

/**
 * @spec-link [[battleui_api_dtos]]
 */
class PlayerDTO
{
    /**
     * @param string $id
     * @param int $team
     * @param bool $ia
     * @param EntityDTO[] $entities
     */
    public function __construct(
        public string $id,
        public int $team,
        public bool $ia,
        public array $entities = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'team' => $this->team,
            'ia' => $this->ia,
            'entities' => array_map(fn(EntityDTO $entity) => $entity->toArray(), $this->entities),
        ];
    }
}
