<?php

namespace App\Http\Requests\Admin;

use App\Models\UserGroup;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserGroupRequest extends FormRequest
{
    public function authorize()
    {
        $userGroup = UserGroup::find($this->id);

        return $userGroup
            ? $this->user()->can('update', $userGroup)
            : false;
    }

    public function rules()
    {
        return [
            'id' => ['required', 'uuid', 'exists:user_groups,id'],
            'title' => [new RequiredWithTranslationRule()],
        ];
    }
}
