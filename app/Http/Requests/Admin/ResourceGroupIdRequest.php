<?php

namespace App\Http\Requests\Admin;

use App\Models\ResourceGroup;

class ResourceGroupIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:resource_groups,id'],
        ];
    }

    public function resourceGroup(): ResourceGroup
    {
        return $this->findModelOrFail(ResourceGroup::class);
    }
}
