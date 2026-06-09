<?php

use App\Library\Utility;
use App\Mail\ClosingMail;
use App\Mail\ClosingMailData;
use App\Mail\HappeningMail;
use App\Mail\HappeningMailData;
use App\Mail\MailEnvelopeData;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\MailContent;
use App\Services\Notifications\MailContentLookup;
use App\Services\Notifications\MailEnvelopeFactory;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Mail\Mailables\Address;

covers(
    ClosingMail::class,
    ClosingMailData::class,
    HappeningMail::class,
    HappeningMailData::class,
    MailEnvelopeData::class,
    MailEnvelopeFactory::class,
    NotificationDispatchService::class,
    MailContentLookup::class
);

test('happening mail exposes envelope content and attachments from the shared data', function (): void {
    $happening = new Happening;
    $contentModel = new MailContent([
        'subject' => Utility::getTranslatable('Reservation created'),
    ]);

    $mail = new HappeningMail(new HappeningMailData(
        happening: $happening,
        content: $contentModel,
        envelope: new MailEnvelopeData('notify@example.test'),
    ));

    $envelope = $mail->envelope();
    $content = $mail->content();

    $fromAddress = $envelope->from instanceof Address ? $envelope->from->address : null;
    $replyToAddress = isset($envelope->replyTo[0]) && $envelope->replyTo[0] instanceof Address ? $envelope->replyTo[0]->address : null;
    expect($mail->happening)->toBe($happening)
        ->and($mail->content)->toBe($contentModel)
        ->and($mail->data->envelope->fromAddress)->toBe('notify@example.test')
        ->and($fromAddress)->toBe('notify@example.test')
        ->and($replyToAddress)->toBe('notify@example.test')
        ->and($content->text)->toBe('emails.text.mail')
        ->and($content->markdown)->toBe('emails.markdown.mail')
        ->and($mail->attachments())->toBe([]);
});

test('closing mail exposes envelope content and affected happenings from the shared data', function (): void {
    $closing = new Closing;
    $happenings = collect([new Happening]);
    $contentModel = new MailContent([
        'subject' => Utility::getTranslatable('Closing created'),
    ]);

    $mail = new ClosingMail(new ClosingMailData(
        closing: $closing,
        happenings: $happenings,
        content: $contentModel,
        envelope: new MailEnvelopeData('closing@example.test'),
    ));

    $envelope = $mail->envelope();
    $content = $mail->content();

    $closingFromAddress = $envelope->from instanceof Address ? $envelope->from->address : null;
    $closingReplyToAddress = isset($envelope->replyTo[0]) && $envelope->replyTo[0] instanceof Address ? $envelope->replyTo[0]->address : null;
    expect($mail->closing)->toBe($closing)
        ->and($mail->happenings)->toBe($happenings)
        ->and($mail->content)->toBe($contentModel)
        ->and($mail->data->envelope->fromAddress)->toBe('closing@example.test')
        ->and($closingFromAddress)->toBe('closing@example.test')
        ->and($closingReplyToAddress)->toBe('closing@example.test')
        ->and($content->text)->toBe('emails.text.mail')
        ->and($content->markdown)->toBe('emails.markdown.mail')
        ->and($mail->attachments())->toBe([]);
});
