<?php

namespace App\Services\Http;

use App\Models\BusinessHour;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Resources\ResourceBusinessHoursResolver;
use Carbon\CarbonImmutable;

class PublicResourcePresenter
{
    public function __construct(private ResourceBusinessHoursResolver $businessHoursResolver)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Resource $resource, ResourceGroup $resourceGroup, CarbonImmutable $date): array
    {
        $businessHours = $this->businessHoursResolver->forDate($resource, $date)->map(
            fn (BusinessHour $businessHour) => [
                'startTime' => $businessHour->start,
                'endTime' => $businessHour->end,
                'daysOfWeek' => $businessHour->week_days->pluck('day_of_week'),
            ]
        );

        if ($businessHours->isEmpty()) {
            $businessHours->push([
                'startTime' => '',
                'endTime' => '',
                'daysOfWeek' => collect(),
            ]);
        }

        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'businessHours' => $businessHours->values(),
            'isVerificationRequired' => $resource->is_verification_required,
            'capacity' => $resource->capacity,
            'location_uri' => $resource->location_uri,
            'resourceGroup' => $resource->resource_group->id,
            'order' => $resource->order,
            'translations' => [
                'title' => $resource->getTranslations('title'),
                'description' => $resource->getTranslations('description'),
                'location' => $resource->getTranslations('location'),
                'resourceGroup' => $resourceGroup->getTranslations('term_singular'),
            ],
        ];
    }
}
