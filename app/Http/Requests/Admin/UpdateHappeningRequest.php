<?php

namespace App\Http\Requests\Admin;

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
        // FIXME: is_confirmed must be false if user_id_02 is not given and vice versa!
        return [
            'start' => ['required'],
            'end' => ['required'],
            'resource_id' => ['required', 'uuid'],
            'user_id_01' => ['required', 'uuid'],
            'user_id_02' => ['sometimes', 'nullable', 'uuid', 'required_with:is_confirmed|boolean'],
            'confirmer' => ['string'],
            'is_confirmed' => ['boolean', 'required_if:user_id_02,'],
        ];
    }
}
