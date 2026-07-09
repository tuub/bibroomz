<?php

declare(strict_types=1);

use App\Http\Requests\Admin\SettingKeyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

covers(SettingKeyRequest::class);

uses(RefreshDatabase::class);

test('SettingKeyRequest defines validation rules', function (): void {
    $request = new SettingKeyRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('settingable_type')
        ->and($rules['settingable_type'])->toContain('required')
        ->and($rules)->toHaveKey('settingable_id')
        ->and($rules['settingable_id'])->toContain('required')
        ->and($rules)->toHaveKey('key')
        ->and($rules['key'])->toContain('required')
        ->and($rules['key'])->toContain('string');
});

test('SettingKeyRequest authorize returns true', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new SettingKeyRequest;

    expect($request->authorize())->toBeTrue();
});

test('SettingKeyRequest key accessor returns validated key', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(SettingKeyRequest::class, [
        'settingable_type' => 'institution',
        'settingable_id' => (string) Str::uuid(),
        'key' => 'timezone',
    ], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->key())->toBe('timezone');
});
