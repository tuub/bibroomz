<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

covers(UserPolicy::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('viewAny returns true when user has view_users permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($user, $institution, 'view_users');

    expect($policy->viewAny($user))->toBeTrue();
});

test('viewAny returns false when user has no permissions', function (): void {
    $user = User::factory()->create();
    $policy = new UserPolicy;

    expect($policy->viewAny($user))->toBeFalse();
});

test('create returns true when user has create_users permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($user, $institution, 'create_users');

    expect($policy->create($user))->toBeTrue();
});

test('create returns false when user lacks create_users permission', function (): void {
    $user = User::factory()->create();
    $policy = new UserPolicy;

    expect($policy->create($user))->toBeFalse();
});

test('update returns false when target is admin and actor lacks edit_admin_users', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $adminTarget = User::factory()->create(['is_admin' => true]);
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'edit_users');

    expect($policy->update($actor, $adminTarget))->toBeFalse();
});

test('update returns true when target is non-admin and actor has edit_users', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $normalTarget = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'edit_users');

    expect($policy->update($actor, $normalTarget))->toBeTrue();
});

test('update returns true when target is admin and actor has both edit_admin_users and edit_users', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $adminTarget = User::factory()->create(['is_admin' => true]);
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'edit_users');
    $this->grantPermission($actor, $institution, 'edit_admin_users');

    expect($policy->update($actor, $adminTarget))->toBeTrue();
});

test('edit delegates to update and returns the same result', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'edit_users');

    expect($policy->edit($actor, $target))->toBe($policy->update($actor, $target));
});

test('delete returns false when target is admin and actor lacks delete_admin_users', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $adminTarget = User::factory()->create(['is_admin' => true]);
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'delete_users');

    expect($policy->delete($actor, $adminTarget))->toBeFalse();
});

test('delete returns true when target is non-admin and actor has delete_users', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $normalTarget = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'delete_users');

    expect($policy->delete($actor, $normalTarget))->toBeTrue();
});

test('delete returns true when target is admin and actor has both delete_admin_users and delete_users', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $adminTarget = User::factory()->create(['is_admin' => true]);
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'delete_users');
    $this->grantPermission($actor, $institution, 'delete_admin_users');

    expect($policy->delete($actor, $adminTarget))->toBeTrue();
});

test('ban delegates to edit and returns the same result', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'edit_users');

    expect($policy->ban($actor, $target))->toBe($policy->edit($actor, $target));
});

test('unban delegates to edit and returns the same result', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'edit_users');

    expect($policy->unban($actor, $target))->toBe($policy->edit($actor, $target));
});

test('impersonate returns true when actor is admin and target is a different user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);
    $policy = new UserPolicy;

    expect($policy->impersonate($admin, $target))->toBeTrue();
});

test('impersonate returns true when actor is admin and target is another admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $otherAdmin = User::factory()->create(['is_admin' => true]);
    $policy = new UserPolicy;

    expect($policy->impersonate($admin, $otherAdmin))->toBeTrue();
});

test('impersonate returns false when actor is not admin', function (): void {
    $actor = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create(['is_admin' => false]);
    $policy = new UserPolicy;

    expect($policy->impersonate($actor, $target))->toBeFalse();
});

test('impersonate returns false when target is the actor themselves', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $policy = new UserPolicy;

    expect($policy->impersonate($admin, $admin))->toBeFalse();
});
