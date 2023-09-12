<?php

namespace App\Http\Requests\Admin;

use App\Library\Utility;
use Illuminate\Foundation\Http\FormRequest;

class StoreClosingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'closable_id' => ['required', 'uuid'],
            'closable_type' => ['required'],
            'start_date' => ['required', 'date_format:d.m.Y'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:d.m.Y'],
            'end_time' => ['required', 'date_format:H:i'],
            'description' => [''],
        ];

        Utility::makeRulesTranslatable($rules, ['description']);

        return $rules;
    }
}
