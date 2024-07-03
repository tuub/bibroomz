<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, HasUuids, HasTranslations;

    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $translatable = [
        'name',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'institution_user_role')
            ->withPivot('institution_id')
            ->using(InstitutionUserRole::class);
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_user_role')
            ->withPivot('user_id')
            ->using(InstitutionUserRole::class);
    }

    public function hasPermission(string $permission, Institution $institution = null): bool
    {
        $pivot = $this->pivot;

        if (!$pivot || !$institution) {
            return $this->permissions->contains('key', $permission);
        }

        return $pivot->hasPermission($permission, $institution);
    }

    public function getPermissionKeys(array $permissions = null): array
    {
        if (!$permissions) {
            return $this->permissions->pluck('key')->toArray();
        }

        return $this->permissions->pluck('key')->intersect($permissions)->toArray();
    }
}
