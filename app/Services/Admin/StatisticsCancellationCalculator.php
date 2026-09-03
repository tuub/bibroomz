<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StatisticsCancellationCalculator
{
    public function __construct(
        private readonly StatisticsHappeningQuery $happeningQuery,
        private readonly StatisticsFormatter $formatter,
    ) {}

    /**
     * Happenings are MassPrunable, so active and soft-deleted rows whose end
     * date is older than the configured cleanup window may already be gone.
     *
     * @param  Collection<int, Resource>  $resources
     * @return array{cancelled: int, active: int, rate: float, retentionDays: int, retentionExceeded: bool}
     */
    public function calculate(Collection $resources, ?CarbonInterface $rangeFrom, ?CarbonInterface $rangeTo): array
    {
        $active = $this->happeningQuery->forResources($resources, $rangeFrom, $rangeTo)->count();
        $cancelled = $this->happeningQuery->forResources($resources, $rangeFrom, $rangeTo, onlyTrashed: true)->count();
        $retentionDays = $this->happeningQuery->retentionDays();

        return [
            'cancelled' => $cancelled,
            'active' => $active,
            'rate' => $this->formatter->percentage($cancelled, $active + $cancelled),
            'retentionDays' => $retentionDays,
            'retentionExceeded' => ! $rangeFrom instanceof CarbonInterface
                || CarbonImmutable::parse($rangeFrom)->lessThan(CarbonImmutable::now()->subDays($retentionDays)),
        ];
    }
}
