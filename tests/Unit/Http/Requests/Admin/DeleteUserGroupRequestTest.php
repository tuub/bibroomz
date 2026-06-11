<?php

declare(strict_types=1);

use App\Http\Requests\Admin\DeleteUserGroupRequest;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteUserGroupRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('DeleteUserGroupRequest defines validation rules', function (): void {
    $request = new DeleteUserGroupRequest;

    expect($request->rules())->toBeArray();
});

test('DeleteUserGroupRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new DeleteUserGroupRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteUserGroupRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:user_groups,id');
});

test('DeleteUserGroupRequest authorize returns true when user can delete user group', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_user_groups');

    $request = buildAdminFormRequest(DeleteUserGroupRequest::class, ['id' => $group->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('DeleteUserGroupRequest authorize returns false when actor is null even with group', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $request = buildFormRequest(DeleteUserGroupRequest::class, ['id' => $group->id]);

    expect($request->authorize())->toBeFalse();
});

test('DeleteUserGroupRequest authorize returns false when group is null even with actor', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildAdminFormRequest(DeleteUserGroupRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('DeleteUserGroupRequest userGroup accessor returns correct model', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_user_groups');

    $request = buildAdminFormRequest(DeleteUserGroupRequest::class, ['id' => $group->id], $user);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->userGroup()->id)->toBe($group->id);
});
