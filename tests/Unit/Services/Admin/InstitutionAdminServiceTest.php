<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Models\WeekDay;
use App\Services\Admin\InstitutionAdminService;
use App\Services\AdminLoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(InstitutionAdminService::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

// -------------------------------------------------------------------------
// getIndexData
// -------------------------------------------------------------------------

test('getIndexData returns institutions key', function (): void {
    $service = app(InstitutionAdminService::class);
    $data = $service->getIndexData();

    expect($data)->toHaveKey('institutions');
});

test('getIndexData with logged-in admin returns all institutions', function (): void {
    Institution::factory()->create();
    Institution::factory()->create();

    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $service = app(InstitutionAdminService::class);
    $data = $service->getIndexData();

    expect($data['institutions'])->toHaveCount(2);
});

test('getIndexData without logged-in user returns all institutions unfiltered', function (): void {
    Institution::factory()->create();
    Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $data = $service->getIndexData();

    expect($data['institutions'])->toHaveCount(2);
});

// -------------------------------------------------------------------------
// getCreateFormData
// -------------------------------------------------------------------------

test('getCreateFormData returns daysOfWeek key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);

    $service = app(InstitutionAdminService::class);
    $data = $service->getCreateFormData();

    expect($data)->toHaveKey('daysOfWeek');
});

test('getCreateFormData returns languages key', function (): void {
    $service = app(InstitutionAdminService::class);
    $data = $service->getCreateFormData();

    expect($data)->toHaveKey('languages');
});

// -------------------------------------------------------------------------
// getEditFormData
// -------------------------------------------------------------------------

test('getEditFormData returns institution key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $data = $service->getEditFormData($institution);

    expect($data)->toHaveKey('institution');
});

test('getEditFormData returns daysOfWeek key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $data = $service->getEditFormData($institution);

    expect($data)->toHaveKey('daysOfWeek');
});

test('getEditFormData returns languages key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $data = $service->getEditFormData($institution);

    expect($data)->toHaveKey('languages');
});

test('getEditFormData institution is the passed model', function (): void {
    $institution = Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $data = $service->getEditFormData($institution);

    expect($data['institution']->id)->toBe($institution->id);
});

// -------------------------------------------------------------------------
// reorder
// -------------------------------------------------------------------------

test('reorder updates institution order', function (): void {
    $institution = Institution::factory()->create(['order' => 1]);

    $service = app(InstitutionAdminService::class);
    $service->reorder([['id' => $institution->id, 'order' => 7]]);

    expect(Institution::findOrFail($institution->id)->order)->toBe(7);
});

test('reorder handles multiple rows', function (): void {
    $i1 = Institution::factory()->create(['order' => 1]);
    $i2 = Institution::factory()->create(['order' => 2]);

    $service = app(InstitutionAdminService::class);
    $service->reorder([
        ['id' => $i1->id, 'order' => 10],
        ['id' => $i2->id, 'order' => 20],
    ]);

    expect(Institution::findOrFail($i1->id)->order)->toBe(10)
        ->and(Institution::findOrFail($i2->id)->order)->toBe(20);
});

// -------------------------------------------------------------------------
// store
// -------------------------------------------------------------------------

test('store creates institution with given attributes', function (): void {
    $service = app(InstitutionAdminService::class);
    $institution = $service->store([
        'title' => ['en' => 'Test Inst', 'de' => 'Test Inst'],
        'short_title' => 'TI',
        'slug' => 'test-inst-'.uniqid(),
        'is_active' => true,
    ], []);

    expect($institution)->toBeInstanceOf(Institution::class)
        ->and($institution->id)->not->toBeNull();
});

test('store creates initial settings for institution', function (): void {
    $service = app(InstitutionAdminService::class);
    $institution = $service->store([
        'title' => ['en' => 'Another Inst', 'de' => 'Another Inst'],
        'short_title' => 'AI',
        'slug' => 'another-inst-'.uniqid(),
        'is_active' => true,
    ], []);

    expect($institution->settings()->count())->toBeGreaterThan(0);
});

test('store syncs week days', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    /** @var WeekDay $weekDay */
    $weekDay = WeekDay::query()->firstOrFail();

    $service = app(InstitutionAdminService::class);
    $institution = $service->store([
        'title' => ['en' => 'Synced Inst', 'de' => 'Synced Inst'],
        'short_title' => 'SI',
        'slug' => 'synced-inst-'.uniqid(),
        'is_active' => true,
    ], [(string) $weekDay->id]);

    expect($institution->week_days()->count())->toBe(1);
});

