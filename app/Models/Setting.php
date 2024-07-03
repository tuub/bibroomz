<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Setting extends Model
{
    use HasFactory, HasUuids;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'settings';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['key', 'value', 'settingable_type', 'settingable_id', 'institution_id'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    public function settingable(): MorphTo
    {
        return $this->morphTo();
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public static function getSettingableModel(string $settingableType)
    {
        $modelName = array_map('ucfirst', explode('_', $settingableType));
        $fullModelName = __NAMESPACE__ . '\\' . implode('', $modelName);

        return new $fullModelName();
    }

    public static function getInitialValues(): array
    {
        return [
            'institution' => [
                'timezone' => config('roomz.default.timezone'),
                'date_format' => config('roomz.default.date_format'),
                'time_format' => config('roomz.default.time_format'),
                'cleanup_interval' => config('roomz.default.cleanup_interval'),
                'allowed_ips' => config('roomz.default.allowed_ips'),
            ],
            'resource_group' => [
                'start_time_slot' => config('roomz.default.start_time_slot'),
                'end_time_slot' => config('roomz.default.end_time_slot'),
                'time_slot_length' => config('roomz.default.timeslot_length'),
                'weeks_in_advance' => config('roomz.default.weeks_in_advance'),
                'quota_weekly_happenings' => config('roomz.default.quota.weekly_happenings'),
                'quota_daily_hours' => config('roomz.default.quota.daily_hours'),
                'quota_weekly_hours' => config('roomz.default.quota.weekly_hours'),
                'quota_happening_block_hours' => config('roomz.default.quota.happening_block_hours'),
                'is_label_enabled' => config('roomz.default.is_label_enabled'),
            ],
        ];
    }
}
