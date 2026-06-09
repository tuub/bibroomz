<?php

namespace App\Http\Requests\Admin;

use App\Library\Utility;
use App\Models\Resource;
use App\Models\User;

abstract class HappeningRequest extends AdminRouteRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user1 = $this->findModel(User::class, 'user_id_01');
        $resource = $this->findModel(Resource::class, 'resource_id');

        $isAdmin = $user1?->hasPermission('no_verifier', $resource?->resource_group->institution) ?? false;
        $isVerificationRequired = ! $isAdmin && (bool) $resource?->is_verification_required;

        return [
            'id' => ['sometimes', 'nullable', 'uuid', 'exists:happenings,id'],
            'start_date' => ['required', 'date_format:d.m.Y'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:d.m.Y'],
            'end_time' => ['required', 'date_format:H:i'],
            'resource_id' => ['required', 'uuid', 'exists:resources,id'],
            'user_id_01' => ['required', 'uuid', 'exists:users,id'],
            'user_id_02' => [
                'sometimes',
                'nullable',
                'uuid',
                $isVerificationRequired ? 'required_if:is_verified,true' : '',
                'exclude_if:is_verified,false',
                'exists:users,id',
            ],
            'verifier' => [
                $isVerificationRequired ? 'required_if:is_verified,false' : '',
                'exclude_if:is_verified,true',
                'not_in:'.$user1?->name,
            ],
            'is_verified' => [
                'required',
                'boolean',
            ],
            'label' => [''],
        ];
    }

    public function userOne(): ?User
    {
        return $this->findModel(User::class, 'user_id_01');
    }

    public function resource(): ?Resource
    {
        return $this->findModel(Resource::class, 'resource_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitized(): array
    {
        $startDate = $this->validated('start_date');
        $startTime = $this->validated('start_time');
        $endDate = $this->validated('end_date');
        $endTime = $this->validated('end_time');

        return $this->normalizeStringKeyedArray($this->safe()->collect()
            ->merge([
                'start' => Utility::createCarbonDateTime(
                    is_string($startDate) ? $startDate : '',
                    is_string($startTime) ? $startTime : '',
                )->toIsoString(),
                'end' => Utility::createCarbonDateTime(
                    is_string($endDate) ? $endDate : '',
                    is_string($endTime) ? $endTime : '',
                )->toIsoString(),
            ])->except([
                'start_date',
                'start_time',
                'end_date',
                'end_time',
            ])->all());
    }
}
