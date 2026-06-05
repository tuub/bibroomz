<?php

namespace App\Services\Resources;

use App\Library\Utility;
use App\Models\Resource;

class ResourceSettingsResolver
{
    public function resourceGroupString(Resource $resource, string $key, string $fallback = ''): string
    {
        $setting = $resource->resource_group->settings->firstWhere('key', $key);
        $value = $setting?->value;

        return is_string($value) ? $value : $fallback;
    }

    public function institutionString(Resource $resource, string $key, string $fallback = ''): string
    {
        $setting = $resource->resource_group->institution->settings->firstWhere('key', $key);
        $value = $setting?->value;

        return is_string($value) ? $value : $fallback;
    }

    public function resourceGroupFloat(Resource $resource, string $key, float $fallback = 0.0): float
    {
        $value = $this->resourceGroupString($resource, $key);

        return is_numeric($value) ? (float) $value : $fallback;
    }

    /**
     * @return array{hour: int, minute: int}
     */
    public function timeSlotLength(Resource $resource): array
    {
        return Utility::getTimeValuesFromEnvTimeString(
            $this->resourceGroupString($resource, 'time_slot_length', '01:00'),
        );
    }
}
