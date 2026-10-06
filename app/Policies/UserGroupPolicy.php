<?php

namespace App\Policies;

use App\Enums\PermissionKey;
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
            PermissionKey::ViewUserGroups,
            PermissionKey::CreateUserGroups,
            PermissionKey::EditUserGroups,
            PermissionKey::DeleteUserGroups,
        ]);
    }

    public function createAny(User $user): bool
    {
        return $this->hasAnyPermission($user, [PermissionKey::CreateUserGroups]);
    }

    public function view(User $user, UserGroup $userGroup): bool
    {
        return $user->can(PermissionKey::ViewUserGroups->value, $userGroup->institution);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $user->can(PermissionKey::CreateUserGroups->value, $institution);
    }

    public function update(User $user, UserGroup $userGroup): bool
    {
        return $user->can(PermissionKey::EditUserGroups->value, $userGroup->institution);
    }

    public function edit(User $user, UserGroup $userGroup): bool
    {
        return $this->update($user, $userGroup);
    }

    public function delete(User $user, UserGroup $userGroup): bool
    {
        return $user->can(PermissionKey::DeleteUserGroups->value, $userGroup->institution);
    }

    public function import(User $user, UserGroup $userGroup): bool
    {
        return $user->can(PermissionKey::EditUserGroups->value, $userGroup->institution);
    }

    /**
     * @param  list<PermissionKey>  $permissions
     */
    private function hasAnyPermission(User $user, array $permissions): bool
    {
        return $user->getPermissions(
            array_map(fn (PermissionKey $permission): string => $permission->value, $permissions),
        )->flatten()->isNotEmpty();
    }
}
