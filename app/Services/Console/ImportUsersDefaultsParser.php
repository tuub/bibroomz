<?php

declare(strict_types=1);

namespace App\Services\Console;

use Carbon\Carbon;

class ImportUsersDefaultsParser
{
    /**
     * @return array<string, string>
     */
    public function parse(?string $from, ?string $until): array
    {
        $defaults = [];

        if ($from) {
            $defaults['valid_from'] = Carbon::parse($from)->format('Y-m-d');
        }

        if ($until) {
            $defaults['valid_until'] = Carbon::parse($until)->format('Y-m-d');
        }

        return $defaults;
    }
}
