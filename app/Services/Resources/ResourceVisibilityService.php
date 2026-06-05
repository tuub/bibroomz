<?php

namespace App\Services\Resources;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;

class ResourceVisibilityService
{
    public function isEditableByUser(Resource $resource, User $user): bool
    {
        return $user->can('edit', $resource);
    }

    public function isViewableByUser(Resource $resource, User $user): bool
    {
        return $user->can('view', $resource);
    }

    public function isUserAbleToCreateHappening(Resource $resource, User $user): bool
    {
        return $user->can('adminCreate', [Happening::class, $resource->resource_group->institution]);
    }
}
