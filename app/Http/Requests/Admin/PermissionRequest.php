<?php

namespace App\Http\Requests\Admin;

use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => [new RequiredWithTranslationRule()],
            'description' => [''],
        ];
    }
}
