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

    expect($data)->toHaveKey('settings');
});

test('update changes setting value', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings->where('key', 'timezone')->first();
    assert($setting instanceof Setting);

    $service = app(SettingAdminService::class);
    $updated = $service->update($setting, ['value' => 'Europe/Berlin']);

    expect($updated->value)->toBe('Europe/Berlin');
});

test('getEditFormData returns setting settingable and settingable_type keys', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings->where('key', 'timezone')->first();
    assert($setting instanceof Setting);

    $service = app(SettingAdminService::class);
    $data = $service->getEditFormData($setting);

    expect($data)->toHaveKey('setting')
        ->and($data)->toHaveKey('settingable')
        ->and($data)->toHaveKey('settingable_type');
});

test('update calls adminLoggingService log', function (): void {
    Log::shouldReceive('channel')->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once()->with(Mockery::type('string'));

    $institution = Institution::factory()->create();
    $setting = $institution->settings->where('key', 'timezone')->first();
    assert($setting instanceof Setting);

    $service = app(SettingAdminService::class);
    $service->update($setting, ['value' => 'UTC']);
});
