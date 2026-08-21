<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\HappeningAdminService;
use App\Services\AdminLoggingService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(HappeningAdminService::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

// -------------------------------------------------------------------------
// getIndexData
// -------------------------------------------------------------------------

test('getIndexData returns happenings key', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $service = app(HappeningAdminService::class);
    $data = $service->getIndexData($user);

    expect($data)->toHaveKey('happenings');
});

// -------------------------------------------------------------------------
// getCreateFormData
// -------------------------------------------------------------------------

test('getCreateFormData returns resources key', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $service = app(HappeningAdminService::class);
    $data = $service->getCreateFormData($user);

    expect($data)->toHaveKey('resources');
});

test('getCreateFormData returns users key', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $service = app(HappeningAdminService::class);
    $data = $service->getCreateFormData($user);

    expect($data)->toHaveKey('users');
});

test('getCreateFormData returns languages key', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $service = app(HappeningAdminService::class);
    $data = $service->getCreateFormData($user);

    expect($data)->toHaveKey('languages');
});

test('getCreateFormData resources items contain id title institution_id is_verification_required', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    $user = User::factory()->create(['is_admin' => true]);

    $service = app(HappeningAdminService::class);
    $data = $service->getCreateFormData($user);

    $resource = $data['resources']->first();

    expect($resource)
        ->toHaveKey('id')
        ->toHaveKey('title')
        ->toHaveKey('institution_id')
        ->toHaveKey('is_verification_required');
});

test('getCreateFormData users items contain id name permissions', function (): void {
    User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    $service = app(HappeningAdminService::class);
    $data = $service->getCreateFormData($user);

    $userItem = $data['users']->first();

    expect($userItem)
        ->toHaveKey('id')
        ->toHaveKey('name')
        ->toHaveKey('permissions');
});

// -------------------------------------------------------------------------
// getEditFormData
// -------------------------------------------------------------------------

test('getEditFormData returns happening key', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $service = app(HappeningAdminService::class);
    $data = $service->getEditFormData($happening);

    expect($data)->toHaveKey('happening');
});

test('getEditFormData returns resources key', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $service = app(HappeningAdminService::class);
    $data = $service->getEditFormData($happening);

    expect($data)->toHaveKey('resources');
});

test('getEditFormData returns users key', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $service = app(HappeningAdminService::class);
    $data = $service->getEditFormData($happening);

    expect($data)->toHaveKey('users');
});

test('getEditFormData returns languages key', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $service = app(HappeningAdminService::class);
    $data = $service->getEditFormData($happening);

    expect($data)->toHaveKey('languages');
});

test('getEditFormData happening array contains all required keys', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $service = app(HappeningAdminService::class);
    $data = $service->getEditFormData($happening);

    expect($data['happening'])
        ->toHaveKey('id')
        ->toHaveKey('user_id_01')
        ->toHaveKey('user_id_02')
        ->toHaveKey('resource_id')
        ->toHaveKey('is_verified')
        ->toHaveKey('verifier')
        ->toHaveKey('start_date')
        ->toHaveKey('start_time')
        ->toHaveKey('end_date')
        ->toHaveKey('end_time')
        ->toHaveKey('label');
});

test('getEditFormData resources items contain id title resource_group_id institution_id is_verification_required', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $service = app(HappeningAdminService::class);
    $data = $service->getEditFormData($happening);

    $resourceItem = $data['resources']->first();

    expect($resourceItem)
        ->toHaveKey('id')
        ->toHaveKey('title')
        ->toHaveKey('resource_group_id')
        ->toHaveKey('institution_id')
        ->toHaveKey('is_verification_required')
        ->and($resourceItem['institution_id'])->toBe($institution->id);
});

// -------------------------------------------------------------------------
// store
// -------------------------------------------------------------------------

test('store creates happening and returns it', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $service = app(HappeningAdminService::class);
    $happening = $service->store([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour()->format('d.m.Y H:i'),
        'end' => now()->addHours(2)->format('d.m.Y H:i'),
        'is_verified' => false,
    ]);

    expect($happening)->toBeInstanceOf(Happening::class)
        ->and($happening->id)->not->toBeNull();
});

// -------------------------------------------------------------------------
// update
// -------------------------------------------------------------------------

test('update saves changes to happening and returns it', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $service = app(HappeningAdminService::class);
    $updated = $service->update($happening, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour()->format('d.m.Y H:i'),
        'end' => now()->addHours(2)->format('d.m.Y H:i'),
        'is_verified' => false,
    ]);

    expect($updated)->toBeInstanceOf(Happening::class)
        ->and($updated->id)->toBe($happening->id);
});

// -------------------------------------------------------------------------
// delete
// -------------------------------------------------------------------------

test('delete removes happening', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);
    $id = $happening->id;

    $service = app(HappeningAdminService::class);
    $service->delete($happening);

    expect(Happening::find($id))->toBeNull();
});

// -------------------------------------------------------------------------
// logging side effects
// -------------------------------------------------------------------------

test('store logs the created happening', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('created', Mockery::type(Happening::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(HappeningAdminService::class);
    $service->store([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour()->format('d.m.Y H:i'),
        'end' => now()->addHours(2)->format('d.m.Y H:i'),
        'is_verified' => false,
    ]);
});

test('update logs the updated happening', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('updated', Mockery::type(Happening::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(HappeningAdminService::class);
    $service->update($happening, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour()->format('d.m.Y H:i'),
        'end' => now()->addHours(2)->format('d.m.Y H:i'),
        'is_verified' => false,
    ]);
});

test('delete logs the deleted happening', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('deleted', Mockery::type(Happening::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(HappeningAdminService::class);
    $service->delete($happening);
});

// -------------------------------------------------------------------------
// resourceTranslations / userPermissions private method coverage
// -------------------------------------------------------------------------

test('getCreateFormData resources items have non-empty title translation array', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create([
        'is_active' => true,
        'title' => ['en' => 'Room A', 'de' => 'Raum A'],
    ]);

    $user = User::factory()->create(['is_admin' => true]);
    $service = app(HappeningAdminService::class);
    $data = $service->getCreateFormData($user);

    $resourceItem = $data['resources']->first();

    // ForeachEmptyIterable / AlwaysReturnEmptyArray mutations would make title empty
    expect($resourceItem['title'])->not->toBeEmpty()
        ->and($resourceItem['title'])->toHaveKey('en');
});

test('getCreateFormData users items have non-empty permissions when admin', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    $this->seed(PermissionSeeder::class);

    $service = app(HappeningAdminService::class);
    $data = $service->getCreateFormData($user);

    $userItem = $data['users']->firstWhere('id', $user->id);

    // RemoveArrayItem mutation would drop the 'permissions' key
    expect($userItem)->toHaveKey('permissions')
        ->and($userItem['permissions'])->not->toBeNull();
});
