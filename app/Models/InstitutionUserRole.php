<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InstitutionUserRole extends Pivot
{
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPermission(string $permission, Institution $institution): bool
    {
        if ($this->institution?->id === $institution->id) {
            return $this->role->hasPermission($permission);
        }

        return false;
    }
}
