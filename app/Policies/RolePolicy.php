<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_roles');
    }

    public function create(User $user): bool
    {
        return $user->can('create_roles');
    }

    public function update(User $user): bool
    {
        return $user->can('edit_roles');
    }

    public function edit(User $user, Role $role): bool
    {
        return $this->update($user);
    }

    public function delete(User $user): bool
    {
        return $user->can('delete_roles');
    }
}
