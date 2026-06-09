<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Happening;
use App\Models\MailContent;

class HappeningMailData
{
    public function __construct(
        public Happening $happening,
        public MailContent $content,
        public MailEnvelopeData $envelope,
    ) {}
}
