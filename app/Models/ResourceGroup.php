<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class ResourceGroup extends Model
{
    use HasFactory, HasUuids, HasTranslations;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'resource_groups';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'institution_id',
        'title',
        'slug',
        'term_singular',
        'term_plural',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $translatable = [
        'title',
        'term_singular',
        'term_plural',
        'description',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function user_groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'resource_group_user_group');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true)->whereHas('institution', fn ($query) => $query->active());
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public function isViewableByUser(User $user): bool
    {
        return $user->can('view', $this);
    }

    public function isAllowedUser(User $user): bool
    {
        if ($this->user_groups->isEmpty()) {
            return true;
        }

        foreach ($user->user_groups as $user_group) {
            if ($this->user_groups->contains($user_group->id)) {
                $now = Carbon::now();
                $valid_from = $user_group->pivot->valid_from;
                $valid_until = $user_group->pivot->valid_until;

                if ($valid_from === null && $valid_until === null) {
                    return true;
                }

                if ($valid_from === null && $now->isBefore($valid_until)) {
                    return true;
                }

                if ($valid_until === null && $now->isAfter($valid_from)) {
                    return true;
                }

                if ($now->isBetween($valid_from, $valid_until)) {
                    return true;
                }
            }
        }

        return false;
    }
}
