<?php

namespace App\Policies;

use App\Enums\PermissionKey;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourceGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Institution $institution): bool
    {
        foreach (
            [
                PermissionKey::ViewResourceGroups,
                PermissionKey::CreateResourceGroups,
                PermissionKey::EditResourceGroups,
                PermissionKey::DeleteResourceGroups,
            ] as $permission
        ) {
            if ($user->can($permission->value, $institution)) {
                return true;
            }
        }

        return false;
    }

    public function view(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can(PermissionKey::ViewResourceGroups->value, $resource_group->institution);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $user->can(PermissionKey::CreateResourceGroups->value, $institution);
    }

    public function update(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can(PermissionKey::EditResourceGroups->value, $resource_group->institution);
    }

    public function edit(User $user, ResourceGroup $resource_group): bool
    {
        return $this->update($user, $resource_group);
    }

    public function delete(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can(PermissionKey::DeleteResourceGroups->value, $resource_group->institution);
    }

    public function clone(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can(PermissionKey::CreateResourceGroups->value, $resource_group->institution);
    }
}
