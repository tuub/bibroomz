<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyPermission($user, [
            'view_user_groups',
            'create_user_groups',
            'edit_user_groups',
            'delete_user_groups',
        ]);
    }

    public function createAny(User $user): bool
    {
        return $this->hasAnyPermission($user, ['create_user_groups']);
    }

    public function view(User $user, UserGroup $userGroup)
    {
        return $user->can('view_user_groups', $userGroup->institution);
    }

    public function create(User $user, Institution $institution)
    {
        if ($user->can('create_user_groups', $institution)) {
            return true;
        }
    }

    public function update(User $user, UserGroup $userGroup)
    {
        return $user->can('edit_user_groups', $userGroup->institution);
    }

    public function edit(User $user, UserGroup $userGroup)
    {
        return $this->update($user, $userGroup);
    }

    public function delete(User $user, UserGroup $userGroup)
    {
        if ($user->can('delete_user_groups', $userGroup->institution)) {
            return true;
        }
    }

    public function import(User $user, UserGroup $userGroup)
    {
        if ($user->can('edit_user_groups', $userGroup->institution)) {
            return true;
        }
    }

    private function hasAnyPermission(User $user, array $permissions): bool
    {
        return $user->getPermissions($permissions)->flatten()->isNotEmpty();
    }
}
