<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Bkwld\Cloner\Cloneable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessHour extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasFactory, HasUUID, UUIDIsPrimaryKey, Cloneable;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'business_hours';
    protected $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'resource_id',
        'start',
        'end',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected $cloneable_relations = [
        'week_days',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    // A business hour belongs to 1 resource
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    // A business hour belongs to many week days
    public function week_days()
    {
        return $this->belongsToMany(WeekDay::class)->orderBy('id', 'asc');
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/

    public function isOpenOnDayOfWeek($dayOfWeek)
    {
        return $this->week_days->pluck('day_of_week')->contains($dayOfWeek);
    }

    public function isFallback()
    {
        return $this->start_date === null && $this->end_date === null;
    }

    public function isValidForDate(CarbonImmutable $date)
    {
        if ($this->start_date && $this->end_date) {
            return $date->startOfDay()->isBetween($this->start_date, $this->end_date);
        }

        if ($this->start_date) {
            return $date->startOfDay() >= $this->start_date;
        }

        if ($this->end_date) {
            return $date->startOfDay() <= $this->end_date;
        }

        return false;
    }

    public function isOpen(CarbonImmutable $start, CarbonImmutable $end)
    {
        $is_open = false;

        $business_hour_start = CarbonImmutable::parse($this->start)->setDateFrom($start);
        $business_hour_end = CarbonImmutable::parse($this->end)->setDateFrom($end);

        if (!$end->isMidnight() && $business_hour_end->isMidnight()) {
            $business_hour_end = $business_hour_end->addDay();
        }

        if ($this->isOpenOnDayOfWeek($start->dayOfWeek)) {
            if (
                $start >= $business_hour_start
                && $end <= $business_hour_end
            ) {
                // business_hour->start <= start < end <= business_hour->end
                $is_open = true;
            } else {
                if (
                    $start >= $business_hour_start
                    && $start < $business_hour_end
                    && $end > $business_hour_end
                ) {
                    // business_hour->start <= start < business_hour->end < end
                    $is_open = true;
                    $end = $business_hour_end;
                }

                if (
                    $end > $business_hour_start
                    && $end <= $business_hour_end
                    && $start < $business_hour_start
                ) {
                    // start < business_hour->start < end <= business_hour->end
                    $is_open = true;
                    $start = $business_hour_start;
                }
            }
        }

        return [$is_open, $start, $end];
    }
}
