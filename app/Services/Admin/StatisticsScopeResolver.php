<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Support\Collection;

class StatisticsScopeResolver
{
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
    public function resolve(
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
}
