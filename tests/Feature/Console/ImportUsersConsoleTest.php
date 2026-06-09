<?php

use App\Console\Commands\ImportUsers;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Console\ImportUsersAction;
use App\Services\Console\ImportUsersColumnsResolver;
use App\Services\Console\ImportUsersCsvReader;
use App\Services\Console\ImportUsersDefaultsParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

covers(
    ImportUsers::class,
    ImportUsersAction::class,
    ImportUsersColumnsResolver::class,
    ImportUsersCsvReader::class,
    ImportUsersDefaultsParser::class
);

uses(RefreshDatabase::class);

function writeFeatureTempCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'roomz-users-');
    file_put_contents($path, $contents);

    return $path;
}

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir().'/roomz-users-*') ?: [] as $path) {
        @unlink($path);
    }
});

test('import users command handles invalid default dates', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Tutors']]);
    $path = writeFeatureTempCsv("name,email\nAlice,alice@example.test\n");

    $this->artisan('roomz:import-users', [
        'path' => $path,
        '--header' => 1,
        '--group' => $group->id,
        '--from' => 'not-a-date',
    ])
        ->expectsOutputToContain('Invalid date format.')
        ->assertExitCode(Command::FAILURE);
});

test('import users command imports header based csv files and updates existing group memberships', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Tutors']]);
    $firstPath = writeFeatureTempCsv("name,email,valid_from\nAlice,alice@example.test,2026-06-01\n");
    $secondPath = writeFeatureTempCsv("name,email,valid_until\nAlice,alice@example.test,2026-07-01\n");

    $this->artisan('roomz:import-users', [
        'path' => $firstPath,
        '--header' => 1,
        '--group' => $group->id,
    ])->assertExitCode(Command::SUCCESS);

    $user = User::where('email', 'alice@example.test')->firstOrFail();
    expect($group->users()->where('users.id', $user->id)->first()?->pivot?->valid_from?->format('Y-m-d'))
        ->toBe('2026-06-01');

    $this->artisan('roomz:import-users', [
        'path' => $secondPath,
        '--header' => 1,
        '--group' => $group->id,
    ])->assertExitCode(Command::SUCCESS);

    expect($group->users()->where('users.id', $user->id)->first()?->pivot?->valid_until?->format('Y-m-d'))
        ->toBe('2026-07-01');
});

test('import users command supports interactive column mapping without a csv header', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Tutors']]);
    $path = writeFeatureTempCsv("Alice,alice@example.test\n");

    $this->artisan('roomz:import-users', [
        'path' => $path,
        '--group' => $group->id,
    ])
        ->expectsConfirmation('Does the file include a CSV header?', 'no')
        ->expectsChoice('Column 1:', 'name', ['name', 'email', 'valid_from', 'valid_until'], true)
        ->expectsChoice('Column 2:', 'email', ['email', 'valid_from', 'valid_until'], true)
        ->expectsConfirmation('Does the file have additional columns?', 'no')
        ->assertExitCode(Command::SUCCESS);

    expect(User::firstWhere('email', 'alice@example.test'))->not->toBeNull();
});

test('import users command applies default valid_until when --until option is provided', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Staff']]);
    $path = writeFeatureTempCsv("name,email\nCarol,carol@example.test\n");

    $this->artisan('roomz:import-users', [
        'path' => $path,
        '--header' => 1,
        '--group' => $group->id,
        '--until' => '2026-12-31',
    ])->assertExitCode(Command::SUCCESS);

    $user = User::where('email', 'carol@example.test')->firstOrFail();
    expect($user)->not->toBeNull()
        ->and($group->users()->where('users.id', $user->id)->first()?->pivot?->valid_until?->format('Y-m-d'))
        ->toBe('2026-12-31');
});
