<?php

namespace App\Listeners;

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingEvent;
use App\Events\ClosingUpdatedEvent;
use App\Mail\ClosingMail;
use App\Models\Institution;
use App\Models\MailContent;
use Illuminate\Support\Facades\Mail;

class ClosingEventSubscriber
{
    private function handleClosingEvent(ClosingEvent $event): void
    {
        $mail_content = $this->getMailContentForEvent($event);

        if (!$mail_content->is_active) {
            return;
        }

        Mail::to($event->user)->queue(new ClosingMail($event->closing, $event->happenings, $mail_content));
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

    public function subscribe(): array
    {
        return [
            ClosingCreatedEvent::class => 'handleClosingCreatedEvent',
            ClosingUpdatedEvent::class => 'handleClosingUpdatedEvent',
            ClosingDeletedEvent::class => 'handleClosingDeletedEvent',
        ];
    }

    private function getMailContentForEvent(ClosingEvent $event)
    {
        $closable = $event->closing->closable;
        $institution = $closable instanceof Institution ? $closable : $closable->resource_group->institution;

        switch (true) {
            case $event instanceof ClosingCreatedEvent:
                $mail_type = 'closing_created';
                break;
            case $event instanceof ClosingUpdatedEvent:
                $mail_type = 'closing_updated';
                break;
            case $event instanceof ClosingDeletedEvent:
                $mail_type = 'closing_deleted';
                break;
        }

        return MailContent::where('institution_id', $institution->id)
            ->whereHas('mail_type', function ($query) use ($mail_type) {
                $query->where('key', $mail_type);
            })
            ->first();
    }
}
