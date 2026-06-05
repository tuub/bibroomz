<?php

namespace App\Listeners;

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingEvent;
use App\Events\ClosingUpdatedEvent;
use App\Services\Closings\ClosingNotificationService;

class ClosingEventSubscriber
{
    public function __construct(private ClosingNotificationService $notificationService)
    {
    }

    private function handleClosingEvent(ClosingEvent $event): void
    {
        $this->notificationService->sendForEvent($event);
    }

    public function handleClosingCreatedEvent(ClosingCreatedEvent $event): void
    {
        $this->handleClosingEvent($event);
    }

    public function handleClosingUpdatedEvent(ClosingUpdatedEvent $event): void
    {
        $this->handleClosingEvent($event);
    }

    public function handleClosingDeletedEvent(ClosingDeletedEvent $event): void
    {
        $this->handleClosingEvent($event);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            ClosingCreatedEvent::class => 'handleClosingCreatedEvent',
            ClosingUpdatedEvent::class => 'handleClosingUpdatedEvent',
            ClosingDeletedEvent::class => 'handleClosingDeletedEvent',
        ];
    }
}
