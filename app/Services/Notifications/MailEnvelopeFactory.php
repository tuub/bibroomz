<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Mail\MailEnvelopeData;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

class MailEnvelopeFactory
{
    public function make(MailEnvelopeData $data, string $subject): Envelope
    {
        return new Envelope(
            from: new Address($data->fromAddress, $data->fromAddress),
            replyTo: [
                new Address($data->fromAddress, $data->fromAddress),
            ],
            subject: $subject,
        );
    }
}
