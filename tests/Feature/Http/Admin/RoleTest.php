<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\RoleController;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\RoleAdminService;
use App\Services\Admin\UserRoleSynchronizer;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;

covers(
    RoleController::class,
    RoleAdminService::class,
    UserRoleSynchronizer::class,
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    config()->set('broadcasting.default', 'log');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * @return array<string, string>
 */
function adminRoleFeatureTranslatable(string $value): array
{
    return Utility::getTranslatable($value);
}

/**
 * @param  list<string>  $permissions
 */
function actingRoleFeatureAdmin(Institution $institution, array $permissions): User
{
    $user = User::factory()->create([
        'is_system_user' => true,
        'is_admin' => false,
    ]);

    foreach ($permissions as $permission) {
        grantAdminPermission($user, $institution, $permission);
    }

    test()->actingAs($user);

    return $user;
}

// ---------------------------------------------------------------------------
// From Http/Controllers/Admin/RoleControllerTest
// ---------------------------------------------------------------------------

test('getRoles returns 403 for user without view_roles permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('viewAny', Role::class)
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.role.index'))
        ->assertForbidden();
});

test('createRole returns 403 for user without create permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('create', Role::class)
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.role.create'))
        ->assertForbidden();
});

test('editRole returns 403 for user without edit permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('edit', $role)
    $role = Role::create(['name' => Utility::getTranslatable('test-edit-role')]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.role.edit', ['id' => $role->id]))
        ->assertForbidden();
});

test('storeRole returns 403 for user without create_roles permission', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->post(route('admin.role.store'), [])
        ->assertForbidden();
});

test('updateRole returns 403 for user without edit_roles permission', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => Utility::getTranslatable('existing-role')]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->post(route('admin.role.update'), ['id' => $role->id])
        ->assertForbidden();
});

test('deleteRole returns 403 for user without delete_roles permission', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => Utility::getTranslatable('deletable-role')]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->post(route('admin.role.delete'), ['id' => $role->id])
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('editRole returns redirect for non-existent role id', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->get(route('admin.role.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// 422 on validation failure (JSON POST)
// ---------------------------------------------------------------------------

test('storeRole returns redirect when name is empty in all locales', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_roles');
    $this->actingAs($actor);

    // An array with empty locale values fails RequiredWithTranslationRule → redirect
    $this->post(route('admin.role.store'), [
        'name' => ['en' => '', 'de' => ''],
        'permissions' => [],
    ])->assertRedirect();
});

test('updateRole returns redirect when name is empty in all locales', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => Utility::getTranslatable('updatable-role')]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_roles');
    $this->actingAs($actor);

    // Provides a valid id but empty locale values for name → fails RequiredWithTranslationRule → redirect
    $this->post(route('admin.role.update'), [
        'id' => $role->id,
        'name' => ['en' => '', 'de' => ''],
        'permissions' => [],
    ])->assertRedirect();
});

// ---------------------------------------------------------------------------
// From AdminPeopleFlowTest — role-related part of the people flow test
// ---------------------------------------------------------------------------

test('people admin role routes render and mutate roles', function (): void {
    $institution = Institution::factory()->create();
    actingRoleFeatureAdmin($institution, [
        'view_roles',
        'create_roles',
        'edit_roles',
        'delete_roles',
        'view_users',
        'create_users',
        'edit_users',
        'delete_users',
        'view_user_groups',
        'create_user_groups',
        'edit_user_groups',
        'delete_user_groups',
        'edit_institutions',
    ]);

    $permission = Permission::query()->firstOrFail();

    $this->get(route('admin.role.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Roles/Index')
            ->has('roles'));

    $this->get(route('admin.role.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Roles/Form')
            ->has('permissions')
            ->has('groups'));

    $this->post(route('admin.role.store'), [
        'name' => adminRoleFeatureTranslatable('Operators'),
        'description' => adminRoleFeatureTranslatable('Resource operators'),
        'permissions' => [$permission->id],
    ])->assertRedirect(route('admin.role.index'));

    $role = Role::query()->get()->firstOrFail(
        fn (Role $candidate): bool => $candidate->getTranslation('name', 'en') === 'Operators',
    );

    $this->get(route('admin.role.edit', ['id' => $role->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Roles/Form')
            ->where('role.id', $role->id));

    $this->post(route('admin.role.update'), [
        'id' => $role->id,
        'name' => adminRoleFeatureTranslatable('Senior Operators'),
        'description' => adminRoleFeatureTranslatable('Updated operators'),
        'permissions' => [$permission->id],
    ])->assertRedirect(route('admin.role.index'));

    expect($role->fresh()?->getTranslation('name', 'en'))->toBe('Senior Operators');

    $this->post(route('admin.role.delete'), ['id' => $role->id])
        ->assertRedirect(route('admin.role.index'));

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});
