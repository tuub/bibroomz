<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserGroupController;
use App\Http\Requests\Admin\UserGroupIdRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Admin\UserGroupAdminService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Response;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Concerns\InteractsWithPermissions;

covers(
    UserGroupController::class,
    UserGroupAdminService::class,
);

uses(MockeryPHPUnitIntegration::class, InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForUserGroups(Institution $institution): User
{
    $actor = User::factory()->create();
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

// ---------------------------------------------------------------------------
// From AdminAuthorizationTest — user group authorization test
// ---------------------------------------------------------------------------

test('user with unrelated admin access cannot remove members from foreign user groups', function (): void {
    $actorInstitution = Institution::factory()->create();
    $targetInstitution = Institution::factory()->create();
    $actor = User::factory()->create();
    $member = User::factory()->create();

    grantAdminPermission($actor, $actorInstitution, 'view_users');

    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Protected Group'),
        'institution_id' => $targetInstitution->id,
    ]);
    $userGroup->users()->attach($member);

    $this->actingAs($actor)
        ->post(route('admin.user_group.users.remove'), ['id' => $userGroup->id, 'users' => [$member->id]])
        ->assertForbidden();

    $this->assertDatabaseHas('user_group_user', ['user_group_id' => $userGroup->id, 'user_id' => $member->id]);
});

// ---------------------------------------------------------------------------
// From Http/Controllers/Admin/UserGroupControllerTest
// ---------------------------------------------------------------------------

test('getUserGroups returns 403 for user without view_user_groups permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('viewAny', UserGroup::class)
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.user_group.index'))
        ->assertForbidden();
});

test('createUserGroup returns 403 for user without create permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('createAny', UserGroup::class)
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.user_group.create'))
        ->assertForbidden();
});

test('editUserGroup returns 403 for user without edit permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('edit', $userGroup)
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.user_group.edit', ['id' => $group->id]))
        ->assertForbidden();
});

test('importForm returns 403 for user without import permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('import', $userGroup)
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.user_group.import', ['id' => $group->id]))
        ->assertForbidden();
});

test('getUsers returns 403 for user without import permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('import', $userGroup)
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.user_group.users', ['id' => $group->id]))
        ->assertForbidden();
});

test('getUserGroups throws before service handoff for a user without view permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(UserGroupAdminService::class);
    $service->shouldNotReceive('getIndexData');

    expect(fn (): Response => (new UserGroupController($service))->getUserGroups())
        ->toThrow(AuthorizationException::class);
});

test('createUserGroup throws before service handoff for a user without create permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(UserGroupAdminService::class);
    $service->shouldNotReceive('getCreateFormData');

    expect(fn (): Response => (new UserGroupController($service))->createUserGroup())
        ->toThrow(AuthorizationException::class);
});

test('editUserGroup throws before service handoff for a user without edit permission', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(UserGroupAdminService::class);
    $request = Mockery::mock(UserGroupIdRequest::class);
    $request->shouldReceive('userGroup')->once()->andReturn($group);
    $service->shouldNotReceive('getEditFormData');

    expect(fn (): Response => (new UserGroupController($service))->editUserGroup($request))
        ->toThrow(AuthorizationException::class);
});

test('importForm throws before service handoff for a user without import permission', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(UserGroupAdminService::class);
    $request = Mockery::mock(UserGroupIdRequest::class);
    $request->shouldReceive('userGroup')->once()->andReturn($group);
    $service->shouldNotReceive('getImportFormData');

    expect(fn (): Response => (new UserGroupController($service))->importForm($request))
        ->toThrow(AuthorizationException::class);
});

test('getUsers throws before service handoff for a user without import permission', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(UserGroupAdminService::class);
    $request = Mockery::mock(UserGroupIdRequest::class);
    $request->shouldReceive('userGroup')->once()->andReturn($group);
    $service->shouldNotReceive('getUsersData');

    expect(fn (): Response => (new UserGroupController($service))->getUsers($request))
        ->toThrow(AuthorizationException::class);
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — user group tests
// ---------------------------------------------------------------------------

test('scoped admin without create_user_groups cannot store user group', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.store'), [
            'institution_id' => $institution->id,
            'title' => Utility::getTranslatable('Blocked Group'),
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('user_groups', ['institution_id' => $institution->id]);
});

test('scoped admin without edit_user_groups cannot update user group', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Existing Group'),
        'institution_id' => $institution->id,
    ]);
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.update'), [
            'id' => $userGroup->id,
            'institution_id' => $institution->id,
            'title' => Utility::getTranslatable('Unauthorized Rename'),
        ])
        ->assertForbidden();

    expect($userGroup->fresh()?->getTranslations('title')['en'])->toBe('Existing Group');
});

