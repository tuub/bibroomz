<?php

namespace App\Http\Requests\Admin;

use App\Library\Utility;
use Illuminate\Foundation\Http\FormRequest;

class InstitutionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'title' => ['required'],
            'short_title' => ['required'],
            'slug' => ['required'],
            'location' => [],
            'week_days' => ['required'],
            'home_uri' => ['url'],
            'logo_uri' => ['url'],
            'teaser_uri' => ['url'],
            'is_active' => ['required'],
        ];

        Utility::makeRulesTranslatable($rules, ['title']);

        return $rules;
    }
}
