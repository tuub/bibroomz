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

    public function view(User $user, UserGroup $userGroup): bool
    {
        return $user->can('view_user_groups', $userGroup->institution);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $user->can('create_user_groups', $institution);
    }

    public function update(User $user, UserGroup $userGroup): bool
    {
        return $user->can('edit_user_groups', $userGroup->institution);
    }

    public function edit(User $user, UserGroup $userGroup): bool
    {
        return $this->update($user, $userGroup);
    }

    public function delete(User $user, UserGroup $userGroup): bool
    {
        return $user->can('delete_user_groups', $userGroup->institution);
    }

    public function import(User $user, UserGroup $userGroup): bool
    {
        return $user->can('edit_user_groups', $userGroup->institution);
    }

    /**
     * @param list<string> $permissions
     */
    private function hasAnyPermission(User $user, array $permissions): bool
    {
        return $user->getPermissions($permissions)->flatten()->isNotEmpty();
    }
}
