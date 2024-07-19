<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WeekDay extends Model
{
    protected $table = 'week_days';

    protected $fillable = [
        'day_of_week',
        'name',
    ];

    protected $hidden = ['pivot'];

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function business_hours(): BelongsToMany
    {
        return $this->belongsToMany(BusinessHour::class);
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class);
    }
}
