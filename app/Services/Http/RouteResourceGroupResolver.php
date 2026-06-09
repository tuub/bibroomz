<?php

namespace App\Services\Http;

use App\Models\ResourceGroup;

class RouteResourceGroupResolver
{
    /**
     * @param  array<int, string>  $relations
     */
    public function resolve(string $institutionSlug, string $resourceGroupSlug, array $relations = []): ResourceGroup
    {
        return ResourceGroup::query()
            ->with($relations)
            ->whereHas(
                'institution',
                fn ($query) => $query->where('slug', $institutionSlug)
            )
            ->where('slug', $resourceGroupSlug)
            ->firstOrFail();
    }
}
