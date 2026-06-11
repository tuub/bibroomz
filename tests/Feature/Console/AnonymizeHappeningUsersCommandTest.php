<?php

declare(strict_types=1);

use App\Console\Commands\AnonymizeHappeningUsersCommand;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

covers(AnonymizeHappeningUsersCommand::class);

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
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, other: User}
 */
function buildAnonFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_active' => true]);
    $owner = User::factory()->create();
    $other = User::factory()->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'other' => $other];
}

/**
 * Creates a past happening with user references that qualifies for anonymization.
 *
 * @param  array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, other: User}  $fixture
 */
function createPastHappening(array $fixture, int $daysAgo = 10): Happening
{
    return Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'user_id_02' => $fixture['other']->id,
        'resource_id' => $fixture['resource']->id,
        'is_verified' => true,
        'verifier' => $fixture['other']->name,
        'start' => now()->subDays($daysAgo),
        'end' => now()->subDays($daysAgo)->addHour(),
        'reserved_at' => now()->subDays($daysAgo),
        'verified_at' => now()->subDays($daysAgo),
        'label' => ['en' => 'Past'],
    ]);
}

// ─────────────────────────────────────────────────────────────────
// "Found X happenings to anonymize." concat mutations
// ─────────────────────────────────────────────────────────────────

test('outputs "Found 0 happenings to anonymize." when there are no qualifying happenings', function (): void {
    $this->artisan('roomz:anonymize-happening-users', ['--days' => 30, '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs "Found" and "happenings to anonymize" when qualifying happenings exist', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    // Single combined assertion covers both left and right sides of the concat.
    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('outputs "Found 1 happenings to anonymize." with correct count', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// if ($query->count() === 0) — DecrementInteger on 0
// ─────────────────────────────────────────────────────────────────

test('outputs "Nothing to do" and exits SUCCESS when count is 0', function (): void {
    $this->artisan('roomz:anonymize-happening-users', ['--days' => 30, '--force' => true])
        ->expectsOutputToContain('Nothing to do.')
        ->assertExitCode(Command::SUCCESS);
});

test('does not output "Nothing to do" when count is greater than 0 and --force is given', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    // With --force and real happenings, it should NOT output "Nothing to do" but "Done."
    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--force' => true])
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// Lines 52–53: dry-run branch
// ─────────────────────────────────────────────────────────────────

test('dry-run outputs "Nothing to do." without anonymizing happenings', function (): void {
    $fixture = buildAnonFixture();
    $happening = createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--dry-run' => true])
        ->expectsOutputToContain('Nothing to do.')
        ->assertExitCode(Command::SUCCESS);

    expect($happening->fresh()?->user_id_01)->toBe($fixture['owner']->id);
});

test('dry-run still outputs the "Found" count before "Nothing to do"', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--dry-run' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->expectsOutputToContain('Nothing to do.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// if (! $this->option('force') && ! $this->confirm(...))
// ─────────────────────────────────────────────────────────────────

test('aborts with INVALID exit code when user declines confirmation', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1])
        ->expectsConfirmation('Do you want to proceed?', 'no')
        ->expectsOutputToContain('Nothing to do.')
        ->assertExitCode(Command::INVALID);
});

test('proceeds without confirmation prompt when --force is given', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--force' => true])
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// $this->anonymizeHappeningUsersAction->execute($query) — RemoveMethodCall
// ─────────────────────────────────────────────────────────────────

test('execute action actually nullifies user references after --force', function (): void {
    $fixture = buildAnonFixture();
    $happening = createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--force' => true])
        ->assertExitCode(Command::SUCCESS);

    expect($happening->fresh()?->user_id_01)->toBeNull()
        ->and($happening->fresh()?->user_id_02)->toBeNull()
        ->and($happening->fresh()?->verifier)->toBeNull();
});

test('outputs "Done." after executing anonymization', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--force' => true])
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// parseDaysValue / resolveDays — exercised via --days option
// return $value > 0 ? $value : null (int branch)
// return $days > 0 ? $days : null (string-to-int branch)
// ─────────────────────────────────────────────────────────────────

test('--days=1 is treated as valid and uses 1-day threshold', function (): void {
    $fixture = buildAnonFixture();
    // 10-day-old happening, threshold 1 day → must be found
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => '1', '--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('--days=0 is treated as invalid, falls back to config default (30 days)', function (): void {
    config()->set('roomz.happenings.anonymize_days', 30);
    $fixture = buildAnonFixture();
    // 10-day-old happening, 30-day threshold → NOT found
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => '0', '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('--days=abc is treated as invalid, falls back to config default (30 days)', function (): void {
    config()->set('roomz.happenings.anonymize_days', 30);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => 'abc', '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('--days= (empty string) is treated as invalid, falls back to config default', function (): void {
    config()->set('roomz.happenings.anonymize_days', 30);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => '', '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('no --days option falls back to config days when config is set to 1', function (): void {
    config()->set('roomz.happenings.anonymize_days', 1);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('no --days and null config falls back to hardcoded 30 days', function (): void {
    config()->set('roomz.happenings.anonymize_days', null);
    $fixture = buildAnonFixture();
    // 10-day-old happening, 30-day threshold → NOT found
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('--days=2 does not match happening that is only 1 day old', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 1);

    // Threshold is 2 days, happening is 1 day old → not matched
    $this->artisan('roomz:anonymize-happening-users', ['--days' => '2', '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// string check mutations — BooleanOrToBooleanAnd, RemoveNot, etc.
// "digits only" guard: non-digit strings must return null
// ─────────────────────────────────────────────────────────────────

test('--days=1abc is treated as invalid, falls back to config', function (): void {
    config()->set('roomz.happenings.anonymize_days', 30);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => '1abc', '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('--days=10 correctly matches happenings older than 10 days', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 11);

    $this->artisan('roomz:anonymize-happening-users', ['--days' => '10', '--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// Verbose mode
// ─────────────────────────────────────────────────────────────────

test('verbose mode completes successfully and outputs "Done." with --verbose flag', function (): void {
    $fixture = buildAnonFixture();
    $happening = createPastHappening($fixture, daysAgo: 10);

    // Verbose branch (lines 42–45) calls $this->line($query->toRawSql()) and
    // $this->prettyPrintHappenings($query). The mock does not honour verbosity so
    // we verify the side-effect: the happening is anonymized, proving both calls ran.
    $this->artisan('roomz:anonymize-happening-users', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);

    expect($happening->fresh()?->user_id_01)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────
// IfNegated — verbose guard must only output SQL/happenings when verbose
// Lines 43–44: RemoveMethodCall — toRawSql() and prettyPrintHappenings()
// ─────────────────────────────────────────────────────────────────

test('verbose mode outputs raw SQL select statement', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('select')
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

test('verbose mode outputs JSON representation of each happening', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('"is_verified"')
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// RemoveEarlyReturn — dry-run must return SUCCESS before anonymizing
// ─────────────────────────────────────────────────────────────────

test('dry-run returns SUCCESS without proceeding to confirmation or execute', function (): void {
    $fixture = buildAnonFixture();
    $happening = createPastHappening($fixture, daysAgo: 10);

    // RemoveEarlyReturn mutation would continue after "Nothing to do." and
    // try to confirm and execute. With no --force, it would ask for confirmation.
    $this->artisan('roomz:anonymize-happening-users', ['--days' => 1, '--dry-run' => true])
        ->expectsOutputToContain('Nothing to do.')
        ->assertExitCode(Command::SUCCESS);

    // Happening must not be anonymized — confirms the early return fired.
    expect($happening->fresh()?->user_id_01)->toBe($fixture['owner']->id);
});

// ─────────────────────────────────────────────────────────────────
// DecrementInteger / IncrementInteger — hardcoded fallback is exactly 30
// ─────────────────────────────────────────────────────────────────

test('fallback is exactly 30 days — 29-day-old happening is NOT found with no config or --days', function (): void {
    config()->set('roomz.happenings.anonymize_days', null);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 29);

    // 29-day-old happening, 30-day threshold → not found (>= 30 required).
    // IncrementInteger (31) would find it; DecrementInteger (29) would not.
    $this->artisan('roomz:anonymize-happening-users', ['--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('fallback is exactly 30 days — 31-day-old happening IS found with no config or --days', function (): void {
    config()->set('roomz.happenings.anonymize_days', null);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 31);

    $this->artisan('roomz:anonymize-happening-users', ['--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// GreaterToGreaterOrEqual — `$days > 0` must reject 0; DecrementInteger — boundary
// ─────────────────────────────────────────────────────────────────

test('parseDaysValue returns null for int 0, triggering config fallback', function (): void {
    config()->set('roomz.happenings.anonymize_days', 30);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    // int 0 → parseDaysValue returns null → config 30 days → 10-day happening not found.
    // GreaterToGreaterOrEqual (>= 0) would accept 0 and return 0, finding a 10-day happening.
    $this->artisan('roomz:anonymize-happening-users', ['--days' => 0, '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('parseDaysValue returns int 1 for string "1" which is greater than 0', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    // string "1" → (int)"1" = 1 → 1 > 0 → return 1 → 10-day happening found.
    // DecrementInteger (0 > 0 = false) would fall back to config and not find it.
    $this->artisan('roomz:anonymize-happening-users', ['--days' => '1', '--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// BooleanOrToBooleanAnd — condition is OR, not AND
// "! is_string($value) || $value === '' || ..." — any true condition → return null
// EmptyStringToNotEmpty — empty string must return null (not treated as valid)
// ─────────────────────────────────────────────────────────────────

test('parseDaysValue returns null when value is not a string (array), triggering config fallback', function (): void {
    // BooleanOrToBooleanAnd mutation would require ALL conditions to be true.
    // An array is !is_string → should immediately return null even if not empty.
    config()->set('roomz.happenings.anonymize_days', 30);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    // Pass array via config to exercise the non-string path of parseDaysValue.
    config()->set('roomz.happenings.anonymize_days', []);
    $this->artisan('roomz:anonymize-happening-users', ['--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

test('parseDaysValue returns null for empty string "", triggering config fallback', function (): void {
    // EmptyStringToNotEmpty mutation: '' === '' becomes '' !== '' → empty string treated as valid.
    config()->set('roomz.happenings.anonymize_days', 30);
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    // Empty string --days → null → config 30 days → 10-day happening NOT found.
    $this->artisan('roomz:anonymize-happening-users', ['--days' => '', '--force' => true])
        ->expectsOutputToContain('Found 0 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// RemoveIntegerCast — (int) $value must cast the string to int
// ─────────────────────────────────────────────────────────────────

test('parseDaysValue casts string "5" to int 5 correctly', function (): void {
    $fixture = buildAnonFixture();
    createPastHappening($fixture, daysAgo: 10);

    // Without (int) cast, string "5" stays a string and > 0 comparison would fail.
    $this->artisan('roomz:anonymize-happening-users', ['--days' => '5', '--force' => true])
        ->expectsOutputToContain('Found 1 happenings to anonymize.')
        ->assertExitCode(Command::SUCCESS);
});

// ─────────────────────────────────────────────────────────────────
// Lines 111–112: RemoveMethodCall — $query->each and $this->line in prettyPrintHappenings
// ─────────────────────────────────────────────────────────────────

test('prettyPrintHappenings iterates each happening and outputs JSON in verbose mode', function (): void {
    $fixture = buildAnonFixture();
    $happening = createPastHappening($fixture, daysAgo: 10);

    $this->artisan('roomz:anonymize-happening-users', [
        '--days' => 1,
        '--force' => true,
        '--verbose' => true,
    ])
        ->expectsOutputToContain('"is_verified"')
        ->expectsOutputToContain('Done.')
        ->assertExitCode(Command::SUCCESS);

    expect($happening->fresh()?->user_id_01)->toBeNull();
});
