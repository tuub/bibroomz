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
            'title' => fake()->unique()->company,
            'short_title' => fake()->unique()->text(5),
            'slug' => fake()->unique()->slug(1),
            'location' => fake()->streetAddress,
            'home_uri' => 'https://www.example.org',
            'email' => 'info@example.org',
            'logo_uri' => 'https://picsum.photos/500/200',
            'teaser_uri' => 'https://picsum.photos/600/300',
        ];
    }
}
