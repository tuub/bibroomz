<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;

class CloneResourceRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $resource = $this->findModel(Resource::class);
        $user = $this->userModel();

        return $resource !== null && $user !== null && $user->can('clone', $resource);
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
