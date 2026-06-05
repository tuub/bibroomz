<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\User;
use App\Rules\CurrentPasswordRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();

        if ($user === null) {
            return false;
        }

        foreach ($this->inputRoles() as $role) {
            if (! isset($role['institution_id']) || ! is_string($role['institution_id'])) {
                return false;
            }

            $institution = Institution::query()->find($role['institution_id']);

            if ($institution === null || ! $user->can('edit', $institution)) {
                return false;
            }
        }

        if (! $this->input('id')) {
            return $user->can('create', User::class);
        }

        $targetUser = $this->targetUserOrNull();

        return $targetUser !== null && $user->can('update', $targetUser);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $canEditAdminUsers = $this->userModel()?->can('edit_admin_users') ?? false;

        return [
            'id' => ['nullable', 'uuid', 'exists:users,id'],
            'is_system_user' => ['required', 'boolean'],
            'name' => ['required_if_accepted:is_system_user', 'string', 'min:3'],
            'email' => ['required_if_accepted:is_system_user', 'email'],
            'is_set_password' => ['required_if_accepted:is_system_user', 'boolean'],
            'current_password' => [
                Rule::requiredIf(function () {
                    return $this->input('is_set_password') && $this->input('id');
                }),
                'nullable',
                'string',
                new CurrentPasswordRule($this->inputString('name'), $this->inputString('current_password')),
            ],
            'password' => [
                'required_if_accepted:is_set_password',
                'nullable',
                'string',
            ],
            'password_confirm' => [
                'required_if_accepted:is_set_password',
                'same:password',
                'nullable',
                'string',
            ],
            'is_admin' => [
                'required',
                'boolean',
                Rule::when(! $canEditAdminUsers, 'declined'),
            ],
            'roles' => ['array'],
            'roles.*' => ['array:role_id,institution_id'],
            'roles.*.role_id' => ['required', 'exists:roles,id'],
            'roles.*.institution_id' => ['required', 'exists:institutions,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'roles' => $this->inputRoles(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function inputRoles(): array
    {
        $roles = $this->input('roles', []);

        if (! is_array($roles)) {
            return [];
        }

        $normalizedRoles = [];

        foreach ($roles as $role) {
            if (is_array($role)) {
                $normalizedRoles[] = $this->normalizeStringKeyedArray($role);
            }
        }

        return $normalizedRoles;
    }

    /**
     * @return array<int, array{role_id: string, institution_id: string}>
     */
    public function roles(): array
    {
        /** @var array<int, array{role_id: string, institution_id: string}> $roles */
        $roles = $this->validated('roles', []);

        return $roles;
    }

    /**
     * @return array<string, mixed>
     */
    public function userData(): array
    {
        return $this->normalizeStringKeyedArray(collect($this->validated())
            ->except(['roles', 'password_confirm'])
            ->except(['current_password', 'is_set_password'])
            ->when(
                $this->boolean('is_set_password'),
                fn ($data) => $data->merge(['password' => Hash::make($this->validatedString('password'))]),
            )
            ->when(
                ! $this->boolean('is_set_password'),
                fn ($data) => $data->except(['password']),
            )
            ->when(
                ! $this->boolean('is_system_user'),
                fn ($data) => $data->except(['email', 'password']),
            )
            ->all());
    }

    public function targetUser(): User
    {
        return $this->findModelOrFail(User::class);
    }

    public function targetUserOrNull(): ?User
    {
        return $this->findModel(User::class);
    }
}
