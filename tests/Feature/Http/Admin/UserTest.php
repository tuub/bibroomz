<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Admin\UserAdminService;
use App\Services\Admin\UserGroupAdminService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;

covers(
    UserController::class,
    UserAdminService::class,
    UserGroupAdminService::class,
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
function adminUserFeatureTranslatable(string $value): array
{
    return Utility::getTranslatable($value);
}

/**
 * @param  list<string>  $permissions
 */
function actingUserFeatureAdmin(Institution $institution, array $permissions): User
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

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForUsers(Institution $institution): User
{
    $actor = User::factory()->create();
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

// ---------------------------------------------------------------------------
// From Http/Controllers/Admin/UserControllerTest
// ---------------------------------------------------------------------------

test('UserController can be resolved from container', function (): void {
    $controller = app(UserController::class);

    expect($controller)->toBeInstanceOf(UserController::class);
});

test('getUsers returns 403 for user without view_users permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('viewAny', User::class)
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.user.index'))
        ->assertForbidden();
});

test('createUser returns 403 for user without create permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('create', User::class)
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.user.create'))
        ->assertForbidden();
});

test('editUser returns 403 for user without edit permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('edit', $user)
    $target = User::factory()->create(['is_admin' => false]);
    $actor = User::factory()->create(['is_admin' => false]);
    $this->actingAs($actor);

    $this->get(route('admin.user.edit', ['id' => $target->id]))
        ->assertForbidden();
});

test('storeUser returns 403 for user without create_users permission', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->post(route('admin.user.store'), [])
        ->assertForbidden();
});

test('updateUser returns 403 for user without edit_users permission', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create(['is_admin' => false]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->post(route('admin.user.update'), ['id' => $target->id])
        ->assertForbidden();
});

test('deleteUser returns 403 for user without delete_users permission', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create(['is_admin' => false]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->post(route('admin.user.delete'), ['id' => $target->id])
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('unbanUser returns 403 for user without edit_users permission', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create(['is_admin' => false, 'banned_at' => now()]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->post(route('admin.user.unban'), ['id' => $target->id])
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $target->id]);
    expect($target->fresh()?->banned_at)->not->toBeNull();
});

test('storeUser returns 422 when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_users');
    $this->actingAs($actor);

    $this->postJson(route('admin.user.store'), [])
        ->assertUnprocessable();
});

test('updateUser returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create(['is_admin' => false]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_users');
    grantAdminPermission($actor, $institution, 'edit_institutions');
    $this->actingAs($actor);

    // Provides a valid id and empty roles to pass authorize(), but omits is_system_user/is_admin → redirect
    $this->post(route('admin.user.update'), [
        'id' => $target->id,
        'roles' => [],
    ])->assertRedirect();
});

test('editUser returns redirect for non-existent user id', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_users');
    $this->actingAs($actor);

    $this->get(route('admin.user.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — user test
// ---------------------------------------------------------------------------

test('scoped admin without edit_users cannot ban another user', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create();
    $actor = buildScopedActorForUsers($institution);

    $this->actingAs($actor)
        ->post(route('admin.user.ban'), ['id' => $target->id])
        ->assertForbidden();

    expect($target->fresh()?->isBanned())->toBeFalse();
});

// ---------------------------------------------------------------------------
// From AdminPeopleFlowTest — full people flow test
// ---------------------------------------------------------------------------

test('scoped admins without people create permission cannot store users', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => adminUserFeatureTranslatable('Viewer')]);

    actingUserFeatureAdmin($institution, ['view_users']);

    $this->post(route('admin.user.store'), [
        'is_system_user' => true,
        'name' => 'blocked.user',
        'email' => 'blocked@example.org',
        'is_set_password' => true,
        'password' => 'Secret123!',
        'password_confirm' => 'Secret123!',
        'is_admin' => false,
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['name' => 'blocked.user']);
});

