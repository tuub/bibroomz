<?php

namespace App\Services\Happenings;

use App\Exceptions\HappeningValidationException;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use App\Services\Resources\ResourceAvailabilityService;
use App\Services\Resources\ResourceQuotaService;
use Carbon\CarbonImmutable;

class ValidateHappeningReservation
{
    public function __construct(
        private ResourceAvailabilityService $availabilityService,
        private ResourceQuotaService $quotaService,
    ) {
    }

    public function execute(
        User $user,
        Resource $resource,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Happening $happening = null,
    ): void {
        $context = [
            'resource_type' => $resource->resource_group->term_singular,
            'resource_title' => $resource->title,
        ];

        if (!$resource->resource_group->isAllowedUser($user)) {
            throw new HappeningValidationException('happening.errors.not_allowed_user', $context);
        }

        [$closed] = $this->availabilityService->findClosed($resource, $start, $end);

        if ($closed) {
            throw new HappeningValidationException('happening.errors.closing', $context);
        }

        [$open] = $this->availabilityService->findOpen($resource, $start, $end);

        if (!$open) {
            throw new HappeningValidationException('happening.errors.business_hours', $context);
        }

        if ($this->availabilityService->hasReservationConflict($resource, $start, $end, $happening)) {
            throw new HappeningValidationException('happening.errors.reserved', $context);
        }

        if ($this->quotaService->isExceedingQuotas($resource, $user, $start, $end, $happening)) {
            throw new HappeningValidationException('happening.errors.quotas');
        }

        if (
            !$user->can('edit', $resource->resource_group->institution)
            && $user->isHavingConcurrentHappening($start, $end, $happening)
        ) {
            throw new HappeningValidationException('happening.errors.concurrent');
        }
    }
}
