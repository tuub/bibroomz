<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Institution>
 */
class InstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => fake()->unique()->stateAbbr,
            'short_title' => fake()->unique()->text(5),
            'slug' => fake()->unique()->slug,
            'location' => fake()->streetAddress,
        ];
    }
}
