<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateAppSettingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(UpdateAppSettingRequest::class);

uses(RefreshDatabase::class);

test('rules allow a nullable string system_notification', function (): void {
    $rules = buildFormRequest(UpdateAppSettingRequest::class, [])->rules();

    expect($rules)->toBe(['system_notification' => ['nullable', 'string']]);
});

test('validation passes when system_notification is missing', function (): void {
    $rules = buildFormRequest(UpdateAppSettingRequest::class, [])->rules();

    $validator = Validator::make([], $rules);

    expect($validator->fails())->toBeFalse();
});

test('validation fails when system_notification is not a string', function (): void {
    $rules = buildFormRequest(UpdateAppSettingRequest::class, [])->rules();

    $validator = Validator::make(['system_notification' => ['not-a-string']], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('system_notification'))->toBeTrue();
});

test('authorize returns false when no user is authenticated', function (): void {
    $request = buildFormRequest(UpdateAppSettingRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when authenticated user is not admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(UpdateAppSettingRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when authenticated user is admin', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(UpdateAppSettingRequest::class, [], $user);

    expect($request->authorize())->toBeTrue();
});
