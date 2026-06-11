<?php

use App\Console\Commands\CreateInstitutionCommand;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Services\Console\CreateInstitutionAction;
use App\Services\Console\CreateInstitutionResourceGroupAction;
use App\Services\Console\InstitutionInputCollector;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

covers(
    CreateInstitutionCommand::class,
    InstitutionInputCollector::class,
    CreateInstitutionAction::class,
    CreateInstitutionResourceGroupAction::class,
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
});

test('create institution command validates input without exiting the process', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'not-an-email',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'no',
    ])
        ->expectsQuestion('Active week days', [])
        ->assertExitCode(Command::FAILURE);

    expect(Institution::where('slug', 'library')->exists())->toBeFalse();
});

test('create institution command creates an institution and an optional resource group through prompts', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday', 'Tuesday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', 'Raeume')
        ->expectsQuestion('Name (en)', 'Rooms')
        ->expectsQuestion('Slug', 'rooms')
        ->expectsQuestion('Singular (de)', 'Raum')
        ->expectsQuestion('Singular (en)', 'Room')
        ->expectsQuestion('Plural (de)', 'Raeume')
        ->expectsQuestion('Plural (en)', 'Rooms')
        ->expectsQuestion('Description (de)', 'Beschreibung')
        ->expectsQuestion('Description (en)', 'Description')
        ->expectsConfirmation('Active?', 'yes')
        ->assertExitCode(Command::SUCCESS);

    $institution = Institution::where('slug', 'library')->firstOrFail();

    expect($institution)->not->toBeNull()
        ->and($institution->settings)->toHaveCount(count(Setting::getInitialValues()['institution']))
        ->and(ResourceGroup::firstWhere('slug', 'rooms'))->not->toBeNull();
});

test('create institution command cancels when user declines confirmation', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'no')
        ->assertExitCode(Command::INVALID);

    expect(Institution::where('slug', 'library')->exists())->toBeFalse();
});

test('create institution command skips resource group creation when user declines', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'no')
        ->assertExitCode(Command::SUCCESS);

    $institution = Institution::where('slug', 'library')->firstOrFail();
    expect($institution)->not->toBeNull();
    expect(ResourceGroup::where('institution_id', $institution->id)->exists())->toBeFalse();
});

test('create institution command handles validation error on resource group creation', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', '')
        ->expectsQuestion('Name (en)', '')
        ->expectsQuestion('Slug', '')
        ->assertExitCode(Command::FAILURE);

    $institution = Institution::where('slug', 'library')->firstOrFail();
    expect($institution)->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────
// Success path — institution created, resource group created
// ─────────────────────────────────────────────────────────────────

test('create institution outputs "Institution created." on success', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-unit',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'no')
        ->expectsOutputToContain('Institution created.')
        ->assertExitCode(Command::SUCCESS);
});

test('create institution persists institution to database on success', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-persist',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'no')
        ->assertExitCode(Command::SUCCESS);

    expect(Institution::where('slug', 'library-persist')->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────
// Resource group creation path
// ─────────────────────────────────────────────────────────────────

test('create institution outputs "Resource group created." when resource group is created', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-rg',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', 'Raeume')
        ->expectsQuestion('Name (en)', 'Rooms')
        ->expectsQuestion('Slug', 'rooms-rg')
        ->expectsQuestion('Singular (de)', 'Raum')
        ->expectsQuestion('Singular (en)', 'Room')
        ->expectsQuestion('Plural (de)', 'Raeume')
        ->expectsQuestion('Plural (en)', 'Rooms')
        ->expectsQuestion('Description (de)', 'Beschreibung')
        ->expectsQuestion('Description (en)', 'Description')
        ->expectsConfirmation('Active?', 'yes')
        ->expectsOutputToContain('Resource group created.')
        ->assertExitCode(Command::SUCCESS);
});

test('create institution persists resource group to database', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-rg2',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', 'Raeume')
        ->expectsQuestion('Name (en)', 'Rooms')
        ->expectsQuestion('Slug', 'rooms-rg2')
        ->expectsQuestion('Singular (de)', 'Raum')
        ->expectsQuestion('Singular (en)', 'Room')
        ->expectsQuestion('Plural (de)', 'Raeume')
        ->expectsQuestion('Plural (en)', 'Rooms')
        ->expectsQuestion('Description (de)', 'Beschreibung')
        ->expectsQuestion('Description (en)', 'Description')
        ->expectsConfirmation('Active?', 'yes')
        ->assertExitCode(Command::SUCCESS);

    $institution = Institution::where('slug', 'library-rg2')->firstOrFail();
    expect(ResourceGroup::where('institution_id', $institution->id)->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────
// Validation failure — institution input
// ─────────────────────────────────────────────────────────────────

test('create institution returns FAILURE on invalid institution input', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-invalid',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'not-an-email',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'no',
    ])
        ->expectsQuestion('Active week days', [])
        ->assertExitCode(Command::FAILURE);

    expect(Institution::where('slug', 'library-invalid')->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────
// Confirmation declined — institution creation
// ─────────────────────────────────────────────────────────────────

test('create institution returns INVALID and outputs "Cancelled." when user declines institution confirmation', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-cancel',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'no')
        ->expectsOutputToContain('Cancelled.')
        ->assertExitCode(Command::INVALID);

    expect(Institution::where('slug', 'library-cancel')->exists())->toBeFalse();
});

