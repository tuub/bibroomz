<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use App\Library\Traits\UUIDIsPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasApiTokens, HasFactory, Notifiable, HasUUID, UUIDIsPrimaryKey;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'users';
    protected string $uuidFieldName = 'id';
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
        'is_logged_in' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'banned_at',
        'email_verified_at',
        'last_login',
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
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function getHappenings()
    {
        return Happening::where('user_id_01', $this->getKey())
            ->orWhere('user_id_02', $this->getKey())
            ->get();
    }

    public function isHavingConcurrentHappening(CarbonImmutable $start, CarbonImmutable $end, Happening $happening = null): bool
    {
        return $this->getHappenings()
            ->whereNotIn('id', [$happening?->id])
            ->filter->isConcurrent($start, $end)
            ->isNotEmpty();
    }

    public function getPermissions(array $filter = null): Collection
    {
        if ($this->isAdmin()) {
            return Institution::all()->pluck('id')->flatMap(fn ($id): array => [
                $id => Permission::all()->pluck('name')->intersect($filter)->values(),
            ]);
        }

        return $this->roles
            ->map(fn (Role $role): array => [
                "institution" => $role->pivot->institution->id,
                "permissions" => $role->getPermissionNames($filter),
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
}
