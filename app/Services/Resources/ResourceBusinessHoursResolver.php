<?php

namespace App\Services\Resources;

use App\Models\BusinessHour;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ResourceBusinessHoursResolver
{
    /**
     * @return Collection<int, BusinessHour>
     */
    public function forDate(Resource $resource, CarbonImmutable $date): Collection
    {
        $validBusinessHours = $resource->business_hours->filter->isValidForDate($date);

        if ($validBusinessHours->isNotEmpty()) {
            return $validBusinessHours;
        }

        return $resource->business_hours->filter->isFallback();
    }
}
