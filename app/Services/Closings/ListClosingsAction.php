<?php

namespace App\Services\Closings;

use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Collection;

class ListClosingsAction
{
    /**
     * @return Collection<int, Closing>
     */
    public function execute(Institution|Resource $closable): Collection
    {
        return Closing::query()
            ->where('closable_id', $closable->getKey())
            ->where('closable_type', $closable->getMorphClass())
            ->orderBy('start')
            ->get();
    }
}
