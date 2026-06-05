<?php

namespace App\Services\Happenings;

use App\Events\HappeningDeletedEvent;
use App\Models\Happening;

class DeleteHappeningAction
{
    public function __construct(private HappeningBroadcaster $broadcaster)
    {
    }

    public function execute(Happening $happening): bool
    {
        if (!$happening->delete()) {
            return false;
        }

        $this->broadcaster->broadcast($happening, HappeningDeletedEvent::class);

        return true;
    }
}
