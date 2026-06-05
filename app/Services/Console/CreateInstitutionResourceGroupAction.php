<?php

namespace App\Services\Console;

use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Support\Facades\Validator;

class CreateInstitutionResourceGroupAction
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validateInput(array $input): array
    {
        return $this->stringKeyedArray(Validator::make(
            $input,
            [
                'institution_id' => ['required', 'exists:institutions,id'],
                'title' => [new RequiredWithTranslationRule()],
                'slug' => ['required'],
                'term_singular' => [new RequiredWithTranslationRule()],
                'term_plural' => [new RequiredWithTranslationRule()],
                'description' => [new RequiredWithTranslationRule()],
                'is_active' => ['required', 'boolean'],
            ],
        )->validate());
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function execute(array $validated): ResourceGroup
    {
        $resourceGroup = ResourceGroup::create($validated);

        foreach (Setting::getInitialValues()['resource_group'] as $key => $value) {
            $resourceGroup->settings()->create([
                'key' => $key,
                'value' => $value,
            ]);
        }

        return $resourceGroup;
    }

    /**
     * @param array<mixed> $values
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
