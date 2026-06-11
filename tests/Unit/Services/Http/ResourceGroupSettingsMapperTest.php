<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Services\Http\ResourceGroupSettingsMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceGroupSettingsMapper::class);

uses(RefreshDatabase::class);

test('map returns institution and resource_group settings indexed by key', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    // Add a distinct setting to check
    $institution->settings()->create(['key' => 'test_key', 'value' => 'inst_value']);
    $resourceGroup->settings()->create(['key' => 'rg_key', 'value' => 'rg_value']);

    $mapper = app(ResourceGroupSettingsMapper::class);
    $settings = $mapper->map($resourceGroup);

    expect($settings)->toBeArray()
        ->and($settings['institution']['test_key'])->toBe('inst_value')
        ->and($settings['resource_group']['rg_key'])->toBe('rg_value');
});

test('map returns empty arrays when no settings exist', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    // Clear all settings
    $institution->settings()->delete();
    $resourceGroup->settings()->delete();

    $mapper = app(ResourceGroupSettingsMapper::class);
    $settings = $mapper->map($resourceGroup);

    expect($settings)->toBeArray()
        ->and($settings)->not->toHaveKey('institution')
        ->and($settings)->not->toHaveKey('resource_group');
});
