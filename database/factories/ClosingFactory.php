<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Closing;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Closing>
 */
class ClosingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'closable_id' => (string) Str::uuid(),
            'closable_type' => Institution::class,
            'start' => now(),
            'end' => now()->addDay(),
            'description' => ['en' => 'Test closing'],
            'notify_users' => true,
        ];
    }
}
