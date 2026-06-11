<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Policies\ResourceGroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

covers(ResourceGroupPolicy::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('viewAny returns false when user has no resource group permissions', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new ResourceGroupPolicy;

    expect($policy->viewAny($user, $institution))->toBeFalse();
});

test('viewAny returns true when user has view_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'view_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->viewAny($user, $institution))->toBeTrue();
});

test('viewAny returns true when user has create_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->viewAny($user, $institution))->toBeTrue();
});

test('viewAny returns true when user has edit_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->viewAny($user, $institution))->toBeTrue();
});

test('viewAny returns true when user has delete_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->viewAny($user, $institution))->toBeTrue();
});

test('view returns true with view_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'view_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->view($user, $rg))->toBeTrue();
});

test('create returns true with create_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->create($user, $institution))->toBeTrue();
});

test('update returns true with edit_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->update($user, $rg))->toBeTrue();
});

test('delete returns true with delete_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->delete($user, $rg))->toBeTrue();
});

test('edit delegates to update', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_resource_groups');
    $policy = new ResourceGroupPolicy;

    expect($policy->edit($user, $rg))->toBe($policy->update($user, $rg));
});
