<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Rules\RequiredWithTranslationRule;
use App\Rules\UniqueResourceGroupAttributeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResourceGroupRequest extends FormRequest
{
    public function authorize()
    {
        $institution = Institution::find($this->institution_id);
        $resourceGroup = ResourceGroup::find($this->id);

        if (! $institution) {
            return false;
        }

        if (! $resourceGroup) {
            return $this->user()->can('create', [ResourceGroup::class, $institution]);
        }

        if (! $this->user()->can('update', $resourceGroup)) {
            return false;
        }

        if ($resourceGroup->institution_id === $institution->id) {
            return true;
        }

        return $this->user()->can('create', [ResourceGroup::class, $institution]);
    }

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
            'title' => [new RequiredWithTranslationRule()],
            'slug' => ['required', new UniqueResourceGroupAttributeRule($this->institution_id, $resource_group?->id)],
            'term_singular' => [new RequiredWithTranslationRule()],
            'term_plural' => [new RequiredWithTranslationRule()],
            'description' => [new RequiredWithTranslationRule()],
            'is_active' => ['required', 'boolean'],
            'user_groups' => ['list'],
            'user_groups.*' => [
                'uuid',
                Rule::exists('user_groups', 'id')->where(fn ($query) => $query->where(
                    'institution_id',
                    $this->institution_id,
                )),
            ],
            'help_uri' => ['nullable', 'url'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_groups' => $this->input('user_groups', []),
        ]);
    }
}
