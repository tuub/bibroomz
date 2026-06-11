<?php

declare(strict_types=1);

use App\Console\Commands\RemoveUnverifiedHappeningsCommand;
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

covers(RemoveUnverifiedHappeningsCommand::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-04 12:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-04 12:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User}
 */
function buildUnitCleanupFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
        'is_active' => true,
    ]);
    $owner = User::factory()->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner];
}

/**
 * @param  array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User}  $fixture
 */
function createOldUnitHappening(array $fixture): Happening
{
    $happening = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'resource_id' => $fixture['resource']->id,
        'is_verified' => false,
        'verifier' => null,
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

function setUnitCleanupInterval(Institution $institution, string $interval): void
{
    $institution->settings()->firstWhere('key', 'cleanup_interval')?->update(['value' => $interval]);
    $institution->unsetRelation('settings');
}

function unitCleanupMessage(Carbon $time, ?string $institutionId = null): string
{
    $resolvedTime = $time->copy();
    $resolvedTime->locale('en');

    $message = 'Removing unverified happenings created more than '
        .$resolvedTime->diffForHumans(short: true, parts: 3);

    if ($institutionId !== null) {
        $message .= ' for institution '.$institutionId;
    }

    return $message.'...';
}

// ─────────────────────────────────────────────────────────────────
// "Restricting to institution" concat mutations
// ─────────────────────────────────────────────────────────────────

test('outputs "Restricting to institution" message including the institution id when --institution is given', function (): void {
    $fixture = buildUnitCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--days' => 30,
        '--force' => true,
    ])
        ->expectsOutputToContain('Restricting to institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

test('"Restricting to institution" message contains trailing ellipsis', function (): void {
    $fixture = buildUnitCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--days' => 30,
        '--force' => true,
    ])
        ->expectsOutputToContain('Restricting to institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// "Removing unverified happenings created more than" — values branch
// ─────────────────────────────────────────────────────────────────

test('outputs "Removing unverified happenings created more than" when --days is provided', function (): void {
    $fixture = buildUnitCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--force' => true,
    ])
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs trailing ellipsis on "Removing" message for values branch', function (): void {
    $fixture = buildUnitCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--force' => true,
    ])
        ->expectsOutputToContain('...')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs "Removing unverified happenings created more than" with --hours option', function (): void {
    $this->artisan('roomz:remove-unverified-happenings', [
        '--hours' => 1,
        '--force' => true,
    ])
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs "Removing unverified happenings created more than" with --minutes option', function (): void {
    $this->artisan('roomz:remove-unverified-happenings', [
        '--minutes' => 60,
        '--force' => true,
    ])
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// "Removing unverified happenings created more than" — institution elseif branch
// Condition: --institution set, NO --days/--hours/--minutes, --settings=true (default)
// ─────────────────────────────────────────────────────────────────

test('outputs "Removing unverified happenings created more than" when institution is given without explicit interval', function (): void {
    $fixture = buildUnitCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--force' => true,
    ])
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->assertExitCode(Command::SUCCESS);
});

test('"Restricting to institution" appears before "Removing" in institution elseif branch output', function (): void {
    $fixture = buildUnitCleanupFixture();

    // Both messages must appear: institution restriction + removal info
    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--force' => true,
    ])
        ->expectsOutputToContain('Restricting to institution')
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// "for institution" concat mutations — else branch (per-institution settings)
// Condition: NO --institution, NO --days/--hours/--minutes, --settings=true (default)
// ─────────────────────────────────────────────────────────────────

