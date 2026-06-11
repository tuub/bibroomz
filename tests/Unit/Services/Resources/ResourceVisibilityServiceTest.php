<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Resources\ResourceVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceVisibilityService::class);

uses(RefreshDatabase::class);

test('isEditableByUser returns false for regular user', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $service = new ResourceVisibilityService;

    expect($service->isEditableByUser($resource, $user))->toBeFalse();
});

test('isEditableByUser returns true for admin user', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $service = new ResourceVisibilityService;

    expect($service->isEditableByUser($resource, $admin))->toBeTrue();
});

test('isViewableByUser returns false for non-admin user', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $service = new ResourceVisibilityService;

    expect($service->isViewableByUser($resource, $user))->toBeFalse();
});
