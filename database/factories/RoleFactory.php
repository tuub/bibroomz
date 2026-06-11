<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Library\Utility;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => Utility::getTranslatable('Test Role'),
            'description' => Utility::getTranslatable('Description'),
        ];
    }
}
