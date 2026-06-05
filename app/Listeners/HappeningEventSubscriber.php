<?php

namespace App\Listeners;

use App\Events\HappeningBroadcastEvent;
use App\Events\HappeningVerifiedEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Services\Happenings\HappeningNotificationService;

class HappeningEventSubscriber
{
    public function __construct(private HappeningNotificationService $notificationService)
    {
    }

    private function handleHappeningEvent(HappeningBroadcastEvent $event): void
    {
        $this->notificationService->sendForEvent($event);
    }

    public function handleHappeningCreatedEvent(HappeningCreatedEvent $event): void
    {
        $this->handleHappeningEvent($event);
    }


    public function handleHappeningUpdatedEvent(HappeningBroadcastEvent $event): void
    {
        $this->handleHappeningEvent($event);
    }

    public function handleHappeningDeletedEvent(HappeningDeletedEvent $event): void
    {
        $this->handleHappeningEvent($event);
    }

    public function handleHappeningVerifiedEvent(HappeningVerifiedEvent $event): void
    {
        $this->handleHappeningEvent($event);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            HappeningCreatedEvent::class => 'handleHappeningCreatedEvent',
            HappeningVerifiedEvent::class => 'handleHappeningVerifiedEvent',
            HappeningUpdatedEvent::class => 'handleHappeningUpdatedEvent',
            HappeningDeletedEvent::class => 'handleHappeningDeletedEvent',
        ];
    }
}
