<?php

namespace App\Services\Http;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use App\Services\Resources\GenerateResourceTimeSlotsAction;
use Carbon\CarbonImmutable;

class GetResourceTimeSlotsAction
{
    public function __construct(private readonly GenerateResourceTimeSlotsAction $generateResourceTimeSlotsAction) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(
        string $resourceId,
        ?string $happeningId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $resource = Resource::query()
            ->with([
                'business_hours.week_days',
                'resource_group.settings',
                'resource_group.institution.settings',
                'resource_group.institution.closings',
                'happenings',
            ])
            ->findOrFail($resourceId);

        $happening = $happeningId ? Happening::find($happeningId) : null;
        $actor = auth()->user();

        return $this->generateResourceTimeSlotsAction->execute(
            $resource,
            $actor instanceof User ? $actor : null,
            $start,
            $end,
            $happening,
        );
    }
}
