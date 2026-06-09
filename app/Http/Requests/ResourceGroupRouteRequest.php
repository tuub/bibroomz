<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResourceGroupRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'institution_slug' => ['required', 'string'],
            'resource_group_slug' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function validationData(): array
    {
        return $this->normalizeStringKeyedArray(array_merge($this->all(), $this->route()?->parameters() ?? []));
    }

    public function institutionSlug(): string
    {
        $slug = $this->validated('institution_slug');

        return is_string($slug) ? $slug : '';
    }

    public function resourceGroupSlug(): string
    {
        $slug = $this->validated('resource_group_slug');

        return is_string($slug) ? $slug : '';
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    protected function normalizeStringKeyedArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<array-key, mixed>  ...$ruleSets
     * @return array<string, mixed>
     */
    protected function mergeRuleSets(array ...$ruleSets): array
    {
        $merged = [];

        foreach ($ruleSets as $ruleSet) {
            foreach ($ruleSet as $key => $value) {
                if (is_string($key)) {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }
}
