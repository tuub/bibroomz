<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Support\Str;

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
            'institution_id' => ['nullable', $this->uuidOrUuidListRule()],
            'resource_group_id' => ['nullable', $this->uuidOrUuidListRule()],
            'resource_id' => ['nullable', $this->uuidOrUuidListRule()],
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
        return $this->institutionIds()[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function institutionIds(): array
    {
        return $this->validatedIds('institution_id');
    }

    public function resourceGroupId(): ?string
    {
        return $this->resourceGroupIds()[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function resourceGroupIds(): array
    {
        return $this->validatedIds('resource_group_id');
    }

    public function resourceId(): ?string
    {
        return $this->resourceIds()[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function resourceIds(): array
    {
        return $this->validatedIds('resource_id');
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

    /**
     * @return Closure(string, mixed, Closure(string): void): void
     */
    private function uuidOrUuidListRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $values = is_array($value) ? $value : [$value];

            foreach ($values as $candidate) {
                if (! is_string($candidate) || $candidate === '' || ! Str::isUuid($candidate)) {
                    $fail(__('validation.uuid', ['attribute' => $attribute]));

                    return;
                }
            }
        };
    }

    /**
     * @return list<string>
     */
    private function validatedIds(string $key): array
    {
        $value = $this->validated($key);

        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                $ids[] = $candidate;
            }
        }

        return array_values(array_unique($ids));
    }
}
