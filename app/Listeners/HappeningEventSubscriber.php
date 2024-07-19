<?php

namespace App\Listeners;

use App\Events\HappeningBroadcastEvent;
use App\Events\HappeningVerifiedEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Mail\HappeningMail;
use Illuminate\Support\Facades\Mail;
use App\Models\MailContent;

class HappeningEventSubscriber
{
    private function handleHappeningEvent(HappeningBroadcastEvent $event): void
    {
        $mail_content = $this->getMailContentForEvent($event);

        if (!$mail_content?->is_active) {
            return;
        }

        Mail::to($event->user)->queue(new HappeningMail($event->happening, $mail_content));
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

    public function subscribe(): array
    {
        return [
            HappeningCreatedEvent::class => 'handleHappeningCreatedEvent',
            HappeningVerifiedEvent::class => 'handleHappeningVerifiedEvent',
            HappeningUpdatedEvent::class => 'handleHappeningUpdatedEvent',
            HappeningDeletedEvent::class => 'handleHappeningDeletedEvent',
        ];
    }

    private function getMailContentForEvent($event)
    {
        switch (true) {
            case $event instanceof HappeningCreatedEvent:
                if ($event->happening->resource->is_verification_required) {
                    $mail_type = 'happening_created_with_verification';
                } else {
                    $mail_type = 'happening_created';
                }
                break;
            case $event instanceof HappeningVerifiedEvent:
                $mail_type = 'happening_verified';
                break;
            case $event instanceof HappeningUpdatedEvent:
                $mail_type = 'happening_updated';
                break;
            case $event instanceof HappeningDeletedEvent:
                $mail_type = 'happening_deleted';
                break;
        }

        return MailContent::where('institution_id', $event->happening->resource->resource_group->institution->id)
            ->whereHas('mail_type', function ($query) use ($mail_type) {
                $query->where('key', $mail_type);
            })
            ->first();
    }
}
