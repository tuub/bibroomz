<?php

namespace Database\Factories;

use App\Library\Utility;
use App\Models\Resource;
use App\Models\WeekDay;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\App;

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
            'title' => $this->getTranslatable(fake()->unique()->numberBetween($min = 100, $max = 500)),
            'location' => $this->getTranslatable(fake()->streetAddress),
            'description' => $this->getTranslatable(fake()->realText(125)),
            'capacity' => fake()->numberBetween(1, 25),
            'is_active' => 1,
        ];
    }

    private function getTranslatable($value): array
    {
        return Utility::getTranslatable($value);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Resource $resource) {
            $resource->business_hours()->create([
                'start' => '09:00:00',
                'end' => '23:00:00',
            ])->week_days()->sync(WeekDay::pluck('id'));
        });
    }
}