// ---------------------------------------------------------------------------
// Success paths
// ---------------------------------------------------------------------------

test('storeUserGroup returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_user_groups');
    $this->actingAs($actor);

    $this->post(route('admin.user_group.store'), [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('New Group'),
    ])->assertRedirect(route('admin.user_group.index'));

    $this->assertDatabaseHas('user_groups', ['institution_id' => $institution->id]);
});

test('updateUserGroup returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Old Name'),
        'institution_id' => $institution->id,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_user_groups');
    $this->actingAs($actor);

    $this->post(route('admin.user_group.update'), [
        'id' => $userGroup->id,
        'title' => Utility::getTranslatable('New Name'),
    ])->assertRedirect(route('admin.user_group.index'));

    expect($userGroup->fresh()?->getTranslation('title', 'en'))->toBe('New Name');
});

test('importForm returns ok for authorized user', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Group'),
        'institution_id' => $institution->id,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_user_groups');
    $this->actingAs($actor);

    $this->get(route('admin.user_group.import', ['id' => $userGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/UserGroups/Import')
            ->where('user_group.id', $userGroup->id));
});

test('getUsers returns ok for authorized user', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Users Group'),
        'institution_id' => $institution->id,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_user_groups');
    $this->actingAs($actor);

    $this->get(route('admin.user_group.users', ['id' => $userGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/UserGroups/Users')
            ->where('user_group.id', $userGroup->id));
});

test('importUsers returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Target'),
        'institution_id' => $institution->id,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_user_groups');
    $this->actingAs($actor);

    $this->post(route('admin.user_group.users.import'), [
        'id' => $userGroup->id,
        'users' => [['name' => 'imported.member']],
        'valid_from_date' => '2026-06-01',
        'valid_until_date' => '2026-12-31',
    ])->assertRedirect(route('admin.user_group.index'));

    expect($userGroup->fresh()?->users()->count())->toBe(1);
});

test('removeUsers returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Remove Target'),
        'institution_id' => $institution->id,
    ]);
    $member = User::factory()->create();
    $userGroup->users()->attach($member->id);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_user_groups');
    $this->actingAs($actor);

    $this->post(route('admin.user_group.users.remove'), [
        'id' => $userGroup->id,
        'users' => [$member->id],
    ])->assertRedirect(route('admin.user_group.users', ['id' => $userGroup->id]));

    expect($userGroup->fresh()?->users()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editUserGroup returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.user_group.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('storeUserGroup returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_user_groups');

    // Provide institution_id to pass authorize(), but omit required title
    $this->actingAs($actor)
        ->post(route('admin.user_group.store'), [
            'institution_id' => $institution->id,
            'title' => ['en' => '', 'de' => ''],
        ])
        ->assertRedirect();
});

test('updateUserGroup returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Existing'),
        'institution_id' => $institution->id,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_user_groups');

    // Provide id to pass authorize(), but provide empty title to fail RequiredWithTranslationRule
    $this->actingAs($actor)
        ->post(route('admin.user_group.update'), [
            'id' => $userGroup->id,
            'title' => ['en' => '', 'de' => ''],
        ])
        ->assertRedirect();
});

test('importUsers returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Validation'),
        'institution_id' => $institution->id,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_user_groups');

    $this->actingAs($actor)
        ->post(route('admin.user_group.users.import'), ['id' => $userGroup->id])
        ->assertRedirect();
});

test('importForm returns redirect for non-existent user group id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.user_group.import', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('getUsers returns redirect for non-existent user group id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.user_group.users', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('importUsers returns 403 for user without edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Protected Group'),
        'institution_id' => $institution->id,
    ]);
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.users.import'), [
            'id' => $userGroup->id,
            'users' => [['name' => 'new.member']],
            'valid_from_date' => null,
            'valid_until_date' => null,
        ])
        ->assertForbidden();

    expect($userGroup->fresh()?->users()->count())->toBe(0);
});

test('removeUsers returns 403 for user without edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Protected Group'),
        'institution_id' => $institution->id,
    ]);
    $member = User::factory()->create();
    $userGroup->users()->attach($member->id);
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.users.remove'), [
            'id' => $userGroup->id,
            'users' => [$member->id],
        ])
        ->assertForbidden();

    expect($userGroup->fresh()?->users()->count())->toBe(1);
});

test('scoped admin without delete_user_groups cannot delete user group', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Protected Group'),
        'institution_id' => $institution->id,
    ]);
    $actor = buildScopedActorForUserGroups($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.delete'), ['id' => $userGroup->id])
        ->assertForbidden();

    $this->assertDatabaseHas('user_groups', ['id' => $userGroup->id]);
});