test('create institution does not persist institution when user declines confirmation', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-cancel2',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'no')
        ->assertExitCode(Command::INVALID);

    expect(Institution::where('slug', 'library-cancel2')->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────
// Skips resource group when user declines
// ─────────────────────────────────────────────────────────────────

test('create institution returns SUCCESS without resource group when user declines resource group creation', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-norg',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'no')
        ->assertExitCode(Command::SUCCESS);

    $institution = Institution::where('slug', 'library-norg')->firstOrFail();
    expect(ResourceGroup::where('institution_id', $institution->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────
// Validation failure — resource group input
// ─────────────────────────────────────────────────────────────────

test('create institution returns FAILURE when resource group validation fails', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-rgfail',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', '')
        ->expectsQuestion('Name (en)', '')
        ->expectsQuestion('Slug', '')
        ->assertExitCode(Command::FAILURE);

    expect(Institution::where('slug', 'library-rgfail')->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────
// renderValidationErrors on institution and resource group failures
// ─────────────────────────────────────────────────────────────────

test('institution validation failure outputs error message prefixed with warning sign', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-errmsg',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'not-an-email',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'no',
    ])
        ->expectsQuestion('Active week days', [])
        ->expectsOutputToContain('⚠')
        ->assertExitCode(Command::FAILURE);
});

test('resource group validation failure outputs "⚠ " concatenated with the exact message text', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-rgerrmsg',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', '')
        ->expectsQuestion('Name (en)', '')
        ->expectsQuestion('Slug', 'valid-slug')
        ->expectsQuestion('Singular (de)', '')
        ->expectsQuestion('Singular (en)', '')
        ->expectsQuestion('Plural (de)', '')
        ->expectsQuestion('Plural (en)', '')
        ->expectsQuestion('Description (de)', '')
        ->expectsQuestion('Description (en)', '')
        ->expectsConfirmation('Active?', 'no')
        ->expectsOutputToContain('⚠ ')
        ->assertExitCode(Command::FAILURE);
});

test('resource group validation failure iterates all error fields outputting each error', function (): void {
    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-rgerrmsg2',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', '')
        ->expectsQuestion('Name (en)', '')
        ->expectsQuestion('Slug', 'valid-slug2')
        ->expectsQuestion('Singular (de)', '')
        ->expectsQuestion('Singular (en)', '')
        ->expectsQuestion('Plural (de)', '')
        ->expectsQuestion('Plural (en)', '')
        ->expectsQuestion('Description (de)', '')
        ->expectsQuestion('Description (en)', '')
        ->expectsConfirmation('Active?', 'no')
        ->expectsOutputToContain('⚠')
        ->assertExitCode(Command::FAILURE);
});

test('renderValidationErrors outputs warning prefix immediately before error message text', function (): void {
    $expectedMessage = '⚠ '.trans('validation.required', [
        'attribute' => trans('validation.attributes.title'),
    ]);

    $this->artisan('roomz:create-institution', [
        '--title' => 'Library',
        '--short-title' => 'LIB',
        '--slug' => 'library-concat-98',
        '--location' => 'Berlin',
        '--home-uri' => 'https://example.test',
        '--email' => 'library@example.test',
        '--logo-uri' => 'https://example.test/logo.png',
        '--teaser-uri' => 'https://example.test/teaser.png',
        '--active' => 'yes',
    ])
        ->expectsQuestion('Active week days', ['Monday'])
        ->expectsConfirmation('Are you sure you want to create this institution?', 'yes')
        ->expectsConfirmation('Do you want to create a resource group for this institution?', 'yes')
        ->expectsQuestion('Name (de)', '')
        ->expectsQuestion('Name (en)', '')
        ->expectsQuestion('Slug', 'valid-slug-concat')
        ->expectsQuestion('Singular (de)', '')
        ->expectsQuestion('Singular (en)', '')
        ->expectsQuestion('Plural (de)', '')
        ->expectsQuestion('Plural (en)', '')
        ->expectsQuestion('Description (de)', '')
        ->expectsQuestion('Description (en)', '')
        ->expectsConfirmation('Active?', 'no')
        ->expectsOutputToContain($expectedMessage)
        ->assertExitCode(Command::FAILURE);
});
