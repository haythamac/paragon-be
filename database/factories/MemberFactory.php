<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->userName(),
            'class' => fake()->randomElement(['archer', 'volva', 'berserker', 'warlord', 'skald']),
            'role' => fake()->randomElement(['member', 'new member']),
            'power' => fake()->numberBetween(90000, 200000),
            'level' => fake()->numberBetween(50, 60),
        ];
    }
}
