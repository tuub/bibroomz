<?php

namespace App\Models;

use App\Contracts\ClosingSubject;
use App\Contracts\SettingSubject;
use App\Traits\HasTranslations;
use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @implements ClosingSubject<$this>
 *
 * @property-read EloquentCollection<int, Closing> $closings
 * @property-read EloquentCollection<int, ResourceGroup> $resource_groups
 * @property-read EloquentCollection<int, Setting> $settings
 * @property-read EloquentCollection<int, WeekDay> $week_days
 */
class Institution extends Model implements ClosingSubject, SettingSubject
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    use HasTranslations, HasUuids;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'institutions';

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
        'email',
        'is_active',
        'order',
    ];

    protected string $morphClass = 'institution';

    /**
     * @var list<string>
     */
    protected $translatable = [
        'title',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    /**
     * @return HasMany<ResourceGroup, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function resource_groups(): HasMany
    {
        return $this->hasMany(ResourceGroup::class);
    }

    /**
     * @return BelongsToMany<WeekDay, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function week_days(): BelongsToMany
    {
        return $this->belongsToMany(WeekDay::class);
    }

    /**
     * @return HasManyThrough<\App\Models\Resource, ResourceGroup, $this>
     */
    public function resources(): HasManyThrough
    {
        return $this->hasManyThrough(Resource::class, ResourceGroup::class);
    }

    /**
     * @return BelongsToMany<User, $this, InstitutionUserRole>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'institution_user_role')
            ->withPivot('role_id')
            ->using(InstitutionUserRole::class);
    }

    /**
     * @return HasMany<UserGroup, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function user_groups(): HasMany
    {
        return $this->hasMany(UserGroup::class);
    }

    /**
     * @return MorphMany<Closing, $this>
     */
    public function closings(): MorphMany
    {
        return $this->morphMany(Closing::class, 'closable');
    }

    /**
     * @return MorphMany<Setting, $this>
     */
    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function active(Builder $query): Builder
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

    public function isUserAbleToCreateResourceGroup(User $user): bool
    {
        return $user->can('create', [ResourceGroup::class, $this]);
    }

    public function isUserAbleToCreateUserGroup(User $user): bool
    {
        return $user->can('create', [UserGroup::class, $this]);
    }

    /**
     * @return Collection<int, int>
     */
    public function getHiddenDays(): Collection
    {
        $hiddenDays = [];

        foreach (
            WeekDay::query()
                ->whereNotIn('id', $this->week_days()->pluck('week_days.id'))
                ->pluck('day_of_week') as $dayOfWeek
        ) {
            if (is_int($dayOfWeek)) {
                $hiddenDays[] = $dayOfWeek;
            }
        }

        return collect($hiddenDays);
    }

    /**
     * @return EloquentCollection<int, Happening>
     */
    public function getHappenings(): EloquentCollection
    {
        return Happening::whereHas(
            'resource',
            fn (Builder $q) => $q->whereHas(
                'resource_group',
                fn (Builder $q) => $q->where('institution_id', $this->id)
            )
        )->get();
    }

    public function institutionForClosings(): Institution
    {
        return $this;
    }

    public function institutionForSettings(): Institution
    {
        return $this;
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
