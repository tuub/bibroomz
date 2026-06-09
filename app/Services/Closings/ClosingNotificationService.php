<?php

namespace App\Services\Closings;

use App\Events\ClosingEvent;
use App\Mail\ClosingMail;
use App\Mail\ClosingMailData;
use App\Mail\MailEnvelopeData;
use App\Services\Notifications\NotificationDispatchService;

class ClosingNotificationService
{
    public function __construct(
        private readonly ClosingNotificationTypeResolver $typeResolver,
        private readonly ClosingInstitutionResolver $institutionResolver,
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {}

    public function sendForEvent(ClosingEvent $event): void
    {
        $closing = $event->closing();
        $institution = $this->institutionResolver->resolveForClosing($closing);
        $mailType = $this->typeResolver->resolve($event);
        $fromAddress = is_string($institution->email) ? $institution->email : '';

        $this->notificationDispatchService->queue(
            recipient: $event->user(),
            institutionId: $institution->id,
            mailTypeKey: $mailType,
            mailBuilder: fn ($mailContent): ClosingMail => new ClosingMail(new ClosingMailData(
                closing: $closing,
                happenings: $event->happenings(),
                content: $mailContent,
                envelope: new MailEnvelopeData($fromAddress),
            )),
        );
    }
}
