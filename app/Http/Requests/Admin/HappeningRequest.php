<?php

namespace App\Http\Requests\Admin;

use App\Library\Utility;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

abstract class HappeningRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        /** @var User */
        $user1 = User::find($this->user_id_01);

        /** @var Resource */
        $resource = Resource::find($this->resource_id);

        $is_admin = $user1?->isAdmin() || $user1?->isInstitutionAdmin($resource?->institution);
        $is_verification_required = !$is_admin && $resource?->is_verification_required;

        return [
            'start_date' => ['required', 'date_format:d.m.Y'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:d.m.Y'],
            'end_time' => ['required', 'date_format:H:i'],
            'resource_id' => ['required', 'uuid'],
            'user_id_01' => ['required', 'uuid'],
            'user_id_02' => [
                'sometimes',
                'nullable',
                'uuid',
                $is_verification_required ? 'required_if:is_verified,true' : '',
                'exclude_if:is_verified,false',
            ],
            'verifier' => [
                $is_verification_required ? 'required_if:is_verified,false' : '',
                'exclude_if:is_verified,true',
                'not_in:' . $user1?->name,
            ],
            'is_verified' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function sanitized()
    {
        return $this->safe()->collect()
            ->merge([
                'start' => Utility::createCarbonDateTime(
                    $this->start_date,
                    $this->start_time,
                )->toIsoString(),
                'end' => Utility::createCarbonDateTime(
                    $this->end_date,
                    $this->end_time,
                )->toIsoString()
            ])->except([
                'start_date',
                'start_time',
                'end_date',
                'end_time',
            ]);
    }
}
