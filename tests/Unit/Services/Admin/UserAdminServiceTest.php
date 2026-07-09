<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Admin\UserAdminService;
use Cog\Laravel\Ban\Models\Ban;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

covers(UserAdminService::class);

uses(RefreshDatabase::class);

// -------------------------------------------------------------------------
// getIndexData
// -------------------------------------------------------------------------

test('getIndexData returns users key', function (): void {
    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    expect($data)->toHaveKey('users');
});

test('getIndexData user map contains all required keys', function (): void {
    User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    $user = $data['users']->first();

    expect($user)
        ->toHaveKey('id')
        ->toHaveKey('name')
        ->toHaveKey('email')
        ->toHaveKey('is_admin')
        ->toHaveKey('is_system_user')
        ->toHaveKey('is_logged_in')
        ->toHaveKey('is_privileged')
        ->toHaveKey('is_banned')
        ->toHaveKey('happenings_count')
        ->toHaveKey('user_groups');
});

test('getIndexData happenings_count reflects user happenings', function (): void {
    User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    expect($data['users']->first()['happenings_count'])->toBe(0);
});

test('getIndexData is_privileged is true when user has roles', function (): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    $mapped = $data['users']->firstWhere('id', $user->id);

    expect($mapped['is_privileged'])->toBeTrue();
});

test('getIndexData is_privileged is false when user has no roles', function (): void {
    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    $mapped = $data['users']->firstWhere('id', $user->id);

    expect($mapped['is_privileged'])->toBeFalse();
});

// -------------------------------------------------------------------------
// getCreateFormData
// -------------------------------------------------------------------------

test('getCreateFormData returns is_system_user key', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    expect($data)->toHaveKey('is_system_user');
});

test('getCreateFormData returns is_set_password key', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    expect($data)->toHaveKey('is_set_password');
});

test('getCreateFormData returns roles key', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    expect($data)->toHaveKey('roles');
});

test('getCreateFormData returns institutions key', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    expect($data)->toHaveKey('institutions');
});

test('getCreateFormData is_system_user is true by default', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    expect($data['is_system_user'])->toBeTrue();
});

test('getCreateFormData is_set_password is true by default', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    expect($data['is_set_password'])->toBeTrue();
});

// -------------------------------------------------------------------------
// getEditFormData
// -------------------------------------------------------------------------

test('getEditFormData returns user key', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    expect($data)->toHaveKey('user');
});

test('getEditFormData returns roles key', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    expect($data)->toHaveKey('roles');
});

test('getEditFormData returns institutions key', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    expect($data)->toHaveKey('institutions');
});

test('getEditFormData user array contains all required sub-keys', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    expect($data['user'])
        ->toHaveKey('id')
        ->toHaveKey('name')
        ->toHaveKey('email')
        ->toHaveKey('is_admin')
        ->toHaveKey('is_system_user')
        ->toHaveKey('roles');
});

// -------------------------------------------------------------------------
// store
// -------------------------------------------------------------------------

test('store creates a user', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $service = app(UserAdminService::class);

    $user = $service->store([
        'name' => 'testuser123',
        'email' => 'testuser123@example.com',
        'password' => 'secret123',
    ], [], $actor);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('testuser123');
});

test('store syncs the provided roles for the created user', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();

    $service = app(UserAdminService::class);
    $user = $service->store([
        'name' => 'rolebound-user',
        'email' => 'rolebound@example.com',
        'password' => 'secret123',
    ], [
        ['role_id' => $role->id, 'institution_id' => $institution->id],
    ], $actor);

    expect($user->roles()->where('roles.id', $role->id)->exists())->toBeTrue()
        ->and($user->roles()->first()?->pivot?->institution_id)->toBe($institution->id);
});

// -------------------------------------------------------------------------
// update
// -------------------------------------------------------------------------

test('update saves changed attributes and returns user', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'original-name']);

    $service = app(UserAdminService::class);
    $updated = $service->update($user, ['name' => 'updated-name'], [], $actor);

    expect($updated)->toBeInstanceOf(User::class)
        ->and($updated->name)->toBe('updated-name')
        ->and(User::findOrFail($user->id)->name)->toBe('updated-name');
});

// -------------------------------------------------------------------------
// delete
// -------------------------------------------------------------------------

test('delete removes the user', function (): void {
    $user = User::factory()->create();
    $id = $user->id;

    $service = app(UserAdminService::class);
    $service->delete($user);

    expect(User::find($id))->toBeNull();
});

// -------------------------------------------------------------------------
// ban / unban
// -------------------------------------------------------------------------

test('ban marks user as banned', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);

    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $service->ban($user);

    $user->refresh();

    expect($user->isBanned())->toBeTrue();
});

test('unban removes ban from user', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);

    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $service->ban($user);
    $service->unban($user);

    $user->refresh();

    expect($user->isBanned())->toBeFalse();
});

// -------------------------------------------------------------------------
// getIndexData – relation loading
// -------------------------------------------------------------------------

