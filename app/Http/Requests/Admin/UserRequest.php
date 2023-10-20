<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\User;
use App\Rules\CurrentPasswordRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        foreach ($this->roles as $role) {
            $institution = Institution::find($role['institution_id']);

            if (!$this->user()->can('edit', $institution)) {
                return false;
            }
        }

        return $this->user()->can('update', User::find($this->id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id' => ['nullable', 'uuid'],
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
                new CurrentPasswordRule($this->input('name'), $this->input('current_password'))
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
                Rule::when(!$this->user()->can('edit admin users'), 'declined'),
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
            'roles' => $this->input('roles', []),
        ]);
    }
}
