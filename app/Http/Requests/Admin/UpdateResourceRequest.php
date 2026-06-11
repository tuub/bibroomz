<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;

class UpdateResourceRequest extends ResourceRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $resource = $this->resourceOrNull();
        $resourceGroup = $this->resourceGroup();

        if (! $user instanceof User || ! $resource instanceof Resource || ! $resourceGroup instanceof ResourceGroup) {
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
}
