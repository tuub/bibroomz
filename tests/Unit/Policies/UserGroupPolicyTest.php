<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Policies\UserGroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

covers(UserGroupPolicy::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('viewAny returns true when user has view_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'view_user_groups');

    expect($policy->viewAny($user))->toBeTrue();
});

test('viewAny returns false when user has no user group permissions', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'view_users');

    expect($policy->viewAny($user))->toBeFalse();
});

test('viewAny returns true when user has any of the other user group permissions', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'delete_user_groups');

    expect($policy->viewAny($user))->toBeTrue();
});

test('viewAny returns true when user has create_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'create_user_groups');

    expect($policy->viewAny($user))->toBeTrue();
});

test('viewAny returns true when user has edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect($policy->viewAny($user))->toBeTrue();
});

test('createAny returns true when user has create_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'create_user_groups');

    expect($policy->createAny($user))->toBeTrue();
});

test('createAny returns false when user lacks create_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect($policy->createAny($user))->toBeFalse();
});

test('view returns true when user has view_user_groups on the user group institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Test Group'],
        'institution_id' => $institution->id,
    ]);
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'view_user_groups');

    expect($policy->view($user, $userGroup))->toBeTrue();
});

test('create returns true when user has create_user_groups on the institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'create_user_groups');

    expect($policy->create($user, $institution))->toBeTrue();
});

test('update returns true when user has edit_user_groups on the user group institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Edit Group'],
        'institution_id' => $institution->id,
    ]);
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect($policy->update($user, $userGroup))->toBeTrue();
});

test('edit delegates to update and returns the same result', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Edit Delegate'],
        'institution_id' => $institution->id,
    ]);
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect($policy->edit($user, $userGroup))->toBe($policy->update($user, $userGroup));
});

test('delete returns true when user has delete_user_groups on the user group institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Delete Group'],
        'institution_id' => $institution->id,
    ]);
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'delete_user_groups');

    expect($policy->delete($user, $userGroup))->toBeTrue();
});

test('import returns true when user has edit_user_groups on the user group institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Import Group'],
        'institution_id' => $institution->id,
    ]);
    $policy = new UserGroupPolicy;

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect($policy->import($user, $userGroup))->toBeTrue();
});
