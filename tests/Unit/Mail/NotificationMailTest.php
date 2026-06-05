<?php

covers(
    App\Mail\ClosingMail::class,
    App\Mail\ClosingMailData::class,
    App\Mail\HappeningMail::class,
    App\Mail\HappeningMailData::class,
    App\Mail\MailEnvelopeData::class,
    App\Services\Notifications\MailEnvelopeFactory::class,
    App\Services\Notifications\NotificationDispatchService::class,
    App\Services\Notifications\MailContentLookup::class
);

use App\Library\Utility;
use App\Mail\ClosingMail;
use App\Mail\ClosingMailData;
use App\Mail\HappeningMail;
use App\Mail\HappeningMailData;
use App\Mail\MailEnvelopeData;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\MailContent;

test('happening mail exposes envelope content and attachments from the shared data', function () {
    $happening = new Happening();
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

    expect($mail->happening)->toBe($happening)
        ->and($mail->content)->toBe($contentModel)
        ->and($mail->data->envelope->fromAddress)->toBe('notify@example.test')
        ->and($envelope->from->address)->toBe('notify@example.test')
        ->and($envelope->replyTo[0]->address)->toBe('notify@example.test')
        ->and($content->text)->toBe('emails.text.mail')
        ->and($content->markdown)->toBe('emails.markdown.mail')
        ->and($mail->attachments())->toBe([]);
});

test('closing mail exposes envelope content and affected happenings from the shared data', function () {
    $closing = new Closing();
    $happenings = collect([new Happening()]);
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

    expect($mail->closing)->toBe($closing)
        ->and($mail->happenings)->toBe($happenings)
        ->and($mail->content)->toBe($contentModel)
        ->and($mail->data->envelope->fromAddress)->toBe('closing@example.test')
        ->and($envelope->from->address)->toBe('closing@example.test')
        ->and($envelope->replyTo[0]->address)->toBe('closing@example.test')
        ->and($content->text)->toBe('emails.text.mail')
        ->and($content->markdown)->toBe('emails.markdown.mail')
        ->and($mail->attachments())->toBe([]);
});
