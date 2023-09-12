<?php

namespace App\Http\Requests\Admin;

use App\Library\Utility;
use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'name' => ['required', 'string'],
            'description' => [''],
        ];

        Utility::makeRulesTranslatable($rules, ['name', 'description']);

        return $rules;
    }
}
