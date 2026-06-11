<?php

use App\Console\Commands\CreateUserGroup;
use App\Models\Institution;
use App\Models\UserGroup;
use App\Services\Console\CreateUserGroupAction;
use App\Services\Console\UserGroupInputCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

covers(
    CreateUserGroup::class,
    UserGroupInputCollector::class,
    CreateUserGroupAction::class,
);

uses(RefreshDatabase::class);

test('create user group command follows the interactive flow', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', 'Hilfskraefte')
        ->expectsQuestion('Title (en)', 'Assistants')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsConfirmation('Are you sure you want to create this user group?', 'yes')
        ->assertExitCode(Command::SUCCESS);

    $createdGroup = UserGroup::query()->where('institution_id', $institution->id)->latest('id')->first();
    expect($createdGroup?->getTranslation('title', 'en'))->toBe('Assistants');
});

test('create user group command validates empty translations', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', '')
        ->expectsQuestion('Title (en)', '')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->assertExitCode(Command::FAILURE);

    expect(UserGroup::where('institution_id', $institution->id)->exists())->toBeFalse();
});

test('create user group command cancels when user declines confirmation', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', 'Hilfskraefte')
        ->expectsQuestion('Title (en)', 'Assistants')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsConfirmation('Are you sure you want to create this user group?', 'no')
        ->assertExitCode(Command::INVALID);

    expect(UserGroup::where('institution_id', $institution->id)->exists())->toBeFalse();
});
