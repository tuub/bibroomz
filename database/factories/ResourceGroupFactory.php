<?php

namespace Database\Factories;

use App\Library\Utility;
use App\Models\ResourceGroup;
use App\Models\Setting;
use Faker\Factory as FakerFactory;
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
        $faker = FakerFactory::create('de_DE');
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

    private function getTranslatable($value): array
    {
        return Utility::getTranslatable($value);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ResourceGroup $resource_group) {
            $settings = Setting::getInitialValues();

            foreach ($settings['resource_group'] as $key => $value) {
                $setting = new Setting([
                    'key' => $key,
                    'value' => $value,
                ]);

                $resource_group->settings()->save($setting);
            }
        });
    }
}
