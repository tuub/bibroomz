<?php

namespace App\Models;

use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Institution extends Model
{
    use HasFactory, HasUUID;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'institutions';
    protected $uuidFieldName = 'id';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [ 'title', 'short_title', 'slug', 'location', 'home_uri', 'logo_uri', 'teaser_uri', 'is_active'];
    protected $morphClass = 'institution';
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function settings(): hasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function closings(): MorphMany
    {
        return $this->morphMany(Closing::class, 'closable');
    }

    public function happenings(): HasManyThrough
    {
        return $this->hasManyThrough(Happening::class, Resource::class);
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
