<?php

namespace App\Policies;

use App\Enums\PermissionKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionKey::ViewRoles->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionKey::CreateRoles->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionKey::EditRoles->value);
    }

    public function edit(User $user, Role $role): bool
    {
        return $this->update($user);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionKey::DeleteRoles->value);
    }
}
