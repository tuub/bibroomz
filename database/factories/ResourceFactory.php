<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => fake()->unique()->numberBetween($min = 100, $max = 500),
            'location' => fake()->streetAddress,
            'description' => fake()->text(125),
            'capacity' => fake()->numberBetween(1, 25),
            'opens_at' => '09:00:00',
            'closes_at' => '00:00:00',
            'is_active' => 1,
        ];
    }
}
