<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Setting;
use App\Services\Admin\SettingAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

covers(SettingAdminService::class);

uses(RefreshDatabase::class);

test('getIndexData returns settings key', function (): void {
    $institution = Institution::factory()->create();
    $service = app(SettingAdminService::class);
    $data = $service->getIndexData($institution, 'institution');

    expect($data)->toHaveKey('settings')
        ->and($data['settings'])->toHaveCount(count(Setting::getDefinitionKeys('institution')));
});

test('update changes setting value', function (): void {
    $institution = Institution::factory()->create();
    $service = app(SettingAdminService::class);
    $updated = $service->update($institution, ['key' => 'timezone', 'value' => 'Europe/Berlin']);

    expect($updated->value)->toBe('Europe/Berlin');
});

test('getEditFormData returns setting settingable settingable_type and input_type keys', function (): void {
    $institution = Institution::factory()->create();
    $service = app(SettingAdminService::class);
    $data = $service->getEditFormData($institution, 'institution', 'timezone');

    expect($data)->toHaveKey('setting')
        ->and($data)->toHaveKey('settingable')
        ->and($data)->toHaveKey('settingable_type')
        ->and($data)->toHaveKey('input_type')
        ->and($data['setting']['key'])->toBe('timezone')
        ->and($data['input_type'])->toBe('text');
});

test('getEditFormData returns default payload when the setting row is missing', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'system_notification')->delete();
    $service = app(SettingAdminService::class);
    $data = $service->getEditFormData($institution, 'institution', 'system_notification');

    expect($data['setting']['id'])->toBeNull()
        ->and($data['setting']['key'])->toBe('system_notification')
        ->and($data['setting']['value'])->toBe('')
        ->and($data['input_type'])->toBe('textarea');
});

test('getIndexData keeps definition entries when a setting row is missing', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'timezone')->delete();
    $service = app(SettingAdminService::class);
    $data = $service->getIndexData($institution, 'institution');

    expect(array_column($data['settings'], 'key'))->toContain('timezone');
});

test('update creates a missing setting row', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'system_notification')->delete();
    $service = app(SettingAdminService::class);

    $created = $service->update($institution, ['key' => 'system_notification', 'value' => 'Outage']);

    expect($created->exists)->toBeTrue()
        ->and($created->key)->toBe('system_notification')
        ->and($created->value)->toBe('Outage');
});

test('update calls adminLoggingService log', function (): void {
    Log::shouldReceive('channel')->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once()->with(Mockery::type('string'));

    $institution = Institution::factory()->create();
    $service = app(SettingAdminService::class);
    $service->update($institution, ['key' => 'timezone', 'value' => 'UTC']);
});
