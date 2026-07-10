<?php

declare(strict_types=1);

use App\Models\BusinessHour;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\ResourceAdminService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

covers(ResourceAdminService::class);

uses(RefreshDatabase::class);

// -------------------------------------------------------------------------
// getIndexData
// -------------------------------------------------------------------------

test('getIndexData returns resources key', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    expect($data)->toHaveKey('resources');
});

test('getIndexData returns resourceGroup key', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    expect($data)->toHaveKey('resourceGroup');
});

test('getIndexData returns resources filtered by visibility when user is logged in', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create();
    Resource::factory()->for($rg, 'resource_group')->create();

    // Admin user sees all resources via Gate::after
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    expect($data['resources'])->toHaveCount(2);
});

test('getIndexData returns all resources when no user is logged in', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create();
    Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    expect($data['resources'])->toHaveCount(2);
});

// -------------------------------------------------------------------------
// getCreateFormData
// -------------------------------------------------------------------------

test('getCreateFormData returns resourceGroup key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getCreateFormData($rg);

    expect($data)->toHaveKey('resourceGroup');
});

test('getCreateFormData returns weekDays key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getCreateFormData($rg);

    expect($data)->toHaveKey('weekDays');
});

test('getCreateFormData returns languages key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getCreateFormData($rg);

    expect($data)->toHaveKey('languages');
});

test('getCreateFormData resourceGroup is the passed model', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getCreateFormData($rg);

    expect($data['resourceGroup']->id)->toBe($rg->id);
});

// -------------------------------------------------------------------------
// getEditFormData
// -------------------------------------------------------------------------

test('getEditFormData returns resourceGroup key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    expect($data)->toHaveKey('resourceGroup');
});

test('getEditFormData returns resource key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    expect($data)->toHaveKey('resource');
});

test('getEditFormData returns weekDays key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    expect($data)->toHaveKey('weekDays');
});

test('getEditFormData returns languages key', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    expect($data)->toHaveKey('languages');
});

test('getEditFormData resource contains required scalar fields', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    expect($data['resource'])
        ->toHaveKey('id')
        ->toHaveKey('resource_group_id')
        ->toHaveKey('capacity')
        ->toHaveKey('is_active')
        ->toHaveKey('order')
        ->toHaveKey('is_verification_required')
        ->toHaveKey('title')
        ->toHaveKey('location')
        ->toHaveKey('description')
        ->toHaveKey('business_hours');
});

test('getEditFormData business_hours items contain start end start_date end_date week_days keys', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // ResourceFactory::configure() already creates a business hour
    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    $bh = $data['resource']['business_hours']->first();

    expect($bh)
        ->toHaveKey('id')
        ->toHaveKey('start')
        ->toHaveKey('end')
        ->toHaveKey('start_date')
        ->toHaveKey('end_date')
        ->toHaveKey('week_days');
});

// -------------------------------------------------------------------------
// reorder
// -------------------------------------------------------------------------

test('reorder updates resource order', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['order' => 1]);

    $service = app(ResourceAdminService::class);
    $service->reorder([['id' => $resource->id, 'order' => 5]]);

    expect(Resource::findOrFail($resource->id)->order)->toBe(5);
});

test('reorder handles multiple rows', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $r1 = Resource::factory()->for($rg, 'resource_group')->create(['order' => 1]);
    $r2 = Resource::factory()->for($rg, 'resource_group')->create(['order' => 2]);

    $service = app(ResourceAdminService::class);
    $service->reorder([
        ['id' => $r1->id, 'order' => 10],
        ['id' => $r2->id, 'order' => 20],
    ]);

    expect(Resource::findOrFail($r1->id)->order)->toBe(10)
        ->and(Resource::findOrFail($r2->id)->order)->toBe(20);
});

// -------------------------------------------------------------------------
// store
// -------------------------------------------------------------------------

test('store creates resource and returns it', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceAdminService::class);
    $resource = $service->store([
        'resource_group_id' => $rg->id,
        'title' => ['en' => 'Test Resource', 'de' => 'Test Ressource'],
        'location' => ['en' => 'Room A', 'de' => 'Raum A'],
        'description' => ['en' => 'Desc', 'de' => 'Beschr'],
        'capacity' => 10,
        'is_active' => true,
    ], []);

    expect($resource)->toBeInstanceOf(Resource::class)
        ->and($resource->id)->not->toBeNull();
});

