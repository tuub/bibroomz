<?php

namespace App\Services\Notifications;

use App\Models\MailContent;
use App\Models\User;
use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class NotificationDispatchService
{
    public function __construct(private readonly MailContentLookup $mailContentLookup) {}

    /**
     * @param  Closure(MailContent): Mailable  $mailBuilder
     */
    public function queue(
        User $recipient,
        string $institutionId,
        string $mailTypeKey,
        Closure $mailBuilder,
    ): ?MailContent {
        $mailContent = $this->mailContentLookup->find($institutionId, $mailTypeKey);

        if (! $mailContent?->is_active) {
            return null;
        }

        Mail::to($recipient)->queue($mailBuilder($mailContent));

        return $mailContent;
    }
}
