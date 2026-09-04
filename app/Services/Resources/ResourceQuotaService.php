<?php

namespace App\Services\Resources;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;

class ResourceQuotaService
{
    /** @var array<string, list<array{happening: Happening, start: CarbonImmutable, end: CarbonImmutable}>> */
    private array $otherHappeningsCache = [];

    public function __construct(
        private readonly ResourceAvailabilityService $availabilityService,
        private readonly ResourceSettingsResolver $settingsResolver,
    ) {
        // Laravel caches the resolved controller (and its whole injected object graph, this
        // service included) on the Route object after its first dispatch, so this instance can
        // outlive a single request/test call and see multiple, independently-mutating requests
        // (e.g. sequential postJson() calls in one Pest test). Without this, a Happening created
        // after otherHappenings() cached an empty result for its cache key would never be seen.
        Happening::saved(function (): void {
            $this->otherHappeningsCache = [];
        });
        Happening::deleted(function (): void {
            $this->otherHappeningsCache = [];
        });
    }

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

        $happenings = $this->otherHappenings($user, $resource, $happening, $start);

        foreach ($happenings as $candidate) {
            $originalOtherStart = $candidate['start'];

            [$isClosed, $otherStart, $otherEnd] = $this->availabilityService->findClosed(
                $resource,
                $originalOtherStart,
                $candidate['end'],
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

        $happenings = $this->otherHappenings($user, $resource, $happening, $timeSlot);

        foreach ($happenings as $candidate) {
            if ($isEnd) {
                if ($timeSlot > $candidate['start'] && $timeSlot <= $candidate['end']) {
                    return true;
                }
            } elseif ($timeSlot >= $candidate['start'] && $timeSlot < $candidate['end']) {
                return true;
            }
        }

        return false;
    }

    private function hoursBetween(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return $start->diffInMinutes($end) / 60;
    }

    /**
     * Bounded to the calendar week containing $referenceDate: isExceedingQuotas() only ever
     * compares candidates against that week (isSameWeek/isSameDay against $start), and
     * isConcurrentUserTimeSlot()'s $timeSlot always falls within that same week (time slots
     * are generated for a single day at a time), so this is a safe bound for both callers.
     *
     * @return list<array{happening: Happening, start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function otherHappenings(
        User $user,
        Resource $resource,
        ?Happening $happening,
        CarbonImmutable $referenceDate,
    ): array {
        $cacheKey = $user->id.'|'.$resource->resource_group->id.'|'.($happening->id ?? '').'|'.$referenceDate->startOfWeek()->toDateString();

        return $this->otherHappeningsCache[$cacheKey] ??= array_values($user->getOtherUserHappeningsForResourceGroup(
            $resource->resource_group,
            $happening,
            $referenceDate->startOfWeek(),
            $referenceDate->endOfWeek(),
        )
            ->map(fn (Happening $otherHappening): array => [
                'happening' => $otherHappening,
                'start' => CarbonImmutable::parse($otherHappening->start),
                'end' => CarbonImmutable::parse($otherHappening->end),
            ])
            ->all());
    }
}
