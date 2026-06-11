<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Library\Utility;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailContent>
 */
class MailContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'institution_id' => Institution::factory(),
            'mail_type_id' => MailType::factory(),
            'subject' => Utility::getTranslatable('Test Subject'),
            'title' => Utility::getTranslatable('Test Title'),
            'salutation' => Utility::getTranslatable('Dear user'),
            'intro' => Utility::getTranslatable('Introduction text'),
            'outro' => Utility::getTranslatable('Closing text'),
            'farewell' => Utility::getTranslatable('Farewell text'),
            'is_active' => true,
        ];
    }
}
