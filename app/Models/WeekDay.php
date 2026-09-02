<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'day_of_week',
    'name',
])]
#[Hidden(['pivot'])]
#[Table(name: 'week_days')]
class WeekDay extends Model
{
    /**
     * @return BelongsToMany<BusinessHour, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function business_hours(): BelongsToMany
    {
        return $this->belongsToMany(BusinessHour::class);
    }

    /**
     * @return BelongsToMany<Institution, $this>
     */
    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class);
    }
}
