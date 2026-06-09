<?php

declare(strict_types=1);

namespace App\Mail;

class MailEnvelopeData
{
    public function __construct(public string $fromAddress) {}
}
