<?php

namespace App\Listeners;

use App\Events\HappeningVerified;
use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningUpdated;
use App\Mail\HappeningVerified as MailHappeningVerified;
use App\Mail\HappeningCreated as MailHappeningCreated;
use App\Mail\HappeningDeleted as MailHappeningDeleted;
use App\Mail\HappeningUpdated as MailHappeningUpdated;
use Illuminate\Support\Facades\Mail;

class HappeningEventSubscriber
{
    public function handleHappeningVerified(HappeningVerified $event): void
    {
        Mail::to($event->user)
            ->queue(new MailHappeningVerified($event->happening));
    }

    public function handleHappeningCreated(HappeningCreated $event): void
    {
        Mail::to($event->user)
            ->queue(new MailHappeningCreated($event->happening));
    }

    public function handleHappeningDeleted(HappeningDeleted $event): void
    {
        Mail::to($event->user)
            ->queue(new MailHappeningDeleted($event->happening));
    }

    public function handleHappeningUpdated(HappeningUpdated $event): void
    {
        Mail::to($event->user)
            ->queue(new MailHappeningUpdated($event->happening));
    }

    public function subscribe(): array
    {
        return [
            HappeningVerified::class => 'handleHappeningVerified',
            HappeningCreated::class => 'handleHappeningCreated',
            HappeningDeleted::class => 'handleHappeningDeleted',
            HappeningUpdated::class => 'handleHappeningUpdated',
        ];
    }
}
