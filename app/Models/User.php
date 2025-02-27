<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\CarbonImmutable;
use Cog\Contracts\Ban\Bannable as BannableInterface;
use Cog\Laravel\Ban\Traits\Bannable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class User extends Authenticatable implements BannableInterface
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasApiTokens, HasFactory, hasUuids, Notifiable;
    use Bannable;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'users';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'is_system_user',
        'banned_at',
        'password',
        'last_login',
        'is_logged_in',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function happenings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Happening::class, 'user_id_01', 'id');
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_user_role')
            ->withPivot('role_id')
            ->using(InstitutionUserRole::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'institution_user_role')
            ->withPivot('institution_id')
            ->using(InstitutionUserRole::class);
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            Happening::where('user_id_01', $user->getKey())->orWhere('user_id_02', $user->getKey())->delete();
        });
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isSystemUser(): bool
    {
        return $this->is_system_user;
    }

    public function getHappenings()
    {
        return Happening::where('user_id_01', $this->getKey())
            ->orWhere('user_id_02', $this->getKey())
            ->get();
    }

    public function isHavingConcurrentHappening(
        CarbonImmutable $start,
        CarbonImmutable $end,
        Happening $happening = null,
    ): bool {
        return $this->getOtherUserHappeningsForResourceGroup($happening?->resource->resource_group, $happening)
            ->filter->isConcurrent($start, $end)
            ->isNotEmpty();
    }

    public function getOtherUserHappeningsForResourceGroup(
        ResourceGroup $resource_group = null,
        Happening $happening = null
    ): Collection {
        return Happening::whereHas(
            'resource',
            fn (Builder $query) => $query->where('resource_group_id', $resource_group?->getKey()),
        )
            ->whereNot('id', $happening?->id)
            ->where(fn (Builder $query) => $query->where('user_id_01', $this->getKey())
                ->orWhere('user_id_02', $this->getKey()))
            ->get();
    }

    public function getPermissions(array $filter = null): Collection
    {
        if ($this->isAdmin()) {
            return Institution::all()->pluck('id')->flatMap(fn ($id): array => [
                $id => Permission::all()->pluck('key')->intersect($filter)->values(),
            ]);
        }

        return $this->roles
            ->map(fn (Role $role): array => [
                'institution' => $role->pivot->institution->id,
                'permissions' => $role->getPermissionKeys($filter),
            ])
            ->reduce(fn (Collection $result, array $value): Collection => $result->mergeRecursive([
                $value['institution'] => $value['permissions'],
            ]), collect([]))
            ->map(fn (array $permissions): Collection => collect($permissions)->unique()->values());
    }

    public function hasPermission(string $permission, Institution $institution = null): bool
    {
        return $this->isAdmin() || $this->roles->contains->hasPermission($permission, $institution);
    }

    public function isLoggedIn(): bool
    {
        if (!$this->is_logged_in) {
            return false;
        }

        if (cache()->has('user_activity_' . $this->getKey())) {
            return true;
        }

        return false;
    }
}
