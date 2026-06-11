<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(MailContent::class);

uses(RefreshDatabase::class);

test('mail content creates with required fields', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'booking_confirmed', 'description' => 'Booking confirmed']);

    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Booking Confirmed', 'de' => 'Buchung bestätigt'],
        'is_active' => true,
    ]);

    expect($mailContent->id)->not->toBeNull()
        ->and($mailContent->institution_id)->toBe($institution->id)
        ->and($mailContent->mail_type_id)->toBe($mailType->id)
        ->and($mailContent->is_active)->toBeTrue();
});

test('mail content translatable fields store and retrieve translations', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'test_type', 'description' => 'Test']);

    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Subject EN', 'de' => 'Betreff DE'],
        'title' => ['en' => 'Title EN', 'de' => 'Titel DE'],
        'salutation' => ['en' => 'Dear user,', 'de' => 'Lieber Nutzer,'],
        'intro' => ['en' => 'Intro EN', 'de' => 'Intro DE'],
        'outro' => ['en' => 'Outro EN', 'de' => 'Outro DE'],
        'farewell' => ['en' => 'Best regards', 'de' => 'Mit freundlichen Grüßen'],
    ]);

    expect($mailContent->getTranslation('subject', 'en'))->toBe('Subject EN')
        ->and($mailContent->getTranslation('subject', 'de'))->toBe('Betreff DE')
        ->and($mailContent->getTranslation('title', 'en'))->toBe('Title EN')
        ->and($mailContent->getTranslation('salutation', 'en'))->toBe('Dear user,')
        ->and($mailContent->getTranslation('farewell', 'de'))->toBe('Mit freundlichen Grüßen');
});

test('mail content is_active is cast to boolean', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'cast_test', 'description' => 'Cast test']);

    $active = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Active'],
        'is_active' => true,
    ]);

    $inactive = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Inactive'],
        'is_active' => false,
    ]);

    expect($active->is_active)->toBeTrue()
        ->and($inactive->is_active)->toBeFalse();
});

test('mail content institution relationship returns BelongsTo', function (): void {
    $mailContent = new MailContent;

    expect($mailContent->institution())->toBeInstanceOf(BelongsTo::class);
});

test('mail content institution relationship resolves to correct institution', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'rel_test', 'description' => 'Rel test']);

    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Test'],
    ]);

    expect($mailContent->institution()->firstOrFail()->id)->toBe($institution->id);
});

test('mail content mail_type relationship returns BelongsTo', function (): void {
    $mailContent = new MailContent;

    expect($mailContent->mail_type())->toBeInstanceOf(BelongsTo::class);
});

test('mail content mail_type relationship resolves to correct mail type', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'type_rel_test', 'description' => 'Type relation test']);

    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Test'],
    ]);

    expect($mailContent->mail_type()->firstOrFail()->id)->toBe($mailType->id);
});

test('mail content isViewableByUser returns true for admin', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'view_test', 'description' => 'View test']);
    $admin = User::factory()->create(['is_admin' => true]);

    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Test'],
    ]);

    expect($mailContent->isViewableByUser($admin))->toBeTrue();
});

test('mail content action_uri and action_uri_label can be stored', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'uri_test', 'description' => 'URI test']);

    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Test'],
        'action_uri' => 'https://example.com/action',
        'action_uri_label' => 'Go to Action',
    ]);

    expect($mailContent->action_uri)->toBe('https://example.com/action')
        ->and($mailContent->action_uri_label)->toBe('Go to Action');
});
