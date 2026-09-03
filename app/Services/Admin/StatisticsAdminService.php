<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Collection;

class StatisticsAdminService
{
    public function __construct(
        private readonly StatisticsScopeResolver $scopeResolver,
        private readonly StatisticsDateRangeResolver $dateRangeResolver,
        private readonly StatisticsBookingCountsCalculator $bookingCountsCalculator,
        private readonly StatisticsTimeSeriesBuilder $timeSeriesBuilder,
        private readonly StatisticsCancellationCalculator $cancellationCalculator,
        private readonly StatisticsHeatmapBuilder $heatmapBuilder,
        private readonly StatisticsComparisonBuilder $comparisonBuilder,
        private readonly StatisticsCsvExporter $csvExporter,
        private readonly StatisticsHappeningQuery $happeningQuery,
    ) {}

    /**
     * @param  array<int, mixed>|string|null  $institutionId
     * @param  array<int, mixed>|string|null  $resourceGroupId
     * @param  array<int, mixed>|string|null  $resourceId
     * @return array{
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     range: string,
     *     from: ?string,
     *     to: ?string,
     *     timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
     *     granularity: string,
     *     timeSeriesSplit: string,
     *     timeSeriesInstitutionIds: list<string>,
     *     timeSeriesResourceGroupIds: list<string>,
     *     timeSeriesResourceIds: list<string>,
     *     timeSeriesInstitutionId: ?string,
     *     timeSeriesResourceGroupId: ?string,
     *     timeSeriesResourceId: ?string,
     *     cancellations: array{cancelled: int, active: int, rate: float, retentionDays: int, retentionExceeded: bool},
     *     heatmap: array{cells: array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>, maxCount: int, totalCount: int},
     *     comparison: ?array{
     *         from: string,
     *         to: string,
     *         currentCount: int,
     *         comparisonCount: int,
     *         deltaPct: float,
     *         timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
     *         institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *         resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *         resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>
     *     },
     * }
     */
    public function getIndexData(
        User $user,
        string $range = 'all',
        ?string $from = null,
        ?string $to = null,
        string $granularity = 'month',
        string|array|null $institutionId = null,
        string|array|null $resourceGroupId = null,
        string|array|null $resourceId = null,
        ?string $compareFrom = null,
        ?string $compareTo = null,
    ): array {
        [$rangeFrom, $rangeTo] = $this->dateRangeResolver->resolve($range, $from, $to);
        $institutionIds = $this->normalizedIds($institutionId);
        $resourceGroupIds = $this->normalizedIds($resourceGroupId);
        $resourceIds = $this->normalizedIds($resourceId);

        [$institutions, $resourceGroups, $resources, $timeSeriesResources, $timeSeriesSplit] = $this->scopeResolver->resolve(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );

        $bookingCounts = $this->bookingCountsCalculator->calculate($institutions, $resourceGroups, $resources, $rangeFrom, $rangeTo);

        $timeSeries = $this->timeSeriesBuilder->build(
            $timeSeriesResources,
            $resourceGroups,
            $institutions,
            $granularity,
            $rangeFrom,
            $rangeTo,
            $timeSeriesSplit,
        );
        $timeSeriesBookingCount = $this->happeningQuery->forResources($timeSeriesResources, $rangeFrom, $rangeTo)->count();

        return [
            'institutions' => $bookingCounts['institutions'],
            'resourceGroups' => $bookingCounts['resourceGroups'],
            'resources' => $bookingCounts['resources'],
            'range' => $range,
            'from' => $rangeFrom?->toDateString(),
            'to' => $rangeTo?->toDateString(),
            'timeSeries' => $timeSeries,
            'granularity' => $granularity,
            'timeSeriesSplit' => $timeSeriesSplit,
            'timeSeriesInstitutionIds' => $institutionIds,
            'timeSeriesResourceGroupIds' => $resourceGroupIds,
            'timeSeriesResourceIds' => $resourceIds,
            'timeSeriesInstitutionId' => $institutionIds[0] ?? null,
            'timeSeriesResourceGroupId' => $resourceGroupIds[0] ?? null,
            'timeSeriesResourceId' => $resourceIds[0] ?? null,
            'cancellations' => $this->cancellationCalculator->calculate($timeSeriesResources, $rangeFrom, $rangeTo),
            'heatmap' => $this->heatmapBuilder->build($timeSeriesResources, $rangeFrom, $rangeTo),
            'comparison' => $this->comparisonBuilder->build(
                $institutions,
                $resourceGroups,
                $resources,
                $timeSeriesResources,
                $granularity,
                $timeSeriesBookingCount,
                $timeSeriesSplit,
                $compareFrom,
                $compareTo,
            ),
        ];
    }

