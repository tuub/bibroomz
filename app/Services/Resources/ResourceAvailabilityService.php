<?php

namespace App\Services\Resources;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Carbon\Exceptions\InvalidTimeZoneException;
use Carbon\Exceptions\InvalidTypeException;
use Illuminate\Support\Collection;

class ResourceAvailabilityService
{
    /** @var array<string, Collection<int, Closing>> */
    private array $closingsCache = [];

    /** @var array<string, list<array{happening: Happening, start: CarbonImmutable, end: CarbonImmutable}>> */
    private array $reservationCandidatesCache = [];

    public function __construct(private readonly ResourceBusinessHoursResolver $businessHoursResolver) {}

    /**
     * @return array{0: bool, 1: CarbonImmutable, 2: CarbonImmutable}
     */
    public function findClosed(Resource $resource, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $isClosed = false;

        foreach ($this->allClosings($resource) as $closing) {
            $closingStart = CarbonImmutable::parse($closing->start);
            $closingEnd = CarbonImmutable::parse($closing->end);

            if ($start >= $closingStart && $end <= $closingEnd) {
                $isClosed = true;
                break;
            } elseif ($start >= $closingStart && $start < $closingEnd) {
                $start = $closingEnd;
            } elseif ($end > $closingStart && $end <= $closingEnd) {
                $end = $closingStart;
            } elseif ($start < $closingStart && $end > $closingEnd) {
                $beforeClosingMinutes = $start->diffInMinutes($closingStart, true);
                $afterClosingMinutes = $end->diffInMinutes($closingEnd, true);

                if ($beforeClosingMinutes < $afterClosingMinutes) {
                    $start = $closingEnd;
                } else {
                    $end = $closingStart;
                }
            }
        }

        return [$isClosed, $start, $end];
    }

    /**
     * @return array{0: bool, 1: CarbonImmutable, 2: CarbonImmutable}
     *
     * @throws InvalidFormatException
     * @throws InvalidTimeZoneException
     * @throws InvalidTypeException
     */
    public function findOpen(Resource $resource, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $isOpen = false;

        foreach ($this->businessHoursResolver->forDate($resource, $start) as $businessHour) {
            [$isOpen, $start, $end] = $businessHour->isOpen($start, $end);

            if ($isOpen) {
                break;
            }
        }

        return [$isOpen, $start, $end];
    }

    public function hasReservationConflict(
        Resource $resource,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Happening $happening = null,
    ): bool {
        // Queried directly (bounded to the checked interval) instead of going through the
        // resource-wide reservationCandidates() cache, which would otherwise pull the
        // resource's entire happening history just to check a single interval.
        foreach ($this->overlappingHappenings($resource, $start, $end, $happening) as $existingHappening) {
            $candidateStart = CarbonImmutable::parse($existingHappening->start);
            $candidateEnd = CarbonImmutable::parse($existingHappening->end);

            if (($candidateStart >= $start && $candidateStart < $end) || ($candidateStart < $start && $candidateEnd > $start)) {
                return true;
            }
        }

        return false;
    }

    public function isTimeSlotInClosing(Resource $resource, CarbonImmutable $timeSlot, bool $isEnd = false): bool
    {
        foreach ($this->allClosings($resource) as $closing) {
            if ($isEnd) {
                if ($timeSlot > $closing->start && $timeSlot < $closing->end) {
                    return true;
                }
            } elseif ($timeSlot >= $closing->start && $timeSlot < $closing->end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws InvalidFormatException
     * @throws InvalidTimeZoneException
     * @throws InvalidTypeException
     */
    public function isTimeSlotInBusinessHour(Resource $resource, CarbonImmutable $timeSlot, bool $isEnd = false): bool
    {
        foreach ($this->businessHoursResolver->forDate($resource, $timeSlot) as $businessHour) {
            $weekDays = [];

            foreach ($businessHour->week_days as $weekDay) {
                $weekDays[] = $weekDay->day_of_week;
            }

            $businessHourStart = CarbonImmutable::parse($businessHour->start)->setDateFrom($timeSlot);
            $businessHourEnd = CarbonImmutable::parse($businessHour->end)->setDateFrom($timeSlot);

            if ($isEnd && $timeSlot->hour === 0 && $timeSlot->minute === 0) {
                $businessHourStart = $businessHourStart->subDay();
            } elseif ($businessHourEnd->hour === 0 && $businessHourEnd->minute === 0) {
                $businessHourEnd = $businessHourEnd->addDay();
            }

            if (in_array($timeSlot->dayOfWeek, $weekDays, true)) {
                if ($isEnd) {
                    if ($timeSlot > $businessHourStart && $timeSlot <= $businessHourEnd) {
                        return true;
                    }
                } elseif ($timeSlot >= $businessHourStart && $timeSlot < $businessHourEnd) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isTimeSlotReserved(
        Resource $resource,
        CarbonImmutable $timeSlot,
        ?Happening $happening = null,
        bool $isEnd = false,
    ): bool {
        foreach ($this->reservationCandidates($resource, $happening) as $candidate) {
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

    /**
     * @return Collection<int, Closing>
     */
    private function allClosings(Resource $resource): Collection
    {
        $cacheKey = $resource->id;

        return $this->closingsCache[$cacheKey] ??= $resource->closings
            ->concat($resource->resource_group->institution->closings)
            ->values();
    }

    /**
     * Happenings on this resource that overlap [$start, $end), pre-filtered in SQL. The
     * bound is a safe superset of hasReservationConflict()'s exact overlap condition, so
     * PHP-side filtering there still applies the precise check afterward.
     *
     * @return Collection<int, Happening>
     */
    private function overlappingHappenings(
        Resource $resource,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Happening $happening,
    ): Collection {
        return Happening::query()
            ->where('resource_id', $resource->id)
            ->where('start', '<', $end)
            ->where('end', '>', $start)
            ->whereNot('id', $happening?->id)
            ->get();
    }

    /**
     * @return list<array{happening: Happening, start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function reservationCandidates(Resource $resource, ?Happening $happening): array
    {
        $cacheKey = $resource->id.'|'.($happening->id ?? '');

        return $this->reservationCandidatesCache[$cacheKey] ??= array_values($resource->happenings
            ->whereNotIn('id', [$happening?->id])
            ->values()
            ->map(fn (Happening $existingHappening): array => [
                'happening' => $existingHappening,
                'start' => CarbonImmutable::parse($existingHappening->start),
                'end' => CarbonImmutable::parse($existingHappening->end),
            ])
            ->all());
    }
}
