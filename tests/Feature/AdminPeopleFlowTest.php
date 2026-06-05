<?php

covers(
    App\Http\Controllers\Admin\UserController::class,
    App\Http\Controllers\Admin\RoleController::class,
    App\Http\Controllers\Admin\UserGroupController::class,
    App\Services\Admin\UserAdminService::class,
    App\Services\Admin\RoleAdminService::class,
    App\Services\Admin\UserGroupAdminService::class,
    App\Services\Admin\UserRoleSynchronizer::class,
    App\Http\Requests\Admin\UserRequest::class
);

use App\Library\Utility;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    config()->set('broadcasting.default', 'log');
});

function adminPeopleFeatureTranslatable(string $value): array
{
    return Utility::getTranslatable($value);
}

/**
 * @param list<string> $permissions
 */
function actingPeopleFeatureAdmin(Institution $institution, array $permissions): User
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

test('scoped admins without people create permission cannot store users', function () {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => adminPeopleFeatureTranslatable('Viewer')]);

    actingPeopleFeatureAdmin($institution, ['view_users']);

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

test('people admin routes render and mutate roles users and user groups', function () {
    $institution = Institution::factory()->create();
    actingPeopleFeatureAdmin($institution, [
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
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles'));

    $this->get(route('admin.role.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Form')
            ->has('permissions')
            ->has('groups'));

    $this->post(route('admin.role.store'), [
        'name' => adminPeopleFeatureTranslatable('Operators'),
        'description' => adminPeopleFeatureTranslatable('Resource operators'),
        'permissions' => [$permission->id],
    ])->assertRedirect(route('admin.role.index'));

    $role = Role::query()->get()->first(
        fn (Role $candidate): bool => $candidate->getTranslation('name', 'en') === 'Operators',
    );

    expect($role)->not->toBeNull();

    $this->get(route('admin.role.edit', ['id' => $role->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Form')
            ->where('role.id', $role->id));

    $this->post(route('admin.role.update'), [
        'id' => $role->id,
        'name' => adminPeopleFeatureTranslatable('Senior Operators'),
        'description' => adminPeopleFeatureTranslatable('Updated operators'),
        'permissions' => [$permission->id],
    ])->assertRedirect(route('admin.role.index'));

    expect($role->fresh()->getTranslation('name', 'en'))->toBe('Senior Operators');

    $this->get(route('admin.user.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->has('users'));

    $this->get(route('admin.user.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
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
        ->assertInertia(fn (Assert $page) => $page
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

    expect($managedUser->fresh()->name)->toBe('local.admin.updated');

    $this->post(route('admin.user.ban'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));

    expect($managedUser->fresh()->isBanned())->toBeTrue();

    $this->post(route('admin.user.unban'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));

    expect($managedUser->fresh()->isBanned())->toBeFalse();

    $this->get(route('admin.user_group.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Index')
            ->has('user_groups'));

    $this->get(route('admin.user_group.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Form')
            ->has('institutions'));

    $this->post(route('admin.user_group.store'), [
        'institution_id' => $institution->id,
        'title' => adminPeopleFeatureTranslatable('Researchers'),
    ])->assertRedirect(route('admin.user_group.index'));

    $userGroup = UserGroup::query()->where('institution_id', $institution->id)->firstOrFail();

    $this->get(route('admin.user_group.edit', ['id' => $userGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Form')
            ->where('user_group.id', $userGroup->id));

    $this->post(route('admin.user_group.update'), [
        'id' => $userGroup->id,
        'title' => adminPeopleFeatureTranslatable('Updated Researchers'),
    ])->assertRedirect(route('admin.user_group.index'));

    expect($userGroup->fresh()->getTranslation('title', 'en'))->toBe('Updated Researchers');

    $this->get(route('admin.user_group.import', ['id' => $userGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
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
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Users')
            ->where('user_group.id', $userGroup->id)
            ->has('users', 2));

    $importedUsers = $userGroup->fresh()->users()->pluck('users.id')->all();

    $this->post(route('admin.user_group.users.remove'), [
        'id' => $userGroup->id,
        'users' => $importedUsers,
    ])->assertRedirect(route('admin.user_group.users', ['id' => $userGroup->id]));

    expect($userGroup->fresh()->users()->count())->toBe(0);

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
