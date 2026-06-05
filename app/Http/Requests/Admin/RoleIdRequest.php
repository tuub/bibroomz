<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;

class RoleIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:roles,id'],
        ];
    }

    public function role(): Role
    {
        return $this->findModelOrFail(Role::class);
    }
}
