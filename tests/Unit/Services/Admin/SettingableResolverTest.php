<?php

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Services\Admin\SettingableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(SettingableResolver::class);

uses(RefreshDatabase::class);

test('settingable resolver resolves institution', function (): void {
    $institution = Institution::factory()->create();

    $resolver = app(SettingableResolver::class);
    $resolved = $resolver->resolve('institution', $institution->id);

    expect($resolved)->toBeInstanceOf(Institution::class)
        ->and($resolved->id)->toBe($institution->id);
});

test('settingable resolver resolves resource group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $resolver = app(SettingableResolver::class);
    $resolved = $resolver->resolve('resource_group', $resourceGroup->id);

    expect($resolved)->toBeInstanceOf(ResourceGroup::class)
        ->and($resolved->id)->toBe($resourceGroup->id);
});

test('settingable resolver returns type for model', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $resolver = app(SettingableResolver::class);

    expect($resolver->typeForModel($institution))->toBe('institution')
        ->and($resolver->typeForModel($resourceGroup))->toBe('resource_group');
});

test('settingable resolver handles invalid type', function (): void {
    $resolver = app(SettingableResolver::class);

    expect(fn () => $resolver->resolve('invalid_type', 'some-id'))
        ->toThrow(Exception::class);
});
