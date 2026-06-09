<?php

namespace App\Services\Happenings;

use App\Events\HappeningBroadcastEvent;
use App\Models\Happening;
use App\Models\User;
use InvalidArgumentException;

class HappeningBroadcastEventFactory
{
    /**
     * @param  class-string  $broadcastEvent
     * @param  array<string, mixed>  $payload
     */
    public function make(
        string $broadcastEvent,
        Happening $happening,
        User $user,
        array $payload,
    ): HappeningBroadcastEvent {
        if (! is_a($broadcastEvent, HappeningBroadcastEvent::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Expected a %s subclass, got %s.',
                HappeningBroadcastEvent::class,
                $broadcastEvent,
            ));
        }

        return new $broadcastEvent($happening, $user, $payload);
    }
}
