<?php

use App\Http\Requests\Admin\UserGroupIdRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(UserGroupIdRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UserGroupIdRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('userGroup accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Test Group'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UserGroupIdRequest::class, ['id' => $userGroup->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->userGroup()->id)->toBe($userGroup->id);
});

test('userGroup accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Test Group 2'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UserGroupIdRequest::class, ['id' => $userGroup->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $userGroup->delete();

    expect(fn () => $request->userGroup())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new UserGroupIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:user_groups,id');
});
