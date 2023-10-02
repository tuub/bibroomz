<?php

namespace App\Http\Requests\Admin;

use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResourceGroupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'name' => [new RequiredWithTranslationRule],
            'slug' => ['required'],
            'term_singular' => [new RequiredWithTranslationRule],
            'term_plural' => [new RequiredWithTranslationRule],
            'description' => [new RequiredWithTranslationRule],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
