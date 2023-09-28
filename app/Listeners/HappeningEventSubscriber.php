<?php

namespace App\Listeners;

use App\Events\HappeningVerifiedEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Mail\HappeningVerifiedMail;
use App\Mail\HappeningCreatedMail;
use App\Mail\HappeningDeletedMail;
use App\Mail\HappeningUpdatedMail;
use Illuminate\Support\Facades\Mail;
use App\Models\MailContent;
use ReflectionClass;

class HappeningEventSubscriber
{
    public function handleHappeningCreatedEvent(HappeningCreatedEvent $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();

        $mail_type = 'happening_created';
        if ($event->happening->resource->is_verification_required) {
            $mail_type = 'happening_created_with_verification';
        }

        $mail_content = $this->getMailContentForEvent($event, $mail_type);

        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new HappeningCreatedMail($event->happening, $mail_class, $mail_content));
        }
    }

    public function handleHappeningUpdatedEvent(HappeningUpdatedEvent $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();
        $mail_content = $this->getMailContentForEvent($event, 'happening_updated');
        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new HappeningUpdatedMail($event->happening, $mail_class, $mail_content));
        }
    }

    public function handleHappeningDeletedEvent(HappeningDeletedEvent $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();
        $mail_content = $this->getMailContentForEvent($event, 'happening_deleted');
        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new HappeningDeletedMail($event->happening, $mail_class, $mail_content));
        }
    }

    public function handleHappeningVerifiedEvent(HappeningVerifiedEvent $event): void
    {
        $mail_class = (new ReflectionClass($event))->getShortName();
        $mail_content = $this->getMailContentForEvent($event, 'happening_verified');
        if ($mail_content && $mail_content->is_active) {
            Mail::to($event->user)
                ->queue(new HappeningVerifiedMail($event->happening, $mail_class, $mail_content));
        }
    }

    public function subscribe(): array
    {
        return [
            HappeningVerifiedEvent::class => 'handleHappeningVerifiedEvent',
            HappeningCreatedEvent::class => 'handleHappeningCreatedEvent',
            HappeningDeletedEvent::class => 'handleHappeningDeletedEvent',
            HappeningUpdatedEvent::class => 'handleHappeningUpdatedEvent',
        ];
    }

    private function getMailContentForEvent($event, string $mail_type)
    {
        return MailContent::where('institution_id', $event->happening->resource->resource_group->institution->id)
            ->whereHas('mail_type', function ($query) use($mail_type) {
                $query->where('name', $mail_type);
            })
            ->first();
    }
}
