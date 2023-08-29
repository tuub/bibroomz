<?php

namespace App\Http\Requests\Admin;

use App\Models\Happening;
use App\Models\Resource;

class UpdateHappeningRequest extends HappeningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $happening = Happening::findOrFail($this->id);
        $resource = Resource::findOrFail($this->resource_id);

        if ($happening->resource->institution_id === $resource->institution_id) {
            return $this->user()->can('adminUpdate', $happening);
        }

        return $this->user()->can('adminCreate', [Happening::class, $resource->institution]);
    }
}
