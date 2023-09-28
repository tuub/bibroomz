<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;

class StoreResourceRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $resource_group = ResourceGroup::findOrFail($this->resource_group_id);

        return $this->user()->can('create', [Resource::class, $resource_group->institution]);
    }
}