    /**
     * The eager portion of {@see getIndexData()}'s data: cheap to compute and
     * needed to render the page's filters and booking-count sections
     * immediately, on both the initial load and any filter-change visit.
     *
     * @param  list<string>  $institutionIds
     * @param  list<string>  $resourceGroupIds
     * @param  list<string>  $resourceIds
     * @return array{
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     range: string,
     *     from: ?string,
     *     to: ?string,
     *     granularity: string,
     *     timeSeriesSplit: string,
     *     timeSeriesInstitutionIds: list<string>,
     *     timeSeriesResourceGroupIds: list<string>,
     *     timeSeriesResourceIds: list<string>,
     *     timeSeriesInstitutionId: ?string,
     *     timeSeriesResourceGroupId: ?string,
     *     timeSeriesResourceId: ?string,
     * }
     */
    public function getBookingCountsAndFilterState(
        User $user,
        string $range,
        ?string $from,
        ?string $to,
        string $granularity,
        array $institutionIds,
        array $resourceGroupIds,
        array $resourceIds,
    ): array {
        [$rangeFrom, $rangeTo] = $this->dateRangeResolver->resolve($range, $from, $to);
        [$institutions, $resourceGroups, $resources, , $timeSeriesSplit] = $this->scopeResolver->resolve(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );

        $bookingCounts = $this->bookingCountsCalculator->calculate($institutions, $resourceGroups, $resources, $rangeFrom, $rangeTo);

        return [
            'institutions' => $bookingCounts['institutions'],
            'resourceGroups' => $bookingCounts['resourceGroups'],
            'resources' => $bookingCounts['resources'],
            'range' => $range,
            'from' => $rangeFrom?->toDateString(),
            'to' => $rangeTo?->toDateString(),
            'granularity' => $granularity,
            'timeSeriesSplit' => $timeSeriesSplit,
            'timeSeriesInstitutionIds' => $institutionIds,
            'timeSeriesResourceGroupIds' => $resourceGroupIds,
            'timeSeriesResourceIds' => $resourceIds,
            'timeSeriesInstitutionId' => $institutionIds[0] ?? null,
            'timeSeriesResourceGroupId' => $resourceGroupIds[0] ?? null,
            'timeSeriesResourceId' => $resourceIds[0] ?? null,
        ];
    }

    /**
     * The `'timeSeries'` deferred prop group: the time series chart and the
     * cancellation summary it's paired with, both scoped to the same
     * resources so they can share one round trip.
     *
     * @param  list<string>  $institutionIds
     * @param  list<string>  $resourceGroupIds
     * @param  list<string>  $resourceIds
     * @return array{
     *     timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
     *     cancellations: array{cancelled: int, active: int, rate: float, retentionDays: int, retentionExceeded: bool},
     * }
     */
    public function getTimeSeriesGroupData(
        User $user,
        string $range,
        ?string $from,
        ?string $to,
        string $granularity,
        array $institutionIds,
        array $resourceGroupIds,
        array $resourceIds,
    ): array {
        [$rangeFrom, $rangeTo] = $this->dateRangeResolver->resolve($range, $from, $to);
        [$institutions, $resourceGroups, , $timeSeriesResources, $timeSeriesSplit] = $this->scopeResolver->resolve(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );

        return [
            'timeSeries' => $this->timeSeriesBuilder->build(
                $timeSeriesResources,
                $resourceGroups,
                $institutions,
                $granularity,
                $rangeFrom,
                $rangeTo,
                $timeSeriesSplit,
            ),
            'cancellations' => $this->cancellationCalculator->calculate($timeSeriesResources, $rangeFrom, $rangeTo),
        ];
    }

    /**
     * The `'heatmap'` deferred prop group.
     *
     * @param  list<string>  $institutionIds
     * @param  list<string>  $resourceGroupIds
     * @param  list<string>  $resourceIds
     * @return array{heatmap: array{cells: array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>, maxCount: int, totalCount: int}}
     */
    public function getHeatmapData(
        User $user,
        string $range,
        ?string $from,
        ?string $to,
        array $institutionIds,
        array $resourceGroupIds,
        array $resourceIds,
    ): array {
        [$rangeFrom, $rangeTo] = $this->dateRangeResolver->resolve($range, $from, $to);
        [, , , $timeSeriesResources] = $this->scopeResolver->resolve($user, $institutionIds, $resourceGroupIds, $resourceIds);

        return [
            'heatmap' => $this->heatmapBuilder->build($timeSeriesResources, $rangeFrom, $rangeTo),
        ];
    }

    /**
     * The `'comparison'` deferred prop group.
     *
     * @param  list<string>  $institutionIds
     * @param  list<string>  $resourceGroupIds
     * @param  list<string>  $resourceIds
     * @return array{comparison: ?array{
     *     from: string,
     *     to: string,
     *     currentCount: int,
     *     comparisonCount: int,
     *     deltaPct: float,
     *     timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     * }}
     */
    public function getComparisonGroupData(
        User $user,
        string $range,
        ?string $from,
        ?string $to,
        string $granularity,
        array $institutionIds,
        array $resourceGroupIds,
        array $resourceIds,
        ?string $compareFrom,
        ?string $compareTo,
    ): array {
        [$rangeFrom, $rangeTo] = $this->dateRangeResolver->resolve($range, $from, $to);
        [$institutions, $resourceGroups, $resources, $timeSeriesResources, $timeSeriesSplit] = $this->scopeResolver->resolve(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );
        $timeSeriesBookingCount = $this->happeningQuery->forResources($timeSeriesResources, $rangeFrom, $rangeTo)->count();

        return [
            'comparison' => $this->comparisonBuilder->build(
                $institutions,
                $resourceGroups,
                $resources,
                $timeSeriesResources,
                $granularity,
                $timeSeriesBookingCount,
                $timeSeriesSplit,
                $compareFrom,
                $compareTo,
            ),
        ];
    }

    /**
     * @param  array{
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
     *     heatmap: array{cells: array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>, maxCount: int, totalCount: int},
     *     comparison?: ?array<string, mixed>,
     * }  $data
     * @return array<int, array<int, string>>
     */
    public function toCsvRows(array $data, string $type): array
    {
        return $this->csvExporter->toRows($data, $type);
    }

    /**
     * @param  array<int, mixed>|string|null  $value
     * @return list<string>
     */
    private function normalizedIds(string|array|null $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                $ids[] = $candidate;
            }
        }

        return array_values(array_unique($ids));
    }
}
