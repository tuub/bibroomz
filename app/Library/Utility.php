<?php
declare(strict_types=1);

namespace App\Library;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Utility {

    public static function carbonize($date): Carbon
    {
        if (is_string($date)) {
            return Carbon::parse($date);
        }

        return $date;
    }

    public static function sendToLog(string $channel, array $data, string $level='info'): void
    {
        /*
        $callingClass = debug_backtrace(
            !DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2
        )[1]['class'];
        */
        $callingMethod = debug_backtrace(
            !DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2
        )[1]['function'];

        $data = ['ACTION' => $callingMethod] + $data;

        Log::channel($channel)->$level(
            urldecode(http_build_query($data, '',' / '))
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
}
