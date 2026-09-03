<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StatisticsComparisonBuilder
{
    public function __construct(
        private readonly StatisticsDateRangeResolver $dateRangeResolver,
        private readonly StatisticsBookingCountsCalculator $bookingCountsCalculator,
        private readonly StatisticsTimeSeriesBuilder $timeSeriesBuilder,
        private readonly StatisticsHappeningQuery $happeningQuery,
        private readonly StatisticsFormatter $formatter,
    ) {}

    /**
     * @param  Collection<int, Institution>  $institutions
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, Resource>  $timeSeriesResources
     * @return ?array{
     *     from: string,
     *     to: string,
     *     currentCount: int,
     *     comparisonCount: int,
     *     deltaPct: float,
     *     timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     * }
     */
    public function build(
        Collection $institutions,
        Collection $resourceGroups,
        Collection $resources,
        Collection $timeSeriesResources,
        string $granularity,
        int $currentCount,
        string $timeSeriesSplit,
        ?string $compareFrom,
        ?string $compareTo,
    ): ?array {
        [$comparisonFrom, $comparisonTo] = $this->dateRangeResolver->resolveComparison($compareFrom, $compareTo);

        if (! $comparisonFrom instanceof CarbonInterface || ! $comparisonTo instanceof CarbonInterface) {
            return null;
        }

        $comparisonCounts = $this->bookingCountsCalculator->calculate($institutions, $resourceGroups, $resources, $comparisonFrom, $comparisonTo);
        $comparisonCount = $this->happeningQuery->forResources($timeSeriesResources, $comparisonFrom, $comparisonTo)->count();

        return [
            'from' => $comparisonFrom->toDateString(),
            'to' => $comparisonTo->toDateString(),
            'currentCount' => $currentCount,
            'comparisonCount' => $comparisonCount,
            'deltaPct' => $this->formatter->deltaPercentage($currentCount, $comparisonCount),
            'timeSeries' => $this->timeSeriesBuilder->build(
                $timeSeriesResources,
                $resourceGroups,
                $institutions,
                $granularity,
                $comparisonFrom,
                $comparisonTo,
                $timeSeriesSplit,
            ),
            'institutions' => $comparisonCounts['institutions'],
            'resourceGroups' => $comparisonCounts['resourceGroups'],
            'resources' => $comparisonCounts['resources'],
        ];
    }
}
