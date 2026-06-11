<?php

use App\Http\Requests\Admin\RoleIdRequest;
use App\Library\Utility;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(RoleIdRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(RoleIdRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('role accessor returns the correct model', function (): void {
    $role = Role::create(['name' => Utility::getTranslatable('test-role')]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(RoleIdRequest::class, ['id' => $role->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->role()->id)->toBe($role->id);
});

test('role accessor throws when model not found', function (): void {
    $role = Role::create(['name' => Utility::getTranslatable('test-role-2')]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(RoleIdRequest::class, ['id' => $role->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $role->delete();

    expect(fn () => $request->role())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new RoleIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:roles,id');
});
