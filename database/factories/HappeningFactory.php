<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Happening;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Happening>
 */
class HappeningFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'resource_id' => Resource::factory(),
            'is_verified' => false,
            'start' => now()->addHour(),
            'end' => now()->addHours(2),
            'reserved_at' => now(),
        ];
    }
}
