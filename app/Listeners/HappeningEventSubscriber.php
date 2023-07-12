<?php

namespace App\Listeners;

use App\Events\HappeningConfirmed;
use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningUpdated;
use App\Mail\HappeningConfirmed as MailHappeningConfirmed;
use App\Mail\HappeningCreated as MailHappeningCreated;
use App\Mail\HappeningDeleted as MailHappeningDeleted;
use App\Mail\HappeningUpdated as MailHappeningUpdated;
use Illuminate\Support\Facades\Mail;

class HappeningEventSubscriber
{
    public function handleHappeningConfirmed(HappeningConfirmed $event): void
    {
        Mail::to($event->user)
            ->queue(new MailHappeningConfirmed($event->happening));
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
            HappeningConfirmed::class => 'handleHappeningConfirmed',
            HappeningCreated::class => 'handleHappeningCreated',
            HappeningDeleted::class => 'handleHappeningDeleted',
            HappeningUpdated::class => 'handleHappeningUpdated',
        ];
    }
}
