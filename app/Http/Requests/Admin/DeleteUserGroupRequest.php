<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Model;

class DeleteUserGroupRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $userGroup = $this->findModel(UserGroup::class);
        $user = $this->userModel();

        return $userGroup instanceof Model && $user instanceof User && $user->can('delete', $userGroup);
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
