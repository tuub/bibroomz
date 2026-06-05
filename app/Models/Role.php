<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read InstitutionUserRole|null $pivot
 */
class Role extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;
    use HasUuids, HasTranslations;

    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'name',
        'description',
    ];

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return BelongsToMany<User, $this, InstitutionUserRole>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'institution_user_role')
            ->withPivot('institution_id')
            ->using(InstitutionUserRole::class);
    }

    /**
     * @return BelongsToMany<Institution, $this, InstitutionUserRole>
     */
    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_user_role')
            ->withPivot('user_id')
            ->using(InstitutionUserRole::class);
    }

    public function hasPermission(string $permission, Institution $institution = null): bool
    {
        $pivot = $this->pivot;

        if (! $pivot instanceof InstitutionUserRole || $institution === null) {
            return $this->permissions->contains('key', $permission);
        }

        return $pivot->hasPermission($permission, $institution);
    }

    /**
     * @param list<string>|null $permissions
     * @return list<string>
     */
    public function getPermissionKeys(?array $permissions = null): array
    {
        $permissionKeys = [];

        foreach ($this->permissions as $permissionModel) {
            $permissionKeys[] = $permissionModel->key;
        }

        if ($permissions === null || $permissions === []) {
            return $permissionKeys;
        }

        return array_values(array_filter(
            $permissionKeys,
            fn (string $permissionKey): bool => in_array($permissionKey, $permissions, true),
        ));
    }
}
