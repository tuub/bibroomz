<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\User;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Validation\Rule;

class InstitutionRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $institution = $this->institutionOrNull();

        if (! $user instanceof User) {
            return false;
        }

        if (! $institution instanceof Institution) {
            return $user->can('create', Institution::class);
        }

        return $user->can('update', $institution);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $institution = $this->institutionOrNull();

        return [
            'id' => ['nullable', 'uuid', 'exists:institutions,id'],
            'title' => [new RequiredWithTranslationRule],
            'short_title' => ['required'],
            'slug' => ['required', Rule::unique('institutions')->ignore($institution?->id)],
            'location' => [],
            'week_days' => ['required_if:is_active,true'],
            'home_uri' => ['url'],
            'logo_uri' => ['url'],
            'teaser_uri' => ['url'],
            'email' => ['email'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'week_days' => $this->input('week_days', []),
        ]);
    }

    public function institution(): Institution
    {
        return $this->findModelOrFail(Institution::class);
    }

    public function institutionOrNull(): ?Institution
    {
        return $this->findModel(Institution::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function institutionData(): array
    {
        return $this->normalizeStringKeyedArray(collect($this->validated())->except('week_days')->all());
    }

    /**
     * @return array<int, int|string>
     */
    public function weekDays(): array
    {
        /** @var array<int, int|string> $weekDays */
        $weekDays = $this->validated('week_days', []);

        return $weekDays;
    }
}
