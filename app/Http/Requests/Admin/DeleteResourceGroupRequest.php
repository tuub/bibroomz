<?php

namespace App\Http\Requests\Admin;

use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeleteResourceGroupRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $resourceGroup = $this->findModel(ResourceGroup::class);
        $user = $this->userModel();

        return $resourceGroup instanceof Model && $user instanceof User && $user->can('delete', $resourceGroup);
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
