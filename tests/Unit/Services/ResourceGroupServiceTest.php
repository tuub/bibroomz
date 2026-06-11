<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\ResourceGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

covers(ResourceGroupService::class);

uses(RefreshDatabase::class);

test('getResourceGroupById returns the resource group', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = new ResourceGroupService;
    $found = $service->getResourceGroupById($rg->id);

    expect($found->id)->toBe($rg->id);
});

test('deleteResourceGroup deletes and returns the resource group', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $id = $rg->id;

    $service = new ResourceGroupService;
    $deleted = $service->deleteResourceGroup($id);

    expect($deleted->id)->toBe($id)
        ->and(ResourceGroup::find($id))->toBeNull();
});

test('getResourceGroupsForUser returns collection', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $service = new ResourceGroupService;
    $result = $service->getResourceGroupsForUser($user);

    expect($result)->toBeInstanceOf(Collection::class);
});
