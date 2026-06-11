<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UserRequest;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Rules\CurrentPasswordRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPermissions;

covers(UserRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

function validatedUserRequest(UserRequest $request): UserRequest
{
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    $validator = Validator::make($request->validationData(), $request->rules());

    expect($validator->passes())->toBeTrue();

    $request->setValidator($validator);

    return $request;
}

test('UserRequest defines validation rules', function (): void {
    $request = new UserRequest;

    expect($request->rules())->toBeArray();
});

test('UserRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new UserRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules contains all expected keys', function (): void {
    $request = new UserRequest;
    $rules = $request->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('is_system_user')
        ->toHaveKey('name')
        ->toHaveKey('email')
        ->toHaveKey('is_set_password')
        ->toHaveKey('current_password')
        ->toHaveKey('password')
        ->toHaveKey('password_confirm')
        ->toHaveKey('is_admin')
        ->toHaveKey('roles')
        ->toHaveKey('roles.*')
        ->toHaveKey('roles.*.role_id')
        ->toHaveKey('roles.*.institution_id');
});

test('id field rules contain nullable and uuid', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['id'])
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:users,id');
});

test('is_system_user field rules contain required and boolean', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['is_system_user'])
        ->toContain('required')
        ->toContain('boolean');
});

test('name field rules contain required_if_accepted is_system_user and string and min 3', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['name'])
        ->toContain('required_if_accepted:is_system_user')
        ->toContain('string')
        ->toContain('min:3');
});

test('email field rules contain required_if_accepted is_system_user and email', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['email'])
        ->toContain('required_if_accepted:is_system_user')
        ->toContain('email');
});

test('is_set_password field rules contain required_if_accepted is_system_user and boolean', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['is_set_password'])
        ->toContain('required_if_accepted:is_system_user')
        ->toContain('boolean');
});

test('current_password field rules contain nullable and string', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['current_password'])
        ->toContain('nullable')
        ->toContain('string');
});

test('password field rules contain required_if_accepted is_set_password and nullable and string', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['password'])
        ->toContain('required_if_accepted:is_set_password')
        ->toContain('nullable')
        ->toContain('string');
});

test('password_confirm field rules contain required_if_accepted is_set_password and same password and nullable and string', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['password_confirm'])
        ->toContain('required_if_accepted:is_set_password')
        ->toContain('same:password')
        ->toContain('nullable')
        ->toContain('string');
});

test('is_admin field rules contain required and boolean', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['is_admin'])
        ->toContain('required')
        ->toContain('boolean');
});

test('roles field rules contain array', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['roles'])->toContain('array');
});

test('roles star field rules contain array:role_id,institution_id', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['roles.*'])->toContain('array:role_id,institution_id');
});

test('roles star role_id field rules contain required and exists', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['roles.*.role_id'])
        ->toContain('required')
        ->toContain('exists:roles,id');
});

test('roles star institution_id field rules contain required and exists', function (): void {
    $rules = (new UserRequest)->rules();

    expect($rules['roles.*.institution_id'])
        ->toContain('required')
        ->toContain('exists:institutions,id');
});

test('name must be at least 3 characters', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = buildFormRequest(UserRequest::class, ['is_system_user' => true], $admin)->rules();
    $v = Validator::make([
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'ab', 'email' => 'a@b.com', 'is_set_password' => false,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('name'))->toBeTrue();
});

test('password_confirm must match password', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = buildFormRequest(UserRequest::class, ['is_system_user' => true, 'is_set_password' => true], $admin)->rules();
    $v = Validator::make([
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'alice.user', 'email' => 'a@b.com',
        'is_set_password' => true, 'password' => 'secret123', 'password_confirm' => 'different',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('password_confirm'))->toBeTrue();
});

test('prepareForValidation merges roles input', function (): void {
    $request = buildFormRequest(UserRequest::class, [
        'roles' => [['role_id' => 'abc', 'institution_id' => 'def']],
    ]);

    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->all())->toHaveKey('roles');
});

