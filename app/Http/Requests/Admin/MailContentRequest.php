<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MailContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'uuid'],
            'mail_type_id' => ['required'],
            'subject' => ['required'],
            'title' => [],
            'salutation' => [],
            'intro' => [],
            'outro' => [],
            'action_uri' => [],
            'action_uri_label' => ['required_with:action_uri'],
            'farewell' => [],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
