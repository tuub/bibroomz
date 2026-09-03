<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class StatisticsDateRangeResolver
{
    /**
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    public function resolve(string $range, ?string $from, ?string $to): array
    {
        $now = CarbonImmutable::now();

        return match ($range) {
            'this_week' => [$now->startOfWeek(), $now],
            'this_month' => [$now->startOfMonth(), $now],
            'this_year' => [$now->startOfYear(), $now],
            'last_7_days' => [$now->subDays(7), $now],
            'last_30_days' => [$now->subDays(30), $now],
            'last_3_months' => [$now->subMonths(3), $now],
            'last_12_months' => [$now->subMonths(12), $now],
            'custom' => [
                $from !== null ? CarbonImmutable::parse($from)->startOfDay() : null,
                $to !== null ? CarbonImmutable::parse($to)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    /**
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    public function resolveComparison(?string $from, ?string $to): array
    {
        if ($from === null || $to === null) {
            return [null, null];
        }

        return [
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay(),
        ];
    }
}
