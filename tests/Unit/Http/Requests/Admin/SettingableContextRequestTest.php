<?php

use App\Http\Requests\Admin\SettingableContextRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

covers(SettingableContextRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(SettingableContextRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('settingableType returns the input value', function (): void {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();

    $request = buildAdminFormRequest(SettingableContextRequest::class, [
        'settingable_type' => 'institution',
        'settingable_id' => $uuid,
    ], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->settingableType())->toBe('institution');
});

test('settingableId returns the input value', function (): void {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();

    $request = buildAdminFormRequest(SettingableContextRequest::class, [
        'settingable_type' => 'institution',
        'settingable_id' => $uuid,
    ], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->settingableId())->toBe($uuid);
});

test('rules returns settingable_type and settingable_id validation rules', function (): void {
    $request = new SettingableContextRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('settingable_type')
        ->and($rules['settingable_type'])->toContain('required')
        ->and($rules['settingable_type'])->toContain('string')
        ->and($rules)->toHaveKey('settingable_id')
        ->and($rules['settingable_id'])->toContain('required')
        ->and($rules['settingable_id'])->toContain('uuid');
});
