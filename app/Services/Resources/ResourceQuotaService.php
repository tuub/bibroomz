<?php

namespace App\Services\Resources;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;

class ResourceQuotaService
{
    public function __construct(
        private readonly ResourceAvailabilityService $availabilityService,
        private readonly ResourceSettingsResolver $settingsResolver,
    ) {}

    /**
     * @throws BindingResolutionException
     */
    public function isExceedingQuotas(
        Resource $resource,
        ?User $user,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Happening $happening = null,
    ): bool {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('unlimited_quotas', $resource->resource_group->institution)) {
            return false;
        }

        $quotaHappeningBlockHours = $this->settingsResolver->resourceGroupFloat(
            $resource,
            'quota_happening_block_hours',
        );
        $quotaWeeklyHappenings = $this->settingsResolver->resourceGroupFloat($resource, 'quota_weekly_happenings');
        $quotaWeeklyHours = $this->settingsResolver->resourceGroupFloat($resource, 'quota_weekly_hours');
        $quotaDailyHours = $this->settingsResolver->resourceGroupFloat($resource, 'quota_daily_hours');

        $happeningBlockHours = $this->hoursBetween($start, $end);

        $weeklyHappenings = 1;
        $weeklyHours = $happeningBlockHours;
        $dailyHours = $happeningBlockHours;

        $happenings = $user->getOtherUserHappeningsForResourceGroup($resource->resource_group, $happening);

        foreach ($happenings as $otherHappening) {
            $originalOtherStart = new CarbonImmutable($otherHappening->start);

            [$isClosed, $otherStart, $otherEnd] = $this->availabilityService->findClosed(
                $resource,
                $originalOtherStart,
                new CarbonImmutable($otherHappening->end),
            );

            if ($isClosed) {
                continue;
            }

            if ($originalOtherStart->isSameWeek($start)) {
                $weeklyHappenings += 1;
                $weeklyHours += $this->hoursBetween($otherStart, $otherEnd);
            }

            if ($originalOtherStart->isSameDay($start)) {
                $dailyHours += $this->hoursBetween($otherStart, $otherEnd);
            }
        }

        if ($quotaHappeningBlockHours > 0 && $happeningBlockHours > $quotaHappeningBlockHours) {
            return true;
        }

        if ($quotaWeeklyHappenings > 0 && $weeklyHappenings > $quotaWeeklyHappenings) {
            return true;
        }

        if ($quotaWeeklyHours > 0 && $weeklyHours > $quotaWeeklyHours) {
            return true;
        }

        return $quotaDailyHours > 0 && $dailyHours > $quotaDailyHours;
    }

    public function isConcurrentUserTimeSlot(
        Resource $resource,
        ?User $user,
        CarbonImmutable $timeSlot,
        ?Happening $happening = null,
        bool $isEnd = false,
    ): bool {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('edit', $resource->resource_group->institution)) {
            return false;
        }

        $happenings = $user->getOtherUserHappeningsForResourceGroup($resource->resource_group, $happening);

        foreach ($happenings as $otherHappening) {
            if ($isEnd) {
                if ($timeSlot > $otherHappening->start && $timeSlot <= $otherHappening->end) {
                    return true;
                }
            } elseif ($timeSlot >= $otherHappening->start && $timeSlot < $otherHappening->end) {
                return true;
            }
        }

        return false;
    }

    private function hoursBetween(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return $start->diffInMinutes($end) / 60;
    }
}