// -------------------------------------------------------------------------
// update
// -------------------------------------------------------------------------

test('update saves changed attributes and returns institution', function (): void {
    $institution = Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $updated = $service->update($institution, ['short_title' => 'UPDATED'], []);

    expect($updated)->toBeInstanceOf(Institution::class)
        ->and($updated->short_title)->toBe('UPDATED')
        ->and(Institution::findOrFail($institution->id)->short_title)->toBe('UPDATED');
});

test('update syncs week days', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 2, 'key' => 'tuesday']);
    /** @var WeekDay $weekDay */
    $weekDay = WeekDay::query()->firstOrFail();
    $institution = Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $service->update($institution, [], [(string) $weekDay->id]);

    expect($institution->week_days()->count())->toBe(1);
});

// -------------------------------------------------------------------------
// delete
// -------------------------------------------------------------------------

test('delete removes institution', function (): void {
    $institution = Institution::factory()->create();
    $id = $institution->id;

    $service = app(InstitutionAdminService::class);
    $service->delete($institution);

    expect(Institution::find($id))->toBeNull();
});

// -------------------------------------------------------------------------
// eager loads (RemoveArrayItem)
// -------------------------------------------------------------------------

test('getIndexData institutions have closings relation loaded', function (): void {
    Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $data = $service->getIndexData();

    $institution = $data['institutions']->first();
    // RemoveArrayItem would remove 'closings' from the with() call
    expect($institution->relationLoaded('closings'))->toBeTrue();
});

test('getIndexData institutions have resource_groups relation loaded', function (): void {
    Institution::factory()->create();

    $service = app(InstitutionAdminService::class);
    $data = $service->getIndexData();

    $institution = $data['institutions']->first();
    // RemoveArrayItem would remove 'resource_groups' from the with() call
    expect($institution->relationLoaded('resource_groups'))->toBeTrue();
});

// -------------------------------------------------------------------------
// InstanceOfToFalse: filter only fires when user is a User instance
// -------------------------------------------------------------------------

test('getIndexData filters institutions by user visibility when admin is logged in', function (): void {
    Institution::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $service = app(InstitutionAdminService::class);
    $data = $service->getIndexData();

    // InstanceOfToFalse would skip the filter entirely regardless of user
    // Admin can view all institutions, so count should match
    expect($data['institutions']->count())->toBe(1);
});

test('getIndexData filters out institutions a restricted non-admin user cannot view', function (): void {
    // InstanceOfToFalse on line 27 changes "if ($user instanceof User)" to "if (false)",
    // skipping the filter entirely — a non-admin with no roles who can view nothing would
    // then incorrectly see all institutions.
    Institution::factory()->create();
    Institution::factory()->create();

    $user = User::factory()->create(['is_admin' => false]); // no roles → can view no institutions
    $this->actingAs($user);

    $service = app(InstitutionAdminService::class);
    $data = $service->getIndexData();

    // With original: filter runs, user can view 0 institutions → count = 0
    // With InstanceOfToFalse: filter skipped → count = 2
    expect($data['institutions'])->toHaveCount(0);
});

// -------------------------------------------------------------------------
// logging side effects
// -------------------------------------------------------------------------

test('reorder logs the reordered institution', function (): void {
    $institution = Institution::factory()->create(['order' => 1]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('reordered institution', Mockery::type(Institution::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(InstitutionAdminService::class);
    $service->reorder([['id' => $institution->id, 'order' => 3]]);
});

test('store logs the created institution', function (): void {
    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('created', Mockery::type(Institution::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(InstitutionAdminService::class);
    $service->store([
        'title' => ['en' => 'Log Inst'],
        'short_title' => 'LI',
        'slug' => 'log-inst-'.uniqid(),
        'is_active' => true,
    ], []);
});

test('update logs the updated institution', function (): void {
    $institution = Institution::factory()->create();

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('updated', Mockery::type(Institution::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(InstitutionAdminService::class);
    $service->update($institution, ['short_title' => 'UPD'], []);
});

test('delete logs the deleted institution', function (): void {
    $institution = Institution::factory()->create();

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('deleted', Mockery::type(Institution::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(InstitutionAdminService::class);
    $service->delete($institution);
});
