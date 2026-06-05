<?php

namespace App\Http\Requests\Admin;

use App\Models\ResourceGroup;

class DeleteResourceGroupRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $resourceGroup = $this->findModel(ResourceGroup::class);
        $user = $this->userModel();

        return $resourceGroup !== null && $user !== null && $user->can('delete', $resourceGroup);
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
