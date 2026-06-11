<?php

declare(strict_types=1);

use App\Mail\MailEnvelopeData;
use App\Services\Notifications\MailEnvelopeFactory;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

covers(MailEnvelopeFactory::class);

test('make returns Envelope with given subject', function (): void {
    $data = new MailEnvelopeData('noreply@example.com');
    $factory = new MailEnvelopeFactory;

    $envelope = $factory->make($data, 'Test Subject');

    expect($envelope)->toBeInstanceOf(Envelope::class)
        ->and($envelope->subject)->toBe('Test Subject');
});

test('make sets from address from envelope data', function (): void {
    $data = new MailEnvelopeData('sender@example.com');
    $factory = new MailEnvelopeFactory;

    $envelope = $factory->make($data, 'Hi');

    expect($envelope->from)->toBeInstanceOf(Address::class);
    if ($envelope->from instanceof Address) {
        expect($envelope->from->address)->toBe('sender@example.com');
    }
});
