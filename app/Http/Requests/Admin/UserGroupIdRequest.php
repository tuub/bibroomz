<?php

namespace App\Http\Requests\Admin;

use App\Models\UserGroup;

class UserGroupIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:user_groups,id'],
        ];
    }

    public function userGroup(): UserGroup
    {
        return $this->findModelOrFail(UserGroup::class);
    }
}