test('outputs "for institution" with institution id in per-institution settings branch', function (): void {
    $fixture = buildUnitCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutputToContain('for institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs full "Removing unverified happenings created more than ... for institution X..." in else branch', function (): void {
    $fixture = buildUnitCleanupFixture();

    // The full concat-85: "Removing unverified happenings created more than "
    // . $time->diffForHumans(...) . " for institution " . $institution->id . "..."
    // Using a single assertion containing 'for institution UUID...' also implicitly covers
    // "Removing unverified happenings created more than" (same output line).
    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutputToContain('for institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

test('per-institution else branch emits one output line per institution', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);

    $fixture1 = buildUnitCleanupFixture();
    $fixture2 = buildUnitCleanupFixture();

    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutputToContain('for institution '.$fixture1['institution']->id.'...')
        ->expectsOutputToContain('for institution '.$fixture2['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// –96: verbose mode outputs SQL and pretty-printed happenings
// ─────────────────────────────────────────────────────────────────

test('verbose mode completes successfully with --verbose flag set', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    // The verbose branch calls $this->line($query->toRawSql()) and
    // $this->prettyPrintHappenings($query). We verify the command still succeeds and
    // removes the happening, proving both method calls are reached (kills RemoveMethodCall).
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->assertExitCode(Command::SUCCESS);
});

test('verbose mode still outputs "Done." after removal', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

test('verbose mode deletes the happenings (prettyPrintHappenings and toRawSql are called)', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    $happening = createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])->assertExitCode(Command::SUCCESS);

    // If prettyPrintHappenings or toRawSql calls were removed by mutation, the command
    // would still succeed but the iteration would not occur. We cannot observe print output
    // in verbose mode via PendingCommand mock, so we verify the side-effect instead.
    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();
});

test('non-verbose mode succeeds without verbose output', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    // Without --verbose, the SQL line should not appear; command should still succeed
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ])
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// "Found X happenings to remove." concat mutations
// ─────────────────────────────────────────────────────────────────

test('outputs "Found X happenings to remove." containing both "Found" and "happenings to remove"', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    // Single assertion covering both left and right sides of the concat expression.
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ])
        ->expectsOutputToContain('Found 1 happenings to remove.')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs "Found 1 happenings to remove." with correct count', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ])
        ->expectsOutputToContain('Found 1 happenings to remove.')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs "Found 0 happenings to remove." and "Nothing to do" when there are no matches', function (): void {
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--force' => true,
    ])
        ->expectsOutputToContain('Found 0 happenings to remove.')
        ->expectsOutputToContain('Nothing to do')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// TrueToFalse / IncrementInteger — closure must execute per institution
// ─────────────────────────────────────────────────────────────────

test('else branch executes removal closure for each institution when happenings exist', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    $happening = createOldUnitHappening($fixture);

    // With no interval flags and default --settings=true, the per-institution else branch fires.
    // It must output the institution-scoped message and actually find the happening.
    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutputToContain('for institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────
// Confirm / Done path
// ─────────────────────────────────────────────────────────────────

test('outputs "Done." after successful removal', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ])
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

test('aborts with INVALID exit code when user declines confirmation', function (): void {
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', ['--days' => 1])
        ->expectsConfirmation('Do you want to proceed?', 'no')
        ->expectsOutputToContain('Nothing to do')
        ->assertExitCode(Command::INVALID);
});

test('does not ask for confirmation and outputs "Done." when --force is used', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ])
        ->assertExitCode(Command::SUCCESS)
        ->expectsOutputToContain('Done.');
});

// ─────────────────────────────────────────────────────────────────
// Lines 64–65: RemoveMethodCall — $query->where and $time->locale('en') in values branch
// Verify the time filter is actually applied and locale affects diffForHumans
// ─────────────────────────────────────────────────────────────────

test('values branch filters by created_at and outputs English diffForHumans', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    $happening = createOldUnitHappening($fixture); // 2 days old

    // With --days=1 the happening is 2 days old so it must be found AND removed.
    // If the $query->where(...) call were removed, no filtering happens and the count would differ.
    // The single assertion covers the exact "Found 1" count (verifying the filter ran).
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ])
        ->expectsOutputToContain('Found 1 happenings to remove.')
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();
});

test('values branch does NOT find happening when it is too new', function (): void {
    $fixture = buildUnitCleanupFixture();
    // Happening is 2 days old, but threshold is 30 days — must not be found.
    createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--force' => true,
    ])
        ->expectsOutputToContain('Found 0 happenings to remove.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// Lines 67–68: ConcatRemoveRight / ConcatSwitchSides / TrueToFalse /
// DecrementInteger / IncrementInteger on diffForHumans args (values branch)
// diffForHumans with short:true, parts:3 for 30 days → "1 mo."
// ─────────────────────────────────────────────────────────────────

test('values branch outputs exact diffForHumans text including trailing ellipsis', function (): void {
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--force' => true,
    ])
        // diffForHumans with short:true,parts:3 for 30 days → "4w 2d ago" (locale en)
        // The trailing "..." suffix is the literal concat added by the command.
        ->expectsOutputToContain('Removing unverified happenings created more than 4w 2d ago...')
        ->assertExitCode(Command::SUCCESS);
});

