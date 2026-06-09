<?php

namespace App\Policies;

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
                'view_resource_groups',
                'create_resource_groups',
                'edit_resource_groups',
                'delete_resource_groups',
            ] as $permission
        ) {
            if ($user->can($permission, $institution)) {
                return true;
            }
        }

        return false;
    }

    public function view(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can('view_resource_groups', $resource_group->institution);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $user->can('create_resource_groups', $institution);
    }

    public function update(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can('edit_resource_groups', $resource_group->institution);
    }

    public function edit(User $user, ResourceGroup $resource_group): bool
    {
        return $this->update($user, $resource_group);
    }

    public function delete(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can('delete_resource_groups', $resource_group->institution);
    }

    public function clone(User $user, ResourceGroup $resource_group): bool
    {
        return $user->can('create_resource_groups', $resource_group->institution);
    }
}
