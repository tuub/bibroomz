<?php

use App\Http\Requests\Admin\RemoveUsersFromUserGroupRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(RemoveUsersFromUserGroupRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('authorize returns false when no authenticated user', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Remove Group 1'),
        'institution_id' => $institution->id,
    ]);
    $request = buildFormRequest(RemoveUsersFromUserGroupRequest::class, ['id' => $userGroup->id, 'users' => []]);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user group not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(RemoveUsersFromUserGroupRequest::class, ['users' => []], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Remove Group 2'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(RemoveUsersFromUserGroupRequest::class, ['id' => $userGroup->id, 'users' => []], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Remove Group 3'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(RemoveUsersFromUserGroupRequest::class, ['id' => $userGroup->id, 'users' => []], $user);
    expect($request->authorize())->toBeTrue();
});

test('userGroup accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Remove Group 4'),
        'institution_id' => $institution->id,
    ]);
    $member = User::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(RemoveUsersFromUserGroupRequest::class, [
        'id' => $userGroup->id,
        'users' => [$member->id],
    ], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->userGroup()->id)->toBe($userGroup->id);
});

test('userGroupOrNull accessor returns the model when found', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Remove Group 5'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(RemoveUsersFromUserGroupRequest::class, ['id' => $userGroup->id, 'users' => []], $user);

    expect($request->userGroupOrNull())->not->toBeNull();
    expect($request->userGroupOrNull()?->id)->toBe($userGroup->id);
});

test('userGroupOrNull accessor returns null when not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(RemoveUsersFromUserGroupRequest::class, ['users' => []], $user);

    expect($request->userGroupOrNull())->toBeNull();
});

test('userIds returns filtered list of string uuids after validation', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Remove Group 6'),
        'institution_id' => $institution->id,
    ]);
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(RemoveUsersFromUserGroupRequest::class, [
        'id' => $userGroup->id,
        'users' => [$member1->id, $member2->id],
    ], $user);

    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $ids = $request->userIds();

    expect($ids)->toContain($member1->id)
        ->and($ids)->toContain($member2->id);
});

test('rules returns all required validation rules', function (): void {
    $request = new RemoveUsersFromUserGroupRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:user_groups,id')
        ->and($rules)->toHaveKey('users')
        ->and($rules['users'])->toContain('required')
        ->and($rules['users'])->toContain('array')
        ->and($rules)->toHaveKey('users.*')
        ->and($rules['users.*'])->toContain('required')
        ->and($rules['users.*'])->toContain('uuid')
        ->and($rules['users.*'])->toContain('exists:users,id');
});
