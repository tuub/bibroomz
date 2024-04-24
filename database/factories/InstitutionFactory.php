<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Setting;
use App\Models\WeekDay;
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

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Institution $institution) {
            $settings = Setting::getInitialValues();

            foreach ($settings['institution'] as $key => $value) {
                $setting = new Setting([
                    'key' => $key,
                    'value' => $value,
                ]);

                $institution->settings()->save($setting);
            }

            $institution->week_days()->sync(WeekDay::pluck('id'));
        });
    }
}
