<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeleteRoleRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $role = $this->findModel(Role::class);
        $user = $this->userModel();

        return $role instanceof Model && $user instanceof User && $user->can('delete', $role);
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
