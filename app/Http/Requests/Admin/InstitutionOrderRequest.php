<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitutionOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $institution = Institution::find($this->id);

        return [
            'title' => [new RequiredWithTranslationRule()],
            'short_title' => ['required'],
            'slug' => ['required', Rule::unique('institutions')->ignore($institution?->id)],
            'location' => [],
            'week_days' => ['required_if:is_active,true'],
            'home_uri' => ['url'],
            'logo_uri' => ['url'],
            'teaser_uri' => ['url'],
            'email' => ['email'],
            'is_active' => ['required', 'boolean'],
            'order' => ['required', 'boolean'],
        ];
    }
}
