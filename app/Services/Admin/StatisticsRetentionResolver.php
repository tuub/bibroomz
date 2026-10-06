<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class StatisticsRetentionResolver
{
    public function __construct(
        private readonly StatisticsHappeningQuery $happeningQuery,
    ) {}

    /**
     * Which bookings the selected period asks for that cleanup has already
     * taken away. Happenings are pruned once they ended more than the
     * configured number of days ago, so nothing before the cut-off is left
     * to count and the buckets covering it stay empty.
     * `missingFrom`/`missingTo` bound that gap.
     *
     * @return array{days: int, cutoff: string, exceeded: bool, missingFrom: ?string, missingTo: ?string}
     */
    public function resolve(?CarbonInterface $rangeFrom, ?CarbonInterface $rangeTo): array
    {
        $days = $this->happeningQuery->retentionDays();
        $cutoff = CarbonImmutable::now()->subDays($days);
        $from = $rangeFrom instanceof CarbonInterface ? CarbonImmutable::parse($rangeFrom) : null;

        // A period without a lower bound of its own reaches back as far as
        // bookings were ever made, so it always covers pruned ones - but it
        // has no start to name them from, only the cut-off.
        if (! $from instanceof CarbonImmutable) {
            return [
                'days' => $days,
                'cutoff' => $cutoff->toDateString(),
                'exceeded' => true,
                'missingFrom' => null,
                'missingTo' => null,
            ];
        }

        if (! $from->lessThan($cutoff)) {
            return [
                'days' => $days,
                'cutoff' => $cutoff->toDateString(),
                'exceeded' => false,
                'missingFrom' => null,
                'missingTo' => null,
            ];
        }

        $to = $rangeTo instanceof CarbonInterface ? CarbonImmutable::parse($rangeTo) : null;

        // A period that ends before the cut-off has lost every booking it
        // covers, not just the ones up to the cut-off.
        $missingTo = $to instanceof CarbonImmutable && $to->lessThan($cutoff) ? $to : $cutoff;

        return [
            'days' => $days,
            'cutoff' => $cutoff->toDateString(),
            'exceeded' => true,
            'missingFrom' => $from->toDateString(),
            'missingTo' => $missingTo->toDateString(),
        ];
    }
}
