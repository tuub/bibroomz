<?php

namespace App\Mail;

use App\Models\Happening;
use App\Models\MailContent;
use App\Services\Notifications\MailEnvelopeFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HappeningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Happening $happening;

    public MailContent $content;

    /**
     * Create a new message instance.
     */
    public function __construct(public HappeningMailData $data)
    {
        $this->happening = $data->happening;
        $this->content = $data->content;
        $this->locale(app()->getLocale());
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return app(MailEnvelopeFactory::class)->make($this->data->envelope, $this->content->subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'emails.text.mail',
            markdown: 'emails.markdown.mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
