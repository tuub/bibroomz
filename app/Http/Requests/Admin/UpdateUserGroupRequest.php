<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Models\UserGroup;
use App\Rules\RequiredWithTranslationRule;

class UpdateUserGroupRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $userGroup = $this->userGroupOrNull();

        return $user instanceof User
            && $userGroup instanceof UserGroup
            && $user->can('update', $userGroup);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:user_groups,id'],
            'title' => [new RequiredWithTranslationRule],
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
}
