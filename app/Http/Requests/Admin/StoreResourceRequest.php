<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\Resource;

class StoreResourceRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $institution = Institution::findOrFail($this->institution_id);

        return $this->user()->can('create', [Resource::class, $institution]);
    }
}
