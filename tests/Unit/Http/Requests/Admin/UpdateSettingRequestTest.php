<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPermissions;

covers(UpdateSettingRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('rules include all setting fields', function (): void {
    $request = buildFormRequest(UpdateSettingRequest::class, []);
    $rules = $request->rules();

    expect($rules)->toHaveKey('settingable_id')
        ->and($rules['settingable_id'])->toContain('required')
        ->and($rules['settingable_id'])->toContain('uuid')
        ->and($rules)->toHaveKey('settingable_type')
        ->and($rules['settingable_type'])->toContain('required')
        ->and($rules['settingable_type'])->toContain('string')
        ->and($rules)->toHaveKey('key')
        ->and($rules['key'])->toContain('required')
        ->and($rules['key'])->toContain('string')
        ->and($rules)->toHaveKey('value')
        ->and($rules['value'])->toContain('required');
});

test('system notification value may be empty', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(UpdateSettingRequest::class, [
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'system_notification',
        'value' => null,
    ], $user);

    expect($request->rules()['value'])->toBe(['nullable', 'string']);
});

test('settingable_id is required', function (): void {
    $rules = buildFormRequest(UpdateSettingRequest::class, [])->rules();

    $validator = Validator::make([
        'settingable_type' => 'institution',
        'key' => 'some_key',
        'value' => 'some_value',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('settingable_id'))->toBeTrue();
});

test('key is required', function (): void {
    $rules = buildFormRequest(UpdateSettingRequest::class, [])->rules();

    $validator = Validator::make([
        'settingable_id' => (string) Str::uuid(),
        'settingable_type' => 'institution',
        'value' => 'some_value',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('key'))->toBeTrue();
});

test('value is required', function (): void {
    $rules = buildFormRequest(UpdateSettingRequest::class, [])->rules();

    $validator = Validator::make([
        'settingable_id' => (string) Str::uuid(),
        'settingable_type' => 'institution',
        'key' => 'some_key',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('value'))->toBeTrue();
});

test('authorize returns false when no user is authenticated', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(UpdateSettingRequest::class, [
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'timezone',
    ]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when settingable is not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UpdateSettingRequest::class, [
        'settingable_type' => Institution::class,
        'settingable_id' => (string) Str::uuid(),
        'key' => 'timezone',
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when admin user has invalid settingable type', function (): void {
    $request = buildAdminFormRequest(UpdateSettingRequest::class, [
        'settingable_type' => 'unknown',
        'settingable_id' => (string) Str::uuid(),
        'key' => 'timezone',
    ], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit permission on settingable', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UpdateSettingRequest::class, [
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'timezone',
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can edit setting definition', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(UpdateSettingRequest::class, [
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'timezone',
    ], $user);

    expect($request->authorize())->toBeTrue();
});

test('settingableOrNull returns null when no settingable is given', function (): void {
    $request = buildFormRequest(UpdateSettingRequest::class, []);

    expect($request->settingableOrNull())->toBeNull();
});

test('settingableOrNull returns the model for a valid institution context', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(UpdateSettingRequest::class, [
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
    ]);

    expect($request->settingableOrNull()?->id)->toBe($institution->id);
});

test('settingableOrNull returns the model for a valid resource group context', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $request = buildFormRequest(UpdateSettingRequest::class, [
        'settingable_type' => ResourceGroup::class,
        'settingable_id' => $resourceGroup->id,
    ]);

    expect($request->settingableOrNull()?->id)->toBe($resourceGroup->id);
});

test('key must be a known definition for the selected settingable type', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(UpdateSettingRequest::class, [
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'timezone',
    ])->rules();

    $validator = Validator::make([
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'missing_key',
        'value' => 'test',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('key'))->toBeTrue();
});

test('settingableId settingableType and key accessors return validated values', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'timezone',
        'value' => 'test_value',
    ];
    $request = buildAdminFormRequest(UpdateSettingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->settingableId())->toBe($institution->id)
        ->and($request->settingableType())->toBe(Institution::class)
        ->and($request->key())->toBe('timezone');
});
