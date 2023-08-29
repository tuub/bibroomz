<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $institution = Institution::findOrFail($this->institution_id);

        return $this->user()->can('create', [Resource::class, $institution]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'institution_id' => ['required'],
            'title' => ['required'],
            'location' => ['required'],
            'location_uri' => ['url', 'nullable'],
            'description' => ['required'],
            'capacity' => ['numeric', 'gt:0'],
            'is_active' => ['required'],
            'is_verification_required' => ['required'],
            'business_hours' => ['array', 'required_if:is_active,true'],
            'business_hours.*.id' => ['required_with:business_hours'],
            'business_hours.*.start' => ['required_with:business_hours'],
            'business_hours.*.end' => ['required_with:business_hours'],
            'business_hours.*.week_days' => ['required_with:business_hours'],
        ];
    }
}
