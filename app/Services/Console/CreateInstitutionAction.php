<?php

namespace App\Services\Console;

use App\Models\Institution;
use App\Models\Setting;
use App\Models\WeekDay;
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
     * Resolves the ISO day numbers the prompt returns ('1' is Monday, '7' is
     * Sunday), or day names, to week_days ids. The ids themselves follow
     * WeekDaySeeder's insert order, which starts with Sunday.
     *
     * @param  array<mixed>  $weekDays
     * @return array<mixed>
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

        $daysOfWeek = [];

        foreach ($weekDays as $day) {
            if (is_string($day) && array_key_exists($day, $map)) {
                $day = $map[$day];
            }

            if (is_numeric($day)) {
                $daysOfWeek[] = (int) $day % 7;
            }
        }

        return WeekDay::query()
            ->whereIn('day_of_week', $daysOfWeek)
            ->pluck('id')
            ->all();
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
