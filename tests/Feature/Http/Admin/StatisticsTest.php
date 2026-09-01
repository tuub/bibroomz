<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\StatisticsAdminService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;

covers(StatisticsController::class, StatisticsAdminService::class, StatisticsRequest::class);

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(PermissionSeeder::class));

test('admin statistics page returns 200 and presents booking counts for a permitted institution', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    Happening::factory()->count(2)->for($resource, 'resource')->create();

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('comparison', null)
            ->has('institutions', 1, fn (Assert $institutionStat): AssertableJson => $institutionStat
                ->where('id', $institution->id)
                ->where('count', 2)
                ->etc())
            ->has('resourceGroups', 1, fn (Assert $resourceGroupStat): AssertableJson => $resourceGroupStat
                ->where('id', $resourceGroup->id)
                ->where('count', 2)
                ->etc())
            ->has('resources', 1, fn (Assert $resourceStat): AssertableJson => $resourceStat
                ->where('id', $resource->id)
                ->where('count', 2)
                ->etc()));
});

test('admin statistics page presents cancellation status by institution, resource group and resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::factory()->for($resource, 'resource')->create();
    Happening::factory()->for($resource, 'resource')->create();
    $cancelled = Happening::factory()->for($resource, 'resource')->create();
    $cancelled->delete();

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('institutions.0.active', 2)
            ->where('institutions.0.cancelled', 1)
            ->where('resourceGroups.0.active', 2)
            ->where('resourceGroups.0.cancelled', 1)
            ->where('resources.0.active', 2)
            ->where('resources.0.cancelled', 1)
            ->where('cancellations.active', 2)
            ->where('cancellations.cancelled', 1));
});

test('admin statistics page presents peak-times heatmap data', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::factory()->for($resource, 'resource')->create([
        'start' => '2026-01-05 10:00:00',
        'end' => '2026-01-05 11:00:00',
    ]);

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['range' => 'custom', 'from' => '2026-01-01', 'to' => '2026-01-31']))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->has('heatmap.cells', 168)
            ->where('heatmap.maxCount', 1)
            ->where('heatmap.totalCount', 1)
            ->where('heatmap.cells.10.dayOfWeek', 1)
            ->where('heatmap.cells.10.hour', 10)
            ->where('heatmap.cells.10.count', 1)
            ->where('heatmap.cells.10.percentage', 100));
});

test('admin statistics page presents period comparison data', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::factory()->for($resource, 'resource')->create([
        'start' => '2026-02-05 10:00:00',
        'end' => '2026-02-05 11:00:00',
    ]);
    Happening::factory()->for($resource, 'resource')->create([
        'start' => '2026-02-06 10:00:00',
        'end' => '2026-02-06 11:00:00',
    ]);
    Happening::factory()->for($resource, 'resource')->create([
        'start' => '2026-01-05 10:00:00',
        'end' => '2026-01-05 11:00:00',
    ]);

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', [
            'range' => 'custom',
            'from' => '2026-02-01',
            'to' => '2026-02-28',
            'compare_from' => '2026-01-01',
            'compare_to' => '2026-01-31',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('comparison.from', '2026-01-01')
            ->where('comparison.to', '2026-01-31')
            ->where('comparison.currentCount', 2)
            ->where('comparison.comparisonCount', 1)
            ->where('comparison.deltaPct', 100)
            ->where('comparison.resources.0.count', 1)
            ->has('comparison.timeSeries', 1)
            ->where('comparison.timeSeries.0.label', '2026-01')
            ->where('comparison.timeSeries.0.count', 1));
});

test('admin statistics page redirects unauthenticated guest', function (): void {
    $this->get(route('admin.statistics.index'))
        ->assertRedirect();
});

test('admin statistics page returns 403 for user without admin permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.statistics.index'))
        ->assertForbidden();
});

test('admin statistics page hides institutions the user has no view_happenings permission for', function (): void {
    $institution = Institution::factory()->create();

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_users');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->has('institutions', 0));
});

test('admin statistics page filters booking counts by a custom range', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::factory()->for($resource, 'resource')->create(['start' => '2026-01-15 10:00:00', 'end' => '2026-01-15 11:00:00']);
    Happening::factory()->for($resource, 'resource')->create(['start' => '2027-01-15 10:00:00', 'end' => '2027-01-15 11:00:00']);

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['range' => 'custom', 'from' => '2026-01-01', 'to' => '2026-12-31']))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('range', 'custom')
            ->where('from', '2026-01-01')
            ->where('to', '2026-12-31')
            ->has('resources', 1, fn (Assert $resourceStat): AssertableJson => $resourceStat
                ->where('id', $resource->id)
                ->where('count', 1)
                ->etc()));
});

test('admin statistics page rejects an unknown range value', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['range' => 'not-a-real-range']))
        ->assertInvalid('range');
});

test('admin statistics page rejects a custom range where "to" precedes "from"', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['range' => 'custom', 'from' => '2026-06-01', 'to' => '2026-01-01']))
        ->assertInvalid('to');
});

test('admin statistics page rejects a comparison range where "compare_to" precedes "compare_from"', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['compare_from' => '2026-06-01', 'compare_to' => '2026-01-01']))
        ->assertInvalid('compare_to');
});

test('admin statistics page defaults to a monthly time series with 12 buckets', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('granularity', 'month')
            ->has('timeSeries', 12));
});

test('admin statistics page returns a weekly time series with 12 buckets', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['granularity' => 'week']))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('granularity', 'week')
            ->has('timeSeries', 12));
});

test('admin statistics page returns a yearly time series with 5 buckets', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['granularity' => 'year']))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('granularity', 'year')
            ->has('timeSeries', 5));
});

test('admin statistics page counts bookings into the correct monthly bucket', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::factory()->for($resource, 'resource')->create(['start' => now()->startOfMonth()->addHour(), 'end' => now()->startOfMonth()->addHours(2)]);

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['granularity' => 'month']))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->has('timeSeries', 12)
            ->where('timeSeries.11.label', now()->format('Y-m'))
            ->where('timeSeries.11.count', 1)
            ->etc());
});

test('admin statistics page rejects an unknown granularity value', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['granularity' => 'not-a-real-granularity']))
        ->assertInvalid('granularity');
});

test('admin statistics page shrinks the time series window to match the selected range', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['range' => 'this_month']))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->has('timeSeries', 1)
            ->where('timeSeries.0.label', now()->format('Y-m')));
});

test('admin statistics page scopes the time series to the selected resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $otherResource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::factory()->for($resource, 'resource')->create(['start' => now(), 'end' => now()->addHour()]);
    Happening::factory()->count(3)->for($otherResource, 'resource')->create(['start' => now(), 'end' => now()->addHour()]);

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', ['resource_id' => $resource->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Statistics/Index')
            ->where('timeSeriesResourceId', (string) $resource->id)
            ->where('timeSeries.11.count', 1));
});