test('people admin routes render and mutate roles users and user groups', function (): void {
    $institution = Institution::factory()->create();
    actingUserFeatureAdmin($institution, [
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
        'name' => adminUserFeatureTranslatable('Operators'),
        'description' => adminUserFeatureTranslatable('Resource operators'),
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
        'name' => adminUserFeatureTranslatable('Senior Operators'),
        'description' => adminUserFeatureTranslatable('Updated operators'),
        'permissions' => [$permission->id],
    ])->assertRedirect(route('admin.role.index'));

    expect($role->fresh()?->getTranslation('name', 'en'))->toBe('Senior Operators');

    $this->get(route('admin.user.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Users/Index')
            ->has('users'));

    $this->get(route('admin.user.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Users/Form')
            ->has('roles')
            ->has('institutions'));

    $this->post(route('admin.user.store'), [
        'is_system_user' => true,
        'name' => 'local.admin.target',
        'email' => 'target@example.org',
        'is_set_password' => true,
        'password' => 'Secret123!',
        'password_confirm' => 'Secret123!',
        'is_admin' => false,
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ])->assertRedirect(route('admin.user.index'));

    $managedUser = User::query()->where('name', 'local.admin.target')->firstOrFail();

    expect(Hash::check('Secret123!', (string) $managedUser->password))->toBeTrue()
        ->and($managedUser->roles()->count())->toBe(1);

    $this->get(route('admin.user.edit', ['id' => $managedUser->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Users/Form')
            ->where('user.id', $managedUser->id)
            ->has('roles')
            ->has('institutions'));

    $this->post(route('admin.user.update'), [
        'id' => $managedUser->id,
        'is_system_user' => true,
        'name' => 'local.admin.updated',
        'email' => 'updated-target@example.org',
        'is_set_password' => false,
        'is_admin' => false,
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ])->assertRedirect(route('admin.user.index'));

    expect($managedUser->fresh()?->name)->toBe('local.admin.updated');

    $this->post(route('admin.user.ban'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));

    expect($managedUser->fresh()?->isBanned())->toBeTrue();

    $this->post(route('admin.user.unban'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));

    expect($managedUser->fresh()?->isBanned())->toBeFalse();

    $this->get(route('admin.user_group.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/UserGroups/Index')
            ->has('user_groups'));

    $this->get(route('admin.user_group.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/UserGroups/Form')
            ->has('institutions'));

    $this->post(route('admin.user_group.store'), [
        'institution_id' => $institution->id,
        'title' => adminUserFeatureTranslatable('Researchers'),
    ])->assertRedirect(route('admin.user_group.index'));

    $userGroup = UserGroup::query()->where('institution_id', $institution->id)->firstOrFail();

    $this->get(route('admin.user_group.edit', ['id' => $userGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/UserGroups/Form')
            ->where('user_group.id', $userGroup->id));

    $this->post(route('admin.user_group.update'), [
        'id' => $userGroup->id,
        'title' => adminUserFeatureTranslatable('Updated Researchers'),
    ])->assertRedirect(route('admin.user_group.index'));

    expect($userGroup->fresh()?->getTranslation('title', 'en'))->toBe('Updated Researchers');

    $this->get(route('admin.user_group.import', ['id' => $userGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/UserGroups/Import')
            ->where('user_group.id', $userGroup->id));

    $this->post(route('admin.user_group.users.import'), [
        'id' => $userGroup->id,
        'users' => [
            ['name' => 'imported.user'],
            ['name' => 'another.user'],
        ],
        'valid_from_date' => '2026-06-01',
        'valid_until_date' => '2026-06-30',
    ])->assertRedirect(route('admin.user_group.index'));

    $this->get(route('admin.user_group.users', ['id' => $userGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/UserGroups/Users')
            ->where('user_group.id', $userGroup->id)
            ->has('users', 2));

    $importedUsers = $userGroup->fresh()?->users()->pluck('users.id')->all() ?? [];

    $this->post(route('admin.user_group.users.remove'), [
        'id' => $userGroup->id,
        'users' => $importedUsers,
    ])->assertRedirect(route('admin.user_group.users', ['id' => $userGroup->id]));

    expect($userGroup->fresh()?->users()->count())->toBe(0);

    $this->post(route('admin.user.delete'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));

    $this->post(route('admin.user_group.delete'), ['id' => $userGroup->id])
        ->assertRedirect(route('admin.user_group.index'));

    $this->post(route('admin.role.delete'), ['id' => $role->id])
        ->assertRedirect(route('admin.role.index'));

    $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);
    $this->assertDatabaseMissing('user_groups', ['id' => $userGroup->id]);
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('getFormUsers returns users for full admins authenticated through the web session', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $bookableUser = User::factory()->create([
        'name' => 'calendar.bookable.user',
        'is_admin' => false,
    ]);

    $this->actingAs($admin)->getJson('/api/admin/user/users')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $bookableUser->id,
            'name' => 'calendar.bookable.user',
            'is_admin' => false,
        ]);
});

test('getFormUsers returns 403 for non-admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->getJson('/api/admin/user/users')
        ->assertStatus(403);
});
