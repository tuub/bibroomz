<?php

use App\Http\Requests\Admin\DeleteRoleRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteRoleRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('authorize returns false when no authenticated user', function (): void {
    $role = Role::create(['name' => Utility::getTranslatable('test-role-del-1')]);
    $request = buildFormRequest(DeleteRoleRequest::class, ['id' => $role->id]);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when role not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(DeleteRoleRequest::class, [], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when admin user has no target role', function (): void {
    $request = buildAdminFormRequest(DeleteRoleRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when permissioned user has no target role', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_roles');

    $request = buildAdminFormRequest(DeleteRoleRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks delete_roles permission', function (): void {
    $role = Role::create(['name' => Utility::getTranslatable('test-role-del-2')]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(DeleteRoleRequest::class, ['id' => $role->id], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has delete_roles permission', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => Utility::getTranslatable('test-role-del-3')]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_roles');

    $request = buildAdminFormRequest(DeleteRoleRequest::class, ['id' => $role->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('role accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => Utility::getTranslatable('test-role-del-4')]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_roles');

    $request = buildAdminFormRequest(DeleteRoleRequest::class, ['id' => $role->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->role()->id)->toBe($role->id);
});

test('role accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => Utility::getTranslatable('test-role-del-5')]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_roles');

    $request = buildAdminFormRequest(DeleteRoleRequest::class, ['id' => $role->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $role->delete();

    expect(fn () => $request->role())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteRoleRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:roles,id');
});
