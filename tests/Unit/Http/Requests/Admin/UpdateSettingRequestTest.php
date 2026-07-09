<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules)->toHaveKey('settingable_id')
        ->and($rules['settingable_id'])->toContain('required')
        ->and($rules['settingable_id'])->toContain('uuid')
        ->and($rules)->toHaveKey('settingable_type')
        ->and($rules['settingable_type'])->toContain('required')
        ->and($rules['settingable_type'])->toContain('string')
        ->and($rules)->toHaveKey('key')
        ->and($rules['key'])->toContain('required')
        ->and($rules)->toHaveKey('value')
        ->and($rules['value'])->toContain('required');
});

test('system notification value may be empty', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings()->where('key', 'system_notification')->firstOrFail();
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(UpdateSettingRequest::class, [
        'id' => $setting->id,
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'system_notification',
        'value' => null,
    ], $user);

    expect($request->rules()['value'])->toBe(['nullable', 'string']);
});

test('id is required', function (): void {
    $rules = buildFormRequest(UpdateSettingRequest::class, [])->rules();

    $validator = Validator::make([
        'settingable_id' => (string) Str::uuid(),
        'settingable_type' => 'institution',
        'key' => 'some_key',
        'value' => 'some_value',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('id'))->toBeTrue();
});

test('settingable_id is required', function (): void {
    $rules = buildFormRequest(UpdateSettingRequest::class, [])->rules();

    $validator = Validator::make([
        'id' => (string) Str::uuid(),
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
        'id' => (string) Str::uuid(),
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
        'id' => (string) Str::uuid(),
        'settingable_id' => (string) Str::uuid(),
        'settingable_type' => 'institution',
        'key' => 'some_key',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('value'))->toBeTrue();
});

test('authorize returns false when no user is authenticated', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);
    $request = buildFormRequest(UpdateSettingRequest::class, ['id' => $setting->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when setting not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UpdateSettingRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when admin user has no target setting', function (): void {
    $request = buildAdminFormRequest(UpdateSettingRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit permission on setting', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UpdateSettingRequest::class, ['id' => $setting->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can edit setting', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(UpdateSettingRequest::class, ['id' => $setting->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('settingOrNull returns null when no id given', function (): void {
    $request = buildFormRequest(UpdateSettingRequest::class, []);

    expect($request->settingOrNull())->toBeNull();
});

test('settingOrNull returns the setting model for a valid id', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);

    $request = buildFormRequest(UpdateSettingRequest::class, ['id' => $setting->id]);

    expect($request->settingOrNull()?->id)->toBe($setting->id);
});

test('setting accessor returns the model after validation', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $setting->id,
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'test_key',
        'value' => 'test_value',
    ];
    $request = buildAdminFormRequest(UpdateSettingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->setting()->id)->toBe($setting->id);
});

test('setting accessor throws ModelNotFoundException when model not found', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $setting->id,
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'test_key',
        'value' => 'test_value',
    ];
    $request = buildAdminFormRequest(UpdateSettingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $setting->delete();

    expect(fn () => $request->setting())->toThrow(ModelNotFoundException::class);
});

test('settingableId and settingableType accessors return validated values', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $setting->id,
        'settingable_id' => $institution->id,
        'settingable_type' => Institution::class,
        'key' => 'test_key',
        'value' => 'test_value',
    ];
    $request = buildAdminFormRequest(UpdateSettingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->settingableId())->toBe($institution->id)
        ->and($request->settingableType())->toBe(Institution::class);
});
