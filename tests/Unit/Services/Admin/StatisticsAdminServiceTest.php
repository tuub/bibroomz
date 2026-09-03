<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\StatisticsAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsAdminService::class);

uses(RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function buildStatisticsFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('getIndexData returns institutions, resourceGroups and resources keys', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data)->toHaveKeys([
        'institutions',
        'resourceGroups',
        'resources',
        'timeSeries',
        'timeSeriesSplit',
        'cancellations',
        'heatmap',
        'comparison',
    ])
        ->and($data['comparison'])->toBeNull();
});

test('getIndexData scopes institutions, resource groups and resources through the scope resolver', function (): void {
    $fixture = buildStatisticsFixture();
    $viewer = User::factory()->create(['is_admin' => false]);
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);

    expect($service->getIndexData($viewer)['institutions'])->toHaveCount(0);

    $data = $service->getIndexData($admin);
    expect($data['institutions'])->toHaveCount(1)
        ->and($data['institutions']->first()['id'])->toBe($fixture['institution']->id);
});

test('getIndexData wires booking counts, cancellations and heatmap from the same scoped resources', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-15 10:00:00', 'end' => '2026-01-15 11:00:00']);
    $cancelled = Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-16 10:00:00', 'end' => '2026-01-16 11:00:00']);
    $cancelled->delete();

    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'custom', '2026-01-01', '2026-01-31');

    expect($data['resources']->first()['active'])->toBe(1)
        ->and($data['cancellations']['active'])->toBe(1)
        ->and($data['cancellations']['cancelled'])->toBe(1)
        ->and($data['heatmap']['totalCount'])->toBe(1);
});

test('getIndexData passes granularity and inferred split through to the time series', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'all', null, null, 'week');

    expect($data['granularity'])->toBe('week')
        ->and($data['timeSeriesSplit'])->toBe('none')
        ->and($data['timeSeries'])->toHaveCount(12);
});

test('getIndexData normalizes a single filter id and an array of filter ids', function (): void {
    $fixture = buildStatisticsFixture();
    $secondResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);

    $single = $service->getIndexData($admin, resourceId: (string) $fixture['resource']->id);
    $multiple = $service->getIndexData($admin, resourceId: [(string) $fixture['resource']->id, (string) $secondResource->id]);

    expect($single['timeSeriesResourceIds'])->toBe([(string) $fixture['resource']->id])
        ->and($multiple['timeSeriesResourceIds'])->toBe([(string) $fixture['resource']->id, (string) $secondResource->id])
        ->and($multiple['timeSeriesResourceId'])->toBe((string) $fixture['resource']->id);
});

test('getIndexData returns comparison data only when a comparison range is given', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->count(2)->for($fixture['resource'], 'resource')->create(['start' => '2026-02-05 10:00:00', 'end' => '2026-02-05 11:00:00']);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-05 10:00:00', 'end' => '2026-01-05 11:00:00']);

    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);

    expect($service->getIndexData($admin)['comparison'])->toBeNull();

    $data = $service->getIndexData(
        $admin,
        'custom',
        '2026-02-01',
        '2026-02-28',
        resourceId: (string) $fixture['resource']->id,
        compareFrom: '2026-01-01',
        compareTo: '2026-01-31',
    );

    expect($data['comparison'])->not->toBeNull()
        ->and($data['comparison']['currentCount'])->toBe(2)
        ->and($data['comparison']['comparisonCount'])->toBe(1)
        ->and($data['comparison']['deltaPct'])->toBe(100.0);
});

test('getBookingCountsAndFilterState returns booking counts and filter state without a time series', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->count(2)->for($fixture['resource'], 'resource')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getBookingCountsAndFilterState($admin, 'all', null, null, 'month', [], [], []);

    expect($data)->toHaveKeys(['institutions', 'resourceGroups', 'resources', 'range', 'timeSeriesSplit'])
        ->and($data)->not->toHaveKey('timeSeries')
        ->and($data['resources']->first()['count'])->toBe(2);
});

test('getTimeSeriesGroupData returns the timeSeries and cancellations deferred prop group', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now(), 'end' => now()->addHour()]);
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getTimeSeriesGroupData($admin, 'all', null, null, 'month', [], [], []);

    expect($data)->toHaveKeys(['timeSeries', 'cancellations'])
        ->and($data['timeSeries'][11]['count'])->toBe(1);
});

test('getHeatmapData returns the heatmap deferred prop group', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-05 10:00:00', 'end' => '2026-01-05 11:00:00']);
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getHeatmapData($admin, 'custom', '2026-01-01', '2026-01-31', [], [], []);

    expect($data)->toHaveKeys(['heatmap'])
        ->and($data['heatmap']['totalCount'])->toBe(1);
});

test('getComparisonGroupData returns the comparison deferred prop group', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-02-05 10:00:00', 'end' => '2026-02-05 11:00:00']);
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getComparisonGroupData(
        $admin,
        'custom',
        '2026-02-01',
        '2026-02-28',
        'month',
        [],
        [],
        [],
        '2026-01-01',
        '2026-01-31',
    );

    expect($data)->toHaveKeys(['comparison'])
        ->and($data['comparison']['currentCount'])->toBe(1);
});

test('toCsvRows delegates to the csv exporter', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    $rows = $service->toCsvRows($data, 'time_series');

    expect($rows[0])->toBe(['Label', 'Count'])
        ->and($service->toCsvRows($data, 'not_a_real_type'))->toBe([]);
});
