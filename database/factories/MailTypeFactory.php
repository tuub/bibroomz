<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MailType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailType>
 */
class MailTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'key' => fake()->unique()->word(),
            'description' => fake()->sentence(),
        ];
    }
}