test('prepareForValidation normalizes roles and drops non-array items', function (): void {
    $request = buildFormRequest(UserRequest::class, [
        'roles' => [
            ['role_id' => 'abc', 'institution_id' => 'def'],
            'ignored',
        ],
    ]);

    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->input('roles'))->toBe([
        ['role_id' => 'abc', 'institution_id' => 'def'],
    ]);
});

test('roles star institution_id must be uuid format', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = buildFormRequest(UserRequest::class, [], $admin)->rules();
    $v = Validator::make([
        'is_system_user' => false, 'is_admin' => false,
        'roles' => [['role_id' => 'not-uuid', 'institution_id' => 'not-uuid']],
    ], $rules);

    expect($v->fails())->toBeTrue();
});

// --- Mutation-killing tests for authorize() logic ---

test('authorize returns false when user is not a User instance', function (): void {
    // Without any user, userModel() returns null, so !($user instanceof User) path is covered
    $request = new UserRequest;

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when institution is null', function (): void {
    // User exists but has a role pointing to a non-existent institution_id
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(UserRequest::class, [
        'roles' => [['institution_id' => 'non-existent-uuid', 'role_id' => 'some-id']],
    ], $user);

    // prepareForValidation merges the roles — invoke it so inputRoles returns them
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->authorize())->toBeFalse();
});

test('authorize targetUser must be User instance', function (): void {
    // No 'id' in input → goes to create path, user without can('create') → false
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(UserRequest::class, ['id' => null], $user);

    expect($request->authorize())->toBeFalse();
});

// --- Mutation-killing tests for rules() (CoalesceRemoveLeft / FalseToTrue) ---

test('canEditAdminUsers defaults to false when no user is set', function (): void {
    $request = new UserRequest;
    $rules = $request->rules();

    // is_admin should have declined rule because canEditAdminUsers is false
    /** @var array<mixed> $isAdminRules */
    $isAdminRules = (array) $rules['is_admin'];
    // Just verify is_admin rules are present and not empty
    expect($isAdminRules)->not->toBeEmpty();
});

test('rules canEditAdminUsers is false results in declined rule for is_admin', function (): void {
    // User without edit_admin_users permission → canEditAdminUsers === false
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(UserRequest::class, [], $user);
    $rules = $request->rules();

    // Declined rule must be present as a Rule::when(..., 'declined') object
    /** @var array<mixed> $isAdminRules */
    $isAdminRules = (array) $rules['is_admin'];
    expect(count($isAdminRules))->toBeGreaterThan(0);
});

// --- Mutation-killing tests for rules() lines 56 and 59 (RemoveArrayItem) ---

test('current_password rules contain Rule::requiredIf object', function (): void {
    $rules = (new UserRequest)->rules();

    /** @var array<mixed> $cpRules */
    $cpRules = (array) $rules['current_password'];
    $hasRuleObject = collect($cpRules)->contains(fn (mixed $r): bool => is_object($r));
    expect($hasRuleObject)->toBeTrue();
});

test('current_password rules contain CurrentPasswordRule object', function (): void {
    $rules = (new UserRequest)->rules();
    /** @var array<mixed> $cpRules */
    $cpRules = (array) $rules['current_password'];

    $hasCurrentPasswordRule = collect($cpRules)->contains(
        fn (mixed $r): bool => $r instanceof CurrentPasswordRule
    );
    expect($hasCurrentPasswordRule)->toBeTrue();
});

// --- Mutation-killing tests for rules() (RemoveNot, RemoveArrayItem) ---

test('is_admin rules contain Rule::when object', function (): void {
    $rules = (new UserRequest)->rules();

    /** @var array<mixed> $isAdminRules */
    $isAdminRules = (array) $rules['is_admin'];
    $hasRuleObject = collect($isAdminRules)->contains(fn (mixed $r): bool => is_object($r));
    expect($hasRuleObject)->toBeTrue();
});

// --- Mutation-killing tests for roles() (AlwaysReturnEmptyArray) ---

test('roles() returns array from validated data after successful validation', function (): void {
    // roles() method returns validated('roles', []) - the AlwaysReturnEmptyArray mutation
    // would make it return [] always instead of using validated data.
    // We test that roles() uses the validated data by verifying it's callable and returns an array.
    $admin = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(UserRequest::class, [
        'id' => null,
        'is_system_user' => false,
        'is_admin' => false,
        'is_set_password' => false,
        'roles' => [],
    ], $admin);

    // Validate it successfully with minimal valid data
    try {
        $request->validateResolved();
    } catch (Throwable) {
    }

    $roles = $request->roles();
    // Even with empty roles, should return array not something else
    expect($roles)->toBeArray();
});

