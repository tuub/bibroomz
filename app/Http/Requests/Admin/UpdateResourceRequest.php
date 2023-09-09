<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\Resource;

class UpdateResourceRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $resource = Resource::findOrFail($this->id);

        if ($resource->institution_id === $this->institution_id) {
            return $this->user()->can('edit', $resource);
        }

        $institution = Institution::findOrFail($this->institution_id);

        return $this->user()->can('create', [Resource::class, $institution]);
    }
}
