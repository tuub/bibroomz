<?php

namespace App\Models;

use Bkwld\Cloner\Cloneable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read Collection<int, WeekDay> $week_days
 */
#[Fillable([
    'resource_id',
    'start',
    'end',
    'start_date',
    'end_date',
])]
#[Table(name: 'business_hours')]
#[WithoutIncrementing]
class BusinessHour extends Model
{
    use Cloneable, HasUuids;

    /*****************************************************************
     * TRAITS
     ****************************************************************/
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $cloneable_relations = [
        'week_days',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    /**
     * @return BelongsTo<\App\Models\Resource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * @return BelongsToMany<WeekDay, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function week_days(): BelongsToMany
    {
        return $this->belongsToMany(WeekDay::class)->orderBy('id', 'asc');
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/

    public function isOpenOnDayOfWeek(int $dayOfWeek): bool
    {
        return $this->week_days->pluck('day_of_week')->contains($dayOfWeek);
    }

    public function isFallback(): bool
    {
        return $this->start_date === null && $this->end_date === null;
    }

    public function isValidForDate(CarbonImmutable $date): bool
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

    /**
     * @return array{0: bool, 1: CarbonImmutable, 2: CarbonImmutable}
     */
    public function isOpen(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $is_open = false;

        $business_hour_start = CarbonImmutable::parse($this->start)->setDateFrom($start);
        $business_hour_end = CarbonImmutable::parse($this->end)->setDateFrom($end);

        if (! $end->isMidnight() && $business_hour_end->isMidnight()) {
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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}
