<?php

namespace App\Http\Requests\Admin;

use App\Rules\RequiredWithTranslationRule;
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
        return [
            'title' => [new RequiredWithTranslationRule()],
            'short_title' => ['required'],
            'slug' => ['required'],
            'location' => [],
            'week_days' => ['required'],
            'home_uri' => ['url'],
            'logo_uri' => ['url'],
            'teaser_uri' => ['url'],
            'email' => ['email'],
            'is_active' => ['required'],
        ];
    }
}
