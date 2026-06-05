<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->userName(),
            'email' => fake()->safeEmail(),
            'password' => bcrypt(fake()->password()),
            'is_admin' => false,
            'is_system_user' => false,
            'is_logged_in' => false,
        ];
    }
}
