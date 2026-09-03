<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StatisticsBookingCountsCalculator
{
    public function __construct(
        private readonly StatisticsHappeningQuery $happeningQuery,
        private readonly StatisticsFormatter $formatter,
    ) {}

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
    public function calculate(
        Collection $institutions,
        Collection $resourceGroups,
        Collection $resources,
        ?CarbonInterface $rangeFrom,
        ?CarbonInterface $rangeTo,
    ): array {
        $bookingCountsByResource = $this->happeningQuery->forResources($resources, $rangeFrom, $rangeTo)
            ->selectRaw('resource_id, count(*) as aggregate')
            ->groupBy('resource_id')
            ->pluck('aggregate', 'resource_id');

        $cancelledCountsByResource = $this->happeningQuery->forResources($resources, $rangeFrom, $rangeTo, onlyTrashed: true)
            ->selectRaw('resource_id, count(*) as aggregate')
            ->groupBy('resource_id')
            ->pluck('aggregate', 'resource_id');

        $resourceStatistics = $resources->map(function (Resource $resource) use ($bookingCountsByResource, $cancelledCountsByResource): array {
            $active = $this->formatter->toInt($bookingCountsByResource[$resource->id] ?? 0);
            $cancelled = $this->formatter->toInt($cancelledCountsByResource[$resource->id] ?? 0);

            return [
                'id' => $resource->id,
                'title' => $this->formatter->stringTranslations($resource->getTranslations('title')),
                'resource_group_id' => $resource->resource_group_id,
                'count' => $active,
                'active' => $active,
                'cancelled' => $cancelled,
                'cancellationRate' => $this->formatter->percentage($cancelled, $active + $cancelled),
            ];
        });

        $resourceStatisticsByResourceGroup = $resourceStatistics->groupBy('resource_group_id');

        $resourceGroupStatistics = $resourceGroups->map(function (ResourceGroup $resourceGroup) use ($resourceStatisticsByResourceGroup): array {
            $resourceStats = $resourceStatisticsByResourceGroup->get($resourceGroup->id, collect());
            $active = $this->formatter->toInt($resourceStats->sum('active'));
            $cancelled = $this->formatter->toInt($resourceStats->sum('cancelled'));

            return [
                'id' => $resourceGroup->id,
                'title' => $this->formatter->stringTranslations($resourceGroup->getTranslations('title')),
                'institution_id' => $resourceGroup->institution_id,
                'count' => $active,
                'active' => $active,
                'cancelled' => $cancelled,
                'cancellationRate' => $this->formatter->percentage($cancelled, $active + $cancelled),
            ];
        });

        $resourceGroupStatisticsByInstitution = $resourceGroupStatistics->groupBy('institution_id');

        $institutionStatistics = $institutions->map(function (Institution $institution) use ($resourceGroupStatisticsByInstitution): array {
            $resourceGroupStats = $resourceGroupStatisticsByInstitution->get($institution->id, collect());
            $active = $this->formatter->toInt($resourceGroupStats->sum('active'));
            $cancelled = $this->formatter->toInt($resourceGroupStats->sum('cancelled'));

            return [
                'id' => $institution->id,
                'title' => $this->formatter->stringTranslations($institution->getTranslations('title')),
                'count' => $active,
                'active' => $active,
                'cancelled' => $cancelled,
                'cancellationRate' => $this->formatter->percentage($cancelled, $active + $cancelled),
            ];
        });

        return [
            'institutions' => $institutionStatistics->values(),
            'resourceGroups' => $resourceGroupStatistics->values(),
            'resources' => $resourceStatistics->values(),
            'total' => $this->formatter->toInt($resourceStatistics->sum('active')),
        ];
    }
}
