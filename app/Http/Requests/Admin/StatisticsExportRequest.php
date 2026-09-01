<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class StatisticsExportRequest extends StatisticsRequest
{
    public const TYPES = [
        'time_series',
        'institutions',
        'resource_groups',
        'resources',
        'heatmap',
    ];

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
        ];
    }

    public function type(): string
    {
        return $this->validatedString('type');
    }
}
