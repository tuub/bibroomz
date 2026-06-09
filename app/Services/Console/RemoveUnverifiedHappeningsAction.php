<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Events\UnverifiedHappeningRemovedBySchedulerEvent;
use App\Models\Happening;
use Illuminate\Database\Eloquent\Builder;

class RemoveUnverifiedHappeningsAction
{
    /**
     * @param  Builder<Happening>  $query
     */
    public function execute(Builder $query): void
    {
        $query->each(function (Happening $happening): void {
            $happening->delete();
            $happening->broadcast(UnverifiedHappeningRemovedBySchedulerEvent::class);
        });
    }
}