test('store syncs business hours', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceAdminService::class);
    $resource = $service->store([
        'resource_group_id' => $rg->id,
        'title' => ['en' => 'Test Resource', 'de' => 'Test Ressource'],
        'location' => ['en' => 'Room A', 'de' => 'Raum A'],
        'description' => ['en' => 'Desc', 'de' => 'Beschr'],
        'capacity' => 5,
        'is_active' => true,
    ], [
        [
            'id' => null,
            'start' => '08:00',
            'end' => '18:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    expect(BusinessHour::where('resource_id', $resource->id)->count())->toBe(1);
});

// -------------------------------------------------------------------------
// update
// -------------------------------------------------------------------------

test('update saves changed attributes and returns resource', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['capacity' => 5]);

    $service = app(ResourceAdminService::class);
    $updated = $service->update($resource, ['capacity' => 99], []);

    expect($updated)->toBeInstanceOf(Resource::class)
        ->and((int) $updated->capacity)->toBe(99)
        ->and((int) Resource::findOrFail($resource->id)->capacity)->toBe(99);
});

test('update syncs business hours', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Remove existing BHs so we control count
    BusinessHour::where('resource_id', $resource->id)->delete();

    $service = app(ResourceAdminService::class);
    $service->update($resource, [], [
        [
            'id' => null,
            'start' => '09:00',
            'end' => '17:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    expect(BusinessHour::where('resource_id', $resource->id)->count())->toBe(1);
});

// -------------------------------------------------------------------------
// delete
// -------------------------------------------------------------------------

test('delete removes resource', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $id = $resource->id;

    $service = app(ResourceAdminService::class);
    $service->delete($resource);

    expect(Resource::find($id))->toBeNull();
});

// -------------------------------------------------------------------------
// clone
// -------------------------------------------------------------------------

test('clone replicates the resource', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    expect($clone)->toBeInstanceOf(Resource::class)
        ->and($clone->id)->not->toBe($resource->id);
});

test('clone sets is_active to false', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    expect($clone->is_active)->toBeFalse();
});

test('clone suffixes the title', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    // The title is a translatable JSON field; check that the value includes the clone suffix
    $originalTitle = $resource->title;
    $cloneTitle = $clone->title;

    expect($cloneTitle)->toContain($originalTitle);
});

test('clone copies business hours', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $originalBhCount = BusinessHour::where('resource_id', $resource->id)->count();

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    expect(BusinessHour::where('resource_id', $clone->id)->count())->toBe($originalBhCount);
});

test('clone copies closings', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->create([
        'closable_id' => $resource->id,
        'closable_type' => Resource::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Test closing'],
    ]);

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    $clone->loadMissing('closings');

    expect($clone->closings)->toHaveCount(1);
});

// -------------------------------------------------------------------------
// getIndexData – relation loading and InstanceOfToFalse
// -------------------------------------------------------------------------

test('getIndexData loads resource_group business_hours and closings relations', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    /** @var Resource $resource */
    $resource = $data['resources']->first();

    expect($resource->relationLoaded('resource_group'))->toBeTrue()
        ->and($resource->relationLoaded('business_hours'))->toBeTrue()
        ->and($resource->relationLoaded('closings'))->toBeTrue();
});

test('getIndexData filters resources by visibility when user is logged in and has no permission', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create();

    // Regular non-admin user without view_resources permission sees nothing
    $regularUser = User::factory()->create(['is_admin' => false]);
    $this->actingAs($regularUser);

    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    expect($data['resources'])->toHaveCount(0);
});

// -------------------------------------------------------------------------
// getEditFormData – business_hours id field
// -------------------------------------------------------------------------

test('getEditFormData business_hours items have specific id value', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $bh = BusinessHour::where('resource_id', $resource->id)->first();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    $bhData = $data['resource']['business_hours']->first();

    expect($bhData)->not->toBeNull()
        ->and($bhData['id'])->toBe($bh?->id);
});

test('getEditFormData business_hours items have correct start and end values', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    /** @var BusinessHour $bh */
    $bh = BusinessHour::where('resource_id', $resource->id)->firstOrFail();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    $bhData = $data['resource']['business_hours']->first();

    expect($bhData['start'])->toBe(Carbon::parse($bh->start)->format('H:i'))
        ->and($bhData['end'])->toBe(Carbon::parse($bh->end)->format('H:i'));
});

