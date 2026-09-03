<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StatisticsAdminService
{
    private const int MAX_TIME_SERIES_BUCKETS = 104;

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
        [$rangeFrom, $rangeTo] = $this->resolveRange($range, $from, $to);
        $institutionIds = $this->normalizedIds($institutionId);
        $resourceGroupIds = $this->normalizedIds($resourceGroupId);
        $resourceIds = $this->normalizedIds($resourceId);

        [$institutions, $resourceGroups, $resources, $timeSeriesResources, $timeSeriesSplit] = $this->resolveScope(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );

        $bookingCounts = $this->buildBookingCounts($institutions, $resourceGroups, $resources, $rangeFrom, $rangeTo);

        $timeSeries = $this->buildTimeSeries(
            $timeSeriesResources,
            $resourceGroups,
            $institutions,
            $granularity,
            $rangeFrom,
            $rangeTo,
            $timeSeriesSplit,
        );
        $timeSeriesBookingCount = $this->happeningQueryForResources($timeSeriesResources, $rangeFrom, $rangeTo)->count();

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
            'cancellations' => $this->buildCancellationStatistics($timeSeriesResources, $rangeFrom, $rangeTo),
            'heatmap' => $this->buildPeakTimesHeatmap($timeSeriesResources, $rangeFrom, $rangeTo),
            'comparison' => $this->buildComparisonData(
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
        [$rangeFrom, $rangeTo] = $this->resolveRange($range, $from, $to);
        [$institutions, $resourceGroups, $resources, , $timeSeriesSplit] = $this->resolveScope(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );

        $bookingCounts = $this->buildBookingCounts($institutions, $resourceGroups, $resources, $rangeFrom, $rangeTo);

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
        [$rangeFrom, $rangeTo] = $this->resolveRange($range, $from, $to);
        [$institutions, $resourceGroups, , $timeSeriesResources, $timeSeriesSplit] = $this->resolveScope(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );

        return [
            'timeSeries' => $this->buildTimeSeries(
                $timeSeriesResources,
                $resourceGroups,
                $institutions,
                $granularity,
                $rangeFrom,
                $rangeTo,
                $timeSeriesSplit,
            ),
            'cancellations' => $this->buildCancellationStatistics($timeSeriesResources, $rangeFrom, $rangeTo),
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
        [$rangeFrom, $rangeTo] = $this->resolveRange($range, $from, $to);
        [, , , $timeSeriesResources] = $this->resolveScope($user, $institutionIds, $resourceGroupIds, $resourceIds);

        return [
            'heatmap' => $this->buildPeakTimesHeatmap($timeSeriesResources, $rangeFrom, $rangeTo),
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
        [$rangeFrom, $rangeTo] = $this->resolveRange($range, $from, $to);
        [$institutions, $resourceGroups, $resources, $timeSeriesResources, $timeSeriesSplit] = $this->resolveScope(
            $user,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );
        $timeSeriesBookingCount = $this->happeningQueryForResources($timeSeriesResources, $rangeFrom, $rangeTo)->count();

        return [
            'comparison' => $this->buildComparisonData(
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
     * Resolves the institutions the user may view, and the resource groups,
     * resources, and time-series-scoped resource subset within them.
     *
     * @param  list<string>  $institutionIds
     * @param  list<string>  $resourceGroupIds
     * @param  list<string>  $resourceIds
     * @return array{
     *     0: Collection<int, Institution>,
     *     1: Collection<int, ResourceGroup>,
     *     2: Collection<int, Resource>,
     *     3: Collection<int, Resource>,
     *     4: string,
     * }
     */
    private function resolveScope(
        User $user,
        array $institutionIds,
        array $resourceGroupIds,
        array $resourceIds,
    ): array {
        $institutions = Institution::query()
            ->with('resource_groups.resources')
            ->orderBy('order')
            ->get()
            ->filter(fn (Institution $institution): bool => $user->can('view_happenings', $institution))
            ->values();

        $resourceGroups = $institutions->flatMap(fn (Institution $institution): Collection => $institution->resource_groups)
            ->values();

        $resources = $resourceGroups->flatMap(fn (ResourceGroup $resourceGroup): Collection => $resourceGroup->resources)
            ->values();

        $timeSeriesResources = $this->scopeResourcesForTimeSeries(
            $resources,
            $resourceGroups,
            $institutionIds,
            $resourceGroupIds,
            $resourceIds,
        );
        $timeSeriesSplit = $this->inferTimeSeriesSplit($timeSeriesResources, $resourceGroups);

        return [$institutions, $resourceGroups, $resources, $timeSeriesResources, $timeSeriesSplit];
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
        return match ($type) {
            'time_series' => $this->timeSeriesCsvRows($data['timeSeries']),
            'institutions' => $this->institutionsCsvRows($data['institutions']),
            'resource_groups' => $this->resourceGroupsCsvRows($data['resourceGroups'], $data['institutions']),
            'resources' => $this->resourcesCsvRows($data['resources'], $data['resourceGroups']),
            'heatmap' => $this->heatmapCsvRows($data['heatmap']['cells']),
            default => [],
        };
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     * @return array<int, array<int, string>>
     */
    private function timeSeriesCsvRows(array $timeSeries): array
    {
        if ($this->timeSeriesHasSegments($timeSeries)) {
            return $this->splitTimeSeriesCsvRows($timeSeries);
        }

        $rows = [['Label', 'Count']];

        foreach ($timeSeries as $entry) {
            $rows[] = [$entry['label'], (string) $entry['count']];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     * @return array<int, array<int, string>>
     */
    private function splitTimeSeriesCsvRows(array $timeSeries): array
    {
        $rows = [['Label', 'Total', ...$this->timeSeriesSegmentHeaders($timeSeries)]];

        foreach ($timeSeries as $entry) {
            $row = [$entry['label'], (string) $entry['count']];

            foreach ($entry['segments'] ?? [] as $segment) {
                $row[] = (string) $segment['count'];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     */
    private function timeSeriesHasSegments(array $timeSeries): bool
    {
        foreach ($timeSeries as $entry) {
            if (array_key_exists('segments', $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     * @return array<int, string>
     */
    private function timeSeriesSegmentHeaders(array $timeSeries): array
    {
        foreach ($timeSeries as $entry) {
            if (! array_key_exists('segments', $entry)) {
                continue;
            }

            $headers = [];

            foreach ($entry['segments'] as $segment) {
                $headers[] = $this->csvTitle($segment['title']);
            }

            return $headers;
        }

        return [];
    }

    /**
     * @param  array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>  $cells
     * @return array<int, array<int, string>>
     */
    private function heatmapCsvRows(array $cells): array
    {
        $rows = [['Day of Week', 'Hour', 'Count', 'Percentage']];

        foreach ($cells as $cell) {
            $rows[] = [
                $this->toCsvString($cell['dayOfWeek']),
                $this->toCsvString($cell['hour']),
                $this->toCsvString($cell['count']),
                $this->toCsvString($cell['percentage']),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>  $institutions
     * @return array<int, array<int, string>>
     */
    private function institutionsCsvRows(Collection $institutions): array
    {
        $rows = [['Title', 'Active', 'Cancelled', 'Cancellation Rate']];

        foreach ($institutions as $institution) {
            $rows[] = [
                $this->csvTitle($institution['title']),
                $this->toCsvString($institution['active']),
                $this->toCsvString($institution['cancelled']),
                $this->toCsvString($institution['cancellationRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>  $resourceGroups
     * @param  Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>  $institutions
     * @return array<int, array<int, string>>
     */
    private function resourceGroupsCsvRows(Collection $resourceGroups, Collection $institutions): array
    {
        $institutionTitleById = $institutions->mapWithKeys(
            fn (array $institution): array => [$institution['id'] => $this->csvTitle($institution['title'])],
        );

        $rows = [['Title', 'Institution', 'Active', 'Cancelled', 'Cancellation Rate']];

        foreach ($resourceGroups as $resourceGroup) {
            $rows[] = [
                $this->csvTitle($resourceGroup['title']),
                $institutionTitleById[$resourceGroup['institution_id']] ?? '',
                $this->toCsvString($resourceGroup['active']),
                $this->toCsvString($resourceGroup['cancelled']),
                $this->toCsvString($resourceGroup['cancellationRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>  $resources
     * @param  Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>  $resourceGroups
     * @return array<int, array<int, string>>
     */
    private function resourcesCsvRows(Collection $resources, Collection $resourceGroups): array
    {
        $resourceGroupTitleById = $resourceGroups->mapWithKeys(
            fn (array $resourceGroup): array => [$resourceGroup['id'] => $this->csvTitle($resourceGroup['title'])],
        );

        $rows = [['Title', 'Resource Group', 'Active', 'Cancelled', 'Cancellation Rate']];

        foreach ($resources as $resource) {
            $rows[] = [
                $this->csvTitle($resource['title']),
                $resourceGroupTitleById[$resource['resource_group_id']] ?? '',
                $this->toCsvString($resource['active']),
                $this->toCsvString($resource['cancelled']),
                $this->toCsvString($resource['cancellationRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $translations
     */
    private function csvTitle(array $translations): string
    {
        $locale = app()->getLocale();

        return $translations[$locale] ?? $translations['en'] ?? (string) reset($translations);
    }

    /**
     * @return array<string, string>
     */
    private function stringTranslations(mixed $translations): array
    {
        if (! is_array($translations)) {
            return [];
        }

        $result = [];

        foreach ($translations as $locale => $value) {
            if (is_string($locale) && is_string($value)) {
                $result[$locale] = $value;
            }
        }

        return $result;
    }

    private function toCsvString(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            is_bool($value) => $value ? '1' : '0',
            default => '',
        };
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  list<string>  $institutionIds
     * @param  list<string>  $resourceGroupIds
     * @param  list<string>  $resourceIds
     * @return Collection<int, Resource>
     */
    private function scopeResourcesForTimeSeries(
        Collection $resources,
        Collection $resourceGroups,
        array $institutionIds,
        array $resourceGroupIds,
        array $resourceIds,
    ): Collection {
        if ($resourceIds !== []) {
            return $resources
                ->filter(fn (Resource $resource): bool => in_array((string) $resource->id, $resourceIds, true))
                ->values();
        }

        if ($resourceGroupIds !== []) {
            return $resources
                ->filter(fn (Resource $resource): bool => in_array((string) $resource->resource_group_id, $resourceGroupIds, true))
                ->values();
        }

        if ($institutionIds !== []) {
            $groupIds = $resourceGroups
                ->filter(fn (ResourceGroup $resourceGroup): bool => in_array((string) $resourceGroup->institution_id, $institutionIds, true))
                ->pluck('id');

            return $resources->filter(fn (Resource $resource): bool => $groupIds->contains($resource->resource_group_id))->values();
        }

        return $resources;
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  Collection<int, Resource>  $resources
     * @return array{
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     total: int,
     * }
     */
    private function buildBookingCounts(
        Collection $institutions,
        Collection $resourceGroups,
        Collection $resources,
        ?CarbonInterface $rangeFrom,
        ?CarbonInterface $rangeTo,
    ): array {
        $bookingCountsByResource = $this->happeningQueryForResources($resources, $rangeFrom, $rangeTo)
            ->selectRaw('resource_id, count(*) as aggregate')
            ->groupBy('resource_id')
            ->pluck('aggregate', 'resource_id');

        $cancelledCountsByResource = $this->happeningQueryForResources($resources, $rangeFrom, $rangeTo, onlyTrashed: true)
            ->selectRaw('resource_id, count(*) as aggregate')
            ->groupBy('resource_id')
            ->pluck('aggregate', 'resource_id');

        $resourceStatistics = $resources->map(function (Resource $resource) use ($bookingCountsByResource, $cancelledCountsByResource): array {
            $active = $this->toInt($bookingCountsByResource[$resource->id] ?? 0);
            $cancelled = $this->toInt($cancelledCountsByResource[$resource->id] ?? 0);

            return [
                'id' => $resource->id,
                'title' => $this->stringTranslations($resource->getTranslations('title')),
                'resource_group_id' => $resource->resource_group_id,
                'count' => $active,
                'active' => $active,
                'cancelled' => $cancelled,
                'cancellationRate' => $this->percentage($cancelled, $active + $cancelled),
            ];
        });

        $resourceStatisticsByResourceGroup = $resourceStatistics->groupBy('resource_group_id');

        $resourceGroupStatistics = $resourceGroups->map(function (ResourceGroup $resourceGroup) use ($resourceStatisticsByResourceGroup): array {
            $resourceStats = $resourceStatisticsByResourceGroup->get($resourceGroup->id, collect());
            $active = $this->toInt($resourceStats->sum('active'));
            $cancelled = $this->toInt($resourceStats->sum('cancelled'));

            return [
                'id' => $resourceGroup->id,
                'title' => $this->stringTranslations($resourceGroup->getTranslations('title')),
                'institution_id' => $resourceGroup->institution_id,
                'count' => $active,
                'active' => $active,
                'cancelled' => $cancelled,
                'cancellationRate' => $this->percentage($cancelled, $active + $cancelled),
            ];
        });

        $resourceGroupStatisticsByInstitution = $resourceGroupStatistics->groupBy('institution_id');

        $institutionStatistics = $institutions->map(function (Institution $institution) use ($resourceGroupStatisticsByInstitution): array {
            $resourceGroupStats = $resourceGroupStatisticsByInstitution->get($institution->id, collect());
            $active = $this->toInt($resourceGroupStats->sum('active'));
            $cancelled = $this->toInt($resourceGroupStats->sum('cancelled'));

            return [
                'id' => $institution->id,
                'title' => $this->stringTranslations($institution->getTranslations('title')),
                'count' => $active,
                'active' => $active,
                'cancelled' => $cancelled,
                'cancellationRate' => $this->percentage($cancelled, $active + $cancelled),
            ];
        });

        return [
            'institutions' => $institutionStatistics->values(),
            'resourceGroups' => $resourceGroupStatistics->values(),
            'resources' => $resourceStatistics->values(),
            'total' => $this->toInt($resourceStatistics->sum('active')),
        ];
    }

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
    private function buildComparisonData(
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
        [$comparisonFrom, $comparisonTo] = $this->resolveComparisonRange($compareFrom, $compareTo);

        if (! $comparisonFrom instanceof CarbonInterface || ! $comparisonTo instanceof CarbonInterface) {
            return null;
        }

        $comparisonCounts = $this->buildBookingCounts($institutions, $resourceGroups, $resources, $comparisonFrom, $comparisonTo);
        $comparisonCount = $this->happeningQueryForResources($timeSeriesResources, $comparisonFrom, $comparisonTo)->count();

        return [
            'from' => $comparisonFrom->toDateString(),
            'to' => $comparisonTo->toDateString(),
            'currentCount' => $currentCount,
            'comparisonCount' => $comparisonCount,
            'deltaPct' => $this->deltaPercentage($currentCount, $comparisonCount),
            'timeSeries' => $this->buildTimeSeries(
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

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  Collection<int, Institution>  $institutions
     * @return array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>
     */
    private function buildTimeSeries(
        Collection $resources,
        Collection $resourceGroups,
        Collection $institutions,
        string $granularity,
        ?CarbonInterface $rangeFrom,
        ?CarbonInterface $rangeTo,
        string $split,
    ): array {
        [$bucketStarts, $windowStart, $format] = $this->buildTimeSeriesBuckets($granularity, $rangeFrom, $rangeTo);

        $rows = $this->happeningQueryForResources($resources, $windowStart, $rangeTo)
            ->toBase()
            ->get(['start', 'resource_id']);

        [$countsByBucket, $countsByBucketAndResource] = $this->summarizeTimeSeriesRows($rows, $format);

        if ($split === 'none') {
            return collect($bucketStarts)
                ->map(fn (CarbonImmutable $bucketStart): array => [
                    'label' => $bucketStart->format($format),
                    'count' => $this->toInt($countsByBucket[$bucketStart->format($format)] ?? 0),
                ])
                ->values()
                ->all();
        }

        $subjects = $this->timeSeriesSplitSubjects($resources, $resourceGroups, $institutions, $split);
        $segmentIdByResourceId = $this->timeSeriesSegmentIdByResourceId($resources, $resourceGroups, $split);
        $countsByBucketAndSegment = $this->segmentTimeSeriesCounts($countsByBucketAndResource, $segmentIdByResourceId);

        return collect($bucketStarts)
            ->map(function (CarbonImmutable $bucketStart) use ($countsByBucket, $countsByBucketAndSegment, $format, $subjects): array {
                $bucket = $bucketStart->format($format);

                return [
                    'label' => $bucket,
                    'count' => $this->toInt($countsByBucket[$bucket] ?? 0),
                    'segments' => $subjects
                        ->map(fn (array $subject): array => [
                            'id' => $subject['id'],
                            'title' => $subject['title'],
                            'count' => $this->toInt($countsByBucketAndSegment[$bucket][$subject['id']] ?? 0),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Happenings are MassPrunable, so active and soft-deleted rows whose end
     * date is older than the configured cleanup window may already be gone.
     *
     * @param  Collection<int, Resource>  $resources
     * @return array{cancelled: int, active: int, rate: float, retentionDays: int, retentionExceeded: bool}
     */
    private function buildCancellationStatistics(Collection $resources, ?CarbonInterface $rangeFrom, ?CarbonInterface $rangeTo): array
    {
        $active = $this->happeningQueryForResources($resources, $rangeFrom, $rangeTo)->count();
        $cancelled = $this->happeningQueryForResources($resources, $rangeFrom, $rangeTo, onlyTrashed: true)->count();
        $retentionDays = $this->happeningRetentionDays();

        return [
            'cancelled' => $cancelled,
            'active' => $active,
            'rate' => $this->percentage($cancelled, $active + $cancelled),
            'retentionDays' => $retentionDays,
            'retentionExceeded' => ! $rangeFrom instanceof CarbonInterface
                || CarbonImmutable::parse($rangeFrom)->lessThan(CarbonImmutable::now()->subDays($retentionDays)),
        ];
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @return array{cells: array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>, maxCount: int, totalCount: int}
     */
    private function buildPeakTimesHeatmap(Collection $resources, ?CarbonInterface $rangeFrom, ?CarbonInterface $rangeTo): array
    {
        $rows = $this->happeningQueryForResources($resources, $rangeFrom, $rangeTo)
            ->toBase()
            ->get(['start']);
        $totalCount = $rows->count();

        $counts = $rows->countBy(function (\stdClass $row): string {
            $start = CarbonImmutable::parse($this->toStringValue($row->start));

            return $start->dayOfWeekIso.'-'.$start->hour;
        });

        $cells = [];

        for ($dayOfWeek = 1; $dayOfWeek <= 7; $dayOfWeek++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $count = $this->toInt($counts[$dayOfWeek.'-'.$hour] ?? 0);

                $cells[] = [
                    'dayOfWeek' => $dayOfWeek,
                    'hour' => $hour,
                    'count' => $count,
                    'percentage' => $this->percentage($count, $totalCount),
                ];
            }
        }

        return [
            'cells' => $cells,
            'maxCount' => $this->toInt(collect($cells)->max('count') ?? 0),
            'totalCount' => $totalCount,
        ];
    }

    /**
     * @return array{0: array<int, CarbonImmutable>, 1: CarbonImmutable, 2: string}
     */
    private function buildTimeSeriesBuckets(string $granularity, ?CarbonInterface $rangeFrom, ?CarbonInterface $rangeTo): array
    {
        $format = match ($granularity) {
            'week' => 'o-\WW',
            'year' => 'Y',
            default => 'Y-m',
        };

        $now = CarbonImmutable::now();

        if ($rangeFrom instanceof CarbonInterface || $rangeTo instanceof CarbonInterface) {
            $windowStart = $this->startOfPeriod(CarbonImmutable::parse($rangeFrom ?? $now->subMonths(12)), $granularity);
            $windowEnd = $this->startOfPeriod(CarbonImmutable::parse($rangeTo ?? $now), $granularity);
        } else {
            $defaultPeriods = match ($granularity) {
                'week' => 12,
                'year' => 5,
                default => 12,
            };

            $windowEnd = $this->startOfPeriod($now, $granularity);
            $windowStart = match ($granularity) {
                'week' => $windowEnd->subWeeks($defaultPeriods - 1),
                'year' => $windowEnd->subYears($defaultPeriods - 1),
                default => $windowEnd->subMonths($defaultPeriods - 1),
            };
        }

        $earliestAllowedStart = match ($granularity) {
            'week' => $windowEnd->subWeeks(self::MAX_TIME_SERIES_BUCKETS - 1),
            'year' => $windowEnd->subYears(self::MAX_TIME_SERIES_BUCKETS - 1),
            default => $windowEnd->subMonths(self::MAX_TIME_SERIES_BUCKETS - 1),
        };

        if ($windowStart->lessThan($earliestAllowedStart)) {
            $windowStart = $earliestAllowedStart;
        }

        $bucketStarts = [];
        $cursor = $windowStart;

        while ($cursor->lessThanOrEqualTo($windowEnd)) {
            $bucketStarts[] = $cursor;
            $cursor = match ($granularity) {
                'week' => $cursor->addWeek(),
                'year' => $cursor->addYear(),
                default => $cursor->addMonth(),
            };
        }

        return [$bucketStarts, $windowStart, $format];
    }

    /**
     * Groups raw `{start, resource_id}` rows into per-bucket and
     * per-bucket-per-resource counts in a single pass, parsing each row's
     * `start` exactly once (instead of once to hydrate an Eloquent
     * `datetime` cast and again via `CarbonImmutable::parse()` on top of
     * that, which is what made this loop expensive at real data volumes).
     *
     * @param  Collection<int, \stdClass>  $rows  Rows carry raw `start` and `resource_id` columns.
     * @return array{0: array<string, int>, 1: array<string, array<string, int>>}
     */
    private function summarizeTimeSeriesRows(Collection $rows, string $format): array
    {
        $countsByBucket = [];
        $countsByBucketAndResource = [];

        foreach ($rows as $row) {
            $bucket = CarbonImmutable::parse($this->toStringValue($row->start))->format($format);
            $resourceId = $this->toStringValue($row->resource_id);

            $countsByBucket[$bucket] = ($countsByBucket[$bucket] ?? 0) + 1;
            $countsByBucketAndResource[$bucket][$resourceId] = ($countsByBucketAndResource[$bucket][$resourceId] ?? 0) + 1;
        }

        return [$countsByBucket, $countsByBucketAndResource];
    }

    /**
     * @param  array<string, array<string, int>>  $countsByBucketAndResource
     * @param  array<string, string>  $segmentIdByResourceId
     * @return array<string, array<string, int>>
     */
    private function segmentTimeSeriesCounts(array $countsByBucketAndResource, array $segmentIdByResourceId): array
    {
        $counts = [];

        foreach ($countsByBucketAndResource as $bucket => $byResource) {
            foreach ($byResource as $resourceId => $aggregate) {
                $segmentId = $segmentIdByResourceId[$resourceId] ?? null;

                if ($segmentId === null) {
                    continue;
                }

                $counts[$bucket][$segmentId] = ($counts[$bucket][$segmentId] ?? 0) + $aggregate;
            }
        }

        return $counts;
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, array{id: string, title: array<string, string>}>
     */
    private function timeSeriesSplitSubjects(
        Collection $resources,
        Collection $resourceGroups,
        Collection $institutions,
        string $split,
    ): Collection {
        return match ($split) {
            'institution' => $this->timeSeriesInstitutionSubjects($resources, $resourceGroups, $institutions),
            'resource_group' => $this->timeSeriesResourceGroupSubjects($resources, $resourceGroups),
            'resource' => $resources
                ->map(fn (Resource $resource): array => [
                    'id' => (string) $resource->id,
                    'title' => $this->stringTranslations($resource->getTranslations('title')),
                ])
                ->values(),
            default => collect(),
        };
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, array{id: string, title: array<string, string>}>
     */
    private function timeSeriesInstitutionSubjects(Collection $resources, Collection $resourceGroups, Collection $institutions): Collection
    {
        $resourceGroupIds = $resources
            ->map(fn (Resource $resource): string => (string) $resource->resource_group_id)
            ->unique()
            ->values()
            ->all();

        $institutionIds = $resourceGroups
            ->filter(fn (ResourceGroup $resourceGroup): bool => in_array((string) $resourceGroup->id, $resourceGroupIds, true))
            ->map(fn (ResourceGroup $resourceGroup): string => (string) $resourceGroup->institution_id)
            ->unique()
            ->values()
            ->all();

        return $institutions
            ->filter(fn (Institution $institution): bool => in_array((string) $institution->id, $institutionIds, true))
            ->map(fn (Institution $institution): array => [
                'id' => (string) $institution->id,
                'title' => $this->stringTranslations($institution->getTranslations('title')),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @return Collection<int, array{id: string, title: array<string, string>}>
     */
    private function timeSeriesResourceGroupSubjects(Collection $resources, Collection $resourceGroups): Collection
    {
        $resourceGroupIds = $resources
            ->map(fn (Resource $resource): string => (string) $resource->resource_group_id)
            ->unique()
            ->values()
            ->all();

        return $resourceGroups
            ->filter(fn (ResourceGroup $resourceGroup): bool => in_array((string) $resourceGroup->id, $resourceGroupIds, true))
            ->map(fn (ResourceGroup $resourceGroup): array => [
                'id' => (string) $resourceGroup->id,
                'title' => $this->stringTranslations($resourceGroup->getTranslations('title')),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @return array<string, string>
     */
    private function timeSeriesSegmentIdByResourceId(Collection $resources, Collection $resourceGroups, string $split): array
    {
        return match ($split) {
            'institution' => $this->timeSeriesInstitutionIdByResourceId($resources, $resourceGroups),
            'resource_group' => $resources
                ->mapWithKeys(fn (Resource $resource): array => [(string) $resource->id => (string) $resource->resource_group_id])
                ->all(),
            'resource' => $resources
                ->mapWithKeys(fn (Resource $resource): array => [(string) $resource->id => (string) $resource->id])
                ->all(),
            default => [],
        };
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @return array<string, string>
     */
    private function timeSeriesInstitutionIdByResourceId(Collection $resources, Collection $resourceGroups): array
    {
        $institutionIdByResourceGroupId = $resourceGroups
            ->mapWithKeys(fn (ResourceGroup $resourceGroup): array => [(string) $resourceGroup->id => (string) $resourceGroup->institution_id])
            ->all();

        $institutionIdByResourceId = [];

        foreach ($resources as $resource) {
            $institutionId = $institutionIdByResourceGroupId[(string) $resource->resource_group_id] ?? null;

            if ($institutionId === null) {
                continue;
            }

            $institutionIdByResourceId[(string) $resource->id] = $institutionId;
        }

        return $institutionIdByResourceId;
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @return Builder<Happening>
     */
    private function happeningQueryForResources(
        Collection $resources,
        ?CarbonInterface $rangeFrom,
        ?CarbonInterface $rangeTo,
        bool $onlyTrashed = false,
    ): Builder {
        $query = $onlyTrashed ? Happening::onlyTrashed() : Happening::query();

        return $query
            ->whereIn('resource_id', $resources->pluck('id'))
            ->when($rangeFrom instanceof CarbonInterface, fn ($query) => $query->where('start', '>=', $rangeFrom))
            ->when($rangeTo instanceof CarbonInterface, fn ($query) => $query->where('start', '<=', $rangeTo));
    }

    private function startOfPeriod(CarbonImmutable $date, string $granularity): CarbonImmutable
    {
        return match ($granularity) {
            'week' => $date->startOfWeek(),
            'year' => $date->startOfYear(),
            default => $date->startOfMonth(),
        };
    }

    /**
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    private function resolveRange(string $range, ?string $from, ?string $to): array
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
    private function resolveComparisonRange(?string $from, ?string $to): array
    {
        if ($from === null || $to === null) {
            return [null, null];
        }

        return [
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay(),
        ];
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function toStringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }

    private function percentage(int $part, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 1);
    }

    private function deltaPercentage(int $current, int $comparison): float
    {
        if ($comparison === 0) {
            return $current === 0 ? 0.0 : 100.0;
        }

        return round((($current - $comparison) / $comparison) * 100, 1);
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     */
    private function inferTimeSeriesSplit(Collection $resources, Collection $resourceGroups): string
    {
        $resourceGroupIds = $resources
            ->map(fn (Resource $resource): string => (string) $resource->resource_group_id)
            ->unique()
            ->values()
            ->all();

        if ($resourceGroupIds === []) {
            return 'none';
        }

        $institutionIds = $resourceGroups
            ->filter(fn (ResourceGroup $resourceGroup): bool => in_array((string) $resourceGroup->id, $resourceGroupIds, true))
            ->map(fn (ResourceGroup $resourceGroup): string => (string) $resourceGroup->institution_id)
            ->unique()
            ->values();

        if ($institutionIds->count() > 1) {
            return 'institution';
        }

        if (count($resourceGroupIds) > 1) {
            return 'resource_group';
        }

        return 'resource';
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

    private function happeningRetentionDays(): int
    {
        $cleanupDays = config('roomz.happenings.cleanup_days');

        return is_numeric($cleanupDays) ? max(0, (int) $cleanupDays) : 0;
    }
}
