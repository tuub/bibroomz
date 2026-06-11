<?php

namespace App\Models;

use App\Contracts\SettingSubject;
use App\Traits\HasTranslations;
use Database\Factories\ResourceGroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Institution $institution
 * @property-read Collection<int, \App\Models\Resource> $resources
 * @property-read Collection<int, Setting> $settings
 * @property-read Collection<int, UserGroup> $user_groups
 */
class ResourceGroup extends Model implements SettingSubject
{
    /** @use HasFactory<ResourceGroupFactory> */
    use HasFactory;

    use HasTranslations, HasUuids;

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
        'is_active',
        'order',
        'help_uri',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'title',
        'term_singular',
        'term_plural',
        'description',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @return HasMany<\App\Models\Resource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * @return MorphMany<Setting, $this>
     */
    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    /**
     * @return BelongsToMany<UserGroup, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function user_groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'resource_group_user_group');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas(
                'institution',
                fn (Builder $institutionQuery): Builder => $institutionQuery->where('is_active', true),
            );
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public function isViewableByUser(User $user): bool
    {
        return $user->can('view', $this);
    }

    public function institutionForSettings(): Institution
    {
        return $this->institution;
    }

    public function isAllowedUser(User $user): bool
    {
        if ($this->user_groups->isEmpty()) {
            return true;
        }

        foreach ($user->user_groups as $user_group) {
            if ($this->user_groups->contains($user_group->id)) {
                /** @var UserGroupUser $pivot */
                $pivot = $user_group->pivot;
                $now = Carbon::now();
                $valid_from = $pivot->valid_from;
                $valid_until = $pivot->valid_until;

                if ($valid_from === null && $valid_until === null) {
                    return true;
                }

                if ($valid_from === null && $now->isBefore($valid_until)) {
                    return true;
                }

                if ($valid_until === null && $now->isAfter($valid_from)) {
                    return true;
                }

                if ($valid_from && $valid_until && $now->isBetween($valid_from, $valid_until)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