test('getIndexData loads happenings, roles and user_groups relations', function (): void {
    User::factory()->create();

    $service = app(UserAdminService::class);
    $service->getIndexData();

    $freshUser = User::query()->with(['happenings', 'roles', 'user_groups'])->firstOrFail();

    expect($freshUser->relationLoaded('happenings'))->toBeTrue()
        ->and($freshUser->relationLoaded('roles'))->toBeTrue()
        ->and($freshUser->relationLoaded('user_groups'))->toBeTrue();
});

// -------------------------------------------------------------------------
// getCreateFormData – field assertions
// -------------------------------------------------------------------------

test('getCreateFormData roles contain id name description fields', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    Role::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    $role = $data['roles']->first();

    expect($role)->not->toBeNull()
        ->and($role->id)->not->toBeNull()
        ->and($role->name)->not->toBeNull()
        ->and($role->description)->not->toBeNull();
});

test('getCreateFormData roles only contain id name description (no extra fields)', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    Role::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    $roleAttributes = $data['roles']->first()?->getAttributes() ?? [];

    expect($roleAttributes)->toBeArray();

    if (! is_array($roleAttributes)) {
        throw new RuntimeException('Expected role attributes to be an array.');
    }

    expect(array_keys($roleAttributes))->toBe(['id', 'name', 'description']);
});

test('getCreateFormData institutions contain id title short_title fields', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    Institution::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    $institution = $data['institutions']->first();

    expect($institution)->not->toBeNull()
        ->and($institution)->toHaveKey('id')
        ->and($institution)->toHaveKey('title')
        ->and($institution)->toHaveKey('short_title');
});

test('getCreateFormData institution map has exactly id title short_title keys', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    Institution::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getCreateFormData($actor);

    $institution = $data['institutions']->first() ?? [];

    expect($institution)->toBeArray();

    if (! is_array($institution)) {
        throw new RuntimeException('Expected institution map to be an array.');
    }

    expect(array_keys($institution))->toBe(['id', 'title', 'short_title']);
});

// -------------------------------------------------------------------------
// getEditFormData – field assertions
// -------------------------------------------------------------------------

test('getEditFormData user only has id name email is_admin is_system_user keys', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    expect($data['user'])
        ->toHaveKey('id')
        ->toHaveKey('name')
        ->toHaveKey('email')
        ->toHaveKey('is_admin')
        ->toHaveKey('is_system_user');
});

test('getEditFormData user id matches the given user', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'SpecificUser', 'email' => 'specific@example.com']);

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    expect($data['user']['id'])->toBe($user->id)
        ->and($data['user']['name'])->toBe('SpecificUser')
        ->and($data['user']['email'])->toBe('specific@example.com');
});

test('getEditFormData institutions contain id title short_title fields', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    Institution::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    $institution = $data['institutions']->first();

    expect($institution)->not->toBeNull()
        ->and($institution)->toHaveKey('id')
        ->and($institution)->toHaveKey('title')
        ->and($institution)->toHaveKey('short_title');
});

test('getEditFormData institution map has exactly id title short_title keys', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    Institution::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    $institution = $data['institutions']->first() ?? [];

    expect($institution)->toBeArray();

    if (! is_array($institution)) {
        throw new RuntimeException('Expected institution map to be an array.');
    }

    expect(array_keys($institution))->toBe(['id', 'title', 'short_title']);
});

test('getEditFormData roles map contains role_id and institution_id keys', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    $userRoles = $data['user']['roles'];

    expect($userRoles)->not->toBeEmpty()
        ->and($userRoles[0])->toHaveKey('role_id')
        ->and($userRoles[0])->toHaveKey('institution_id')
        ->and($userRoles[0]['role_id'])->toBe($role->id)
        ->and($userRoles[0]['institution_id'])->toBe((string) $institution->id);
});

test('getEditFormData role institution_id falls back to empty string for non-string value', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    $userRoles = $data['user']['roles'];

    expect($userRoles[0]['institution_id'])->toBeString();
});

// -------------------------------------------------------------------------
// ban – expiry calculation
// -------------------------------------------------------------------------

test('ban sets expiry using configured suspension_days', function (): void {
    config(['roomz.user.suspension_days' => 7]);

    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);

    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $service->ban($user);

    $ban = Ban::query()
        ->where('bannable_id', $user->id)
        ->firstOrFail();

    expect($ban->expired_at)->not->toBeNull()
        ->and($ban->expired_at?->format('Y-m-d'))->toBe(Carbon::now()->addDays(7)->format('Y-m-d'));
});

test('ban uses zero days when suspension_days config is not integer', function (): void {
    config(['roomz.user.suspension_days' => 'invalid']);

    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);

    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $service->ban($user);

    $ban = Ban::query()
        ->where('bannable_id', $user->id)
        ->firstOrFail();

    expect($ban->expired_at?->format('Y-m-d'))->toBe(Carbon::now()->format('Y-m-d'));
});

// -------------------------------------------------------------------------
// store / update / delete – logging
// -------------------------------------------------------------------------

