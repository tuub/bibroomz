<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreClosingRequest extends FormRequest
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
            'closable_id' => ['required'],
            'closable_type' => ['required'],
            'start_date' => ['required'],
            'start_time' => ['required'],
            'end_date' => ['required'],
            'end_time' => ['required'],
            'description' => [''],
        ];
    }
}
