<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHappeningRequest extends FormRequest
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
        // FIXME: check verifier requirement here

        return [
            'id' => ['required', 'uuid'],
            'resource' => ['required'],
            'start' => ['required'],
            'end' => ['required'],
            // 'verifier' => ['required'],
        ];
    }
}
