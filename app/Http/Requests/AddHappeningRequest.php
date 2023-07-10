<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddHappeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'resource' => ['required'],
            'start' => ['required'],
            'end' => ['required'],
            'confirmer' => [
                auth()->user()->is_admin ? '' : 'required', 'not_in:' . auth()->user()->name,
            ],
        ];
    }
}
