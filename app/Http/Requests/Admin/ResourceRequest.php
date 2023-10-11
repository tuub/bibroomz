<?php

namespace App\Http\Requests\Admin;

use App\Rules\RequiredWithTranslationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class ResourceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'resource_group_id' => ['required', 'uuid', 'exists:resource_groups,id'],
            'title' => [new RequiredWithTranslationRule()],
            'location' => [new RequiredWithTranslationRule()],
            'location_uri' => ['url', 'nullable'],
            'description' => [new RequiredWithTranslationRule()],
            'capacity' => ['numeric', 'gt:0'],
            'is_active' => ['required', 'boolean'],
            'is_verification_required' => ['required', 'boolean'],
            'business_hours' => ['array', 'required_if:is_active,true'],
            'business_hours.*.id' => ['required_with:business_hours'],
            'business_hours.*.start' => ['required_with:business_hours'],
            'business_hours.*.end' => ['required_with:business_hours'],
            'business_hours.*.week_days' => ['required_with:business_hours'],
        ];
    }
}
