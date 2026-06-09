<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Models\UserGroup;

class RemoveUsersFromUserGroupRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $userGroup = $this->userGroupOrNull();

        return $user instanceof User && $userGroup instanceof UserGroup && $user->can('import', $userGroup);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:user_groups,id'],
            'users' => ['required', 'array'],
            'users.*' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    public function userGroup(): UserGroup
    {
        return $this->findModelOrFail(UserGroup::class);
    }

    public function userGroupOrNull(): ?UserGroup
    {
        return $this->findModel(UserGroup::class);
    }

    /**
     * @return list<string>
     */
    public function userIds(): array
    {
        $users = $this->validated('users');

        return is_array($users)
            ? array_values(array_filter($users, is_string(...)))
            : [];
    }
}
