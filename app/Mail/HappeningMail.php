<?php

namespace App\Mail;

use App\Models\Happening;
use App\Models\MailContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HappeningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        public Happening $happening,
        public MailContent $content,
    ) {
        $this->locale(app()->getLocale());
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: new Address(
                $this->happening->resource->resource_group->institution->email,
                $this->happening->resource->resource_group->institution->email
            ),
            replyTo: new Address(
                $this->happening->resource->resource_group->institution->email,
                $this->happening->resource->resource_group->institution->email
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
