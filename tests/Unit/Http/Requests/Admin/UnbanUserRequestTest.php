<?php

use App\Http\Requests\Admin\UnbanUserRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(UnbanUserRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('authorize returns false when no authenticated user', function (): void {
    $target = User::factory()->create();
    $request = buildFormRequest(UnbanUserRequest::class, ['id' => $target->id]);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when target user not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UnbanUserRequest::class, [], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when admin user has no target user', function (): void {
    $request = buildAdminFormRequest(UnbanUserRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when permissioned user has no target user', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(UnbanUserRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit_users permission', function (): void {
    $target = User::factory()->create();
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UnbanUserRequest::class, ['id' => $target->id], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has edit_users permission', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(UnbanUserRequest::class, ['id' => $target->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('targetUser accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(UnbanUserRequest::class, ['id' => $target->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->targetUser()->id)->toBe($target->id);
});

test('targetUser accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(UnbanUserRequest::class, ['id' => $target->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $target->delete();

    expect(fn () => $request->targetUser())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new UnbanUserRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:users,id');
});
