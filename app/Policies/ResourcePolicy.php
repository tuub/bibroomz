<?php

namespace App\Policies;

use App\Enums\PermissionKey;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Institution $institution): bool
    {
        return $user->can(PermissionKey::ViewResources->value, $institution);
    }

    public function view(User $user, Resource $resource): bool
    {
        return $user->can(PermissionKey::ViewResources->value, $resource->resource_group->institution);
    }

    public function create(User $user, Institution $institution): bool
    {
        return $user->can(PermissionKey::CreateResources->value, $institution);
    }

    public function update(User $user, Resource $resource): bool
    {
        return $user->can(PermissionKey::EditResources->value, $resource->resource_group->institution);
    }

    public function edit(User $user, Resource $resource): bool
    {
        return $this->update($user, $resource);
    }

    public function delete(User $user, Resource $resource): bool
    {
        return $user->can(PermissionKey::DeleteResources->value, $resource->resource_group->institution);
    }

    public function clone(User $user, Resource $resource): bool
    {
        return $user->can(PermissionKey::CreateResources->value, $resource->resource_group->institution);
    }
}
