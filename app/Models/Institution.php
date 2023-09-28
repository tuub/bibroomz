<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use App\Traits\HasTranslations;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Institution extends Model
{
    use HasFactory, HasUUID, UUIDIsPrimaryKey, HasTranslations;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'institutions';
    protected $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'title',
        'short_title',
        'slug',
        'location',
        'home_uri',
        'logo_uri',
        'teaser_uri',
        'is_active',
    ];

    protected $morphClass = 'institution';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $translatable = [
        'title',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    // FIXME: DEPRECATED
    /*
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
    */

    public function resource_groups(): HasMany
    {
        return $this->hasMany(ResourceGroup::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function week_days(): BelongsToMany
    {
        return $this->belongsToMany(WeekDay::class);
    }

    public function closings(): MorphMany
    {
        return $this->morphMany(Closing::class, 'closable');
    }

    public function resources(): HasManyThrough
    {
        return $this->hasManyThrough(Resource::class, ResourceGroup::class);
    }

    public function happenings(): HasManyThrough
    {
        return $this->hasManyThrough(Happening::class, Resource::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'institution_user_role')
            ->withPivot('role_id')
            ->using(InstitutionUserRole::class);
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public function isEditableByUser(User $user): bool
    {
        return $user->can('edit', $this);
    }

    public function isViewableByUser(User $user): bool
    {
        return $user->can('view', $this);
    }

    public function isUserAbleToCreateResource(User $user): bool
    {
        return $user->can('create', [Resource::class, $this]);
    }

    public function getHiddenDays()
    {
        return WeekDay::get()
            ->diff($this->week_days)
            ->map(function ($week_day) {
                return $week_day->day_of_week;
            });
    }
}
