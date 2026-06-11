<?php

namespace App\Services\Console;

use App\Models\Institution;
use Carbon\Carbon;

class CleanupIntervalResolver
{
    public function fromInstitution(Institution $institution): Carbon
    {
        $time = now();
        $settingModel = $institution->settings->where('key', 'cleanup_interval')->first();
        /** @var string|null $rawSetting */
        $rawSetting = $settingModel !== null
            ? $settingModel->value
            : config('roomz.default.cleanup_interval');
        $setting = $rawSetting ?? '';

        foreach (explode(':', $setting) as $index => $interval) {
            $value = (int) $interval;

            if ($index === 0) {
                $time->subDays($value);
            }

            if ($index === 1) {
                $time->subHours($value);
            }

            if ($index === 2) {
                $time->subMinutes($value);
            }
        }

        return $time;
    }

    public function fromValues(int|string|null $minutes, int|string|null $hours, int|string|null $days): Carbon
    {
        $time = now();
        $resolvedMinutes = $this->resolveInt($minutes);
        $resolvedHours = $this->resolveInt($hours);
        $resolvedDays = $this->resolveInt($days);

        if ($resolvedMinutes) {
            $time->subMinutes($resolvedMinutes);
        }

        if ($resolvedHours) {
            $time->subHours($resolvedHours);
        }

        if ($resolvedDays) {
            $time->subDays($resolvedDays);
        }

        return $time;
    }

    private function resolveInt(int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_int($value) ? $value : (int) $value;
    }
}