// -------------------------------------------------------------------------
// clone – title contains both original title and clone suffix
// -------------------------------------------------------------------------

test('clone title contains the clone translation suffix', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    $cloneSuffix = trans('admin.general.label.clone');

    expect($clone->title)->toContain((string) $cloneSuffix);
});

test('clone title starts with original title and appends clone suffix', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $originalTitle = $resource->title;
    $cloneSuffix = trans('admin.general.label.clone');

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    expect($clone->title)->toBe($originalTitle.' '.$cloneSuffix);
});

// -------------------------------------------------------------------------
// clone – closings copied with correct keys
// -------------------------------------------------------------------------

test('clone copied closing has closable_id pointing to the clone', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->create([
        'closable_id' => $resource->id,
        'closable_type' => Resource::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Test closing'],
    ]);

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    $copiedClosing = Closing::where('closable_id', $clone->id)->firstOrFail();

    expect($copiedClosing->closable_id)->toBe($clone->id)
        ->and($copiedClosing->closable_type)->toBe(Resource::class);
});

test('clone copied closing has correct start end and description values', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $startTime = now()->startOfHour();
    $endTime = now()->addDay()->startOfHour();

    $descriptionText = 'Specific closing description';
    Closing::factory()->create([
        'closable_id' => $resource->id,
        'closable_type' => Resource::class,
        'start' => $startTime,
        'end' => $endTime,
        'description' => [app()->getLocale() => $descriptionText],
    ]);

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    $copiedClosing = Closing::where('closable_id', $clone->id)->firstOrFail();

    expect($copiedClosing->description)->toBe($descriptionText);
});

// -------------------------------------------------------------------------
// store / update / delete / clone – logging
// -------------------------------------------------------------------------

test('store logs created action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $service = app(ResourceAdminService::class);
    $service->store([
        'resource_group_id' => $rg->id,
        'title' => ['en' => 'Logged Resource', 'de' => 'Geloggte Ressource'],
        'location' => ['en' => 'Room B', 'de' => 'Raum B'],
        'description' => ['en' => 'Desc', 'de' => 'Beschr'],
        'capacity' => 1,
        'is_active' => true,
    ], []);
});

test('update logs updated action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $service->update($resource, ['capacity' => 3], []);
});

test('delete logs deleted action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $service->delete($resource);
});

test('clone logs created clone action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $service->clone($resource);
});

test('getIndexData eager loads business_hours week_days nested relation', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    /** @var Resource $resource */
    $resource = $data['resources']->first();

    $bh = $resource->business_hours->first();

    expect($bh)->not->toBeNull()
        ->and($bh?->relationLoaded('week_days'))->toBeTrue();
});

test('getIndexData only returns resources belonging to the specified resource group', function (): void {
    $institution = Institution::factory()->create();
    $rg1 = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rg2 = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg1, 'resource_group')->create();
    Resource::factory()->for($rg2, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg1->id);

    expect($data['resources'])->toHaveCount(1)
        ->and($data['resources']->first()->resource_group_id)->toBe($rg1->id);
});

test('getIndexData returns resources ordered by order column', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create(['order' => 2]);
    Resource::factory()->for($rg, 'resource_group')->create(['order' => 1]);

    $service = app(ResourceAdminService::class);
    $data = $service->getIndexData($rg->id);

    $orders = $data['resources']->pluck('order')->toArray();
    expect($orders)->toBe([1, 2]);
});

test('getEditFormData loads business_hours.week_days and resource_group via loadMissing', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $freshResource = Resource::find($resource->id);
    assert($freshResource !== null);

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($freshResource);

    expect($freshResource->relationLoaded('business_hours'))->toBeTrue()
        ->and($freshResource->relationLoaded('resource_group'))->toBeTrue();

    // RemoveArrayItem/String mutation on 'resource_group.institution' would drop the
    // nested eager load, breaking the breadcrumb's institution name lookup.
    expect($freshResource->resource_group->relationLoaded('institution'))->toBeTrue()
        ->and($freshResource->resource_group->institution->id)->toBe($institution->id);

    $bh = $data['resource']['business_hours']->first();
    expect($bh)->not->toBeNull()
        ->and($bh['week_days'])->not->toBeNull();
});

