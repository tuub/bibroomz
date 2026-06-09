<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\MailContent;
use Illuminate\Support\Collection;

class ClosingMailData
{
    /**
     * @param  Collection<int, Happening>  $happenings
     */
    public function __construct(
        public Closing $closing,
        public Collection $happenings,
        public MailContent $content,
        public MailEnvelopeData $envelope,
    ) {}
}
