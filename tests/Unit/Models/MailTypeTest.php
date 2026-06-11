<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(MailType::class);

uses(RefreshDatabase::class);

test('mail type creates with key and description', function (): void {
    $mailType = MailType::create([
        'key' => 'booking_created',
        'description' => 'Sent when a booking is created',
    ]);

    expect($mailType->id)->not->toBeNull()
        ->and($mailType->key)->toBe('booking_created')
        ->and($mailType->description)->toBe('Sent when a booking is created');
});

test('mail type has no timestamps', function (): void {
    $mailType = MailType::create([
        'key' => 'no_timestamps',
        'description' => 'No timestamps test',
    ]);

    expect($mailType->timestamps)->toBeFalse();
});

test('mail type mail_contents relationship returns HasMany', function (): void {
    $mailType = new MailType;

    expect($mailType->mail_contents())->toBeInstanceOf(HasMany::class);
});

test('mail type mail_contents loads related mail contents', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'relation_test', 'description' => 'Relation test']);

    MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Test subject'],
    ]);

    MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Another subject'],
    ]);

    expect($mailType->mail_contents()->count())->toBe(2);
});

test('mail type can be retrieved by key', function (): void {
    MailType::create(['key' => 'unique_key_find', 'description' => 'Findable type']);

    $found = MailType::firstWhere('key', 'unique_key_find');

    expect($found)->not->toBeNull()
        ->and($found?->key)->toBe('unique_key_find');
});

test('mail type key is unique per row', function (): void {
    MailType::create(['key' => 'distinct_key', 'description' => 'First']);

    $count = MailType::where('key', 'distinct_key')->count();

    expect($count)->toBe(1);
});
