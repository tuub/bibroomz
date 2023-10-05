<?php

namespace Database\Factories;

use App\Models\Institution;
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

    public function getTranslatable($value): array
    {
        $locales = config('app.supported_locales');
        $output = [];
        foreach ($locales as $locale) {
            App::setLocale($locale);
            $output[$locale] = $value;
        }

        return $output;
    }
}
