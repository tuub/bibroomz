<?php

namespace App\Http\Requests;

use App\Models\Happening;
use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;

class AddHappeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Happening::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $resource = Resource::find($this->resource['id']);
        $institution = $resource->institution;

        $user = $this->user();

        $is_verification_required = $resource->isVerificationRequired() && !$user->hasPermission('no verifier', $institution);

        return [
            'resource' => ['required'],
            'start' => ['required'],
            'end' => ['required'],
            'verifier' => [
                $is_verification_required ? 'required' : '', 'not_in:' . $user->name,
            ],
        ];
    }
}
