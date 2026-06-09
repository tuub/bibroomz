<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;

class ResourceTimeSlotsRequest extends ResourceGroupRouteRequest
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return $this->mergeRuleSets(parent::rules(), [
            'id' => ['required', 'uuid', 'exists:resources,id'],
            'happening_id' => ['nullable', 'uuid', 'exists:happenings,id'],
            'start' => ['required'],
            'end' => ['required'],
        ]);
    }

    public function start(): CarbonImmutable
    {
        $start = $this->input('start');

        return CarbonImmutable::parse(is_string($start) ? $start : null);
    }

    public function end(): CarbonImmutable
    {
        $end = $this->input('end');

        return CarbonImmutable::parse(is_string($end) ? $end : null);
    }

    public function resourceId(): string
    {
        $resourceId = $this->validated('id');

        return is_string($resourceId) ? $resourceId : '';
    }

    public function happeningId(): ?string
    {
        $happeningId = $this->validated('happening_id');

        return is_string($happeningId) ? $happeningId : null;
    }
}
