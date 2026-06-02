<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\UserGroup;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserGroupRequest extends FormRequest
{
    public function authorize()
    {
        $institution = Institution::find($this->institution_id);

        return $institution
            ? $this->user()->can('create', [UserGroup::class, $institution])
            : false;
    }

    public function rules()
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'title' => [new RequiredWithTranslationRule()],
        ];
    }
}
