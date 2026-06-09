<?php

namespace Database\Factories;

use App\Library\Utility;
use App\Models\ResourceGroup;
use App\Models\Setting;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceGroup>
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
            'term_plural' => $this->getTranslatable($title.'s'),
            'description' => $this->getTranslatable($faker->realText(125)),
            'is_active' => 1,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getTranslatable(string $value): array
    {
        return Utility::getTranslatable($value);
    }

    /**
     * Configure the model factory.
     */
    #[\Override]
    public function configure(): static
    {
        return $this->afterCreating(function (ResourceGroup $resource_group): void {
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
