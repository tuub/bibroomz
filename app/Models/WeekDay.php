<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public function business_hours()
    {
        return $this->belongsToMany(BusinessHour::class);
    }
}
