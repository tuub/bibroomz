<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Support\Collection;

class StatisticsCsvExporter
{
    /**
     * @param  array{
     *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
     *     timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
     *     heatmap: array{cells: array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>, maxCount: int, totalCount: int},
     *     comparison?: ?array<string, mixed>,
     * }  $data
     * @return array<int, array<int, string>>
     */
    public function toRows(array $data, string $type): array
    {
        return match ($type) {
            'time_series' => $this->timeSeriesCsvRows($data['timeSeries']),
            'institutions' => $this->institutionsCsvRows($data['institutions']),
            'resource_groups' => $this->resourceGroupsCsvRows($data['resourceGroups'], $data['institutions']),
            'resources' => $this->resourcesCsvRows($data['resources'], $data['resourceGroups']),
            'heatmap' => $this->heatmapCsvRows($data['heatmap']['cells']),
            default => [],
        };
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     * @return array<int, array<int, string>>
     */
    private function timeSeriesCsvRows(array $timeSeries): array
    {
        if ($this->timeSeriesHasSegments($timeSeries)) {
            return $this->splitTimeSeriesCsvRows($timeSeries);
        }

        $rows = [['Label', 'Count']];

        foreach ($timeSeries as $entry) {
            $rows[] = [$entry['label'], (string) $entry['count']];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     * @return array<int, array<int, string>>
     */
    private function splitTimeSeriesCsvRows(array $timeSeries): array
    {
        $rows = [['Label', 'Total', ...$this->timeSeriesSegmentHeaders($timeSeries)]];

        foreach ($timeSeries as $entry) {
            $row = [$entry['label'], (string) $entry['count']];

            foreach ($entry['segments'] ?? [] as $segment) {
                $row[] = (string) $segment['count'];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     */
    private function timeSeriesHasSegments(array $timeSeries): bool
    {
        foreach ($timeSeries as $entry) {
            if (array_key_exists('segments', $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>  $timeSeries
     * @return array<int, string>
     */
    private function timeSeriesSegmentHeaders(array $timeSeries): array
    {
        foreach ($timeSeries as $entry) {
            if (! array_key_exists('segments', $entry)) {
                continue;
            }

            $headers = [];

            foreach ($entry['segments'] as $segment) {
                $headers[] = $this->csvTitle($segment['title']);
            }

            return $headers;
        }

        return [];
    }

    /**
     * @param  array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>  $cells
     * @return array<int, array<int, string>>
     */
    private function heatmapCsvRows(array $cells): array
    {
        $rows = [['Day of Week', 'Hour', 'Count', 'Percentage']];

        foreach ($cells as $cell) {
            $rows[] = [
                $this->toCsvString($cell['dayOfWeek']),
                $this->toCsvString($cell['hour']),
                $this->toCsvString($cell['count']),
                $this->toCsvString($cell['percentage']),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>  $institutions
     * @return array<int, array<int, string>>
     */
    private function institutionsCsvRows(Collection $institutions): array
    {
        $rows = [['Title', 'Active', 'Cancelled', 'Cancellation Rate']];

        foreach ($institutions as $institution) {
            $rows[] = [
                $this->csvTitle($institution['title']),
                $this->toCsvString($institution['active']),
                $this->toCsvString($institution['cancelled']),
                $this->toCsvString($institution['cancellationRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>  $resourceGroups
     * @param  Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>  $institutions
     * @return array<int, array<int, string>>
     */
    private function resourceGroupsCsvRows(Collection $resourceGroups, Collection $institutions): array
    {
        $institutionTitleById = $institutions->mapWithKeys(
            fn (array $institution): array => [$institution['id'] => $this->csvTitle($institution['title'])],
        );

        $rows = [['Title', 'Institution', 'Active', 'Cancelled', 'Cancellation Rate']];

        foreach ($resourceGroups as $resourceGroup) {
            $rows[] = [
                $this->csvTitle($resourceGroup['title']),
                $institutionTitleById[$resourceGroup['institution_id']] ?? '',
                $this->toCsvString($resourceGroup['active']),
                $this->toCsvString($resourceGroup['cancelled']),
                $this->toCsvString($resourceGroup['cancellationRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>  $resources
     * @param  Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>  $resourceGroups
     * @return array<int, array<int, string>>
     */
    private function resourcesCsvRows(Collection $resources, Collection $resourceGroups): array
    {
        $resourceGroupTitleById = $resourceGroups->mapWithKeys(
            fn (array $resourceGroup): array => [$resourceGroup['id'] => $this->csvTitle($resourceGroup['title'])],
        );

        $rows = [['Title', 'Resource Group', 'Active', 'Cancelled', 'Cancellation Rate']];

        foreach ($resources as $resource) {
            $rows[] = [
                $this->csvTitle($resource['title']),
                $resourceGroupTitleById[$resource['resource_group_id']] ?? '',
                $this->toCsvString($resource['active']),
                $this->toCsvString($resource['cancelled']),
                $this->toCsvString($resource['cancellationRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $translations
     */
    private function csvTitle(array $translations): string
    {
        $locale = app()->getLocale();

        return $translations[$locale] ?? $translations['en'] ?? (string) reset($translations);
    }

    private function toCsvString(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            is_bool($value) => $value ? '1' : '0',
            default => '',
        };
    }
}
