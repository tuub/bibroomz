<?php

namespace App\Listeners;

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Mail\ClosingCreatedMail;
use App\Mail\ClosingUpdatedMail;
use App\Models\Institution;
use App\Models\MailContent;
use Illuminate\Support\Facades\Mail;
use ReflectionClass;

class ClosingEventSubscriber
{
    public function handleClosingCreatedEvent(ClosingCreatedEvent $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();
        $mail_content = $this->getMailContentForEvent($event, 'closing_created');

        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new ClosingCreatedMail($event->closing, $event->happenings, $mail_class, $mail_content));
        }
    }

    public function handleClosingUpdatedEvent(ClosingUpdatedEvent $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();
        $mail_content = $this->getMailContentForEvent($event, 'closing_updated');

        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new ClosingUpdatedMail($event->closing, $event->happenings, $mail_class, $mail_content));
        }
    }

    public function subscribe(): array
    {
        return [
            ClosingCreatedEvent::class => 'handleClosingCreatedEvent',
            ClosingUpdatedEvent::class => 'handleClosingUpdatedEvent',
        ];
    }

    private function getMailContentForEvent($event, string $mail_type)
    {
        $closable = $event->closing->closable;
        $institution = $closable instanceof Institution ? $closable : $closable->institution;

        return MailContent::where('institution_id', $institution->id)
            ->whereHas('mail_type', function ($query) use($mail_type) {
                $query->where('name', $mail_type);
            })
            ->first();
    }
}