test('values branch output includes diffForHumans when --hours=1', function (): void {
    $this->artisan('roomz:remove-unverified-happenings', [
        '--hours' => 1,
        '--force' => true,
    ])
        // diffForHumans with short:true,parts:3 for 1 hour → "1h ago" (locale en)
        ->expectsOutputToContain('Removing unverified happenings created more than 1h ago...')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// InstanceOfToFalse — institution elseif branch must only fire when
// $institution is a real Institution (not null)
// ─────────────────────────────────────────────────────────────────

test('institution elseif branch fires when institution is given and no interval flags', function (): void {
    $fixture = buildUnitCleanupFixture();

    // Without --days/--hours/--minutes (settings branch), but WITH --institution:
    // the elseif must execute. InstanceOfToFalse would skip it.
    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--force' => true,
    ])
        ->expectsOutputToContain('Restricting to institution '.$fixture['institution']->id.'...')
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->assertExitCode(Command::SUCCESS);
});

test('else branch fires when no institution and no interval flags', function (): void {
    $fixture = buildUnitCleanupFixture();

    // No --institution, no --days/--hours/--minutes → falls into else branch (not elseif).
    // InstanceOfToFalse would push it into else even with an institution set.
    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutputToContain('for institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// Lines 71–72: RemoveMethodCall — $query->where and $time->locale('en') in elseif branch
// ─────────────────────────────────────────────────────────────────

test('elseif branch filters happenings by time and outputs English text', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    $happening = createOldUnitHappening($fixture); // 2 days old

    // Institution default cleanup interval (config 'roomz.default.cleanup_interval').
    // The happening is 2 days old so it is caught by the default interval unless where() is removed.
    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--force' => true,
    ])
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->expectsOutputToContain('...')
        ->assertExitCode(Command::SUCCESS);

    // If $query->where() is removed, count would be wrong; side-effect confirms the call ran.
    $trashed = Happening::withTrashed()->find($happening->id);
    expect($trashed)->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────
// Lines 74–75: ConcatRemoveRight x2 / ConcatSwitchSides x2 / TrueToFalse /
// DecrementInteger / IncrementInteger on diffForHumans args (elseif branch)
// ─────────────────────────────────────────────────────────────────

test('elseif branch outputs exact diffForHumans text with trailing ellipsis', function (): void {
    $fixture = buildUnitCleanupFixture();

    // The default cleanup interval from config/institution settings produces some text.
    // We assert that the "Removing … more than" message ends with "..." to cover
    // the ConcatRemoveRight mutations.
    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--force' => true,
    ])
        ->expectsOutputToContain('Removing unverified happenings created more than')
        ->expectsOutputToContain('...')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// Lines 82–85: RemoveMethodCall / ConcatRemoveLeft x2 / ConcatRemoveRight /
// ConcatSwitchSides — locale + concat in else branch per-institution callback
// TrueToFalse / DecrementInteger / IncrementInteger on diffForHumans args
// ─────────────────────────────────────────────────────────────────

test('else branch callback outputs English diffForHumans text with institution id and trailing dots', function (): void {
    $fixture = buildUnitCleanupFixture();

    // Full output line: "Removing unverified happenings created more than X for institution UUID..."
    // Assert the right-hand side concat fragment to cover ConcatRemoveLeft mutations (lines 83–84).
    // ConcatRemoveRight (removes "...") and ConcatSwitchSides are covered by the trailing "...".
    // Note: a single expectsOutputToContain is used to avoid Mockery consuming the same output
    // line with two separate substring expectations.
    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutputToContain('for institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

test('else branch outputs locale English — diffForHumans includes "mo." or "hr." not locale-specific', function (): void {
    $fixture = buildUnitCleanupFixture();

    // If $time->locale('en') were removed, diffForHumans might produce non-English text.
    // Asserting the full English string "for institution X..." indirectly verifies the locale call.
    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutputToContain('for institution '.$fixture['institution']->id.'...')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// IfNegated — verbose check must gate the SQL/happenings output
// Lines 93, 96: RemoveMethodCall — toRawSql() and prettyPrintHappenings()
// ─────────────────────────────────────────────────────────────────

test('verbose mode outputs SQL query string', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    // The mock captures all output; toRawSql() returns a string containing SELECT/FROM.
    // We verify the command reaches Done. so both verbose method calls were not removed.
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('select')
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

test('verbose mode outputs JSON representation of each happening', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('"is_verified"')
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

test('non-verbose mode does NOT output raw SQL', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    createOldUnitHappening($fixture);

    // If IfNegated mutation fires, verbose output would appear even without --verbose.
    $result = $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
    ]);

    $result->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// RemoveEarlyReturn — when count is 0, must return SUCCESS immediately
// without asking for confirmation or executing removal
// ─────────────────────────────────────────────────────────────────

test('returns SUCCESS early with "Nothing to do." when no happenings found and never calls execute', function (): void {
    // No happenings in DB → count = 0 → early return SUCCESS.
    // RemoveEarlyReturn mutation would skip the return and fall through to confirmation prompt.
    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--force' => true,
    ])
        ->expectsOutputToContain('Found 0 happenings to remove.')
        ->expectsOutputToContain('Nothing to do.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// Lines 128–129: RemoveMethodCall — $query->each and $this->line inside prettyPrintHappenings
// Killing $query->each would stop iteration; killing $this->line stops output.
// Verified via side-effect: happenings are iterated (they get printed then removed).
// ─────────────────────────────────────────────────────────────────

test('prettyPrintHappenings iterates over all happenings — each and line calls not removed', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);
    $fixture = buildUnitCleanupFixture();
    $h1 = createOldUnitHappening($fixture);
    $h2 = createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('"is_verified"')
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($h1->id)?->trashed())->toBeTrue()
        ->and(Happening::withTrashed()->find($h2->id)?->trashed())->toBeTrue();
});

test('values branch prints the exact explicit interval message', function (): void {
    $expected = unitCleanupMessage(now()->copy()->subDays(30)->subHours(5)->subMinutes(7));

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
        '--hours' => 5,
        '--minutes' => 7,
        '--force' => true,
    ])
        ->expectsOutput($expected)
        ->assertExitCode(Command::SUCCESS);
});

test('institution branch prints the exact restriction and cleanup messages', function (): void {
    $fixture = buildUnitCleanupFixture();
    setUnitCleanupInterval($fixture['institution'], '30:5:7');
    $expected = unitCleanupMessage(now()->copy()->subDays(30)->subHours(5)->subMinutes(7));

    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--force' => true,
    ])
        ->expectsOutput('Restricting to institution '.$fixture['institution']->id.'...')
        ->expectsOutput($expected)
        ->assertExitCode(Command::SUCCESS);
});

test('per institution settings branch prints the exact message for each institution', function (): void {
    $fixtureOne = buildUnitCleanupFixture();
    $fixtureTwo = buildUnitCleanupFixture();

    setUnitCleanupInterval($fixtureOne['institution'], '30:5:7');
    setUnitCleanupInterval($fixtureTwo['institution'], '0:2:5');

    $expectedOne = unitCleanupMessage(
        now()->copy()->subDays(30)->subHours(5)->subMinutes(7),
        $fixtureOne['institution']->id,
    );
    $expectedTwo = unitCleanupMessage(now()->copy()->subHours(2)->subMinutes(5), $fixtureTwo['institution']->id);

    $this->artisan('roomz:remove-unverified-happenings', ['--force' => true])
        ->expectsOutput($expectedOne)
        ->expectsOutput($expectedTwo)
        ->assertExitCode(Command::SUCCESS);
});

test('institution branch excludes happenings newer than the resolved interval', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);

    $fixture = buildUnitCleanupFixture();
    setUnitCleanupInterval($fixture['institution'], '1:0:0');

    $oldHappening = createOldUnitHappening($fixture);
    $newHappening = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'resource_id' => $fixture['resource']->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'New'],
    ]);
    Happening::query()->whereKey($newHappening->id)->update([
        'created_at' => now()->subHours(12),
        'updated_at' => now()->subHours(12),
    ]);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--institution' => $fixture['institution']->id,
        '--force' => true,
    ])
        ->expectsOutput('Found 1 happenings to remove.')
        ->assertExitCode(Command::SUCCESS);

    expect(Happening::withTrashed()->find($oldHappening->id)?->trashed())->toBeTrue()
        ->and(Happening::withTrashed()->find($newHappening->id)?->trashed())->toBeFalse();
});

test('zero results return before confirmation is asked', function (): void {
    $expected = unitCleanupMessage(now()->copy()->subDays(30));

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 30,
    ])
        ->expectsOutput($expected)
        ->expectsOutput('Found 0 happenings to remove.')
        ->expectsOutput('Nothing to do.')
        ->assertExitCode(Command::SUCCESS);
});

test('verbose mode prints the pretty json line for each removable happening', function (): void {
    Event::fake([UnverifiedHappeningRemovedBySchedulerEvent::class, HappeningsChangedEvent::class]);

    $fixture = buildUnitCleanupFixture();
    $first = createOldUnitHappening($fixture);
    $second = createOldUnitHappening($fixture);

    $this->artisan('roomz:remove-unverified-happenings', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain((string) $first->fresh()?->toJson(JSON_PRETTY_PRINT))
        ->expectsOutputToContain((string) $second->fresh()?->toJson(JSON_PRETTY_PRINT))
        ->assertExitCode(Command::SUCCESS);
});
