<?php

namespace App\Services\Closings;

use App\Library\Utility;
use Illuminate\Support\Arr;

class ClosingDataSanitizer
{
    /**
     * @param  array<string, mixed>  $closingData
     * @return array<string, mixed>
     */
    public function sanitize(array $closingData): array
    {
        $startDate = isset($closingData['start_date']) && is_string($closingData['start_date'])
            ? $closingData['start_date'] : '';
        $startTime = isset($closingData['start_time']) && is_string($closingData['start_time'])
            ? $closingData['start_time'] : '';
        $endDate = isset($closingData['end_date']) && is_string($closingData['end_date'])
            ? $closingData['end_date'] : '';
        $endTime = isset($closingData['end_time']) && is_string($closingData['end_time'])
            ? $closingData['end_time'] : '';

        $closingData['start'] = Utility::createCarbonDateTime(
            $startDate,
            $startTime,
        )->toISOString();
        $closingData['end'] = Utility::createCarbonDateTime(
            $endDate,
            $endTime,
        )->toIsoString();

        return $this->normalizeStringKeys(Arr::except($closingData, [
            'start_date',
            'start_time',
            'end_date',
            'end_time',
        ]));
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeStringKeys(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
