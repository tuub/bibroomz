<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WeekDay extends Model
{
    // FIXME: add UUID
    protected $table = 'week_days';
    protected $fillable = [
        'day_of_week',
        'name',
    ];
    protected $hidden = ['pivot'];

    // A week day belongs to many time slots
    public function business_hours(): BelongsToMany
    {
        return $this->belongsToMany(BusinessHour::class);
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class);
    }
}
