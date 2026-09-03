<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Happening;
use App\Models\Resource;
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

    public function retentionDays(): int
    {
        $cleanupDays = config('roomz.happenings.cleanup_days');

        return is_numeric($cleanupDays) ? max(0, (int) $cleanupDays) : 0;
    }
}
