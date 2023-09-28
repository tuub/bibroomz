<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClosingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id' => ['required', 'uuid'],
            'start_date' => ['required', 'date_format:d.m.Y'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:d.m.Y'],
            'end_time' => ['required', 'date_format:H:i'],
            'description' => [''],
        ];
    }
}
