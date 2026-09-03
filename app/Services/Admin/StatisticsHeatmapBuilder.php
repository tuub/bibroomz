<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StatisticsHeatmapBuilder
{
    public function __construct(
        private readonly StatisticsHappeningQuery $happeningQuery,
        private readonly StatisticsFormatter $formatter,
    ) {}

    /**
     * @param  Collection<int, Resource>  $resources
     * @return array{cells: array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>, maxCount: int, totalCount: int}
     */
    public function build(Collection $resources, ?CarbonInterface $rangeFrom, ?CarbonInterface $rangeTo): array
    {
        $rows = $this->happeningQuery->forResources($resources, $rangeFrom, $rangeTo)
            ->toBase()
            ->get(['start']);
        $totalCount = $rows->count();

        $counts = $rows->countBy(function (\stdClass $row): string {
            $start = CarbonImmutable::parse($this->formatter->toStringValue($row->start));

            return $start->dayOfWeekIso.'-'.$start->hour;
        });

        $cells = [];

        for ($dayOfWeek = 1; $dayOfWeek <= 7; $dayOfWeek++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $count = $this->formatter->toInt($counts[$dayOfWeek.'-'.$hour] ?? 0);

                $cells[] = [
                    'dayOfWeek' => $dayOfWeek,
                    'hour' => $hour,
                    'count' => $count,
                    'percentage' => $this->formatter->percentage($count, $totalCount),
                ];
            }
        }

        return [
            'cells' => $cells,
            'maxCount' => $this->formatter->toInt(collect($cells)->max('count') ?? 0),
            'totalCount' => $totalCount,
        ];
    }
}
