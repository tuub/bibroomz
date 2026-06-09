<?php

namespace App\Services\Http;

use App\Models\Resource;
use Carbon\CarbonImmutable;

class ListPublicResourcesAction
{
    public function __construct(
        private readonly RouteResourceGroupResolver $resourceGroupResolver,
        private readonly PublicResourcePresenter $presenter
    ) {}

    /**
     * @return array{
     *   resources: list<array<string, mixed>>,
     *   pagination: array{previousPage: string|null, nextPage: string|null}
     * }
     */
    public function execute(
        string $institutionSlug,
        string $resourceGroupSlug,
        int $count,
        CarbonImmutable $date,
        string $url
    ): array {
        $resourceGroup = $this->resourceGroupResolver->resolve($institutionSlug, $resourceGroupSlug);

        $resources = Resource::query()
            ->where('is_active', true)
            ->with(['business_hours.week_days', 'resource_group'])
            ->where('resource_group_id', $resourceGroup->id)
            ->orderBy('order')
            ->paginate($count)
            ->withPath($url.'?count='.$count.'&date='.$date->format('Y-m-d'));

        return [
            'resources' => array_values($resources->map(
                fn (Resource $resource): array => $this->presenter->present($resource, $resourceGroup, $date)
            )->values()->all()),
            'pagination' => [
                'previousPage' => $resources->previousPageUrl(),
                'nextPage' => $resources->nextPageUrl(),
            ],
        ];
    }
}
