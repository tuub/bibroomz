<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResourceGroup>
 */
class ResourceGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = ucfirst(fake()->colorName);
        return [
            'name' => strtoupper($name),
            'slug' => strtolower($name),
            'term_singular' => $name,
            'term_plural' => $name . 's',
            'description' => fake()->realText(125),
            'is_active' => 1,
        ];
    }
}
