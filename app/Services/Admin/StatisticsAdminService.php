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
     * @return array{
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     range: string,
     *     from: ?string,
     *     to: ?string,
     *     timeSeries: array<int, array{label: string, count: int}>,
     *     granularity: string,
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
     *         timeSeries: array<int, array{label: string, count: int}>,
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
        ?string $institutionId = null,
        ?string $resourceGroupId = null,
        ?string $resourceId = null,
        ?string $compareFrom = null,
        ?string $compareTo = null,
    ): array {
        [$rangeFrom, $rangeTo] = $this->resolveRange($range, $from, $to);

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

        $bookingCounts = $this->buildBookingCounts($institutions, $resourceGroups, $resources, $rangeFrom, $rangeTo);

        $timeSeriesResources = $this->scopeResourcesForTimeSeries(
            $resources,
            $resourceGroups,
            $institutionId,
            $resourceGroupId,
            $resourceId,
        );
        $timeSeries = $this->buildTimeSeries($timeSeriesResources, $granularity, $rangeFrom, $rangeTo);
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
            'timeSeriesInstitutionId' => $institutionId,
            'timeSeriesResourceGroupId' => $resourceGroupId,
            'timeSeriesResourceId' => $resourceId,
            'cancellations' => $this->buildCancellationStatistics($timeSeriesResources, $rangeFrom, $rangeTo),
            'heatmap' => $this->buildPeakTimesHeatmap($timeSeriesResources, $rangeFrom, $rangeTo),
            'comparison' => $this->buildComparisonData(
                $institutions,
                $resourceGroups,
                $resources,
                $timeSeriesResources,
                $granularity,
                $timeSeriesBookingCount,
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
     *     timeSeries: array<int, array{label: string, count: int}>,
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
     * @param  array<int, array{label: string, count: int}>  $timeSeries
     * @return array<int, array<int, string>>
     */
    private function timeSeriesCsvRows(array $timeSeries): array
    {
        $rows = [['Label', 'Count']];

        foreach ($timeSeries as $entry) {
            $rows[] = [$entry['label'], (string) $entry['count']];
        }

        return $rows;
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
     * @return Collection<int, Resource>
     */
    private function scopeResourcesForTimeSeries(
        Collection $resources,
        Collection $resourceGroups,
        ?string $institutionId,
        ?string $resourceGroupId,
        ?string $resourceId,
    ): Collection {
        if ($resourceId !== null) {
            return $resources->filter(fn (Resource $resource): bool => (string) $resource->id === $resourceId)->values();
        }

        if ($resourceGroupId !== null) {
            return $resources->filter(fn (Resource $resource): bool => (string) $resource->resource_group_id === $resourceGroupId)->values();
        }

        if ($institutionId !== null) {
            $groupIds = $resourceGroups
                ->filter(fn (ResourceGroup $resourceGroup): bool => (string) $resourceGroup->institution_id === $institutionId)
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
     *     timeSeries: array<int, array{label: string, count: int}>,
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
        ?string $compareFrom,
        ?string $compareTo,
    ): ?array {
        [$comparisonFrom, $comparisonTo] = $this->resolveComparisonRange($compareFrom, $compareTo);

        if ($comparisonFrom === null || $comparisonTo === null) {
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
            'timeSeries' => $this->buildTimeSeries($timeSeriesResources, $granularity, $comparisonFrom, $comparisonTo),
            'institutions' => $comparisonCounts['institutions'],
            'resourceGroups' => $comparisonCounts['resourceGroups'],
            'resources' => $comparisonCounts['resources'],
        ];
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @return array<int, array{label: string, count: int}>
     */
    private function buildTimeSeries(Collection $resources, string $granularity, ?CarbonInterface $rangeFrom, ?CarbonInterface $rangeTo): array
    {
        [$bucketStarts, $windowStart, $format] = $this->buildTimeSeriesBuckets($granularity, $rangeFrom, $rangeTo);

        $happenings = $this->happeningQueryForResources($resources, $windowStart, $rangeTo)
            ->get(['start']);

        $countsByBucket = $this->countByTimeSeriesBucket($happenings, $format);

        return collect($bucketStarts)
            ->map(fn (CarbonImmutable $bucketStart): array => [
                'label' => $bucketStart->format($format),
                'count' => $this->toInt($countsByBucket[$bucketStart->format($format)] ?? 0),
            ])
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
        $happenings = $this->happeningQueryForResources($resources, $rangeFrom, $rangeTo)
            ->get(['start']);
        $totalCount = $happenings->count();

        $counts = $happenings
            ->countBy(function (Happening $happening): string {
                $start = CarbonImmutable::parse($happening->start);

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
     * @param  Collection<int, Happening>  $happenings
     * @return Collection<string, int>
     */
    private function countByTimeSeriesBucket(Collection $happenings, string $format): Collection
    {
        return $happenings->countBy(
            fn (Happening $happening): string => CarbonImmutable::parse($happening->start)->format($format),
        );
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

    private function happeningRetentionDays(): int
    {
        $cleanupDays = config('roomz.happenings.cleanup_days');

        return is_numeric($cleanupDays) ? max(0, (int) $cleanupDays) : 0;
    }
}
