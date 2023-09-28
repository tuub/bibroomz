<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Resource  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Resource $resource)
    {
        if ($user->can('view_resources', $resource->resource_group->institution)) {
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
        if ($user->can('create_resources', $institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Resource  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Resource $resource)
    {
        if ($user->can('edit_resources', $resource->resource_group->institution)) {
            return true;
        }
    }

    public function edit(User $user, Resource $resource)
    {
        return $this->update($user, $resource);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Resource  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Resource $resource)
    {
        if ($user->can('delete_resources', $resource->resource_group->institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can clone the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Resource  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function clone(User $user, Resource $resource)
    {
        if ($user->can('create_resources', $resource->resource_group->institution)) {
            return true;
        }
    }
}
