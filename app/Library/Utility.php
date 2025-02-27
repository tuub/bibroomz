<?php

declare(strict_types=1);

namespace App\Library;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class Utility
{
    public static function carbonize($date): Carbon
    {
        if (is_string($date)) {
            return Carbon::parse($date);
        }

        return $date;
    }

    public static function getCarbonNow(): CarbonImmutable
    {
        $tz_offset = CarbonImmutable::now(config('roomz.app.timezone'))->offsetHours;
        return CarbonImmutable::now()->addHours($tz_offset);
    }

    public static function createCarbonDateTime($date, $time): Carbon
    {
        return Carbon::createFromFormat('d.m.Y H:i', $date . ' ' . $time);
    }

    public static function sendToLog(string $channel, array $data, string $level = null): void
    {
        if ($level == null) {
            $level = config('roomz.log.level');
        }

        $callingMethod = debug_backtrace(
            !DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS,
            2
        )[1]['function'];

        $data = ['ACTION' => $callingMethod] + $data;

        Log::channel($channel)->$level(
            urldecode(http_build_query($data, '', ' / '))
        );
    }

    public static function getTimeValuesFromEnvTimeString($envTimeValue): array
    {
        return array_combine(['hour', 'minute'], array_map('intval', explode(':', $envTimeValue)));
    }

    public static function getDateTimeFromStrings(string $date_str, string $time_str): Carbon
    {
        $date = Carbon::parse($date_str);
        $time_arr = explode(':', $time_str);

        return $date->hour(str($time_arr[0]))->minute(str($time_arr[1]));
    }

    public static function convertCamelCaseToSnakeCase(string $camel_case): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camel_case));
    }

    public static function normalizeLoginName(?string $login_name): ?string
    {
        $method = config('roomz.user.login_name_normalization_method');

        if ($login_name === null) {
            return null;
        }

        $username = match ($method) {
            1 => strtolower($login_name),
            default => $login_name,
        };

        return $username;
    }

    public static function getTranslatable($value): array
    {
        $saved_locale = app()->getLocale();
        $supported_locales = config('app.supported_locales');

        $output = [];
        foreach ($supported_locales as $locale) {
            app()->setLocale($locale);
            $output[$locale] = $value;
        }

        app()->setLocale($saved_locale);

        return $output;
    }
}
