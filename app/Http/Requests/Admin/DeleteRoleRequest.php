<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;

class DeleteRoleRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $role = $this->findModel(Role::class);
        $user = $this->userModel();

        return $role !== null && $user !== null && $user->can('delete', $role);
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
