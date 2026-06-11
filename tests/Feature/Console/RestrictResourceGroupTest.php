<?php

declare(strict_types=1);

use App\Console\Commands\RestrictResourceGroup;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\UserGroup;
use App\Services\Console\ResourceGroupRestrictionInputCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Symfony\Component\Console\Command\Command;

covers(RestrictResourceGroup::class);

uses(RefreshDatabase::class);

test('handle returns INVALID when user does not confirm', function (): void {
    // RemoveFunctionCall would remove error('⚠ Cancelled.').
    // RemoveMethodCall would remove setLocale.
    // We test by mocking the collector and rejecting the confirmation.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $this->mock(ResourceGroupRestrictionInputCollector::class, function (MockInterface $mock) use ($resourceGroup): void {
        $mock->shouldReceive('collect')->andReturn([
            'resource_group' => $resourceGroup,
            'user_group_ids' => [],
        ]);
    });

    $this->artisan(RestrictResourceGroup::class)
        ->expectsConfirmation(
            'Are you sure you want to restrict this resource group to the selected user groups?',
            'no'
        )
        ->assertExitCode(Command::INVALID);
});

test('handle executes restriction and returns SUCCESS', function (): void {
    // RemoveMethodCall would remove $this->info('Success.').
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);

    $this->mock(ResourceGroupRestrictionInputCollector::class, function (MockInterface $mock) use ($resourceGroup, $userGroup): void {
        $mock->shouldReceive('collect')->andReturn([
            'resource_group' => $resourceGroup,
            'user_group_ids' => [$userGroup->id],
        ]);
    });

    $this->artisan(RestrictResourceGroup::class)
        ->expectsConfirmation(
            'Are you sure you want to restrict this resource group to the selected user groups?',
            'yes'
        )
        ->assertExitCode(Command::SUCCESS);

    // Verify the restriction was applied (RemoveMethodCall on execute would prevent this)
    expect($resourceGroup->user_groups()->count())->toBe(1);
});
