<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SkillTemplate>
 */
class SkillTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'            => fake()->words(2, true),
            'behavior'        => fake()->randomElement(['Direct', 'Reaction', 'Passive', 'Counter', 'Trap']),
            'targeting'       => ['range' => ['value' => 1, 'max' => 3]],
            'costs'           => ['mp' => ['value' => 2, 'max' => 10]],
            'effect'          => ['damage' => 5],
            'grade'           => fake()->randomElement(['I', 'II', 'III', 'IV', 'V']),
            'weight_positive' => fake()->numberBetween(10, 100),
            'weight_negative' => fake()->numberBetween(0, 50),
            'available'       => true,
            'version'         => 1,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(['available' => false]);
    }
}
