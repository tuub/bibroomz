<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'id' => ['required', 'exists:users'],
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
