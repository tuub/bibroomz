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
            'timezone' => env('DEFAULT_TIMEZONE'),
            'start_time_slot' => env('DEFAULT_START_TIME_SLOT'),
            'end_time_slot' => env('DEFAULT_END_TIME_SLOT'),
            'time_slot_length' => env('DEFAULT_TIMESLOT_LENGTH'),
            'weeks_in_advance' => env('DEFAULT_WEEKS_IN_ADVANCE'),
            'quota_weekly_happenings' => env('DEFAULT_QUOTA_WEEKLY_HAPPENINGS'),
            'quota_daily_hours' => env('DEFAULT_QUOTA_DAILY_HOURS'),
            'quota_weekly_hours' => env('DEFAULT_QUOTA_WEEKLY_HOURS'),
            'quota_happening_block_hours' => env('DEFAULT_QUOTA_HAPPENING_BLOCK_HOURS'),
            'date_format' => env('DEFAULT_DATE_FORMAT'),
            'time_format' => env('DEFAULT_TIME_FORMAT'),
            'cleanup_interval' => env('DEFAULT_CLEANUP_INTERVAL'),
        ];
    }
}
