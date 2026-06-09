<?php

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Policies\ClosingPolicy;
use App\Policies\HappeningPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\MailContentPolicy;
use App\Policies\ResourceGroupPolicy;
use App\Policies\ResourcePolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserGroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

covers(
    HappeningPolicy::class,
    ResourceGroupPolicy::class,
    ResourcePolicy::class,
    SettingPolicy::class,
    ClosingPolicy::class,
    InstitutionPolicy::class,
    MailContentPolicy::class,
    RolePolicy::class
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('resource group view any accepts create permission for same institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'create_resource_groups');

    expect((new ResourceGroupPolicy)->viewAny($user, $institution))->toBeTrue();
});

test('resource group view any rejects unrelated permissions', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'view_users');

    expect((new ResourceGroupPolicy)->viewAny($user, $institution))->toBeFalse();
});

test('user group view any accepts any user group permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'delete_user_groups');

    expect((new UserGroupPolicy)->viewAny($user))->toBeTrue();
});

test('user group create any requires create permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect((new UserGroupPolicy)->createAny($user))->toBeFalse();
});

test('user group import is scoped to institution permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Protected'],
        'institution_id' => $institution->id,
    ]);

    $this->grantPermission($user, $institution, 'edit_user_groups');

    expect((new UserGroupPolicy)->import($user, $userGroup))->toBeTrue();
});
