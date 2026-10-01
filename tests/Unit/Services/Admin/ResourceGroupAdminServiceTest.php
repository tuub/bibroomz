<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\UserGroup;
use App\Services\Admin\ResourceGroupAdminService;
use App\Services\AdminLoggingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(ResourceGroupAdminService::class);

uses(MockeryPHPUnitIntegration::class, LazilyRefreshDatabase::class);

test('getIndexData returns resource groups for institution', function (): void {
    $institution = Institution::factory()->create();
    $service = app(ResourceGroupAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data)->toHaveKey('resource_groups');
});

test('store creates a resource group', function (): void {
    $institution = Institution::factory()->create();
    $service = app(ResourceGroupAdminService::class);
    $rg = $service->store([
        'institution_id' => $institution->id,
        'title' => ['en' => 'New Group', 'de' => 'Neue Gruppe'],
        'slug' => 'new-group-'.uniqid(),
        'term_singular' => ['en' => 'Room'],
        'term_plural' => ['en' => 'Rooms'],
        'description' => ['en' => 'Desc'],
        'is_active' => true,
    ]);

    expect($rg)->toBeInstanceOf(ResourceGroup::class)
        ->and($rg->id)->not->toBeNull();
});

test('delete removes resource group', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $id = $rg->id;

    $service = app(ResourceGroupAdminService::class);
    $service->delete($rg);

    expect(ResourceGroup::find($id))->toBeNull();
});

test('getIndexData returns institution key', function (): void {
    $institution = Institution::factory()->create();
    $service = app(ResourceGroupAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data)->toHaveKey('institution');
});

test('getCreateFormData returns institution and languages keys', function (): void {
    $institution = Institution::factory()->create();

    $service = app(ResourceGroupAdminService::class);
    $data = $service->getCreateFormData($institution);

    // RemoveArrayItem would drop one of these keys
    expect($data)->toHaveKey('institution')
        ->and($data)->toHaveKey('languages')
        ->and($data)->not->toHaveKey('institutions');
});

test('getCreateFormData returns the context institution with its user groups loaded', function (): void {
    $institution = Institution::factory()->create(['is_active' => false]);
    UserGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceGroupAdminService::class);
    $data = $service->getCreateFormData(Institution::query()->findOrFail($institution->id));

    // RemoveMethodCall would skip loadMissing; the user_groups relation should be loaded
    expect($data['institution']->id)->toBe($institution->id)
        ->and($data['institution']->relationLoaded('user_groups'))->toBeTrue()
        ->and($data['institution']->user_groups)->toHaveCount(1);
});

test('getEditFormData returns institution resource_group and languages keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceGroupAdminService::class);
    $data = $service->getEditFormData($rg);

    // RemoveArrayItem would drop one of these keys
    expect($data)->toHaveKey('institution')
        ->and($data)->toHaveKey('resource_group')
        ->and($data)->toHaveKey('languages')
        ->and($data)->not->toHaveKey('institutions');
});

test('getEditFormData returns the owning institution even when it is disabled', function (): void {
    $institution = Institution::factory()->create(['is_active' => false]);
    UserGroup::factory()->for($institution, 'institution')->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceGroupAdminService::class);
    $data = $service->getEditFormData(ResourceGroup::query()->findOrFail($rg->id));

    expect($data['institution']->id)->toBe($institution->id)
        ->and($data['institution']->relationLoaded('user_groups'))->toBeTrue()
        ->and($data['institution']->user_groups)->toHaveCount(1);
});

test('getEditFormData loads institution relation on resource_group', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceGroupAdminService::class);
    $data = $service->getEditFormData($rg);

    // RemoveMethodCall would skip loadMissing; the institution relation should be loaded
    expect($data['resource_group']->relationLoaded('institution'))->toBeTrue();
});

test('store logs the created resource group', function (): void {
    $institution = Institution::factory()->create();

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('created', Mockery::type(ResourceGroup::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(ResourceGroupAdminService::class);
    $service->store([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Log Group'],
        'slug' => 'log-group-'.uniqid(),
        'term_singular' => ['en' => 'Room'],
        'term_plural' => ['en' => 'Rooms'],
        'description' => ['en' => 'Desc'],
        'is_active' => true,
    ]);
});

test('update saves changed attributes and logs', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => false]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('updated', Mockery::type(ResourceGroup::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(ResourceGroupAdminService::class);
    $updated = $service->update($rg, ['is_active' => true]);

    expect($updated)->toBeInstanceOf(ResourceGroup::class)
        ->and((bool) ResourceGroup::findOrFail($rg->id)->is_active)->toBeTrue();
});

test('delete logs the deleted resource group', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('deleted', Mockery::type(ResourceGroup::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(ResourceGroupAdminService::class);
    $service->delete($rg);
});

test('reorder updates resource group order and logs', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create(['order' => 1]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('reordered resource group', Mockery::type(ResourceGroup::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(ResourceGroupAdminService::class);
    $service->reorder([['id' => $rg->id, 'order' => 5]]);

    expect(ResourceGroup::findOrFail($rg->id)->order)->toBe(5);
});
