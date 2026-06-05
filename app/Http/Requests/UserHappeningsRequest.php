<?php

namespace App\Http\Requests;

use App\Models\ResourceGroup;
use Illuminate\Foundation\Http\FormRequest;

class UserHappeningsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resource_group_id' => ['required', 'uuid', 'exists:resource_groups,id'],
        ];
    }

    public function resourceGroup(): ResourceGroup
    {
        $resourceGroupId = $this->validated('resource_group_id');

        return ResourceGroup::query()->findOrFail(is_string($resourceGroupId) ? $resourceGroupId : null);
    }
}