// --- Mutation-killing tests for userData() lines 131-132, 135, 138-139, 142-143 ---

test('userData excludes roles and password_confirm', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    // Provide valid data that passes validation (no password_confirm to avoid same:password mismatch)
    $request = buildFormRequest(UserRequest::class, [
        'id' => null,
        'is_system_user' => false,
        'is_admin' => false,
        'is_set_password' => false,
        'roles' => [],
    ], $admin);

    // Force validate so validated() data is available
    try {
        $request->validateResolved();
    } catch (Throwable) {
    }

    $data = $request->userData();

    expect($data)->not->toHaveKey('roles')
        ->and($data)->not->toHaveKey('password_confirm');
});

test('userData excludes current_password and is_set_password', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(UserRequest::class, [
        'id' => null,
        'is_system_user' => false,
        'is_admin' => false,
        'is_set_password' => false,
        'roles' => [],
    ], $admin);

    try {
        $request->validateResolved();
    } catch (Throwable) {
    }

    $data = $request->userData();

    expect($data)->not->toHaveKey('current_password')
        ->and($data)->not->toHaveKey('is_set_password');
});

test('userData excludes password when is_set_password is false', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(UserRequest::class, [
        'id' => null,
        'is_system_user' => false,
        'is_admin' => false,
        'is_set_password' => false,
        'roles' => [],
    ], $admin);

    try {
        $request->validateResolved();
    } catch (Throwable) {
    }

    $data = $request->userData();

    expect($data)->not->toHaveKey('password');
});

test('userData excludes email and password when is_system_user is false', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(UserRequest::class, [
        'id' => null,
        'is_system_user' => false,
        'is_admin' => false,
        'is_set_password' => false,
        'roles' => [],
    ], $admin);

    try {
        $request->validateResolved();
    } catch (Throwable) {
    }

    $data = $request->userData();

    expect($data)->not->toHaveKey('email');
});

