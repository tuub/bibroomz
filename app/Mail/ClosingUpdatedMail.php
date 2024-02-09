<?php

namespace App\Mail;

use App\Models\Closing;
use App\Models\Institution;
use App\Models\MailContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClosingUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        public Closing $closing,
        public Collection $happenings,
        public string $class,
        public MailContent $content,
        //public Collection $previously,
    ) {
        //
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $closable = $this->closing->closable;
        if ($closable instanceof Institution) {
            $from_email = $closable->email;
        } else {
            $from_email = $closable->resource_group->institution->email;
        }

        return new Envelope(
            from: new Address(
                $from_email,
                $from_email
            ),
            replyTo: new Address(
                $from_email,
                $from_email
            ),
            subject: $this->content->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            text: 'emails.text.mail',
            markdown: 'emails.markdown.mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
