<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Carbon\Traits\Week;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Institution extends Model
{
    use HasFactory, HasUUID, UUIDIsPrimaryKey;

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

    public function week_days(): BelongsToMany
    {
        return $this->belongsToMany(WeekDay::class);
    }

    public function closings(): MorphMany
    {
        return $this->morphMany(Closing::class, 'closable');
    }

    public function happenings(): HasManyThrough
    {
        return $this->hasManyThrough(Happening::class, Resource::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'institution_admins');
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
    public function isAdmin($user): bool
    {
        return $this->admins->contains($user);
    }

    public static function abortIfUnauthorized(Institution $institution = null, string $verb = 'edit'): void
    {
        /** @var User */
        $user = auth()->user();

        if ($institution) {
            if (!$user->can($verb, $institution)) {
                abort(403);
            }

            return;
        }

        if (!$user->can($verb, self::class)) {
            abort(403);
        }
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