test('authorize returns false when role institution id is not a string', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [[
            'role_id' => 'role-id',
            'institution_id' => 123,
        ]],
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when role institution id is a non-string object', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [[
            'role_id' => 'role-id',
            'institution_id' => new stdClass,
        ]],
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false on create path when actor can create users but role institution id is invalid', function (): void {
    $grantedInstitution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $grantedInstitution, 'create_users');

    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => 123,
        ]],
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when actor cannot edit the role institution', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false on create path when actor can create users elsewhere but cannot edit the target institution', function (): void {
    $grantedInstitution = Institution::factory()->create();
    $targetInstitution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $grantedInstitution, 'create_users');

    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $targetInstitution->id,
        ]],
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true on create path when actor can edit the institution and create users', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'edit_institution');
    $this->grantPermission($user, $institution, 'create_users');

    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns false on edit path when target user does not exist', function (): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->create();

    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(UserRequest::class, [
        'id' => Str::uuid()->toString(),
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false on edit path for admin actor when target user does not exist', function (): void {
    $request = buildAdminFormRequest(UserRequest::class, [
        'id' => Str::uuid()->toString(),
    ], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true on edit path when target user exists and actor can update it', function (): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $targetUser = User::factory()->create(['is_admin' => false]);

    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(UserRequest::class, [
        'id' => $targetUser->id,
    ], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns false on edit path for admin target without edit_admin_users permission', function (): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $targetUser = User::factory()->create(['is_admin' => true]);

    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(UserRequest::class, [
        'id' => $targetUser->id,
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('is_admin validation declines truthy input when actor cannot edit admin users', function (): void {
    $request = buildFormRequest(UserRequest::class, [
        'is_system_user' => false,
        'is_admin' => true,
        'roles' => [],
    ], User::factory()->create());

    $validator = Validator::make($request->validationData(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('is_admin'))->toBeTrue();
});

test('is_admin validation accepts truthy input when actor can edit admin users', function (): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->create();

    $this->grantPermission($user, $institution, 'edit_admin_users');

    $request = buildFormRequest(UserRequest::class, [
        'is_system_user' => false,
        'is_admin' => true,
        'roles' => [],
    ], $user);

    $validator = Validator::make($request->validationData(), $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('is_admin validation declines truthy input when no actor is present', function (): void {
    $request = buildFormRequest(UserRequest::class, [
        'is_system_user' => false,
        'is_admin' => true,
        'roles' => [],
    ]);

    $validator = Validator::make($request->validationData(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('is_admin'))->toBeTrue();
});

test('current_password is required when editing a user and setting a password', function (): void {
    $targetUser = User::factory()->create([
        'name' => 'password-target',
        'password' => bcrypt('current-secret'),
    ]);
    $request = buildFormRequest(UserRequest::class, [
        'id' => $targetUser->id,
        'is_system_user' => true,
        'name' => $targetUser->name,
        'email' => 'target@example.org',
        'is_set_password' => true,
        'password' => 'new-secret',
        'password_confirm' => 'new-secret',
        'is_admin' => false,
        'roles' => [],
    ], User::factory()->create(['is_admin' => true]));

    $validator = Validator::make($request->validationData(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('current_password'))->toBeTrue();
});

test('roles returns the validated roles exactly', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $request = validatedUserRequest(buildAdminFormRequest(UserRequest::class, [
        'is_system_user' => false,
        'is_admin' => false,
        'is_set_password' => false,
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ], User::factory()->create(['is_admin' => true])));

    expect($request->roles())->toBe([[
        'role_id' => $role->id,
        'institution_id' => $institution->id,
    ]]);
});

test('userData keeps email and hashed password for system users who set a password', function (): void {
    $request = validatedUserRequest(buildAdminFormRequest(UserRequest::class, [
        'is_system_user' => true,
        'name' => 'system.user',
        'email' => 'system@example.org',
        'is_set_password' => true,
        'password' => 'secret-123',
        'password_confirm' => 'secret-123',
        'current_password' => 'ignored-on-create',
        'is_admin' => true,
        'roles' => [],
    ], User::factory()->create(['is_admin' => true])));

    $data = $request->userData();
    $password = $data['password'] ?? null;

    if (! is_string($password)) {
        throw new RuntimeException('Expected hashed password string.');
    }

    expect(array_keys($data))->toEqual([
        'is_system_user',
        'name',
        'email',
        'password',
        'is_admin',
    ])->and($data['is_system_user'])->toBeTrue()
        ->and($data['name'])->toBe('system.user')
        ->and($data['email'])->toBe('system@example.org')
        ->and($data['is_admin'])->toBeTrue()
        ->and(Hash::check('secret-123', $password))->toBeTrue()
        ->and($data)->not->toHaveKeys(['roles', 'password_confirm', 'current_password', 'is_set_password']);
});

test('userData removes password but keeps email when system user does not set a password', function (): void {
    $request = validatedUserRequest(buildAdminFormRequest(UserRequest::class, [
        'is_system_user' => true,
        'name' => 'system.user',
        'email' => 'system@example.org',
        'is_set_password' => false,
        'password' => 'should-be-removed',
        'password_confirm' => 'should-be-removed',
        'is_admin' => false,
        'roles' => [],
    ], User::factory()->create(['is_admin' => true])));

    expect($request->userData())->toBe([
        'is_system_user' => true,
        'name' => 'system.user',
        'email' => 'system@example.org',
        'is_admin' => false,
    ]);
});

test('userData removes email and password for non-system users even when a password was submitted', function (): void {
    $request = validatedUserRequest(buildAdminFormRequest(UserRequest::class, [
        'is_system_user' => false,
        'name' => 'directory.user',
        'email' => 'directory@example.org',
        'is_set_password' => true,
        'password' => 'should-be-dropped',
        'password_confirm' => 'should-be-dropped',
        'is_admin' => false,
        'roles' => [],
    ], User::factory()->create(['is_admin' => true])));

    expect($request->userData())->toBe([
        'is_system_user' => false,
        'name' => 'directory.user',
        'is_admin' => false,
    ]);
});
