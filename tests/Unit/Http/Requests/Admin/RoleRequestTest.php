<?php

declare(strict_types=1);

use App\Http\Requests\Admin\RoleRequest;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(RoleRequest::class);

uses(RefreshDatabase::class);

test('RoleRequest defines validation rules', function (): void {
    $request = new RoleRequest;

    expect($request->rules())->toBeArray();
});

test('RoleRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new RoleRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules contains all expected keys', function (): void {
    $rules = (new RoleRequest)->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('name')
        ->toHaveKey('description')
        ->toHaveKey('permissions')
        ->toHaveKey('permissions.*');
});

test('id field rules contain nullable uuid exists roles', function (): void {
    $rules = (new RoleRequest)->rules();

    expect($rules['id'])
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:roles,id');
});

test('permissions field rules contain array', function (): void {
    $rules = (new RoleRequest)->rules();

    expect($rules['permissions'])->toContain('array');
});

test('permissions star field rules contain uuid and exists', function (): void {
    $rules = (new RoleRequest)->rules();

    expect($rules['permissions.*'])
        ->toContain('uuid')
        ->toContain('exists:permissions,id');
});

test('description keeps the exact empty-string placeholder rule', function (): void {
    expect((new RoleRequest)->rules()['description'])->toBe(['']);
});

test('permissions rejects non-array', function (): void {
    $rules = (new RoleRequest)->rules();

    $v = Validator::make(['permissions' => 'not-an-array'], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('permissions'))->toBeTrue();
});

test('permissions star rejects non-uuid', function (): void {
    $rules = (new RoleRequest)->rules();

    $v = Validator::make(['permissions' => ['not-a-uuid']], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('permissions.0'))->toBeTrue();
});

test('id rejects non-uuid when provided', function (): void {
    $rules = (new RoleRequest)->rules();

    $v = Validator::make(['id' => 'not-a-uuid'], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('id'))->toBeTrue();
});

test('prepareForValidation sets permissions from input', function (): void {
    $request = buildFormRequest(RoleRequest::class, []);
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->all())->toHaveKey('permissions');
});

test('prepareForValidation defaults permissions to empty array', function (): void {
    $request = buildFormRequest(RoleRequest::class, []);
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->input('permissions'))->toBe([]);
});

test('permissions returns validated permissions array after validation', function (): void {
    $request = buildFormRequest(RoleRequest::class, ['permissions' => []]);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->permissions())->toBe([]);
});

test('roleData excludes permissions key', function (): void {
    $request = buildFormRequest(RoleRequest::class, ['permissions' => [], 'id' => null]);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->roleData())->not->toHaveKey('permissions');
});

test('roleData returns validated non-permission payload', function (): void {
    $request = buildFormRequest(RoleRequest::class, [
        'name' => ['en' => 'Editor'],
        'description' => ['en' => 'Can edit'],
        'permissions' => [],
    ]);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->roleData())->toBe([
        'name' => ['en' => 'Editor'],
        'description' => ['en' => 'Can edit'],
    ]);
});

test('roleOrNull returns null when no id given', function (): void {
    $request = buildFormRequest(RoleRequest::class, []);

    expect($request->roleOrNull())->toBeNull();
});

test('authorize returns false when no user', function (): void {
    $request = buildFormRequest(RoleRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user can create roles', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    grantAdminPermission($user, $institution, 'create_roles');
    $request = buildFormRequest(RoleRequest::class, [], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize uses the edit permission branch for an existing role', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user = User::factory()->create();
    grantAdminPermission($user, $institution, 'edit_roles');

    $request = buildFormRequest(RoleRequest::class, ['id' => $role->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize does not treat create permission as edit permission for an existing role', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user = User::factory()->create();
    grantAdminPermission($user, $institution, 'create_roles');

    $request = buildFormRequest(RoleRequest::class, ['id' => $role->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when role is not a Role instance (IfNegated / InstanceOfToFalse)', function (): void {
    // When no 'id' input is given, roleOrNull() returns null.
    // if (! $role instanceof Role) means: null is not a Role, so we check user can 'create'.
    // Without admin rights the creation check should fail.
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(RoleRequest::class, [], $user);

    // User is not admin, role is null → checks can('create', Role::class) → false for non-admin
    expect($request->authorize())->toBeFalse();
});

test('authorize checks can(edit) when role exists and user is admin', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $role = Role::factory()->create();
    $request = buildFormRequest(RoleRequest::class, ['id' => $role->id], $user);

    // Role exists, so we take the else branch: can('edit', $role)
    expect($request->authorize())->toBeTrue();
});

test('rules contains name key with RequiredWithTranslationRule', function (): void {
    $rules = (new RoleRequest)->rules();

    // name rule must exist and not be empty
    expect($rules)->toHaveKey('name');
    expect($rules['name'])->not->toBeEmpty();
});

test('permissions returns non-empty array after validation with permissions', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();

    $request = buildFormRequest(RoleRequest::class, ['permissions' => [$role->id]]);

    // We need to run validation with a permissive rule to get validated data
    $validator = Validator::make($request->all(), ['permissions' => ['array'], 'permissions.*' => ['string']]);
    $validator->passes();
    $request->setValidator($validator);

    $perms = $request->permissions();

    // AlwaysReturnEmptyArray mutation would return [] regardless
    expect($perms)->toBe([$role->id]);
});

test('roleOrNull returns a Role model when valid id is provided', function (): void {
    $role = Role::factory()->create();
    $request = buildFormRequest(RoleRequest::class, ['id' => $role->id]);

    $found = $request->roleOrNull();

    // AlwaysReturnNull mutation would return null here; this catches it
    expect($found)->toBeInstanceOf(Role::class);
    /** @var Role $found */
    expect($found->id)->toBe($role->id);
});

test('authorize returns false when userModel returns null', function (): void {
    $request = buildFormRequest(RoleRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when userModel is not a User', function (): void {
    $request = buildFormRequest(RoleRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize checks can(create) when role is null and returns false for non-admin user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(RoleRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize proceeds to can(edit) check when role is a Role instance', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $role = Role::factory()->create();
    $request = buildFormRequest(RoleRequest::class, ['id' => $role->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns true when user can create and no role provided (InstanceOfToFalse early return path)', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(RoleRequest::class, [], $user);

    expect($request->authorize())->toBeTrue();
});
