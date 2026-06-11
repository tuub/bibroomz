<?php

declare(strict_types=1);

namespace App\Library;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class Utility
{
    public static function getCarbonNow(): CarbonImmutable
    {
        $timezone = config('roomz.app.timezone');
        $tzOffset = CarbonImmutable::now(is_string($timezone) ? $timezone : null)->offsetHours;

        return CarbonImmutable::now()->addHours($tzOffset);
    }

    public static function createCarbonDateTime(string $date, string $time): Carbon
    {
        try {
            $result = Carbon::createFromFormat('d.m.Y H:i', $date.' '.$time);
        } catch (\Exception) {
            throw new InvalidArgumentException('Invalid date/time combination.');
        }

        assert($result instanceof Carbon);

        return $result;
    }

    /**
     * @return array{hour: int, minute: int}
     */
    public static function getTimeValuesFromEnvTimeString(string $envTimeValue): array
    {
        $parts = explode(':', $envTimeValue);

        return [
            'hour' => (int) $parts[0],
            'minute' => isset($parts[1]) ? (int) $parts[1] : 0,
        ];
    }

    public static function convertCamelCaseToSnakeCase(string $camel_case): string
    {
        $snakeCase = preg_replace('/(?<!^)[A-Z]/', '_$0', $camel_case);

        return strtolower(is_string($snakeCase) ? $snakeCase : $camel_case);
    }

    public static function normalizeLoginName(?string $login_name): ?string
    {
        $method = config('roomz.user.login_name_normalization_method');

        if ($login_name === null) {
            return null;
        }

        return match ($method) {
            1 => strtolower($login_name),
            default => $login_name,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getTranslatable(string $value): array
    {
        $savedLocale = app()->getLocale();
        $supportedLocales = config('app.supported_locales');

        $output = [];
        if (is_array($supportedLocales)) {
            foreach ($supportedLocales as $locale) {
                if (! is_string($locale)) {
                    continue;
                }

                app()->setLocale($locale);
                $output[$locale] = $value;
            }
        }

        app()->setLocale($savedLocale);

        return $output;
    }
}
