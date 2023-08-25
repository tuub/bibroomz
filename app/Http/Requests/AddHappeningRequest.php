<?php

namespace App\Http\Requests;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
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
        /** @var User */
        $user = auth()->user();

        return $user->can('create', Happening::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $resource = Resource::find($this->resource['id']);

        /** @var User */
        $user = auth()->user();

        $is_admin = $user->isAdmin() || $user->isInstitutionAdmin($resource->institution);
        $is_verification_required = $resource->is_verification_required && !$is_admin;

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
