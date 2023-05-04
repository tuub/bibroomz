<?php

namespace App\Models;

use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Resource extends Model
{
    use HasFactory, HasUUID;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'resources';
    protected $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['institution_id', 'title', 'location', 'description', 'capacity', 'opens_at', 'closes_at', 'is_active'];
    protected $with = ['closings'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function happenings(): HasMany
    {
        return $this->hasMany(Happening::class);
    }

    public function closings(): MorphMany
    {
        return $this->morphMany(Closing::class, 'closable');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
