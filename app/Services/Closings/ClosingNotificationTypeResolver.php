<?php

declare(strict_types=1);

namespace App\Services\Closings;

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingEvent;
use App\Events\ClosingUpdatedEvent;

class ClosingNotificationTypeResolver
{
    public function resolve(ClosingEvent $event): string
    {
        return match (true) {
            $event instanceof ClosingCreatedEvent => 'closing_created',
            $event instanceof ClosingUpdatedEvent => 'closing_updated',
            $event instanceof ClosingDeletedEvent => 'closing_deleted',
            default => 'closing_updated',
        };
    }
}
