<?php

namespace App\Http\Requests\Admin;

use App\Models\ResourceGroup;
use App\Rules\RequiredWithTranslationRule;

abstract class ResourceRequest extends AdminRouteRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['sometimes', 'nullable', 'uuid', 'exists:resources,id'],
            'resource_group_id' => ['required', 'uuid', 'exists:resource_groups,id'],
            'title' => [new RequiredWithTranslationRule],
            'location' => [new RequiredWithTranslationRule],
            'location_uri' => ['url', 'nullable'],
            'description' => [new RequiredWithTranslationRule],
            'capacity' => ['numeric', 'gt:0'],
            'is_active' => ['required', 'boolean'],
            'is_verification_required' => ['required', 'boolean'],
            'business_hours' => ['array', 'required_if:is_active,true'],
            'business_hours.*.id' => ['required_with:business_hours'],
            'business_hours.*.start' => ['required_with:business_hours'],
            'business_hours.*.end' => ['required_with:business_hours'],
            'business_hours.*.week_days' => ['required_with:business_hours'],
            'business_hours.*.start_date' => ['nullable', 'date'],
            'business_hours.*.end_date' => ['nullable', 'date'],
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_hours' => $this->input('business_hours', []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function resourceData(): array
    {
        return $this->normalizeStringKeyedArray(collect($this->validated())->except('business_hours')->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function businessHours(): array
    {
        /** @var array<int, array<string, mixed>> $businessHours */
        $businessHours = $this->validated('business_hours', []);

        return $businessHours;
    }

    public function resourceGroup(): ?ResourceGroup
    {
        return $this->findModel(ResourceGroup::class, 'resource_group_id');
    }
}
