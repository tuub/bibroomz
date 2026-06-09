<?php

namespace App\Services\Closings;

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Models\Closing;

class ClosingEventDispatcher
{
    public function dispatchCreated(Closing $closing): void
    {
        $this->dispatch($closing, ClosingCreatedEvent::class);
    }

    public function dispatchUpdated(Closing $closing): void
    {
        $this->dispatch($closing, ClosingUpdatedEvent::class);
    }

    public function dispatchDeleted(Closing $closing): void
    {
        $this->dispatch($closing, ClosingDeletedEvent::class);
    }

    private function dispatch(Closing $closing, string $eventClass): void
    {
        $closing->loadMissing('closable');

        foreach ($closing->getUsersAffected() as $user) {
            $eventClass::dispatch($user, $closing->getUserHappeningsAffected($user), $closing);
        }
    }
}
