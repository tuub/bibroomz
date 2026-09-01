<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class StatisticsRequest extends AdminRouteRequest
{
    public const RANGES = [
        'all',
        'this_week',
        'this_month',
        'this_year',
        'last_7_days',
        'last_30_days',
        'last_3_months',
        'last_12_months',
        'custom',
    ];

    public const GRANULARITIES = [
        'week',
        'month',
        'year',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', 'string', 'in:'.implode(',', self::RANGES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'granularity' => ['nullable', 'string', 'in:'.implode(',', self::GRANULARITIES)],
            'institution_id' => ['nullable', 'string', 'uuid'],
            'resource_group_id' => ['nullable', 'string', 'uuid'],
            'resource_id' => ['nullable', 'string', 'uuid'],
            'compare_from' => ['nullable', 'date'],
            'compare_to' => ['nullable', 'date', 'after_or_equal:compare_from'],
        ];
    }

    public function range(): string
    {
        $range = $this->validated('range');

        return is_string($range) && $range !== '' ? $range : 'all';
    }

    public function from(): ?string
    {
        $value = $this->validated('from');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function to(): ?string
    {
        $value = $this->validated('to');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function granularity(): string
    {
        $granularity = $this->validated('granularity');

        return is_string($granularity) && $granularity !== '' ? $granularity : 'month';
    }

    public function institutionId(): ?string
    {
        return $this->validatedId('institution_id');
    }

    public function resourceGroupId(): ?string
    {
        return $this->validatedId('resource_group_id');
    }

    public function resourceId(): ?string
    {
        return $this->validatedId('resource_id');
    }

    public function compareFrom(): ?string
    {
        $value = $this->validated('compare_from');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function compareTo(): ?string
    {
        $value = $this->validated('compare_to');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function validatedId(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
