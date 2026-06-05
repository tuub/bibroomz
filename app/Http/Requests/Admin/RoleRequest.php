<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Rules\RequiredWithTranslationRule;

class RoleRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $role = $this->roleOrNull();

        if ($user === null) {
            return false;
        }

        if ($role === null) {
            return $user->can('create', Role::class);
        }

        return $user->can('edit', $role);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'uuid', 'exists:roles,id'],
            'name' => [new RequiredWithTranslationRule()],
            'description' => [''],
            'permissions' => ['array'],
            'permissions.*' => ['uuid', 'exists:permissions,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permissions' => $this->input('permissions', []),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function permissions(): array
    {
        /** @var array<int, string> $permissions */
        $permissions = $this->validated('permissions', []);

        return $permissions;
    }

    /**
     * @return array<string, mixed>
     */
    public function roleData(): array
    {
        return $this->normalizeStringKeyedArray(collect($this->validated())->except('permissions')->all());
    }

    public function role(): Role
    {
        return $this->findModelOrFail(Role::class);
    }

    public function roleOrNull(): ?Role
    {
        return $this->findModel(Role::class);
    }
}
