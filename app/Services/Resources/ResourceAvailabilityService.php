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
    public function __construct(private ResourceBusinessHoursResolver $businessHoursResolver)
    {
    }

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
        foreach ($resource->happenings->whereNotIn('id', [$happening?->id]) as $existingHappening) {
            if ($existingHappening->isConcurrent($start, $end)) {
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
        foreach ($resource->happenings->whereNotIn('id', [$happening?->id]) as $existingHappening) {
            if ($isEnd) {
                if ($timeSlot > $existingHappening->start && $timeSlot <= $existingHappening->end) {
                    return true;
                }
            } elseif ($timeSlot >= $existingHappening->start && $timeSlot < $existingHappening->end) {
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
        return $resource->closings->concat($resource->resource_group->institution->closings)->values();
    }
}
