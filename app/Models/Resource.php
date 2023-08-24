<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use App\Library\Utility;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Carbon\Exceptions\InvalidFormatException;
use Carbon\Exceptions\InvalidTimeZoneException;
use Carbon\Exceptions\InvalidTypeException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use \Bkwld\Cloner\Cloneable;

class Resource extends Model
{
    use HasFactory, HasUUID, UUIDIsPrimaryKey, Cloneable;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'resources';
    protected string $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'institution_id',
        'title',
        'location',
        'location_uri',
        'description',
        'capacity',
        'is_active',
        'is_verification_required',
    ];
    protected $with = ['closings'];
    protected $casts = [
        'is_active' => 'boolean',
        'is_verification_required' => 'boolean',
    ];
    protected $cloneable_relations = [
        'institution',
        'business_hours',
        'closings',
    ];

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

    public function onCloning($source, $child = null) {
        $this->title = $source->title . ' (' . trans('admin.general.table.clone') . ')';
        if ($child) echo 'This was cloned as a relation!';
        echo 'The original key is: '.$source->getKey();
    }

    /**
     * @param CarbonImmutable $start
     * @param CarbonImmutable $end
     * @return array
     */
    public function findClosed(CarbonImmutable $start, CarbonImmutable $end)
    {
        $closed = false;
        $closings = $this->closings->merge($this->institution->closings);

        foreach ($closings as $closing) {
            if ($start >= $closing->start && $end <= $closing->end) {
                // start and end are in closing: not happening
                $closed = true;
                break;
            } elseif ($start >= $closing->start && $start < $closing->end) {
                // start is in closing: start happening after closing end
                $start = $closing->end;
            } elseif ($end > $closing->start  && $end <= $closing->end) {
                // end is in closing: end happening before closing start
                $end = $closing->start;
            } elseif ($start < $closing->start && $end > $closing->end) {
                // start < closing->start < closing->end < end:
                // pick (start, closing->start) or (closing->end, end), wichever is longer
                if ($start->diffInMinutes($closing->start) < $end->diffInMinutes($closing->end)) {
                    $start = $closing->end;
                } else {
                    $end = $closing->start;
                }
            }
        }

        return [$closed, $start, $end];
    }

    /**
     * @param CarbonImmutable $start
     * @param CarbonImmutable $end
     * @return (bool|CarbonImmutable)[]
     * @throws InvalidFormatException
     * @throws InvalidTimeZoneException
     * @throws InvalidTypeException
     */
    public function findOpen(CarbonImmutable $start, CarbonImmutable $end)
    {
        $open = false;
        $business_hours = $this->business_hours;

        foreach ($business_hours as $business_hour) {
            $week_days = $business_hour->week_days->pluck('day_of_week')->toArray();

            $business_hour_start = CarbonImmutable::parse($business_hour->start)->setDateFrom($start);
            $business_hour_end = CarbonImmutable::parse($business_hour->end)->setDateFrom($end);

            if (!($end->hour == 0 && $end->minute == 0)) {
                if ($business_hour_end->hour == 0 && $business_hour_end->minute == 0) {
                    $business_hour_end = $business_hour_end->addDay();
                }
            }

            if (in_array($start->dayOfWeek, $week_days)) {
                if ($start >= $business_hour_start && $end <= $business_hour_end) {
                    // business_hour->start <= start < end <= business_hour->end
                    $open = true;
                    break;
                }

                if (
                    $start >= $business_hour_start && $start < $business_hour_end
                    && $end > $business_hour_end
                ) {
                    // business_hour->start <= start < business_hour->end < end
                    $open = true;
                    $end = $business_hour_end;
                }

                if (
                    $end > $business_hour_start && $end <= $business_hour_end
                    && $start < $business_hour_start
                ) {
                    // start < business_hour->start < end <= business_hour->end
                    $open = true;
                    $start = $business_hour_start;
                }
            }
        }

        return [$open, $start, $end];
    }

    /**
     * @param CarbonImmutable $start
     * @param CarbonImmutable $end
     * @param Happening|null $happening
     * @return bool
     */
    public function isHappening(CarbonImmutable $start, CarbonImmutable $end, Happening $happening = null): bool
    {
        // Log::debug('Happening: ' . $happening?->id. ', ' . $start . ', ' . $end);

        foreach ($this->happenings->whereNotIn('id', [$happening?->id]) as $_happening) {
            // Log::debug('Also: ' . $_happening->id . ', ' . $_happening->start . ', ' . $_happening->end);

            if ($start >= $_happening->start && $start < $_happening->end) {
                // happening start < start < happening end
                return true;
            } elseif ($start < $_happening->start && $end > $_happening->start) {
                // start < happening start < end
                return true;
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
     * @param CarbonImmutable $date
     * @return CarbonPeriod
     */
    private function initTimePeriod(CarbonImmutable $date): CarbonPeriod
    {
        $time_slot_length = $this->institution->settings->where('key', 'time_slot_length')->first()->value;
        $interval = Utility::getTimeValuesFromEnvTimeString($time_slot_length);

        $start = $date->startOfDay();
        $end = $start->addDay();

        $period = CarbonPeriod::start($start)->end($end);

        if ($interval['hour'] > 0) {
            $period = $period->hours($interval['hour']);
        }

        if ($interval['minute'] > 0) {
            $period = $period->minutes($interval['minute']);
        }

        return $period;
    }

    /**
     * @param CarbonPeriod $time_slots
     * @param CarbonImmutable $selected
     * @return Collection
     */
    private function initTimeSlots(CarbonPeriod $time_slots, CarbonImmutable $selected): Collection
    {
        return collect($time_slots)->map(function ($time_slot) use ($selected) {
            $time_slot = new CarbonImmutable($time_slot);

            $time_format = $this->institution->settings->where('key', 'time_format')->first()->value;

            return [
                'time' => $time_slot,
                'label' => $time_slot->format($time_format),
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
        $time_slots = $this->initTimeSlots($this->initTimePeriod($start), $start);

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
        $time_slots = $this->initTimeSlots($this->initTimePeriod($start), $end);

        $time_slots = $this->removePastTimeSlots($time_slots);
        $time_slots = $this->enableBusinessHours($time_slots, is_end: true);
        $time_slots = $this->disableClosedTimeSlots($time_slots, is_end: true);

        // disable time slots before start
        $time_slots = $time_slots->map(function ($time_slot) use ($start) {
            if ($time_slot['time'] <= ($start)) {
                $time_slot['is_disabled'] = true;
            }
            return $time_slot;
        });

        $time_slots = $this->disableQuotas($time_slots, $start, $happening);

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
            $now = Utility::getCarbonNow();

            // keep future time slots
            if ($time_slot['time']->isAfter($now)) {
                return true;
            }

            // keep current time slot
            $time_slot_length = $this->institution->settings->where('key', 'time_slot_length')->first()->value;
            $interval = Utility::getTimeValuesFromEnvTimeString($time_slot_length);

            if ($time_slot['time']->diffInMinutes($now) < $interval['minute'] + 60 * $interval['hour']) {
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
            if ($this->isTimeSlotInBusinessHour($time_slot['time'], $is_end)) {
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
    private function isTimeSlotInBusinessHour(CarbonImmutable $time_slot, bool $is_end = false): bool
    {
        foreach ($this->business_hours as $business_hour) {
            $week_days = $business_hour->week_days->pluck('day_of_week')->toArray();

            $business_hour_start = CarbonImmutable::parse($business_hour->start)->setDateFrom($time_slot);
            $business_hour_end = CarbonImmutable::parse($business_hour->end)->setDateFrom($time_slot);

            if ($is_end && $time_slot->hour == 0 && $time_slot->minute == 0) {
                $business_hour_start = $business_hour_start->subDay();
            } elseif ($business_hour_end->hour == 0 && $business_hour_end->minute == 0) {
                $business_hour_end = $business_hour_end->addDay();
            }

            if (in_array($time_slot->dayOfWeek, $week_days)) {
                if ($is_end) {
                    if ($time_slot > $business_hour_start && $time_slot <= $business_hour_end) {
                        return true;
                    }
                } elseif ($time_slot >= $business_hour_start && $time_slot < $business_hour_end) {
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

    /**
     * @param Collection $time_slots
     * @param CarbonImmutable $start
     * @param Happening|null $happening
     * @return Collection
     */
    private function disableQuotas(Collection $time_slots, CarbonImmutable $start, Happening $happening = null): Collection
    {
        return $time_slots->map(function ($time_slot) use ($start, $happening) {
            $end = $time_slot['time'];

            if ($this->isExceedingQuotas($start, $end, $happening)) {
                $time_slot['is_disabled'] = true;
            }

            return $time_slot;
        });
    }

    /**
     * @param CarbonImmutable $start
     * @param CarbonImmutable $end
     * @param Happening|null $happening
     * @return bool
     * @throws BindingResolutionException
     */
    public function isExceedingQuotas(CarbonImmutable $start, CarbonImmutable $end, Happening $happening = null): bool
    {
        /** @var User */
        $user = auth()->user();
        if ($user->can('edit', $this->institution)) {
            return false;
        }

        $settings = $this->institution->settings;

        $quota_happening_block_hours = $settings->where('key', 'quota_happening_block_hours')->pluck('value')->first();
        $quota_weekly_happenings = $settings->where('key', 'quota_weekly_happenings')->pluck('value')->first();
        $quota_weekly_hours = $settings->where('key', 'quota_weekly_hours')->pluck('value')->first();
        $quota_daily_hours = $settings->where('key', 'quota_daily_hours')->pluck('value')->first();

        $happening_block_hours = $start->floatDiffInHours($end);
        if ($happening_block_hours > $quota_happening_block_hours) {
            return true;
        }

        $weekly_happenings = 1;
        $weekly_hours = $happening_block_hours;
        $daily_hours = $happening_block_hours;

        $happenings = Happening::whereHas('resource', fn (Builder $query) => $query->where('institution_id', $this->institution->getKey()))
            ->whereNot('id', $happening?->id)
            ->where(fn (Builder $query) => $query->where('user_id_01', $user->getKey())
                ->orWhere('user_id_02', $user->getKey()))
            ->get();

        foreach ($happenings as $_happening) {
            $_start = new CarbonImmutable($_happening->start);
            $_end = new CarbonImmutable($_happening->end);

            if ($_start->isSameWeek($start)) {
                $weekly_happenings += 1;
                $weekly_hours += $_start->floatDiffInHours($_end);
            }

            if ($_start->isSameDay($start)) {
                $daily_hours += $_start->floatDiffInHours($_end);
            }
        }

        if ($quota_weekly_happenings > 0 && $weekly_happenings > $quota_weekly_happenings) {
            return true;
        } elseif ($quota_weekly_hours > 0 && $weekly_hours > $quota_weekly_hours) {
            return true;
        } elseif ($quota_daily_hours > 0 && $daily_hours > $quota_daily_hours) {
            return true;
        }

        return false;
    }
}
