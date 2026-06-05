<?php

covers(
    App\Mail\HappeningMail::class,
    App\Mail\ClosingMail::class,
    App\Listeners\HappeningEventSubscriber::class
);

use App\Library\Utility;
use App\Mail\ClosingMail;
use App\Mail\ClosingMailData;
use App\Mail\HappeningMail;
use App\Mail\HappeningMailData;
use App\Mail\MailEnvelopeData;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MailTypeSeeder::class);
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00'));
    config()->set('broadcasting.default', 'log');
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('happening mail renders content and envelope correctly', function () {
    $institution = Institution::factory()->create(['email' => 'test@example.test']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_active' => true]);
    $owner = User::factory()->create(['name' => 'mail.render.owner']);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => '2026-06-10 08:05:00',
        'label' => Utility::getTranslatable('Test Booking'),
    ]);

    $mailType = MailType::query()->firstWhere('key', 'happening_created');
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => 'Your booking',
        'title' => 'Booking Confirmed',
        'salutation' => 'Dear User',
        'intro' => 'Your booking is confirmed.',
        'outro' => 'Best regards',
        'is_active' => true,
    ]);

    $mail = new HappeningMail(new HappeningMailData(
        happening: $happening,
        content: $mailContent,
        envelope: new MailEnvelopeData('library@example.test'),
    ));

    expect($mail->envelope())->toBeInstanceOf(\Illuminate\Mail\Mailables\Envelope::class)
        ->and($mail->content())->toBeInstanceOf(\Illuminate\Mail\Mailables\Content::class)
        ->and($mail->attachments())->toBeArray();
});

test('closing mail renders content and attachments correctly', function () {
    $institution = Institution::factory()->create(['email' => 'library@example.test']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_active' => true]);
    $owner = User::factory()->create(['name' => 'closing.mail.owner']);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => '2026-06-10 08:05:00',
        'label' => Utility::getTranslatable('Closing Test'),
    ]);

    $closing = $institution->closings()->create([
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
        'description' => Utility::getTranslatable('Maintenance'),
    ]);

    $mailType = MailType::query()->firstWhere('key', 'closing_created');
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => 'Library Closure',
        'title' => 'Upcoming Closure',
        'salutation' => 'Dear User',
        'intro' => 'Library will be closed.',
        'outro' => 'Best regards',
        'is_active' => true,
    ]);

    $mail = new ClosingMail(new ClosingMailData(
        closing: $closing,
        happenings: collect([$happening]),
        content: $mailContent,
        envelope: new MailEnvelopeData('library@example.test'),
    ));

    expect($mail->content())->toBeInstanceOf(\Illuminate\Mail\Mailables\Content::class)
        ->and($mail->attachments())->toBeArray();
});

test('happening verification dispatches the verified event and triggers the subscriber', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => true,
    ]);
    $owner = User::factory()->create(['name' => 'verifsubscriber.owner']);
    $verifier = User::factory()->create(['name' => 'verifsubscriber.verifier']);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Needs Verification'),
    ]);

    Mail::fake();
    Sanctum::actingAs($verifier);

    $this->postJson(route('happening.verify', ['id' => $happening->id]), [
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
    ])->assertNoContent();

    expect($happening->fresh()->is_verified)->toBeTrue();
});
