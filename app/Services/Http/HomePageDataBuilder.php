<?php

namespace App\Services\Http;

use App\Models\Institution;
use App\Models\ResourceGroup;
use Illuminate\Database\Eloquent\Builder;

class HomePageDataBuilder
{
    public function __construct(
        private InstitutionAccessService $institutionAccessService,
        private ResourceGroupSettingsMapper $settingsMapper
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStartPageData(?string $ip = null): array
    {
        $institutions = Institution::query()
            ->where('is_active', true)
            ->whereHas('resource_groups', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with([
                'settings',
                'resource_groups' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('order'),
            ])
            ->orderBy('order')
            ->get();

        $institutions = $this->institutionAccessService->filterAllowed($institutions, $ip);
        $resourceGroups = $institutions->flatMap(
            static fn (Institution $institution): array => $institution->resource_groups
                ->where('is_active', true)
                ->sortBy('order')
                ->values()
                ->all(),
        );

        if ($resourceGroups->count() === 1) {
            $resourceGroup = $resourceGroups->first();

            if (! $resourceGroup instanceof ResourceGroup) {
                return [
                    'props' => [
                        'appName' => config('app.name'),
                        'institutions' => $institutions,
                    ],
                ];
            }

            $institution = $institutions->firstWhere('id', $resourceGroup->institution_id);

            if (! $institution instanceof Institution) {
                return [
                    'props' => [
                        'appName' => config('app.name'),
                        'institutions' => $institutions,
                    ],
                ];
            }

            return [
                'redirect' => [
                    'institution_slug' => $institution->slug,
                    'resource_group_slug' => $resourceGroup->slug,
                ],
            ];
        }

        return [
            'props' => [
                'appName' => config('app.name'),
                'institutions' => $institutions,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildHomePageData(ResourceGroup $resourceGroup): array
    {
        return [
            'resourceGroup' => $resourceGroup,
            'settings' => $this->settingsMapper->map($resourceGroup),
            'hiddenDays' => $resourceGroup->institution->getHiddenDays(),
            'isMultiTenancy' => ResourceGroup::query()
                ->where('is_active', true)
                ->whereHas('institution', fn ($query) => $query->where('is_active', true))
                ->count() > 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildTerminalViewData(ResourceGroup $resourceGroup): array
    {
        return [
            'resourceGroup' => $resourceGroup,
            'settings' => $this->settingsMapper->map($resourceGroup),
            'hiddenDays' => $resourceGroup->institution->getHiddenDays(),
        ];
    }
}
