<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Models\ResourceGroup;

class UpdateResourceRequest extends ResourceRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $resource = $this->resourceOrNull();
        $resourceGroup = $this->resourceGroup();

        if ($user === null || $resource === null || $resourceGroup === null) {
            return false;
        }

        if ($resource->resource_group_id === $resourceGroup->id) {
            return $user->can('edit', $resource);
        }

        return $user->can('create', [Resource::class, $resourceGroup->institution]);
    }

    public function resource(): Resource
    {
        return $this->findModelOrFail(Resource::class);
    }

    public function resourceOrNull(): ?Resource
    {
        return $this->findModel(Resource::class);
    }

    public function resourceGroup(): ?ResourceGroup
    {
        return $this->findModel(ResourceGroup::class, 'resource_group_id');
    }
}
