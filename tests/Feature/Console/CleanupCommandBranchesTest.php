<?php

covers(
    App\Console\Commands\RemoveUnverifiedHappeningsCommand::class,
    App\Console\Commands\RemoveUsersCommand::class,
    App\Services\Console\RemoveUnverifiedHappeningsQueryBuilder::class,
    App\Services\Console\CleanupIntervalResolver::class
);

use App\Events\HappeningsChangedEvent;
use App\Events\UnverifiedHappeningRemovedBySchedulerEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

function buildBranchCleanupFixture(): array
{
    $institution = Institution::factory()->create(['title' => 'Branch Library']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
        'is_active' => true,
    ]);
    $owner = User::factory()->create(['name' => 'branch.owner.' . uniqid()]);
    $verifier = User::factory()->create(['name' => 'branch.verifier.' . uniqid()]);

    return compact('institution', 'resourceGroup', 'resource', 'owner', 'verifier');
}

function createOldUnverifiedHappening(array $fixture): Happening
{
    $happening = Happening::create([
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
    Happening::query()->whereKey($happening->id)->update([
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    return $happening;
}

test('remove unverified happenings uses per-institution settings when no interval is given', function () {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture1 = buildBranchCleanupFixture();
    $fixture2 = buildBranchCleanupFixture();

    // institution 1: cleanup after 1 day (should match)
    $fixture1['institution']->settings()->firstWhere('key', 'cleanup_interval')->update(['value' => '0:1:0']);
    // institution 2: cleanup after 10 hours (should match since happening is 2 days old)
    $fixture2['institution']->settings()->firstWhere('key', 'cleanup_interval')->update(['value' => '0:0:10']);

    $h1 = createOldUnverifiedHappening($fixture1);
    $h2 = createOldUnverifiedHappening($fixture2);

    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($h1->id)?->trashed())->toBeTrue()
        ->and(Happening::withTrashed()->find($h2->id)?->trashed())->toBeTrue();
});

test('remove unverified happenings returns success immediately when there is nothing to remove', function () {
    buildBranchCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS)
        ->expectsOutputToContain('Nothing to do');
});

test('remove unverified happenings aborts when user declines confirmation', function () {
    $fixture = buildBranchCleanupFixture();
    createOldUnverifiedHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', ['--days' => 1])
        ->expectsConfirmation('Do you want to proceed?', 'no')
        ->assertExitCode(Command::INVALID);
});

test('remove unverified happenings verbose mode outputs sql and happenings list', function () {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildBranchCleanupFixture();
    createOldUnverifiedHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', ['--days' => 1, '--force' => true, '--verbose' => true])
        ->assertExitCode(Command::SUCCESS);
});

test('remove users command skips force confirmation when no candidates exist', function () {
    Institution::factory()->create();

    $this->artisan('roomz:remove-users', [
        '--days' => 30,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS)
        ->expectsOutputToContain('Nothing to do');
});

test('remove users command supports dry-run mode without deleting users', function () {
    $candidate = User::factory()->create(['is_admin' => false, 'is_logged_in' => false]);

    $this->artisan('roomz:remove-users', [
        '--days' => 0,
        '--dry-run' => true,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(User::find($candidate->id))->not->toBeNull();
});

test('remove users command aborts when user declines confirmation', function () {
    User::factory()->create(['is_admin' => false, 'is_logged_in' => false]);

    $this->artisan('roomz:remove-users', ['--days' => 0])
        ->expectsConfirmation('Do you want to proceed?', 'no')
        ->assertExitCode(Command::INVALID);
});

test('remove unverified happenings runs cleanly when no institutions exist', function () {
    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->assertExitCode(Command::SUCCESS);
});

test('--days flag controls deletion independently of institution cleanup_interval setting', function () {
    // This test distinguishes the `--days` branch from the per-institution settings branch.
    // If BooleanOrToBooleanAnd mutates the conditional so that --days is not checked, the
    // command falls back to institution settings (10 days) and would NOT delete the happening.
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);

    $fixture = buildBranchCleanupFixture();
    // Configure institution to only clean up after 10 days — with --days=1, it must still delete
    $fixture['institution']->settings()->firstWhere('key', 'cleanup_interval')->update(['value' => '10:0:0']);

    $happening = createOldUnverifiedHappening($fixture); // 2 days old

    $this->artisan('roomz:remove-unverified-happenings', ['--days' => 1, '--force' => true])
        ->assertExitCode(Command::SUCCESS);

    // --days=1 means 2-day-old happenings must be deleted regardless of the 10-day institution setting
    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();
});

test('--hours flag routes into the values branch, not the institution-settings branch', function () {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);

    $fixture = buildBranchCleanupFixture();
    $fixture['institution']->settings()->firstWhere('key', 'cleanup_interval')->update(['value' => '10:0:0']);

    $happening = createOldUnverifiedHappening($fixture); // 2 days old

    $this->artisan('roomz:remove-unverified-happenings', ['--hours' => 1, '--force' => true])
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();
});

test('--minutes flag routes into the values branch', function () {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);

    $fixture = buildBranchCleanupFixture();
    $fixture['institution']->settings()->firstWhere('key', 'cleanup_interval')->update(['value' => '10:0:0']);

    $happening = createOldUnverifiedHappening($fixture); // 2 days old = 2880+ minutes

    $this->artisan('roomz:remove-unverified-happenings', ['--minutes' => 60, '--force' => true])
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();
});

test('--settings=false disables institution-setting lookup and uses zero interval', function () {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);

    $fixture = buildBranchCleanupFixture();

    $happening = createOldUnverifiedHappening($fixture); // 2 days old

    // --settings=false with no interval options triggers the "fromValues(null, null, null)" path
    // which subtracts nothing (time = now) so ALL unverified happenings are matched
    $this->artisan('roomz:remove-unverified-happenings', ['--settings' => 'false', '--force' => true])
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();
});
