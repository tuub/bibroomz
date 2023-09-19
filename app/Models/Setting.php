<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, HasUUID, UUIDIsPrimaryKey;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'settings';
    protected string $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['key', 'value', 'institution_id'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public static function getInitialValues(): array
    {
        return [
            'timezone' => config('roomz.default.timezone'),
            'start_time_slot' => config('roomz.default.start_time_slot'),
            'end_time_slot' => config('roomz.default.end_time_slot'),
            'time_slot_length' => config('roomz.default.timeslot_length'),
            'weeks_in_advance' => config('roomz.default.weeks_in_advance'),
            'quota_weekly_happenings' => config('roomz.default.quota.weekly_happenings'),
            'quota_daily_hours' => config('roomz.default.quota.daily_hours'),
            'quota_weekly_hours' => config('roomz.default.quota.weekly_hours'),
            'quota_happening_block_hours' => config('roomz.default.quota.happening_block_hours'),
            'date_format' => config('roomz.default.date_format'),
            'time_format' => config('roomz.default.time_format'),
            'cleanup_interval' => config('roomz.default.cleanup_interval'),
        ];
    }
}
