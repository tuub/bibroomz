<?php

namespace App\Services\Happenings;

use App\Events\HappeningsChangedEvent;
use App\Models\Happening;

use function event;

class HappeningBroadcaster
{
    public function __construct(
        private HappeningAudienceResolver $audienceResolver,
        private HappeningBroadcastPayloadFactory $payloadFactory,
        private HappeningBroadcastEventFactory $eventFactory,
    ) {
    }

    /**
     * @param class-string $broadcastEvent
     */
    public function broadcast(Happening $happening, string $broadcastEvent): void
    {
        foreach ($this->audienceResolver->resolve($happening) as $user) {
            event($this->eventFactory->make(
                $broadcastEvent,
                $happening,
                $user,
                $this->payloadFactory->make($happening, $user),
            ));
        }

        HappeningsChangedEvent::dispatch();
    }
}
