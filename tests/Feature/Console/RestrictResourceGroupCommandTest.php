<?php

use App\Console\Commands\RestrictResourceGroup;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\UserGroup;
use App\Services\Console\ResourceGroupRestrictionInputCollector;
use App\Services\Console\RestrictResourceGroupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

covers(
    RestrictResourceGroup::class,
    ResourceGroupRestrictionInputCollector::class,
    RestrictResourceGroupAction::class,
);

uses(RefreshDatabase::class);

test('restrict resource group command follows the interactive flow', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['title' => ['en' => 'Rooms']]);
    $firstUserGroup = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Tutors']]);
    $secondUserGroup = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Staff']]);

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

    expect($resourceGroup->fresh()?->user_groups->pluck('id')->all())->toBe([$firstUserGroup->id]);
});

test('restrict resource group command cancels when user declines confirmation', function (): void {
    $institution = Institution::factory()->create(['title' => 'Library']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['title' => ['en' => 'Rooms']]);
    $userGroup = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Tutors']]);

    $this->artisan('roomz:restrict-resource-group')
        ->expectsQuestion('Select an institution', $institution->title)
        ->expectsQuestion('Select a resource group', $resourceGroup->title)
        ->expectsQuestion(
            'Select some user groups to restrict this resource group to',
            [$userGroup->title],
        )
        ->expectsConfirmation(
            'Are you sure you want to restrict this resource group to the selected user groups?',
            'no',
        )
        ->assertExitCode(Command::INVALID);

    expect($resourceGroup->fresh()?->user_groups()->exists())->toBeFalse();
});
