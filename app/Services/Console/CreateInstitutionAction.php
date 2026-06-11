<?php

namespace App\Services\Console;

use App\Models\Institution;
use App\Models\Setting;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Support\Facades\Validator;

class CreateInstitutionAction
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validateInput(array $input): array
    {
        return $this->stringKeyedArray(Validator::make(
            $input,
            [
                'title' => [new RequiredWithTranslationRule],
                'short_title' => ['required'],
                'slug' => ['required', 'unique:institutions'],
                'location' => [],
                'week_days' => ['required_if:is_active,true'],
                'home_uri' => ['url'],
                'logo_uri' => ['url'],
                'teaser_uri' => ['url'],
                'email' => ['email'],
                'is_active' => ['required', 'boolean'],
            ],
        )->validate());
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): Institution
    {
        $institution = Institution::create(collect($validated)->except('week_days')->all());
        /** @var array<mixed> $weekDays */
        $weekDays = $validated['week_days'] ?? [];
        $institution->week_days()->sync($this->normalizeWeekDays($weekDays));

        foreach (Setting::getInitialValues()['institution'] as $key => $value) {
            $institution->settings()->create([
                'key' => $key,
                'value' => $value,
            ]);
        }

        return $institution;
    }

    /**
     * @return array<int, int|string>
     */
    /**
     * @param  array<mixed>  $weekDays
     * @return array<int, int|string>
     */
    private function normalizeWeekDays(array $weekDays): array
    {
        $map = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];

        $normalized = [];

        foreach ($weekDays as $day) {
            if (is_string($day) && array_key_exists($day, $map)) {
                $normalized[] = $map[$day];

                continue;
            }

            if (is_int($day) || is_string($day)) {
                $normalized[] = $day;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
