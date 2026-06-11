<?php

declare(strict_types=1);

use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPermissions;

covers(StoreUserGroupRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('rules include institution_id and title', function (): void {
    $request = buildFormRequest(StoreUserGroupRequest::class, []);
    $rules = $request->rules();

    expect($rules)->toHaveKey('institution_id')
        ->and($rules)->toHaveKey('title');
});

test('institution_id is required', function (): void {
    $rules = buildFormRequest(StoreUserGroupRequest::class, [])->rules();

    $validator = Validator::make(['title' => Utility::getTranslatable('Group')], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('institution_id'))->toBeTrue();
});

test('institution_id must be a uuid', function (): void {
    $rules = buildFormRequest(StoreUserGroupRequest::class, ['institution_id' => 'bad'])->rules();

    $validator = Validator::make([
        'institution_id' => 'bad',
        'title' => Utility::getTranslatable('Group'),
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('institution_id'))->toBeTrue();
});

test('institution_id uuid validation fails specifically on the uuid rule', function (): void {
    $rules = buildFormRequest(StoreUserGroupRequest::class, ['institution_id' => 'bad'])->rules();

    $validator = Validator::make([
        'institution_id' => 'bad',
        'title' => Utility::getTranslatable('Group'),
    ], $rules);
    $validator->fails();

    $failedRules = $validator->failed();
    $institutionFailures = $failedRules['institution_id'] ?? null;

    if (! is_array($institutionFailures)) {
        throw new RuntimeException('Expected institution_id validation failures.');
    }

    expect(array_key_exists('Uuid', $institutionFailures))->toBeTrue();
});

test('institution_id must exist in institutions table', function (): void {
    $rules = buildFormRequest(StoreUserGroupRequest::class, [])->rules();
    $fakeUuid = (string) Str::uuid();

    $validator = Validator::make([
        'institution_id' => $fakeUuid,
        'title' => Utility::getTranslatable('Group'),
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('institution_id'))->toBeTrue();
});

test('title is required and must not be empty translations', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id])->rules();

    $validator = Validator::make(['institution_id' => $institution->id, 'title' => []], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue();
});

test('validation passes with valid institution and title', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id])->rules();

    $validator = Validator::make([
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Group'),
    ], $rules);

    expect($validator->passes())->toBeTrue();
});

test('authorize returns false when no user is authenticated', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when institution not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(StoreUserGroupRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks create_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has create_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_user_groups');

    $request = buildAdminFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('institution accessor returns the institution model for a valid id', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id]);

    expect($request->institution()?->id)->toBe($institution->id);
});

test('institution accessor returns null when no institution_id given', function (): void {
    $request = buildFormRequest(StoreUserGroupRequest::class, []);

    expect($request->institution())->toBeNull();
});
