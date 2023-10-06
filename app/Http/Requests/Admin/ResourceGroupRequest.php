<?php

namespace App\Http\Requests\Admin;

use App\Models\ResourceGroup;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResourceGroupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $resource_group = ResourceGroup::find($this->id);

        return [
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'name' => [new RequiredWithTranslationRule()],
            'slug' => ['required', Rule::unique('resource_groups')->ignore($resource_group?->id)],
            'term_singular' => [new RequiredWithTranslationRule()],
            'term_plural' => [new RequiredWithTranslationRule()],
            'description' => [new RequiredWithTranslationRule()],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
