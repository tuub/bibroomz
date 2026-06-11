<?php

use App\Http\Requests\Admin\UserIdRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(UserIdRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UserIdRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('targetUser accessor returns the correct model', function (): void {
    $target = User::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UserIdRequest::class, ['id' => $target->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->targetUser()->id)->toBe($target->id);
});

test('targetUser accessor throws when model not found', function (): void {
    $target = User::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UserIdRequest::class, ['id' => $target->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $target->delete();

    expect(fn () => $request->targetUser())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new UserIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:users,id');
});
