<?php

namespace App\Http\Requests\Admin;

use App\Models\Happening;
use App\Models\Resource;

class StoreHappeningRequest extends HappeningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $resource = Resource::findOrFail($this->resource_id);

        return $this->user()->can('adminCreate', [Happening::class, $resource->institution]);
    }
}
