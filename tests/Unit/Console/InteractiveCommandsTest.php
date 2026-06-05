<?php

covers(
    App\Console\Commands\CreateInstitutionCommand::class,
    App\Console\Commands\CreateUserGroup::class,
    App\Console\Commands\RestrictResourceGroup::class,
    App\Services\Console\CreateInstitutionAction::class,
    App\Services\Console\CreateInstitutionResourceGroupAction::class,
    App\Services\Console\CreateUserGroupAction::class,
    App\Services\Console\InstitutionInputCollector::class,
    App\Services\Console\UserGroupInputCollector::class,
    App\Services\Console\ResourceGroupRestrictionInputCollector::class,
    App\Services\Console\RestrictResourceGroupAction::class
);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\UserGroup;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
});

test('create institution command validates input without exiting the process', function () {
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

test('create institution command creates an institution and an optional resource group through prompts', function () {
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

    $institution = Institution::firstWhere('slug', 'library');

    expect($institution)->not->toBeNull()
        ->and($institution->settings)->toHaveCount(count(\App\Models\Setting::getInitialValues()['institution']))
        ->and(ResourceGroup::firstWhere('slug', 'rooms'))->not->toBeNull();
});

test('create user group and restrict resource group commands follow the interactive flow', function () {
    $institution = Institution::factory()->create(['title' => 'Library']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['title' => ['en' => 'Rooms']]);
    $firstUserGroup = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Tutors']]);
    $secondUserGroup = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Staff']]);

    $this->artisan('roomz:create-user-group')
        ->expectsQuestion('Title (de)', 'Hilfskraefte')
        ->expectsQuestion('Title (en)', 'Assistants')
        ->expectsQuestion('Which institution does this user group belong to?', $institution->title)
        ->expectsConfirmation('Are you sure you want to create this user group?', 'yes')
        ->assertExitCode(Command::SUCCESS);

    $createdGroup = UserGroup::query()->where('institution_id', $institution->id)->latest('id')->first();
    expect($createdGroup?->getTranslation('title', 'en'))->toBe('Assistants');

    $this->artisan('roomz:restrict-resource-group')
        ->expectsQuestion('Select an institution', $institution->title)
        ->expectsQuestion('Select a resource group', $resourceGroup->title)
        ->expectsQuestion(
            'Select some user groups to restrict this resource group to',
            [$firstUserGroup->title],
        )
        ->expectsConfirmation(
            'Are you sure you want to restrict this resource group to the selected user groups?',
            'yes',
        )
        ->assertExitCode(Command::SUCCESS);

    expect($resourceGroup->fresh()->user_groups->pluck('id')->all())->toBe([$firstUserGroup->id]);
});
