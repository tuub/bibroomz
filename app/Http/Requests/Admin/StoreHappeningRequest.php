<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Happening;
use App\Models\User;

class StoreHappeningRequest extends HappeningRequest
{
    public function authorize(): bool
    {
        $resource = $this->resource();
        $user = $this->userModel();

        return $user instanceof User
            && $resource instanceof \App\Models\Resource
            && $user->can('adminCreate', [Happening::class, $resource->resource_group->institution]);
    }
}
