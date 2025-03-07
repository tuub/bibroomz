<?php

namespace App\Http\Requests\Admin;

use App\Models\UserGroup;
use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function rules()
    {
        return [
            'users' => ['required', 'array'],
            'users.*.name' => ['required', 'string'],

            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
        ];
    }

    public function authorize()
    {
        return $this->user()->can('import', UserGroup::find($this->id));
    }
}
