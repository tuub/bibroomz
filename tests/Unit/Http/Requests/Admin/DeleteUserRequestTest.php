<?php

declare(strict_types=1);

use App\Http\Requests\Admin\DeleteUserRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteUserRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('DeleteUserRequest defines validation rules', function (): void {
    $request = new DeleteUserRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:users,id');
});

test('DeleteUserRequest authorize returns false when no target found', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new DeleteUserRequest;

    expect($request->authorize())->toBeFalse();
});

test('DeleteUserRequest authorize returns false for non-admin with no target', function (): void {
    $request = new DeleteUserRequest;

    expect($request->authorize())->toBeFalse();
});

test('DeleteUserRequest authorize returns false for permissioned user with no target', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_users');

    $request = buildAdminFormRequest(DeleteUserRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('DeleteUserRequest authorize returns true when user can delete target', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create(['is_admin' => false]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_users');

    $request = buildAdminFormRequest(DeleteUserRequest::class, ['id' => $target->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('DeleteUserRequest authorize returns false when actor is null even with target', function (): void {
    // InstanceOfToTrue on $user instanceof User would bypass the null user check
    $target = User::factory()->create();
    $request = buildFormRequest(DeleteUserRequest::class, ['id' => $target->id]);

    expect($request->authorize())->toBeFalse();
});

test('DeleteUserRequest authorize returns false for admin with no target user', function (): void {
    $request = buildAdminFormRequest(DeleteUserRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('DeleteUserRequest targetUser accessor returns correct model', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create(['is_admin' => false]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_users');

    $request = buildAdminFormRequest(DeleteUserRequest::class, ['id' => $target->id], $user);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->targetUser()->id)->toBe($target->id);
});
