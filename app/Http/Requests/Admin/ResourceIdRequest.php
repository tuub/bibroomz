<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;

class ResourceIdRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:resources,id'],
        ];
    }

    public function resource(): Resource
    {
        return $this->findModelOrFail(Resource::class);
    }
}
