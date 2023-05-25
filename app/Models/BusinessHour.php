<?php

namespace App\Models;

use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessHour extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasFactory, HasUUID;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'business_hours';
    protected string $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = true;
    protected $fillable = ['resource_id', 'start', 'end'];
    protected $dates = ['created_at', 'updated_at'];

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
}
