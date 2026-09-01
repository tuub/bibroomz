<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Cog\Contracts\Ban\Bannable as BannableInterface;
use Cog\Laravel\Ban\Traits\Bannable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read EloquentCollection<int, Role> $roles
 * @property-read EloquentCollection<int, UserGroup> $user_groups
 * @property-read int $happenings_count
 */
#[Fillable([
    'name',
    'email',
    'is_admin',
    'is_system_user',
    'banned_at',
    'password',
    'last_login',
    'is_logged_in',
])]
#[Hidden([
    'password',
    'remember_token',
])]
#[Table(name: 'users')]
#[WithoutIncrementing]
class User extends Authenticatable implements BannableInterface
{
    use Bannable;
    use HasApiTokens, HasUuids, Notifiable;

    /*****************************************************************
     * TRAITS
     ****************************************************************/
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    /**
     * @return HasMany<Happening, $this>
     */
    public function happenings(): HasMany
    {
        return $this->hasMany(Happening::class, 'user_id_01', 'id');
    }

    /**
     * @return BelongsToMany<Institution, $this, InstitutionUserRole>
     *
     * @codeCoverageIgnore
     */
    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_user_role')
            ->withPivot('role_id')
            ->using(InstitutionUserRole::class);
    }

    /**
     * @return BelongsToMany<Role, $this, InstitutionUserRole>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'institution_user_role')
            ->withPivot('institution_id')
            ->using(InstitutionUserRole::class);
    }

    /**
     * @return BelongsToMany<UserGroup, $this, UserGroupUser>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function user_groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_user')
            ->withPivot('valid_from', 'valid_until')
            ->using(UserGroupUser::class);
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    #[\Override]
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            Happening::where('user_id_01', $user->getKey())->orWhere('user_id_02', $user->getKey())->delete();
        });
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isSystemUser(): bool
    {
        return (bool) $this->is_system_user;
    }

    /**
     * @return EloquentCollection<int, Happening>
     *
     * @codeCoverageIgnore
     */
    public function getHappenings(): EloquentCollection
    {
        return Happening::where('user_id_01', $this->getKey())
            ->orWhere('user_id_02', $this->getKey())
            ->get();
    }

    public function isHavingConcurrentHappening(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Happening $happening = null,
    ): bool {
        return $this->getOtherUserHappeningsForResourceGroup($happening?->resource->resource_group, $happening)
            ->filter->isConcurrent($start, $end)
            ->isNotEmpty();
    }

    /**
     * @return EloquentCollection<int, Happening>
     */
    public function getOtherUserHappeningsForResourceGroup(
        ?ResourceGroup $resource_group = null,
        ?Happening $happening = null
    ): EloquentCollection {
        return Happening::whereHas(
            'resource',
            fn (Builder $query) => $query->where('resource_group_id', $resource_group?->getKey()),
        )
            ->whereNot('id', $happening?->id)
            ->where(fn (Builder $query) => $query->where('user_id_01', $this->getKey())
                ->orWhere('user_id_02', $this->getKey()))
            ->get();
    }

    /**
     * @param  list<string>|null  $filter
     * @return Collection<string, Collection<int, string>>
     */
    public function getPermissions(?array $filter = null): Collection
    {
        if ($this->isAdmin()) {
            $permissionKeys = Permission::query()
                ->when($filter !== null, fn (Builder $query): Builder => $query->whereIn('key', $filter))
                ->pluck('key')
                ->filter(fn (mixed $key): bool => is_string($key))
                ->values();

            $permissionsByInstitution = [];

            /** @var Collection<int, string|int> $institutionIds */
            $institutionIds = Institution::query()->pluck('id');
            foreach ($institutionIds as $id) {
                $permissionsByInstitution[(string) $id] = $permissionKeys->values();
            }

            return collect($permissionsByInstitution);
        }

        $permissionsByInstitution = [];

        foreach ($this->roles as $role) {
            /** @var InstitutionUserRole $pivot */
            $pivot = $role->pivot;
            $institutionId = (string) $pivot->institution_id;
            $permissionsByInstitution[$institutionId] ??= [];

            foreach ($role->getPermissionKeys($filter) as $permissionKey) {
                $permissionsByInstitution[$institutionId][] = $permissionKey;
            }
        }

        return collect($permissionsByInstitution)
            ->map(fn (array $permissions): Collection => collect($permissions)->unique()->values());
    }

    public function hasPermission(string $permission, ?Institution $institution = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->roles->contains(fn (Role $role): bool => $role->hasPermission($permission, $institution));
    }

    public function isLoggedIn(): bool
    {
        if (! $this->is_logged_in) {
            return false;
        }

        $userKey = $this->getKey();

        return (is_string($userKey) || is_int($userKey)) && cache()->has('user_activity_'.$userKey);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'is_system_user' => 'boolean',
        'is_logged_in' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'banned_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
    ];
}
