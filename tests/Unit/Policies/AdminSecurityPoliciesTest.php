<?php

use App\Models\Happening;
use App\Models\Institution;
use App\Models\ResourceGroup;
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
    RolePolicy::class,
    UserGroupPolicy::class
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

// ── InstitutionPolicy — global permission branches ────────────────────────────

test('institution policy view returns true for global view_institutions permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new InstitutionPolicy;

    $this->grantPermission($user, $institution, 'view_institutions');

    expect($policy->view($user, $institution))->toBeTrue();
});

test('institution policy update returns true for global edit_institutions permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new InstitutionPolicy;

    $this->grantPermission($user, $institution, 'edit_institutions');

    expect($policy->update($user, $institution))->toBeTrue();
});

test('institution policy delete returns true for global delete_institutions permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new InstitutionPolicy;

    $this->grantPermission($user, $institution, 'delete_institutions');

    expect($policy->delete($user, $institution))->toBeTrue();
});

test('institution policy returns false when no matching permissions are granted', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new InstitutionPolicy;

    expect($policy->view($user, $institution))->toBeFalse()
        ->and($policy->create($user))->toBeFalse()
        ->and($policy->update($user, $institution))->toBeFalse()
        ->and($policy->delete($user, $institution))->toBeFalse();
});

// ── ResourceGroupPolicy — all methods ────────────────────────────────────────

test('resource group policy create view update delete and clone require scoped permissions', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $policy = new ResourceGroupPolicy;

    $this->grantPermission($user, $institution, 'view_resource_groups');
    $this->grantPermission($user, $institution, 'create_resource_groups');
    $this->grantPermission($user, $institution, 'edit_resource_groups');
    $this->grantPermission($user, $institution, 'delete_resource_groups');

    expect($policy->viewAny($user, $institution))->toBeTrue()
        ->and($policy->view($user, $resourceGroup))->toBeTrue()
        ->and($policy->create($user, $institution))->toBeTrue()
        ->and($policy->update($user, $resourceGroup))->toBeTrue()
        ->and($policy->edit($user, $resourceGroup))->toBeTrue()
        ->and($policy->delete($user, $resourceGroup))->toBeTrue()
        ->and($policy->clone($user, $resourceGroup))->toBeTrue();
});

test('resource group policy returns false when no permissions are granted', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $policy = new ResourceGroupPolicy;

    expect($policy->viewAny($user, $institution))->toBeFalse()
        ->and($policy->view($user, $resourceGroup))->toBeFalse()
        ->and($policy->create($user, $institution))->toBeFalse()
        ->and($policy->update($user, $resourceGroup))->toBeFalse()
        ->and($policy->delete($user, $resourceGroup))->toBeFalse()
        ->and($policy->clone($user, $resourceGroup))->toBeFalse();
});

// ── HappeningPolicy — update rejects missing user1 ───────────────────────────

test('happening policy update returns false when happening has no user1', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'user_id_01' => null,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'label' => ['en' => 'Test'],
    ]);

    $policy = new HappeningPolicy;

    expect($policy->update($user, $happening))->toBeFalse();
});
