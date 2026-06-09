<?php

namespace App\Services\Resources;

use App\Library\Utility;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class GenerateResourceTimeSlotsAction
{
    public function __construct(
        private readonly ResourceAvailabilityService $availabilityService,
        private readonly ResourceQuotaService $quotaService,
        private readonly ResourceSettingsResolver $settingsResolver,
    ) {}

    /**
     * @return array{
     *   start: list<array{time: CarbonImmutable, label: string, is_disabled: bool, is_selected: bool}>,
     *   end: list<array{time: CarbonImmutable, label: string, is_disabled: bool, is_selected: bool}>
     * }
     *
     * @throws InvalidArgumentException
     */
    public function execute(
        Resource $resource,
        ?User $actor,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Happening $happening = null,
    ): array {
        $startSlots = array_values($this->getStartTimeSlots($resource, $actor, $start, $happening)
            ->values()
            ->map(fn (ResourceTimeSlot $timeSlot): array => $timeSlot->toArray())
            ->all());
        $endSlots = array_values($this->getEndTimeSlots($resource, $actor, $start, $end, $happening)
            ->values()
            ->map(fn (ResourceTimeSlot $timeSlot): array => $timeSlot->toArray())
            ->all());

        return [
            'start' => $startSlots,
            'end' => $endSlots,
        ];
    }

    /**
     * @return Collection<int, ResourceTimeSlot>
     */
    private function getStartTimeSlots(
        Resource $resource,
        ?User $actor,
        CarbonImmutable $start,
        ?Happening $happening = null,
    ): Collection {
        $timeSlots = $this->initTimeSlots($resource, $this->initTimePeriod($resource, $start), $start);

        $timeSlots = $this->removePastTimeSlots($resource, $timeSlots);
        $timeSlots = $this->enableBusinessHours($resource, $timeSlots);
        $timeSlots = $this->disableClosedTimeSlots($resource, $timeSlots);
        $timeSlots = $this->disableReservedTimeSlots($resource, $timeSlots, $happening);
        $timeSlots = $this->disableConcurrentUserHappeningTimeSlots($resource, $actor, $timeSlots, $happening);

        return $this->adjustSelectedTimeSlots($timeSlots);
    }

    /**
     * @return Collection<int, ResourceTimeSlot>
     */
    private function getEndTimeSlots(
        Resource $resource,
        ?User $actor,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Happening $happening = null,
    ): Collection {
        $timeSlots = $this->initTimeSlots($resource, $this->initTimePeriod($resource, $start), $end);

        $timeSlots = $this->removePastTimeSlots($resource, $timeSlots);
        $timeSlots = $this->enableBusinessHours($resource, $timeSlots, isEnd: true);
        $timeSlots = $this->disableClosedTimeSlots($resource, $timeSlots, isEnd: true);
        $timeSlots = $timeSlots->map(function (ResourceTimeSlot $timeSlot) use ($start): ResourceTimeSlot {
            if ($timeSlot->time <= $start) {
                return $timeSlot->withDisabled(true);
            }

            return $timeSlot;
        });
        $timeSlots = $this->disableQuotas($resource, $actor, $timeSlots, $start, $happening);
        $timeSlots = $this->disableReservedTimeSlots($resource, $timeSlots, $happening, isEnd: true);
        $timeSlots = $this->disableConcurrentUserHappeningTimeSlots(
            $resource,
            $actor,
            $timeSlots,
            $happening,
            isEnd: true,
        );
        $timeSlots = $this->disableNonSequentialTimeSlots($timeSlots, $start);

        return $this->adjustSelectedTimeSlots($timeSlots);
    }

    private function initTimePeriod(Resource $resource, CarbonImmutable $date): CarbonPeriod
    {
        $interval = $this->settingsResolver->timeSlotLength($resource);
        $start = $date->startOfDay();
        $end = $start->addDay();

        $period = new CarbonPeriod($start, $end);

        if ($interval['hour'] > 0) {
            $period = $period->hours($interval['hour']);
        }

        if ($interval['minute'] > 0) {
            return $period->minutes($interval['minute']);
        }

        return $period;
    }

    /**
     * @return Collection<int, ResourceTimeSlot>
     */
    private function initTimeSlots(
        Resource $resource,
        CarbonPeriod $timeSlots,
        CarbonImmutable $selected,
    ): Collection {
        $timeFormat = $this->settingsResolver->institutionString($resource, 'time_format', 'H:i');
        $slots = [];

        foreach ($timeSlots as $timeSlot) {
            if (! $timeSlot instanceof \DateTimeInterface) {
                continue;
            }

            $slotTime = CarbonImmutable::instance($timeSlot);
            $slots[] = new ResourceTimeSlot(
                time: $slotTime,
                label: $slotTime->format($timeFormat),
                isDisabled: true,
                isSelected: $slotTime == $selected,
            );
        }

        return collect($slots);
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function removePastTimeSlots(Resource $resource, Collection $timeSlots): Collection
    {
        $interval = $this->settingsResolver->timeSlotLength($resource);
        $intervalMinutes = $interval['minute'] + 60 * $interval['hour'];

        return $timeSlots->filter(function (ResourceTimeSlot $timeSlot) use ($intervalMinutes): bool {
            $now = Utility::getCarbonNow();

            if ($timeSlot->time->isAfter($now)) {
                return true;
            }

            return $timeSlot->time->diffInMinutes($now) < $intervalMinutes;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function enableBusinessHours(Resource $resource, Collection $timeSlots, bool $isEnd = false): Collection
    {
        return $timeSlots->map(function (ResourceTimeSlot $timeSlot) use ($resource, $isEnd): ResourceTimeSlot {
            if ($this->availabilityService->isTimeSlotInBusinessHour($resource, $timeSlot->time, $isEnd)) {
                return $timeSlot->withDisabled(false);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function disableClosedTimeSlots(Resource $resource, Collection $timeSlots, bool $isEnd = false): Collection
    {
        return $timeSlots->map(function (ResourceTimeSlot $timeSlot) use ($resource, $isEnd): ResourceTimeSlot {
            if ($this->availabilityService->isTimeSlotInClosing($resource, $timeSlot->time, $isEnd)) {
                return $timeSlot->withDisabled(true);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function disableReservedTimeSlots(
        Resource $resource,
        Collection $timeSlots,
        ?Happening $happening = null,
        bool $isEnd = false,
    ): Collection {
        return $timeSlots->map(function (ResourceTimeSlot $timeSlot) use ($resource, $happening, $isEnd): ResourceTimeSlot {
            if ($this->availabilityService->isTimeSlotReserved($resource, $timeSlot->time, $happening, $isEnd)) {
                return $timeSlot->withDisabled(true);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function disableQuotas(
        Resource $resource,
        ?User $actor,
        Collection $timeSlots,
        CarbonImmutable $start,
        ?Happening $happening = null,
    ): Collection {
        return $timeSlots->map(function (ResourceTimeSlot $timeSlot) use ($resource, $actor, $start, $happening): ResourceTimeSlot {
            if ($this->quotaService->isExceedingQuotas($resource, $actor, $start, $timeSlot->time, $happening)) {
                return $timeSlot->withDisabled(true);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function disableConcurrentUserHappeningTimeSlots(
        Resource $resource,
        ?User $actor,
        Collection $timeSlots,
        ?Happening $happening = null,
        bool $isEnd = false,
    ): Collection {
        return $timeSlots->map(function (ResourceTimeSlot $timeSlot) use ($resource, $actor, $happening, $isEnd): ResourceTimeSlot {
            if ($this->quotaService->isConcurrentUserTimeSlot($resource, $actor, $timeSlot->time, $happening, $isEnd)) {
                return $timeSlot->withDisabled(true);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function disableNonSequentialTimeSlots(Collection $timeSlots, CarbonImmutable $start): Collection
    {
        return $timeSlots->map(function (ResourceTimeSlot $timeSlot) use ($start, $timeSlots): ResourceTimeSlot {
            $hasDisabledGap = $timeSlots->contains(
                fn (ResourceTimeSlot $candidate): bool => $candidate->time > $start
                    && $candidate->time < $timeSlot->time
                    && $candidate->isDisabled
            );

            if ($hasDisabledGap) {
                return $timeSlot->withDisabled(true);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function adjustSelectedTimeSlots(Collection $timeSlots): Collection
    {
        $timeSlots = $this->deselectDisabledTimeSlots($timeSlots);

        if (! $this->containsSelectedTimeSlot($timeSlots)) {
            return $this->selectFirstEnabledTimeSlot($timeSlots);
        }

        return $timeSlots;
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function deselectDisabledTimeSlots(Collection $timeSlots): Collection
    {
        return $timeSlots->map(function (ResourceTimeSlot $timeSlot): ResourceTimeSlot {
            if ($timeSlot->isSelected && $timeSlot->isDisabled) {
                return $timeSlot->withSelected(false);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     */
    private function containsSelectedTimeSlot(Collection $timeSlots): bool
    {
        return $timeSlots->contains(fn (ResourceTimeSlot $timeSlot): bool => $timeSlot->isSelected);
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     * @return Collection<int, ResourceTimeSlot>
     */
    private function selectFirstEnabledTimeSlot(Collection $timeSlots): Collection
    {
        $keyToSelect = $this->getFirstEnabledTimeSlotKey($timeSlots);

        return $timeSlots->map(function (ResourceTimeSlot $timeSlot, int|string $key) use ($keyToSelect): ResourceTimeSlot {
            if ($key === $keyToSelect) {
                return $timeSlot->withSelected(true);
            }

            return $timeSlot;
        });
    }

    /**
     * @param  Collection<int, ResourceTimeSlot>  $timeSlots
     */
    private function getFirstEnabledTimeSlotKey(Collection $timeSlots): int|false
    {
        return $timeSlots->search(fn (ResourceTimeSlot $timeSlot): bool => ! $timeSlot->isDisabled);
    }
}