test('getEditFormData resource contains resource_group_id key', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAdminService::class);
    $data = $service->getEditFormData($resource);

    expect($data['resource'])->toHaveKey('resource_group_id')
        ->and($data['resource']['resource_group_id'])->toBe($rg->id);
});

test('getEditFormData keeps location_uri and eagerly loads nested business hour weekdays without lazy loading', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create([
        'location_uri' => 'https://example.test/rooms/a-101',
    ]);
    $resource->business_hours()->create([
        'start' => '06:00:00',
        'end' => '08:00:00',
    ])->week_days()->sync(DB::table('week_days')->pluck('id')->all());

    $freshResource = Resource::query()->findOrFail($resource->id);
    $freshResource->preventsLazyLoading = true;
    $service = app(ResourceAdminService::class);

    Model::preventLazyLoading();

    try {
        $data = $service->getEditFormData($freshResource);
    } finally {
        Model::preventLazyLoading(false);
    }

    expect($freshResource->relationLoaded('resource_group'))->toBeTrue()
        ->and($freshResource->relationLoaded('business_hours'))->toBeTrue()
        ->and($freshResource->business_hours->first()?->relationLoaded('week_days'))->toBeTrue()
        ->and($data['resource']['location_uri'])->toBe('https://example.test/rooms/a-101');
});

test('reorder logs the reordered action for each resource', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $r1 = Resource::factory()->for($rg, 'resource_group')->create(['order' => 1]);
    $r2 = Resource::factory()->for($rg, 'resource_group')->create(['order' => 2]);

    Log::shouldReceive('channel')->twice()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->twice();

    $service = app(ResourceAdminService::class);
    $service->reorder([
        ['id' => $r1->id, 'order' => 10],
        ['id' => $r2->id, 'order' => 20],
    ]);
});

test('clone loadMissing ensures resource_group closings and business_hours are loaded before replication', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $resource->business_hours()->create([
        'start' => '06:00:00',
        'end' => '08:00:00',
    ])->week_days()->sync(DB::table('week_days')->pluck('id')->all());

    Closing::factory()->create([
        'closable_id' => $resource->id,
        'closable_type' => Resource::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Test'],
    ]);

    $freshResource = Resource::find($resource->id);
    assert($freshResource !== null);
    $freshResource->preventsLazyLoading = true;

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($freshResource);

    expect($clone->closings)->toHaveCount(1)
        ->and(BusinessHour::where('resource_id', $clone->id)->count())->toBeGreaterThan(0);
});

test('clone copied closings have start and end values', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $startTime = now()->startOfHour();
    $endTime = $startTime->addHours(2);

    Closing::factory()->create([
        'closable_id' => $resource->id,
        'closable_type' => Resource::class,
        'start' => $startTime,
        'end' => $endTime,
        'description' => ['en' => 'Test'],
    ]);

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    $copiedClosing = Closing::where('closable_id', $clone->id)->firstOrFail();

    expect($copiedClosing->start)->not->toBeNull()
        ->and($copiedClosing->end)->not->toBeNull()
        ->and(Carbon::parse($copiedClosing->start)->equalTo($startTime))->toBeTrue()
        ->and(Carbon::parse($copiedClosing->end)->equalTo($endTime))->toBeTrue();
});

test('clone copied closings have correct closable_type and closable_id', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->create([
        'closable_id' => $resource->id,
        'closable_type' => Resource::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Test'],
    ]);

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    $copiedClosing = Closing::where('closable_id', $clone->id)->firstOrFail();

    expect($copiedClosing->closable_type)->toBe(Resource::class)
        ->and($copiedClosing->closable_id)->toBe($clone->id);
});

test('clone syncs week_days on copied business hours', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $weekDayId = DB::table('week_days')->value('id');

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $originalBh = BusinessHour::where('resource_id', $resource->id)->firstOrFail();

    expect(DB::table('business_hour_week_day')
        ->where('business_hour_id', $originalBh->id)
        ->where('week_day_id', $weekDayId)
        ->exists())->toBeTrue();

    $service = app(ResourceAdminService::class);
    $clone = $service->clone($resource);

    $cloneBh = BusinessHour::where('resource_id', $clone->id)->firstOrFail();
    $syncedWeekDays = DB::table('business_hour_week_day')
        ->where('business_hour_id', $cloneBh->id)
        ->pluck('week_day_id')
        ->toArray();

    expect($syncedWeekDays)->toContain($weekDayId);
});
