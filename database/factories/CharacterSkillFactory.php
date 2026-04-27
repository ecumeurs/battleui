<?php

namespace Database\Factories;

use App\Models\Character;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CharacterSkill>
 */
class CharacterSkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id'      => Character::factory(),
            'skill_template_id' => null,
            'source'            => 'roll',
            'instance_data'     => [
                'id'       => Str::uuid()->toString(),
                'name'     => fake()->words(2, true),
                'behavior' => 'offensive',
                'grade'    => 'III',
            ],
            'equipped'    => false,
            'acquired_at' => now(),
            'equipped_at' => null,
        ];
    }

    public function equipped(): static
    {
        return $this->state([
            'equipped'    => true,
            'equipped_at' => now(),
        ]);
    }
}
