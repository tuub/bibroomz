<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\UserGroup;
use App\Services\Console\RestrictResourceGroupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(RestrictResourceGroupAction::class);

uses(RefreshDatabase::class);

test('restrict resource group action restricts to user groups', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup1 = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group1']]);
    $userGroup2 = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group2']]);

    $action = app(RestrictResourceGroupAction::class);
    $action->execute($resourceGroup, [$userGroup1->id, $userGroup2->id]);

    /** @var ResourceGroup $fresh */
    $fresh = ResourceGroup::findOrFail($resourceGroup->id);
    expect($fresh->user_groups()->count())->toBe(2)
        ->and($fresh->user_groups()->where('user_groups.id', $userGroup1->id)->exists())->toBeTrue();
});

test('restrict resource group action updates existing restrictions', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup1 = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group1']]);
    $userGroup2 = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group2']]);

    $action = app(RestrictResourceGroupAction::class);
    $action->execute($resourceGroup, [$userGroup1->id]);

    /** @var ResourceGroup $after1 */
    $after1 = ResourceGroup::findOrFail($resourceGroup->id);
    expect($after1->user_groups()->count())->toBe(1);

    $action->execute($resourceGroup, [$userGroup2->id]);

    /** @var ResourceGroup $after2 */
    $after2 = ResourceGroup::findOrFail($resourceGroup->id);
    expect($after2->user_groups()->count())->toBe(1)
        ->and($after2->user_groups()->where('user_groups.id', $userGroup2->id)->exists())->toBeTrue();
});

test('restrict resource group action handles empty user groups list', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $action = app(RestrictResourceGroupAction::class);
    $action->execute($resourceGroup, []);

    /** @var ResourceGroup $fresh */
    $fresh = ResourceGroup::findOrFail($resourceGroup->id);
    expect($fresh->user_groups()->count())->toBe(0);
});
