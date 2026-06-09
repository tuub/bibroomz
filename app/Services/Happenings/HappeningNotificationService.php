<?php

namespace App\Services\Happenings;

use App\Events\HappeningBroadcastEvent;
use App\Mail\HappeningMail;
use App\Mail\HappeningMailData;
use App\Mail\MailEnvelopeData;
use App\Services\Notifications\NotificationDispatchService;

class HappeningNotificationService
{
    public function __construct(
        private readonly HappeningNotificationTypeResolver $typeResolver,
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {}

    public function sendForEvent(HappeningBroadcastEvent $event): void
    {
        $event->happening->loadMissing('resource.resource_group.institution');
        $institution = $event->happening->resource->resource_group->institution;
        $mailType = $this->typeResolver->resolve($event);
        $fromAddress = is_string($institution->email) ? $institution->email : '';

        $this->notificationDispatchService->queue(
            recipient: $event->user,
            institutionId: $institution->id,
            mailTypeKey: $mailType,
            mailBuilder: fn ($mailContent): HappeningMail => new HappeningMail(new HappeningMailData(
                happening: $event->happening,
                content: $mailContent,
                envelope: new MailEnvelopeData($fromAddress),
            )),
        );
    }
}
