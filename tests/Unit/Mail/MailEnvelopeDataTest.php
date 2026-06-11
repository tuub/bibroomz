<?php

declare(strict_types=1);

use App\Mail\MailEnvelopeData;

covers(MailEnvelopeData::class);

test('stores fromAddress as public property', function (): void {
    $data = new MailEnvelopeData('admin@example.com');

    expect($data->fromAddress)->toBe('admin@example.com');
});

test('accepts any valid email string', function (): void {
    $data = new MailEnvelopeData('no-reply@tu-berlin.de');

    expect($data->fromAddress)->toBe('no-reply@tu-berlin.de');
});
