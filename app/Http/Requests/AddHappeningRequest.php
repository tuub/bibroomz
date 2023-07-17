<?php

namespace App\Http\Requests;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

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
        $resource = Resource::find($this->resource['id']);

        $is_admin = auth()->user()->is_admin;
        $is_needing_confirmer = $resource->is_needing_confirmer && !$is_admin;

        return [
            'resource' => ['required'],
            'start' => ['required'],
            'end' => ['required'],
            'confirmer' => [
                $is_needing_confirmer ? 'required' : '', 'not_in:' . auth()->user()->name,
            ],
        ];
    }
}
