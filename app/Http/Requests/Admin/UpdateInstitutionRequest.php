<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInstitutionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $institution = Institution::findOrFail($this->id);

        return $this->user()->can('update', $institution);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
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
    }
}
