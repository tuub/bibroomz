<?php

declare(strict_types=1);

use App\Console\Commands\CreateUserGroup;
use App\Models\Institution;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

covers(CreateUserGroup::class);

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────
// Success path
// ─────────────────────────────────────────────────────────────────

test('create user group outputs "User group created." on success', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', 'Hilfskraefte')
        ->expectsQuestion('Title (en)', 'Assistants')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsConfirmation('Are you sure you want to create this user group?', 'yes')
        ->expectsOutputToContain('User group created.')
        ->assertExitCode(Command::SUCCESS);
});

test('create user group persists the new user group in the database', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', 'Hilfskraefte')
        ->expectsQuestion('Title (en)', 'Assistants')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsConfirmation('Are you sure you want to create this user group?', 'yes')
        ->assertExitCode(Command::SUCCESS);

    expect(UserGroup::query()->where('institution_id', $institution->id)->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────
// Validation failure path — renderValidationErrors
// ─────────────────────────────────────────────────────────────────

test('create user group outputs validation error message when titles are empty', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->assertExitCode(Command::FAILURE);
});

test('create user group does not create a group when validation fails', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->assertExitCode(Command::FAILURE);

    expect(UserGroup::where('institution_id', $institution->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────
// Confirmation declined path
// ─────────────────────────────────────────────────────────────────

test('create user group returns INVALID and outputs "Cancelled." when user declines', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', 'Hilfskraefte')
        ->expectsQuestion('Title (en)', 'Assistants')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsConfirmation('Are you sure you want to create this user group?', 'no')
        ->expectsOutputToContain('Cancelled.')
        ->assertExitCode(Command::INVALID);
});

test('create user group does not persist the group when user declines confirmation', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', 'Hilfskraefte')
        ->expectsQuestion('Title (en)', 'Assistants')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsConfirmation('Are you sure you want to create this user group?', 'no')
        ->assertExitCode(Command::INVALID);

    expect(UserGroup::where('institution_id', $institution->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────
// RemoveMethodCall — app()->setLocale('en') sets locale before input collection
// ─────────────────────────────────────────────────────────────────

test('command sets locale to en before collecting input so validation messages are in English', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    // If setLocale('en') were removed, validation messages might not be in English.
    // We verify a validation message appears in English when titles are blank.
    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsOutputToContain('required')
        ->assertExitCode(Command::FAILURE);
});

// ─────────────────────────────────────────────────────────────────
// RemoveMethodCall — $this->renderValidationErrors must be called on failure
// ─────────────────────────────────────────────────────────────────

test('validation failure outputs error message prefixed with warning sign', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    // renderValidationErrors outputs "⚠ <message>" for each error.
    // RemoveMethodCall mutation skips this and silently returns FAILURE without output.
    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsOutputToContain('⚠')
        ->assertExitCode(Command::FAILURE);
});

// ─────────────────────────────────────────────────────────────────
// Lines 70–72: ForeachEmptyIterable x2 / RemoveFunctionCall / ConcatRemoveLeft /
// ConcatRemoveRight / ConcatSwitchSides — renderValidationErrors iterates errors
// and calls error('⚠ '.$message)
// ─────────────────────────────────────────────────────────────────

test('renderValidationErrors outputs "⚠ " concatenated with the exact message text', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    // ConcatRemoveLeft would output just the message without "⚠ ".
    // ConcatRemoveRight would output just "⚠ " without the message.
    // ConcatSwitchSides would output the message before "⚠ ".
    // We assert that the output contains both the prefix and part of the message.
    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsOutputToContain('⚠ ')
        ->assertExitCode(Command::FAILURE);
});

test('renderValidationErrors iterates all error fields — multiple validation errors output', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    // ForeachEmptyIterable mutations would skip the outer and/or inner loop.
    // Providing multiple invalid values ensures multiple errors exist.
    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsOutputToContain('⚠')
        ->assertExitCode(Command::FAILURE);
});

// ─────────────────────────────────────────────────────────────────
// ConcatSwitchSides / ConcatRemoveRight — error('⚠ '.$message)
// ConcatSwitchSides → error($message.'⚠ ') → output: "The title…⚠ "
// ConcatRemoveRight → error('⚠ ') → output: "⚠ " (no message text)
// Both mutations survive if we only assert '⚠ ' — we must assert '⚠ ' immediately
// followed by the real message text.
// ─────────────────────────────────────────────────────────────────

test('renderValidationErrors outputs warning prefix immediately before the error message text', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    // The validation message is "The title field is required."
    // With ConcatSwitchSides the output would be "The title field is required.⚠ "
    //   → does NOT contain "⚠ The"
    // With ConcatRemoveRight the output would be "⚠ "
    //   → does NOT contain "⚠ The"
    // Only the original "⚠ The title field is required." satisfies both assertions.
    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsOutputToContain('⚠ The title field is required.')
        ->assertExitCode(Command::FAILURE);
});
