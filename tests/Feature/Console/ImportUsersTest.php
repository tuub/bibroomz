<?php

declare(strict_types=1);

use App\Console\Commands\ImportUsers;
use App\Models\Institution;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;
use Tests\Feature\Console\ImportUsersTestStreamWrapper;

covers(ImportUsers::class);

uses(RefreshDatabase::class);

if (! in_array('roomz-import-users-unit', stream_get_wrappers(), true)) {
    stream_wrapper_register('roomz-import-users-unit', ImportUsersTestStreamWrapper::class);
}

/**
 * @return list<string>
 */
function &importUsersTempFiles(): array
{
    /** @var list<string> $paths */
    static $paths = [];

    return $paths;
}

function writeUnitImportUsersCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'roomz-import-users-unit-');
    assert(is_string($path));
    file_put_contents($path, $contents);
    importUsersTempFiles()[] = $path;

    return $path;
}

afterEach(function (): void {
    foreach (importUsersTempFiles() as $path) {
        @unlink($path);
    }
    $paths = &importUsersTempFiles();
    $paths = [];

    ImportUsersTestStreamWrapper::reset();
});

test('handle fails when file does not exist', function (): void {
    // BooleanOrToBooleanAnd: ! is_file($path) || ! is_readable($path) becomes &&
    // With a non-existent file, is_file=false → !is_file=true, is_readable=false → !is_readable=true
    // With || (original): true || true = true → fails correctly
    // With && (mutation): true && true = true → same result for non-existent
    // But for a directory (is_file=false, is_readable=true): || still fails, && would pass
    $this->artisan(ImportUsers::class, ['path' => '/tmp/non-existent-file-roomz-test.csv'])
        ->assertExitCode(Command::FAILURE);
});

test('handle outputs file warning when path points to a readable directory', function (): void {
    $this->artisan(ImportUsers::class, ['path' => sys_get_temp_dir()])
        ->expectsOutputToContain('⚠ File does not exist or is not readable.')
        ->assertExitCode(Command::FAILURE);
});

test('handle outputs file warning when file does not exist', function (): void {
    $this->artisan(ImportUsers::class, ['path' => '/tmp/non-existent-file-roomz-test.csv'])
        ->expectsOutputToContain('⚠ File does not exist or is not readable.')
        ->assertExitCode(Command::FAILURE);
});

test('handle processes valid csv and returns success', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Import Test']]);
    $tmpFile = writeUnitImportUsersCsv("name,email\nalice,alice@example.com");
    app()->setLocale('de');

    $this->artisan(ImportUsers::class, [
        'path' => $tmpFile,
        '--header' => '1',
        '--group' => $group->id,
    ])->assertExitCode(Command::SUCCESS);

    expect(app()->currentLocale())->toBe('en');
});

test('handle imports users successfully and returns SUCCESS', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Import Info']]);
    $tmpFile = writeUnitImportUsersCsv("name,email\nbob,bob@example.com");

    $this->artisan(ImportUsers::class, [
        'path' => $tmpFile,
        '--header' => '1',
        '--group' => $group->id,
    ])
        ->expectsOutputToContain('Import completed successfully.')
        ->assertExitCode(Command::SUCCESS);

    expect($group->users()->count())->toBe(1);
});

test('handle closes file on validation exception', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Close Test']]);

    $tmpFile = writeUnitImportUsersCsv("foo,bar\nbaz,qux");

    $this->artisan(ImportUsers::class, [
        'path' => $tmpFile,
        '--header' => '1',
        '--columns' => 'foo,bar',
        '--group' => $group->id,
    ])
        ->expectsOutputToContain('⚠ The columns field is missing a required value.')
        ->expectsOutputToContain('⚠ The selected columns.0 is invalid.')
        ->expectsOutputToContain('⚠ The selected columns.1 is invalid.')
        ->assertExitCode(Command::FAILURE);
});

test('handle closes the opened stream when validation fails', function (): void {
    $path = ImportUsersTestStreamWrapper::put('validation-close', "foo,bar\nbaz,qux\n");

    $this->artisan(ImportUsers::class, [
        'path' => $path,
        '--header' => '1',
        '--columns' => 'foo,bar',
    ])->assertExitCode(Command::FAILURE);

    expect(ImportUsersTestStreamWrapper::closeCount('validation-close'))->toBe(1);
});

test('handle outputs csv row validation errors with warning prefix', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Row Validation']]);
    $tmpFile = writeUnitImportUsersCsv("name,email\nAlice,\n");

    $this->artisan(ImportUsers::class, [
        'path' => $tmpFile,
        '--header' => '1',
        '--group' => $group->id,
    ])
        ->expectsOutputToContain('⚠ The users.0.email field is required.')
        ->assertExitCode(Command::FAILURE);
});

test('handle closes the opened stream after a successful import', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Stream Close']]);
    $path = ImportUsersTestStreamWrapper::put('success-close', "name,email\nDana,dana@example.com\n");

    $this->artisan(ImportUsers::class, [
        'path' => $path,
        '--header' => '1',
        '--group' => $group->id,
    ])->assertExitCode(Command::SUCCESS);

    expect(ImportUsersTestStreamWrapper::closeCount('success-close'))->toBe(1);
});
