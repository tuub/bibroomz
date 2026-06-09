<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;

class PublicResourcesRequest extends ResourceGroupRouteRequest
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return $this->mergeRuleSets(parent::rules(), [
            'count' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date'],
        ]);
    }

    public function requestedDate(): CarbonImmutable
    {
        $date = $this->input('date');

        return CarbonImmutable::parse(is_string($date) && $date !== '' ? $date : 'today')->startOfDay();
    }

    public function perPage(): int
    {
        return $this->integer('count', 15);
    }
}
