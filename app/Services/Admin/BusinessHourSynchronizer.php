<?php

namespace App\Services\Admin;

use App\Models\BusinessHour;
use App\Models\Resource;
use Carbon\Carbon;

class BusinessHourSynchronizer
{
    /**
     * @param  array<int, array<string, mixed>>  $businessHours
     */
    public function sync(Resource $resource, array $businessHours): void
    {
        $businessHourIds = array_values(array_filter(array_map(
            fn (array $businessHour): ?string => $this->stringValue($businessHour, 'id'),
            $businessHours,
        )));

        BusinessHour::query()
            ->where('resource_id', $resource->id)
            ->when(
                $businessHourIds === [],
                fn ($query) => $query,
                fn ($query) => $query->whereNotIn('id', $businessHourIds),
            )
            ->delete();

        foreach ($businessHours as $businessHour) {
            $startDateValue = $this->stringValue($businessHour, 'start_date');
            $endDateValue = $this->stringValue($businessHour, 'end_date');
            $start = $this->stringValue($businessHour, 'start') ?? '00:00';
            $end = $this->stringValue($businessHour, 'end') ?? '00:00';

            $startDate = $startDateValue
                ? Carbon::parse($startDateValue)->startOfDay()
                : null;
            $endDate = $endDateValue
                ? Carbon::parse($endDateValue)->startOfDay()
                : null;

            $result = BusinessHour::updateOrCreate(
                ['id' => $businessHour['id']],
                [
                    'resource_id' => $resource->id,
                    'start' => Carbon::parse($start)->isMidnight() ? '00:00' : $start,
                    'end' => Carbon::parse($end)->isMidnight() ? '24:00' : $end,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            );

            $result->week_days()->sync($this->weekDayIds($businessHour['week_days'] ?? []));
        }
    }

    /**
     * @param  array<string, mixed>  $businessHour
     */
    private function stringValue(array $businessHour, string $key): ?string
    {
        $value = $businessHour[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @return list<string>
     */
    private function weekDayIds(mixed $weekDays): array
    {
        if (! is_array($weekDays)) {
            return [];
        }

        $ids = [];

        foreach ($weekDays as $weekDay) {
            if (is_string($weekDay)) {
                $ids[] = $weekDay;
            }
        }

        return $ids;
    }
}
