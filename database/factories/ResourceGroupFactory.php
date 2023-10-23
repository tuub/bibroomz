<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\App;

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
        $faker = \Faker\Factory::create('de_DE');
        $title = ucfirst($faker->colorName);
        return [
            'title' => $this->getTranslatable($title),
            'slug' => strtolower($title),
            'term_singular' => $this->getTranslatable($title),
            'term_plural' => $this->getTranslatable($title . 's'),
            'description' => $this->getTranslatable($faker->realText(125)),
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
