<?php

namespace App\Http\Requests;

use App\Models\ResourceGroup;
use App\Services\Http\RouteResourceGroupResolver;
use Carbon\CarbonImmutable;

class CalendarEntriesRequest extends ResourceGroupRouteRequest
{
    private ?ResourceGroup $resourceGroup = null;

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return $this->mergeRuleSets(parent::rules(), [
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);
    }

    public function resourceGroup(): ResourceGroup
    {
        if ($this->resourceGroup instanceof ResourceGroup) {
            return $this->resourceGroup;
        }

        return $this->resourceGroup = app(RouteResourceGroupResolver::class)->resolve(
            $this->institutionSlug(),
            $this->resourceGroupSlug(),
            [
                'institution.closings',
                'resources.closings',
                'resources.business_hours.week_days',
                'resources.resource_group.settings',
            ],
        );
    }

    public function startAt(): CarbonImmutable
    {
        $start = $this->input('start');

        return CarbonImmutable::parse(is_string($start) ? $start : null);
    }

    public function endAt(): CarbonImmutable
    {
        $end = $this->input('end');

        return CarbonImmutable::parse(is_string($end) ? $end : null);
    }
}
