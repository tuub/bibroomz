<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Models\ResourceGroup;

class StoreResourceRequest extends ResourceRequest
{
    public function authorize(): bool
    {
        $resourceGroup = $this->resourceGroup();
        $user = $this->userModel();

        return $user !== null
            && $resourceGroup !== null
            && $user->can('create', [Resource::class, $resourceGroup->institution]);
    }
}
