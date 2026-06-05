<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\UserGroup;
use App\Rules\RequiredWithTranslationRule;

class StoreUserGroupRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $institution = $this->institution();
        $user = $this->userModel();

        return $user !== null
            && $institution !== null
            && $user->can('create', [UserGroup::class, $institution]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'title' => [new RequiredWithTranslationRule()],
        ];
    }

    public function institution(): ?Institution
    {
        return $this->findModel(Institution::class, 'institution_id');
    }
}
