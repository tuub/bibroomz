<?php

namespace App\Models;

use App\Library\Utility;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Carbon\Exceptions\InvalidTimeZoneException;
use Carbon\Exceptions\InvalidTypeException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Resource extends Model
{
    use HasFactory, HasUUID;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'resources';
    protected string $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['institution_id', 'title', 'location', 'description', 'capacity', 'is_active'];
    protected $with = ['closings'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function happenings(): HasMany
    {
        return $this->hasMany(Happening::class);
    }

    public function business_hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    public function closings(): MorphMany
    {
        return $this->morphMany(Closing::class, 'closable');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    // FIXME: where to use? See platzbuchung-lite
    public function isCurrentlyOpen(): bool
    {
        foreach ($this->business_hours() as $business_hour) {
            if ($business_hour->week_days()->count() > 0) {
                if ($business_hour->week_days->where('day_of_week', Carbon::today()->dayOfWeek)->first()) {
                    $opening = Utility::getDateTimeFromStrings(
                        Carbon::today()->toDateString(),
                        $business_hour->start
                    );
                    $closing = Utility::getDateTimeFromStrings(
                        Carbon::today()->toDateString(),
                        $business_hour->end
                    );

                    if (Carbon::now()->isBetween($opening, $closing)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param CarbonImmutable $start
     * @param CarbonImmutable $end
     * @param Happening|null $happening
     * @return array
     * @throws InvalidArgumentException
     */
    public function getFormBusinessHours(CarbonImmutable $start, CarbonImmutable $end, Happening $happening = null): array
    {
        $start_time_slots = $this->getStartTimeSlots($start, $happening)->values();
        $end_time_slots = $this->getEndTimeSlots($start, $end, $happening)->values();

        return ['start' => $start_time_slots, 'end' => $end_time_slots];
    }

    /**
     * @param CarbonPeriodImmutable $time_slots
     * @param CarbonImmutable $selected
     * @return Collection
     */
    private function initTimeSlots(CarbonPeriodImmutable $time_slots, CarbonImmutable $selected): Collection
    {
        return collect($time_slots)->map(function ($time_slot) use ($selected) {
            return [
                'time' => $time_slot,
                'label' => $time_slot->format(env('TIME_FORMAT')),
                'is_disabled' => true,
                'is_selected' => $time_slot == $selected,
            ];
        });
    }

    /**
     * @param CarbonImmutable $start
     * @param Happening|null $happening
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function getStartTimeSlots(CarbonImmutable $start, Happening $happening = null): Collection
    {
        $interval = Utility::getTimeValuesFromEnvTimeString(env('RESERVATION_TIMESLOT_LENGTH'));

        $time_slots = $this->initTimeSlots(CarbonPeriodImmutable::start($start->startOfDay())->minutes($interval['minute'])->end($start->endOfDay()), $start);

        $time_slots = $this->removePastTimeSlots($time_slots);
        $time_slots = $this->enableBusinessHours($time_slots);
        $time_slots = $this->disableClosedTimeSlots($time_slots);
        $time_slots = $this->disableHappeningTimeSlots($time_slots, $happening);
        $time_slots = $this->adjustSelectedTimeSlots($time_slots);

        return $time_slots;
    }

    /**
     * @param CarbonImmutable $start
     * @param CarbonImmutable $end
     * @param Happening|null $happening
     * @return Collection
     * @throws InvalidArgumentException
     */
    private function getEndTimeSlots(CarbonImmutable $start, CarbonImmutable $end, Happening $happening = null): Collection
    {
        $interval = Utility::getTimeValuesFromEnvTimeString(env('RESERVATION_TIMESLOT_LENGTH'));

        $time_slots = $this->initTimeSlots(CarbonPeriodImmutable::start($start->startOfDay())->minutes($interval['minute'])->end($start->endOfDay()), $end);

        $time_slots = $this->removePastTimeSlots($time_slots);
        $time_slots = $this->enableBusinessHours($time_slots, is_end: true);
        $time_slots = $this->disableClosedTimeSlots($time_slots, is_end: true);

        $time_slots = $time_slots->map(function ($time_slot) use ($start) {
            $max_hours = env('RESERVATION_BLOCK_HOUR_QUOTA');

            if ($time_slot['time'] <= ($start)) {
                $time_slot['is_disabled'] = true;
            }

            if ($time_slot['time']->floatDiffInHours($start) > $max_hours) {
                $time_slot['is_disabled'] = true;
            }

            return $time_slot;
        });

        $time_slots = $this->disableHappeningTimeSlots($time_slots, $happening, is_end: true);
        $time_slots = $this->disableNonSeqentialTimeSlots($time_slots, $start);
        $time_slots = $this->adjustSelectedTimeSlots($time_slots);

        return $time_slots;
    }

    /**
     * @param Collection $time_slots
     * @return Collection
     */
    private function adjustSelectedTimeSlots(Collection $time_slots): Collection
    {
        $time_slots = $this->deselectDisabledTimeSlots($time_slots);

        if (!$this->containsSelectedTimeSlot($time_slots)) {
            $time_slots = $this->selectFirstEnabledTimeSlot($time_slots);
        }

        return $time_slots;
    }

    /**
     * @param Collection $time_slots
     * @return Collection
     */
    private function deselectDisabledTimeSlots(Collection $time_slots): Collection
    {
        return $time_slots->map(function ($time_slot) {
            if ($time_slot['is_selected'] && $time_slot['is_disabled']) {
                $time_slot['is_selected'] = false;
            }

            return $time_slot;
        });
    }

    /**
     * @param Collection $time_slots
     * @return bool
     */
    private function containsSelectedTimeSlot(Collection $time_slots): bool
    {
        return $time_slots->contains(fn ($time_slot) => $time_slot['is_selected']);
    }

    /**
     * @param Collection $time_slots
     * @return Collection
     */
    private function selectFirstEnabledTimeSlot(Collection $time_slots): Collection
    {
        $key_to_select = $this->getFirstEnabledTimeSlotKey($time_slots);

        return $time_slots->map(function ($time_slot, $key) use ($key_to_select) {
            if ($key == $key_to_select) {
                $time_slot['is_selected'] = true;
            }

            return $time_slot;
        });
    }

    /**
     * @param Collection $time_slots
     * @return array-key|bool
     */
    private function getFirstEnabledTimeSlotKey(Collection $time_slots)
    {
        return $time_slots->search(fn ($time_slot) => !$time_slot['is_disabled']);
    }

    /**
     * @param Collection $time_slots
     * @param CarbonImmutable $start
     * @return Collection
     */
    private function disableNonSeqentialTimeSlots(Collection $time_slots, CarbonImmutable $start): Collection
    {
        return $time_slots->map(function ($time_slot) use ($start, $time_slots) {
            if ($time_slots->contains(fn ($_time_slot) => $_time_slot['time'] > $start && $_time_slot['time'] < $time_slot['time'] && $_time_slot['is_disabled'])) {
                $time_slot['is_disabled'] = true;
            }

            return $time_slot;
        });
    }

    /**
     * @param Collection $time_slots
     * @return Collection
     */
    private function removePastTimeSlots(Collection $time_slots): Collection
    {
        return $time_slots->filter(function ($time_slot) {
            // HACK!
            $tz_offset = Carbon::now(env('APP_TIMEZONE'))->offsetHours;
            $now = Carbon::now()->addHours($tz_offset);

            // keep future time slots
            if ($time_slot['time']->isAfter($now)) {
                return true;
            }

            // keep current time slot
            $interval = Utility::getTimeValuesFromEnvTimeString(env('RESERVATION_TIMESLOT_LENGTH'));
            $diff = $time_slot['time']->diffInMinutes($now);

            if ($diff < $interval['minute']) {
                return true;
            }

            // discard all other time slots
            return false;
        });
    }

    /**
     * @param Collection $time_slots
     * @param bool $is_end
     * @return Collection
     */
    private function disableClosedTimeSlots(Collection $time_slots, bool $is_end =  false): Collection
    {
        return $time_slots->map(function ($time_slot) use ($is_end) {
            if ($this->isTimeSlotInClosing($time_slot, $is_end)) {
                $time_slot['is_disabled'] = true;
            }

            return $time_slot;
        });
    }

    /**
     * @param mixed $time_slot
     * @param bool $is_end
     * @return bool
     */
    private function isTimeSlotInClosing($time_slot, bool $is_end = false): bool
    {
        $closings = $this->closings->merge($this->institution->closings);
        foreach ($closings as $closing) {
            if ($is_end) {
                if ($time_slot['time'] > $closing->start && $time_slot['time'] < $closing->end) {
                    return true;
                }
            } elseif ($time_slot['time'] >= $closing->start && $time_slot['time'] < $closing->end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Collection $time_slots
     * @param bool $is_end
     * @return Collection
     */
    private function enableBusinessHours(Collection $time_slots, bool $is_end = false): Collection
    {
        return $time_slots->map(function ($time_slot) use ($is_end) {
            if ($this->isTimeSlotInBusinessHour($time_slot, $is_end)) {
                $time_slot['is_disabled'] = false;
            }

            return $time_slot;
        });
    }

    /**
     * @param mixed $time_slot
     * @param bool $is_end
     * @return bool
     * @throws InvalidFormatException
     * @throws InvalidTimeZoneException
     * @throws InvalidTypeException
     */
    private function isTimeSlotInBusinessHour($time_slot, bool $is_end = false): bool
    {
        foreach ($this->business_hours as $business_hour) {
            $week_days = $business_hour->week_days->pluck('day_of_week')->toArray();

            $business_hour_start = Carbon::parse($business_hour->start)->setDateFrom($time_slot['time']);
            $business_hour_end = Carbon::parse($business_hour->end)->setDateFrom($time_slot['time']);

            if (in_array($time_slot['time']->dayOfWeek, $week_days)) {
                if ($is_end) {
                    if ($time_slot['time'] > $business_hour_start && $time_slot['time'] <= $business_hour_end) {
                        return true;
                    }
                } elseif ($time_slot['time'] >= $business_hour_start && $time_slot['time'] < $business_hour_end) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Collection $time_slots
     * @param Happening|null $happening
     * @param bool $is_end
     * @return Collection
     */
    private function disableHappeningTimeSlots(Collection $time_slots, Happening $happening = null, bool $is_end = false): Collection
    {
        return $time_slots->map(function ($time_slot) use ($happening, $is_end) {
            if ($this->isTimeSlotReserved($time_slot, $happening, $is_end)) {
                $time_slot['is_disabled'] = true;
            }

            return $time_slot;
        });
    }

    /**
     * @param mixed $time_slot
     * @param Happening|null $happening
     * @param bool $is_end
     * @return bool
     */
    private function isTimeSlotReserved($time_slot, Happening $happening = null, bool $is_end = false): bool
    {
        foreach ($this->happenings->whereNotIn('id', [$happening?->id]) as $_happening) {
            if ($is_end) {
                if ($time_slot['time'] > ($_happening->start) && $time_slot['time'] <= ($_happening->end)) {
                    return true;
                }
            } elseif ($time_slot['time'] >= ($_happening->start) && $time_slot['time'] < ($_happening->end)) {
                return true;
            }
        }

        return false;
    }

    protected static function booted()
    {
        static::deleting(function (Resource $resource) {
            $resource->business_hours()->delete();
        });
    }
}
