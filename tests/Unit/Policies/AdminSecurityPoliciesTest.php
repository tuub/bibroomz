<?php

covers(
    App\Policies\HappeningPolicy::class,
    App\Policies\ResourceGroupPolicy::class,
    App\Policies\ResourcePolicy::class,
    App\Policies\SettingPolicy::class,
    App\Policies\ClosingPolicy::class,
    App\Policies\InstitutionPolicy::class,
    App\Policies\MailContentPolicy::class,
    App\Policies\RolePolicy::class
);

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Policies\ResourceGroupPolicy;
use App\Policies\UserGroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('resource group view any accepts create permission for same institution', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'create_resource_groups');

    expect((new ResourceGroupPolicy())->viewAny($user, $institution))->toBeTrue();
});

test('resource group view any rejects unrelated permissions', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'view_users');

    expect((new ResourceGroupPolicy())->viewAny($user, $institution))->toBeFalse();
});

test('user group view any accepts any user group permission', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'delete_user_groups');

    expect((new UserGroupPolicy())->viewAny($user))->toBeTrue();
});

test('user group create any requires create permission', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect((new UserGroupPolicy())->createAny($user))->toBeFalse();
});

test('user group import is scoped to institution permission', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Protected'],
        'institution_id' => $institution->id,
    ]);

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect((new UserGroupPolicy())->import($user, $userGroup))->toBeTrue();
});
