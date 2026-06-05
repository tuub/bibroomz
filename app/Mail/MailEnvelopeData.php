<?php

namespace App\Mail;

class MailEnvelopeData
{
    public function __construct(public string $fromAddress)
    {
    }
}
