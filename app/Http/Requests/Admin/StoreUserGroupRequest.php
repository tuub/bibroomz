<?php

namespace App\Http\Requests\Admin;

use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserGroupRequest extends FormRequest
{
    public function rules()
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'title' => [new RequiredWithTranslationRule()],
        ];
    }
}
