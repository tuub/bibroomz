<?php

namespace App\Http\Requests\Admin;

use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserGroupRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => [new RequiredWithTranslationRule()],
        ];
    }
}
