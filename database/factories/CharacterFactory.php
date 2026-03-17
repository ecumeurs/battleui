<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => User::factory(),
            'name' => fake()->name(),
            'hp' => 3,
            'attack' => 1,
            'defense' => 1,
            'movement' => 1,
            'initial_movement' => 1,
        ];
    }
}
