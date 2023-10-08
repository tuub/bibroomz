<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourceGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ResourceGroup  $resource_group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ResourceGroup $resource_group)
    {
        if ($user->can('view_resource_groups', $resource_group->institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Institution  $institution
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Institution $institution)
    {
        if ($user->can('create_resource_groups', $institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ResourceGroup  $resource_group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ResourceGroup $resource_group)
    {
        if ($user->can('edit_resource_groups', $resource_group->institution)) {
            return true;
        }
    }

    public function edit(User $user, ResourceGroup $resource_group)
    {
        return $this->update($user, $resource_group);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ResourceGroup  $resource_group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ResourceGroup $resource_group)
    {
        if ($user->can('delete_resource_groups', $resource_group->institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can clone the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ResourceGroup  $resource_group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function clone(User $user, ResourceGroup $resource_group)
    {
        if ($user->can('create_resource_groups', $resource_group->institution)) {
            return true;
        }
    }
}
