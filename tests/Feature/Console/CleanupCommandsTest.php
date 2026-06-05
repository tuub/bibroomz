<?php

covers(
    App\Console\Commands\RemoveUnverifiedHappeningsCommand::class,
    App\Console\Commands\AnonymizeHappeningUsersCommand::class,
    App\Console\Commands\RemoveUsersCommand::class,
    App\Services\Console\RemoveUnverifiedHappeningsAction::class,
    App\Services\Console\RemoveUnverifiedHappeningsQueryBuilder::class,
    App\Services\Console\AnonymizeHappeningUsersAction::class,
    App\Services\Console\CleanupIntervalResolver::class,
    App\Services\Console\RemoveUsersAction::class,
    App\Services\Console\RemoveUsersQueryBuilder::class
);

use App\Events\HappeningsChangedEvent;
use App\Events\UnverifiedHappeningRemovedBySchedulerEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Command\Command;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-04 12:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-04 12:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function buildCleanupFixture(): array
{
    $institution = Institution::factory()->create(['title' => 'Library']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
        'is_active' => true,
    ]);
    $suffix = uniqid('.', true);
    $owner = User::factory()->create(['name' => 'owner' . $suffix]);
    $verifier = User::factory()->create(['name' => 'verifier' . $suffix]);

    return compact('institution', 'resourceGroup', 'resource', 'owner', 'verifier');
}

test('remove unverified happenings command deletes matching records and broadcasts the scheduler event', function () {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildCleanupFixture();

    $old = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'resource_id' => $fixture['resource']->id,
        'is_verified' => false,
        'verifier' => $fixture['verifier']->name,
        'start' => now()->subDays(2),
        'end' => now()->subDays(2)->addHour(),
        'reserved_at' => now()->subDays(2),
        'verified_at' => null,
        'label' => ['en' => 'Old'],
    ]);
    Happening::query()->whereKey($old->id)->update([
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($old->id)?->trashed())->toBeTrue();
    Event::assertDispatched(UnverifiedHappeningRemovedBySchedulerEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('remove unverified happenings command supports institution slug filtering and cleanup settings', function () {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $target = buildCleanupFixture();
    $other = buildCleanupFixture();

    $target['institution']->settings()->firstWhere('key', 'cleanup_interval')->update(['value' => '0:1:0']);
    $other['institution']->settings()->firstWhere('key', 'cleanup_interval')->update(['value' => '10:0:0']);

    $targetHappening = Happening::create([
        'user_id_01' => $target['owner']->id,
        'resource_id' => $target['resource']->id,
        'is_verified' => false,
        'verifier' => $target['verifier']->name,
        'start' => now()->subHours(2),
        'end' => now()->subHours(2)->addHour(),
        'reserved_at' => now()->subHours(2),
        'verified_at' => null,
        'label' => ['en' => 'Target'],
    ]);
    Happening::query()->whereKey($targetHappening->id)->update([
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);
    $otherHappening = Happening::create([
        'user_id_01' => $other['owner']->id,
        'resource_id' => $other['resource']->id,
        'is_verified' => false,
        'verifier' => $other['verifier']->name,
        'start' => now()->subHours(2),
        'end' => now()->subHours(2)->addHour(),
        'reserved_at' => now()->subHours(2),
        'verified_at' => null,
        'label' => ['en' => 'Other'],
    ]);
    Happening::query()->whereKey($otherHappening->id)->update([
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $target['institution']->slug,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($targetHappening->id)?->trashed())->toBeTrue()
        ->and(Happening::find($otherHappening->id))->not->toBeNull();
});

test('anonymize happening users command respects dry runs and anonymizes past bookings', function () {
    $fixture = buildCleanupFixture();
    $happening = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'user_id_02' => $fixture['verifier']->id,
        'resource_id' => $fixture['resource']->id,
        'is_verified' => true,
        'verifier' => $fixture['verifier']->name,
        'start' => now()->subDays(10),
        'end' => now()->subDays(10)->addHour(),
        'reserved_at' => now()->subDays(10),
        'verified_at' => now()->subDays(10),
        'label' => ['en' => 'Past'],
    ]);

    $this->artisan('roomz:anonymize-happening-users', [
        '--days' => 1,
        '--dry-run' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect($happening->fresh()->user_id_01)->toBe($fixture['owner']->id);

    $this->artisan('roomz:anonymize-happening-users', [
        '--days' => 1,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect($happening->fresh()->user_id_01)->toBeNull()
        ->and($happening->fresh()->user_id_02)->toBeNull()
        ->and($happening->fresh()->verifier)->toBeNull();
});

test('remove users command deletes only inactive unprivileged users', function () {
    $candidate = User::factory()->create(['is_admin' => false, 'is_logged_in' => false]);
    $admin = User::factory()->create(['is_admin' => true]);
    $withRole = User::factory()->create(['is_admin' => false]);
    $withGroup = User::factory()->create(['is_admin' => false]);
    $withRecentHappening = User::factory()->create(['is_admin' => false]);
    $loggedIn = User::factory()->create(['is_admin' => false, 'is_logged_in' => true]);

    $institution = Institution::factory()->create();
    $role = Role::create(['name' => ['en' => 'Editor']]);
    $withRole->roles()->attach($role->id, ['institution_id' => $institution->id]);

    $userGroup = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Members']]);
    $userGroup->users()->attach($withGroup, ['valid_until' => null]);

    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::create([
        'user_id_01' => $withRecentHappening->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => now()->subDay(),
        'end' => now()->subDay()->addHour(),
        'reserved_at' => now()->subDay(),
        'verified_at' => now()->subDay(),
        'label' => ['en' => 'Recent'],
    ]);

    Cache::put('user_activity_' . $loggedIn->id, true, 60);

    $this->artisan('roomz:remove-users', [
        '--days' => 30,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(User::find($candidate->id))->toBeNull()
        ->and(User::find($admin->id))->not->toBeNull()
        ->and(User::find($withRole->id))->not->toBeNull()
        ->and(User::find($withGroup->id))->not->toBeNull()
        ->and(User::find($withRecentHappening->id))->not->toBeNull()
        ->and(User::find($loggedIn->id))->not->toBeNull();
});

// Regression: resolveDays() returned 0 when config('roomz.happenings.anonymize_days') was null
// or non-numeric (e.g. missing env var). subDays(0) = now() → matched ALL ended happenings.
test('anonymize command does not anonymize recent happenings when config days is missing', function () {
    $fixture = buildCleanupFixture();

    // A happening that ended 5 minutes ago — should NOT be anonymized when days=0 fallback.
    $recent = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'resource_id' => $fixture['resource']->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => now()->subMinutes(35),
        'end' => now()->subMinutes(5),
        'reserved_at' => now()->subHour(),
        'verified_at' => now()->subMinutes(35),
        'label' => ['en' => 'Recent'],
    ]);

    // Wipe the config so resolveDays() cannot resolve a valid integer.
    config()->set('roomz.happenings.anonymize_days', null);

    // Without --days and with a null config, the command should refuse to run (or default safely)
    // rather than anonymize happenings from 0 days ago (= all past happenings).
    $this->artisan('roomz:anonymize-happening-users', [
        '--force' => true,
    ])->assertExitCode(\Illuminate\Console\Command::SUCCESS);

    // The recent happening must NOT have been anonymized.
    expect($recent->fresh()->user_id_01)->toBe($fixture['owner']->id);
});
