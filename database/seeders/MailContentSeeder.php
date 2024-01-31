<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class MailContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = Institution::get();
        $mail_types = MailType::get();

        foreach ($institutions as $institution) {
            foreach ($mail_types as $mail_type) {
                if ($mail_type->mail_contents()->where('institution_id', $institution->id)->exists()) {
                    continue;
                }

                MailContent::create([
                    'institution_id' => $institution->id,
                    'mail_type_id' => $mail_type->id,
                    'subject' => $this->getDefaultTranslatable($mail_type->key, 'subject'),
                    'title' => $this->getDefaultTranslatable($mail_type->key, 'title'),
                    'salutation' => $this->getDefaultTranslatable($mail_type->key, 'salutation'),
                    'intro' => $this->getDefaultTranslatable($mail_type->key, 'intro'),
                    'outro' => $this->getDefaultTranslatable($mail_type->key, 'outro'),
                    'farewell' => $this->getDefaultTranslatable($mail_type->key, 'farewell'),
                    'is_active' => false,
                ]);
            }
        }
    }

    public function getDefaultTranslatable($mail_type, $field): array
    {
        $locales = config('app.supported_locales');

        $output = [];
        foreach ($locales as $locale) {
            App::setLocale($locale);
            $output[$locale] = trans('email.defaults.' . $mail_type . '.' . $field);
        }

        return $output;
    }
}
