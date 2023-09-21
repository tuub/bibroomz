<?php

namespace App\Listeners;

use App\Events\ClosingCreated;
use App\Events\ClosingUpdated;
use App\Mail\ClosingCreated as MailClosingCreated;
use App\Mail\ClosingUpdated as MailClosingUpdated;
use App\Models\Institution;
use Illuminate\Support\Facades\Mail;
use App\Models\MailContent;
use ReflectionClass;

class ClosingEventSubscriber
{
    public function handleClosingCreated(ClosingCreated $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();
        $mail_content = $this->getMailContentForEvent($event, 'closing_created');

        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new MailClosingCreated($event->closing, $event->happenings, $mail_class, $mail_content));
        }
    }

    public function handleClosingUpdated(ClosingUpdated $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();
        $mail_content = $this->getMailContentForEvent($event, 'closing_updated');

        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new MailClosingUpdated($event->closing, $event->happenings, $mail_class, $mail_content));
        }
    }

    public function subscribe(): array
    {
        return [
            ClosingCreated::class => 'handleClosingCreated',
            ClosingUpdated::class => 'handleClosingUpdated',
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
