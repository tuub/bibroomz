<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Rules\RequiredWithTranslationRule;
use App\Rules\UniqueResourceGroupAttributeRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class ResourceGroupRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $institution = $this->institution();
        $resourceGroup = $this->resourceGroupOrNull();

        if ($user === null || $institution === null) {
            return false;
        }

        if ($resourceGroup === null) {
            return $user->can('create', [ResourceGroup::class, $institution]);
        }

        if (! $user->can('update', $resourceGroup)) {
            return false;
        }

        if ($resourceGroup->institution_id === $institution->id) {
            return true;
        }

        return $user->can('create', [ResourceGroup::class, $institution]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $resourceGroup = $this->resourceGroupOrNull();
        $institutionId = $this->inputString('institution_id') ?? '';

        return [
            'id' => ['nullable', 'uuid', 'exists:resource_groups,id'],
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'title' => [new RequiredWithTranslationRule()],
            'slug' => ['required', new UniqueResourceGroupAttributeRule($institutionId, $resourceGroup?->id)],
            'term_singular' => [new RequiredWithTranslationRule()],
            'term_plural' => [new RequiredWithTranslationRule()],
            'description' => [new RequiredWithTranslationRule()],
            'is_active' => ['required', 'boolean'],
            'user_groups' => ['list'],
            'user_groups.*' => [
                'uuid',
                Rule::exists('user_groups', 'id')->where(
                    fn (Builder $query): Builder => $query->where('institution_id', $institutionId),
                ),
            ],
            'help_uri' => ['nullable', 'url'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $userGroups = $this->input('user_groups', []);

        $this->merge([
            'user_groups' => is_array($userGroups) ? $userGroups : [],
        ]);
    }

    public function institution(): ?Institution
    {
        return $this->findModel(Institution::class, 'institution_id');
    }

    public function resourceGroup(): ResourceGroup
    {
        return $this->findModelOrFail(ResourceGroup::class);
    }

    public function resourceGroupOrNull(): ?ResourceGroup
    {
        return $this->findModel(ResourceGroup::class);
    }
}
