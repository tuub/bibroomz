<?php

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Library\Utility;
use App\Listeners\ClosingEventSubscriber;
use App\Mail\ClosingMail;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Closings\ClosingInstitutionResolver;
use App\Services\Closings\ClosingNotificationService;
use App\Services\Closings\ClosingNotificationTypeResolver;
use App\Services\Notifications\MailEnvelopeFactory;
use App\Services\Notifications\NotificationDispatchService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

covers(
    ClosingNotificationService::class,
    ClosingInstitutionResolver::class,
    ClosingNotificationTypeResolver::class,
    ClosingEventSubscriber::class,
    ClosingCreatedEvent::class,
    ClosingUpdatedEvent::class,
    ClosingDeletedEvent::class,
    MailEnvelopeFactory::class,
    NotificationDispatchService::class,
    ClosingMail::class
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MailTypeSeeder::class);
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00'));
    config()->set('broadcasting.default', 'log');
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, happening: Happening, admin: User}
 */
function buildClosingNotificationFixture(): array
{
    $institution = Institution::factory()->create([
        'is_active' => true,
        'email' => 'library@example.test',
    ]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => false,
    ]);

    $owner = User::factory()->create(['name' => 'closing.notify.owner']);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => '2026-06-10 08:05:00',
        'label' => Utility::getTranslatable('Study'),
    ]);

    $mailTypeCreated = MailType::query()->firstWhere('key', 'closing_created');
    $mailTypeUpdated = MailType::query()->firstWhere('key', 'closing_updated');
    $mailTypeDeleted = MailType::query()->firstWhere('key', 'closing_deleted');

    foreach ([$mailTypeCreated, $mailTypeUpdated, $mailTypeDeleted] as $mailType) {
        MailContent::create([
            'institution_id' => $institution->id,
            'mail_type_id' => $mailType?->id,
            'subject' => 'Closing Notice',
            'title' => 'Library Closing',
            'salutation' => 'Dear User',
            'intro' => 'We regret to inform you...',
            'outro' => 'Best regards',
            'is_active' => true,
        ]);
    }

    $admin = User::factory()->create(['is_system_user' => true, 'is_admin' => false]);
    foreach (['view_closings', 'create_closings', 'edit_closings', 'delete_closings'] as $perm) {
        grantAdminPermission($admin, $institution, $perm);
    }
    test()->actingAs($admin);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'happening' => $happening, 'admin' => $admin];
}

test('creating an institution closing with an affected happening dispatches a closing mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('System maintenance'),
    ])->assertRedirect();

    Mail::assertQueued(ClosingMail::class, fn (ClosingMail $mail): bool => $mail->hasTo($fixture['owner']->email ?? ''));
});

test('updating a closing with an affected happening dispatches an update mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Initial'),
    ])->assertRedirect();

    Mail::fake();

    $closing = $fixture['institution']->closings()->firstOrFail();

    $this->post(route('admin.closing.update'), [
        'id' => $closing->id,
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '11:00',
        'description' => Utility::getTranslatable('Extended'),
    ])->assertRedirect();

    Mail::assertQueued(ClosingMail::class);
});

test('deleting a closing with a previously affected happening dispatches a deletion mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Temporary'),
    ])->assertRedirect();

    Mail::fake();

    $closing = $fixture['institution']->closings()->firstOrFail();

    $this->post(route('admin.closing.delete'), ['id' => $closing->id])
        ->assertRedirect();

    Mail::assertQueued(ClosingMail::class);
});
