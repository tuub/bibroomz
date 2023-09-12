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
        $tz_offset = CarbonImmutable::now(env('APP_TIMEZONE'))->offsetHours;
        return CarbonImmutable::now()->addHours($tz_offset);
    }

    public static function createCarbonDateTime($date, $time): Carbon
    {
        return Carbon::createFromFormat('d.m.Y H:i',  $date . ' ' . $time);
    }

    public static function sendToLog(string $channel, array $data, string $level = 'info'): void
    {
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

    public static function getDateTimeFromStrings(String $date_str, String $time_str): Carbon
    {
        $date = Carbon::parse($date_str);
        $time_arr = explode(':', $time_str);

        return $date->hour(str($time_arr[0]))->minute(str($time_arr[1]));
    }

    /**
     * Make the given rule keys translatable.
     *
     * @param array $rules
     * @param array $translatables
     * @return array
     */
    public static function makeRulesTranslatable(array &$rules, array $translatables): array
    {
        $lanugages = config('app.supported_locales');

        foreach ($translatables as $translatable) {
            foreach ($lanugages as $language) {
                $rules[$translatable . '.' . $language] = $rules[$translatable];
            }

            unset($rules[$translatable]);
        }

        return $rules;
    }
}
