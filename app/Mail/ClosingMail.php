<?php

namespace App\Mail;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\MailContent;
use App\Services\Notifications\MailEnvelopeFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClosingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Closing $closing;

    /**
     * @var Collection<int, Happening>
     */
    public Collection $happenings;

    public MailContent $content;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public ClosingMailData $data)
    {
        $this->closing = $data->closing;
        $this->happenings = $data->happenings;
        $this->content = $data->content;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        return app(MailEnvelopeFactory::class)->make($this->data->envelope, $this->content->subject);
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
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
