<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Library\Utility;
use App\Models\Institution;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGroup>
 */
class UserGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => Utility::getTranslatable('Test Group'),
            'institution_id' => Institution::factory(),
        ];
    }
}
