<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Services\Http\HomePageDataBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HomePageDataBuilder::class);

uses(RefreshDatabase::class);

test('buildStartPageData returns props with appName and institutions when multiple resource groups exist', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => true]);
    ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => true]);

    $builder = app(HomePageDataBuilder::class);
    $data = $builder->buildStartPageData();

    expect($data)->toHaveKey('props')
        ->and($data['props'])->toHaveKeys(['appName', 'institutions']);
});

test('buildStartPageData returns redirect with institution_slug and resource_group_slug when exactly one resource group exists', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => true]);

    $builder = app(HomePageDataBuilder::class);
    $data = $builder->buildStartPageData();

    expect($data)->toHaveKey('redirect')
        ->and($data['redirect'])->toHaveKeys(['institution_slug', 'resource_group_slug'])
        ->and($data['redirect']['institution_slug'])->toBe($institution->slug)
        ->and($data['redirect']['resource_group_slug'])->toBe($rg->slug);
});

test('buildHomePageData returns array with all expected keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = app(HomePageDataBuilder::class);
    $data = $builder->buildHomePageData($rg);

    expect($data)->toHaveKeys([
        'resourceGroup',
        'settings',
        'hiddenDays',
        'isMultiTenancy',
    ]);
});

test('buildHomePageData isMultiTenancy is true when more than one active resource group exists', function (): void {
    $inst1 = Institution::factory()->create(['is_active' => true]);
    $inst2 = Institution::factory()->create(['is_active' => true]);
    $rg1 = ResourceGroup::factory()->for($inst1, 'institution')->create(['is_active' => true]);
    ResourceGroup::factory()->for($inst2, 'institution')->create(['is_active' => true]);

    $builder = app(HomePageDataBuilder::class);
    $data = $builder->buildHomePageData($rg1);

    expect($data['isMultiTenancy'])->toBeTrue();
});

test('buildHomePageData isMultiTenancy is false when only one active resource group exists', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => true]);

    $builder = app(HomePageDataBuilder::class);
    $data = $builder->buildHomePageData($rg);

    expect($data['isMultiTenancy'])->toBeFalse();
});

test('buildTerminalViewData returns array with resourceGroup settings and hiddenDays keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = app(HomePageDataBuilder::class);
    $data = $builder->buildTerminalViewData($rg);

    expect($data)->toHaveKeys([
        'resourceGroup',
        'settings',
        'hiddenDays',
    ]);
});
