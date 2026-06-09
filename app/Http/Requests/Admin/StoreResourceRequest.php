<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;

class StoreResourceRequest extends ResourceRequest
{
    public function authorize(): bool
    {
        $resourceGroup = $this->resourceGroup();
        $user = $this->userModel();

        return $user instanceof User
            && $resourceGroup instanceof ResourceGroup
            && $user->can('create', [Resource::class, $resourceGroup->institution]);
    }
}
