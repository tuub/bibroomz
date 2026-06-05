<?php

namespace App\Http\Requests\Admin;

use App\Models\Happening;
use App\Models\Resource;

class StoreHappeningRequest extends HappeningRequest
{
    public function authorize(): bool
    {
        $resource = $this->resource();
        $user = $this->userModel();

        return $user !== null
            && $resource !== null
            && $user->can('adminCreate', [Happening::class, $resource->resource_group->institution]);
    }
}
