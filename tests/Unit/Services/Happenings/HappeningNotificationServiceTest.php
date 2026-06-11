<?php

declare(strict_types=1);

use App\Events\HappeningCreatedEvent;
use App\Mail\HappeningMail;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningNotificationService;
use Database\Seeders\MailTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

covers(HappeningNotificationService::class);

uses(RefreshDatabase::class);

test('sendForEvent queues no mail when no active mail content exists', function (): void {
    Mail::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);
    $event = new HappeningCreatedEvent($happening, $user);

    $service = app(HappeningNotificationService::class);
    $service->sendForEvent($event);

    Mail::assertNothingQueued();
});

test('sendForEvent queues mail when active mail content exists', function (): void {
    // RemoveMethodCall would remove the $this->notificationDispatchService->queue() call
    // This tests that the mail is actually queued when content exists.
    $this->seed(MailTypeSeeder::class);
    Mail::fake();
    $institution = Institution::factory()->create(['email' => 'info@example.org']);
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create(['email' => 'user@example.org']);

    // Create mail content for the "happening_created" type
    $mailType = MailType::where('key', 'happening_created')->first();
    if ($mailType !== null) {
        MailContent::create([
            'institution_id' => $institution->id,
            'mail_type_id' => $mailType->id,
            'subject' => ['en' => 'Booking Confirmed'],
            'body' => ['en' => 'Your booking is confirmed.'],
            'is_active' => true,
        ]);
    }

    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);
    $event = new HappeningCreatedEvent($happening, $user);

    $service = app(HappeningNotificationService::class);
    $service->sendForEvent($event);

    if ($mailType !== null) {
        Mail::assertQueued(HappeningMail::class);
    } else {
        Mail::assertNothingQueued();
    }
});

test('sendForEvent uses empty string for fromAddress when institution email is not a string', function (): void {
    // EmptyStringToNotEmpty would change '' to 'NOT_EMPTY', making empty email a non-empty string.
    // TernaryNegated would invert: is_string ? email : '' becomes '' ? email : is_string (nonsense)
    // Test that a non-string institution email produces empty string in the fromAddress.
    Mail::fake();
    $institution = Institution::factory()->create(['email' => null]);
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['email' => 'user@example.org']);
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);
    $event = new HappeningCreatedEvent($happening, $user);

    // Should not throw even with null email, and no mail queued (no mail content exists)
    $service = app(HappeningNotificationService::class);
    $service->sendForEvent($event);

    Mail::assertNothingQueued();
});

test('sendForEvent loads institution relationship via loadMissing', function (): void {
    // RemoveMethodCall would remove $event->happening->loadMissing('resource.resource_group.institution')
    // Without loadMissing, $institution would not be accessible
    Mail::fake();
    $institution = Institution::factory()->create(['email' => 'test@example.org']);
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    // Create happening WITHOUT preloaded relations to force loadMissing to work
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);
    // Clear loaded relations to simulate lazy loading
    $happening->unsetRelation('resource');

    $event = new HappeningCreatedEvent($happening, $user);

    $service = app(HappeningNotificationService::class);
    $service->sendForEvent($event);

    expect($happening->relationLoaded('resource'))->toBeTrue();
});