test('store logs created action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $actor = User::factory()->create(['is_admin' => true]);
    $service = app(UserAdminService::class);

    $service->store([
        'name' => 'loggeduser',
        'email' => 'loggeduser@example.com',
        'password' => 'secret123',
    ], [], $actor);
});

test('update logs updated action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $service = app(UserAdminService::class);

    $service->update($user, ['name' => 'newname'], [], $actor);
});

test('delete logs deleted action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $user = User::factory()->create();
    $service = app(UserAdminService::class);

    $service->delete($user);
});

test('getIndexData eager loads happenings, roles and user_groups relations', function (): void {
    User::factory()->create();

    $service = app(UserAdminService::class);
    $service->getIndexData();

    $freshUser = User::query()->firstOrFail();

    expect($freshUser->relationLoaded('happenings'))->toBeFalse();

    $service->getIndexData();

    $users = User::query()->with(['happenings', 'roles', 'user_groups'])->get();

    expect($users->first()?->relationLoaded('happenings'))->toBeTrue()
        ->and($users->first()?->relationLoaded('roles'))->toBeTrue()
        ->and($users->first()?->relationLoaded('user_groups'))->toBeTrue();
});

test('getIndexData result has happenings_count that reflects actual loaded happenings', function (): void {
    $user = User::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    $mapped = $data['users']->firstWhere('id', $user->id);

    expect($mapped['happenings_count'])->toBe(0);
});

test('getIndexData user_groups contains full translations object not a plain string', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup->id);

    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    $mapped = $data['users']->firstWhere('id', $user->id);

    expect($mapped['user_groups'])->toHaveCount(1)
        ->and($mapped['user_groups'][0]['id'])->toBe($userGroup->id)
        ->and($mapped['user_groups'][0]['title'])->toBeArray();
});

test('getIndexData result has is_privileged that reflects loaded roles', function (): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    $service = app(UserAdminService::class);
    $data = $service->getIndexData();

    $mapped = $data['users']->firstWhere('id', $user->id);

    expect($mapped['is_privileged'])->toBeTrue()
        ->and($mapped['happenings_count'])->toBe(0);
});

test('getIndexData does not rely on lazy loading for happenings or roles when mapping users', function (): void {
    $user = User::factory()->create();
    User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    Model::preventLazyLoading();

    try {
        $data = app(UserAdminService::class)->getIndexData();
    } finally {
        Model::preventLazyLoading(false);
    }

    $mapped = $data['users']->firstWhere('id', $user->id);

    expect($mapped['is_privileged'])->toBeTrue()
        ->and($mapped['happenings_count'])->toBe(0);
});

test('getEditFormData role institution_id falls back to empty string when attribute is not string', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    DB::table('institution_user_role')
        ->where('user_id', $user->id)
        ->where('role_id', $role->id)
        ->update(['institution_id' => $institution->id]);

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    $userRoles = $data['user']['roles'];

    expect($userRoles)->not->toBeEmpty();
    $institutionId = $userRoles[0]['institution_id'];
    expect(is_string($institutionId))->toBeTrue();
    expect($institutionId)->not->toBe(' ');
});

test('getEditFormData role options only include id name and description attributes', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    Role::factory()->create();

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    $roleAttributes = $data['roles']->first()?->getAttributes() ?? [];

    expect($roleAttributes)->toBeArray();

    if (! is_array($roleAttributes)) {
        throw new RuntimeException('Expected edit-form role attributes to be an array.');
    }

    expect(array_keys($roleAttributes))->toBe(['id', 'name', 'description']);
});

test('getEditFormData roles map contains both role_id and institution_id with correct values', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    $service = app(UserAdminService::class);
    $data = $service->getEditFormData($user, $actor);

    $userRoles = $data['user']['roles'];

    expect($userRoles)->not->toBeEmpty();

    if (! is_array($userRoles) || ! is_array($userRoles[0])) {
        throw new RuntimeException('Expected user roles to be an array.');
    }

    expect(array_keys($userRoles[0]))->toBe(['role_id', 'institution_id']);
    expect($userRoles[0]['role_id'])->toBe($role->id);
    expect($userRoles[0]['institution_id'])->toBe((string) $institution->id);
});

test('update syncs roles for the user', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();

    $service = app(UserAdminService::class);
    $service->update($user, [], [
        ['role_id' => $role->id, 'institution_id' => $institution->id],
    ], $actor);

    $user->refresh();

    $hasRole = $user->roles()->exists();
    expect($hasRole)->toBeTrue();
});

test('ban actually suspends the user account', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);

    $user = User::factory()->create();

    expect($user->isBanned())->toBeFalse();

    $service = app(UserAdminService::class);
    $service->ban($user);

    $user->refresh();

    expect($user->isBanned())->toBeTrue();
});

test('ban logs the banned action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);
    $user = User::factory()->create();

    app(UserAdminService::class)->ban($user);
});

test('unban logs the unbanned action', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);

    $user = User::factory()->create();
    $service = app(UserAdminService::class);
    $service->ban($user);

    $user->refresh();

    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $service->unban($user);
});
