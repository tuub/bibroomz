<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StatisticsTimeSeriesBuilder
{
    /**
     * A ceiling on the payload, not a display window: the chart scrolls, so
     * every bucket of the chosen period is rendered. Only a range far beyond
     * what bookings are kept for is cut back to the most recent buckets.
     */
    private const int MAX_TIME_SERIES_BUCKETS = 600;

    public function __construct(
        private readonly StatisticsHappeningQuery $happeningQuery,
        private readonly StatisticsFormatter $formatter,
    ) {}

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  Collection<int, Institution>  $institutions
     * @param  'none'|'institution'|'resource_group'|'resource'  $split
     * @return array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>
     */
    public function build(
        Collection $resources,
        Collection $resourceGroups,
        Collection $institutions,
        string $granularity,
        ?CarbonInterface $rangeFrom,
        ?CarbonInterface $rangeTo,
        string $split,
    ): array {
        [$bucketStarts, $windowStart, $format] = $this->buildTimeSeriesBuckets($resources, $granularity, $rangeFrom, $rangeTo);

        $rows = $this->happeningQuery->forResources($resources, $windowStart, $rangeTo)
            ->toBase()
            ->get(['start', 'resource_id']);

        [$countsByBucket, $countsByBucketAndResource] = $this->summarizeTimeSeriesRows($rows, $format);

        if ($split === 'none') {
            return collect($bucketStarts)
                ->map(fn (CarbonImmutable $bucketStart): array => [
                    'label' => $bucketStart->format($format),
                    'count' => $this->formatter->toInt($countsByBucket[$bucketStart->format($format)] ?? 0),
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
                    'count' => $this->formatter->toInt($countsByBucket[$bucket] ?? 0),
                    'segments' => $subjects
                        ->map(fn (array $subject): array => [
                            'id' => $subject['id'],
                            'title' => $subject['title'],
                            'count' => $this->formatter->toInt($countsByBucketAndSegment[$bucket][$subject['id']] ?? 0),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @return array{0: array<int, CarbonImmutable>, 1: CarbonImmutable, 2: string}
     */
    private function buildTimeSeriesBuckets(
        Collection $resources,
        string $granularity,
        ?CarbonInterface $rangeFrom,
        ?CarbonInterface $rangeTo,
    ): array {
        $format = match ($granularity) {
            'week' => 'o-\WW',
            'year' => 'Y',
            default => 'Y-m',
        };

        $now = CarbonImmutable::now();
        $defaultStart = $this->defaultWindowStart($now, $granularity);

        // Without a range of its own the window has to come from the bookings
        // themselves, so that "all" covers every period that holds one -
        // including the ones booked ahead of today. An empty installation
        // still gets the default window, rather than a single bucket.
        [$firstBooking, $lastBooking] = $rangeFrom instanceof CarbonInterface && $rangeTo instanceof CarbonInterface
            ? [null, null]
            : $this->happeningQuery->bookedPeriod($resources);

        $start = $rangeFrom ?? ($firstBooking instanceof CarbonImmutable && $firstBooking->lessThan($defaultStart)
            ? $firstBooking
            : $defaultStart);

        $end = $rangeTo ?? ($lastBooking instanceof CarbonImmutable && $lastBooking->greaterThan($now)
            ? $lastBooking
            : $now);

        $windowStart = $this->startOfPeriod(CarbonImmutable::parse($start), $granularity);
        $windowEnd = $this->startOfPeriod(CarbonImmutable::parse($end), $granularity);

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
            $bucket = CarbonImmutable::parse($this->formatter->toStringValue($row->start))->format($format);
            $resourceId = $this->formatter->toStringValue($row->resource_id);

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
     * @param  'institution'|'resource_group'|'resource'  $split
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
                    'title' => $this->formatter->stringTranslations($resource->getTranslations('title')),
                ])
                ->values(),
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
                'title' => $this->formatter->stringTranslations($institution->getTranslations('title')),
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
                'title' => $this->formatter->stringTranslations($resourceGroup->getTranslations('title')),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  Collection<int, ResourceGroup>  $resourceGroups
     * @param  'institution'|'resource_group'|'resource'  $split
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

    private function defaultWindowStart(CarbonImmutable $now, string $granularity): CarbonImmutable
    {
        $windowEnd = $this->startOfPeriod($now, $granularity);

        return match ($granularity) {
            'week' => $windowEnd->subWeeks(11),
            'year' => $windowEnd->subYears(4),
            default => $windowEnd->subMonths(11),
        };
    }

    private function startOfPeriod(CarbonImmutable $date, string $granularity): CarbonImmutable
    {
        return match ($granularity) {
            'week' => $date->startOfWeek(),
            'year' => $date->startOfYear(),
            default => $date->startOfMonth(),
        };
    }
}
