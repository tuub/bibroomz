<?php

namespace App\Services\Notifications;

use App\Models\MailContent;

class MailContentLookup
{
    public function find(string $institutionId, string $mailTypeKey): ?MailContent
    {
        return MailContent::where('institution_id', $institutionId)
            ->whereHas('mail_type', function ($query) use ($mailTypeKey): void {
                $query->where('key', $mailTypeKey);
            })
            ->first();
    }
}
