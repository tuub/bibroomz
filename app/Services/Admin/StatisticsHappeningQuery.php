<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Happening;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StatisticsHappeningQuery
{
    /**
     * @param  Collection<int, Resource>  $resources
     * @return Builder<Happening>
     */
    public function forResources(
        Collection $resources,
        ?CarbonInterface $rangeFrom,
        ?CarbonInterface $rangeTo,
        bool $onlyTrashed = false,
    ): Builder {
        $query = $onlyTrashed ? Happening::onlyTrashed() : Happening::query();

        return $query
            ->whereIn('resource_id', $resources->pluck('id'))
            ->when($rangeFrom instanceof CarbonInterface, fn ($query) => $query->where('start', '>=', $rangeFrom))
            ->when($rangeTo instanceof CarbonInterface, fn ($query) => $query->where('start', '<=', $rangeTo));
    }

    /**
     * The first and last booking the given resources hold, as wall-clock
     * dates. A range of "all" has no bounds of its own, so the time series
     * takes its window from here instead of guessing one.
     *
     * @param  Collection<int, Resource>  $resources
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    public function bookedPeriod(Collection $resources): array
    {
        $period = $this->forResources($resources, null, null)
            ->toBase()
            ->selectRaw('MIN(start) as first_start, MAX(start) as last_start')
            ->first();

        $first = $period->first_start ?? null;
        $last = $period->last_start ?? null;

        if (! is_string($first) || ! is_string($last)) {
            return [null, null];
        }

        return [CarbonImmutable::parse($first), CarbonImmutable::parse($last)];
    }

    public function retentionDays(): int
    {
        $cleanupDays = config('roomz.happenings.cleanup_days');

        return is_numeric($cleanupDays) ? max(0, (int) $cleanupDays) : 0;
    }
}
