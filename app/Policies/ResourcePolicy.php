<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourcePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Resource $resource): bool
    {
        return $user->can('view_resources', $resource->resource_group->institution);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $user->can('create_resources', $institution);
    }

    public function update(User $user, Resource $resource): bool
    {
        return $user->can('edit_resources', $resource->resource_group->institution);
    }

    public function edit(User $user, Resource $resource): bool
    {
        return $this->update($user, $resource);
    }

    public function delete(User $user, Resource $resource): bool
    {
        return $user->can('delete_resources', $resource->resource_group->institution);
    }

    public function clone(User $user, Resource $resource): bool
    {
        return $user->can('create_resources', $resource->resource_group->institution);
    }
}
