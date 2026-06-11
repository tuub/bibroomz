<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateUserGroupRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPermissions;

covers(UpdateUserGroupRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('rules include id and title', function (): void {
    $request = buildFormRequest(UpdateUserGroupRequest::class, []);
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules)->toHaveKey('title');
});

test('id is required', function (): void {
    $rules = buildFormRequest(UpdateUserGroupRequest::class, [])->rules();

    $validator = Validator::make(['title' => Utility::getTranslatable('Group')], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('id'))->toBeTrue();
});

test('id must be a uuid', function (): void {
    $rules = buildFormRequest(UpdateUserGroupRequest::class, ['id' => 'bad'])->rules();

    $validator = Validator::make(['id' => 'bad', 'title' => Utility::getTranslatable('Group')], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('id'))->toBeTrue();
});

test('id uuid validation fails specifically on the uuid rule', function (): void {
    $rules = buildFormRequest(UpdateUserGroupRequest::class, ['id' => 'bad'])->rules();

    $validator = Validator::make(['id' => 'bad', 'title' => Utility::getTranslatable('Group')], $rules);
    $validator->fails();

    $failedRules = $validator->failed();
    $idFailures = $failedRules['id'] ?? null;

    if (! is_array($idFailures)) {
        throw new RuntimeException('Expected id validation failures.');
    }

    expect(array_key_exists('Uuid', $idFailures))->toBeTrue();
});

test('id must exist in user_groups table', function (): void {
    $rules = buildFormRequest(UpdateUserGroupRequest::class, [])->rules();
    $fakeUuid = (string) Str::uuid();

    $validator = Validator::make(['id' => $fakeUuid, 'title' => Utility::getTranslatable('Group')], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('id'))->toBeTrue();
});

test('title must not be empty translations', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = buildFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id])->rules();

    $validator = Validator::make(['id' => $group->id, 'title' => []], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue();
});

test('validation passes with valid id and title', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = buildFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id])->rules();

    $validator = Validator::make(['id' => $group->id, 'title' => Utility::getTranslatable('Group')], $rules);

    expect($validator->passes())->toBeTrue();
});

test('authorize returns false when no user is authenticated', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $request = buildFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user group not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G']]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('userGroupOrNull returns null when no id given', function (): void {
    $request = buildFormRequest(UpdateUserGroupRequest::class, []);

    expect($request->userGroupOrNull())->toBeNull();
});

test('userGroupOrNull returns the user group model for a valid id', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $request = buildFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id]);

    expect($request->userGroupOrNull()?->id)->toBe($group->id);
});

test('authorize returns false when userGroup is null even if user has permission', function (): void {
    // InstanceOfToTrue on $userGroup instanceof UserGroup would make it always true,
    // causing can('update', null) to be called; this test ensures authorize() returns false
    // when no userGroup is provided, even for a permissioned user.
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    // No 'id' in data → userGroupOrNull() returns null
    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when id is a random uuid', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, ['id' => (string) Str::uuid()], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false for admin user when target user group is missing', function (): void {
    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('userGroup accessor returns the model after validation', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $request = buildFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id]);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->userGroup()->id)->toBe($group->id);
});

test('userGroup accessor throws ModelNotFoundException when model not found', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $request = buildFormRequest(UpdateUserGroupRequest::class, ['id' => $group->id]);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $group->delete();

    expect(fn () => $request->userGroup())->toThrow(ModelNotFoundException::class);
});
