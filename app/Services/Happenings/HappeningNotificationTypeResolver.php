<?php

declare(strict_types=1);

namespace App\Services\Happenings;

use App\Events\HappeningBroadcastEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Events\HappeningVerifiedEvent;

class HappeningNotificationTypeResolver
{
    public function resolve(HappeningBroadcastEvent $event): string
    {
        return match (true) {
            $event instanceof HappeningCreatedEvent && $event->happening->resource->is_verification_required => 'happening_created_with_verification',
            $event instanceof HappeningCreatedEvent => 'happening_created',
            $event instanceof HappeningVerifiedEvent => 'happening_verified',
            $event instanceof HappeningUpdatedEvent => 'happening_updated',
            $event instanceof HappeningDeletedEvent => 'happening_deleted',
            default => 'happening_updated',
        };
    }
}
