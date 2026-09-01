<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\StatisticsAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

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

/**
 * @param  array<string, string>  $title
 * @return array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}
 */
function buildInstitutionStat(string $id, array $title): array
{
    return ['id' => $id, 'title' => $title, 'count' => 0, 'active' => 0, 'cancelled' => 0, 'cancellationRate' => 0.0];
}

/**
 * @param  array<string, string>  $title
 * @return array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}
 */
function buildResourceGroupStat(string $id, array $title, string $institutionId): array
{
    return ['id' => $id, 'title' => $title, 'institution_id' => $institutionId, 'count' => 0, 'active' => 0, 'cancelled' => 0, 'cancellationRate' => 0.0];
}

/**
 * @param  array<string, string>  $title
 * @return array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}
 */
function buildResourceStat(string $id, array $title, string $resourceGroupId): array
{
    return ['id' => $id, 'title' => $title, 'resource_group_id' => $resourceGroupId, 'count' => 0, 'active' => 0, 'cancelled' => 0, 'cancellationRate' => 0.0];
}

test('getIndexData returns institutions, resourceGroups and resources keys', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data)->toHaveKeys(['institutions', 'resourceGroups', 'resources', 'cancellations', 'heatmap', 'comparison'])
        ->and($data['comparison'])->toBeNull();
});

test('getIndexData omits institutions the user cannot view', function (): void {
    buildStatisticsFixture();
    $user = User::factory()->create(['is_admin' => false]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($user);

    expect($data['institutions'])->toHaveCount(0)
        ->and($data['resourceGroups'])->toHaveCount(0)
        ->and($data['resources'])->toHaveCount(0);
});

test('getIndexData includes institutions the user can view via view_happenings permission', function (): void {
    $fixture = buildStatisticsFixture();
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['institutions'])->toHaveCount(1)
        ->and($data['institutions']->first()['id'])->toBe($fixture['institution']->id)
        ->and($data['resourceGroups']->first()['id'])->toBe($fixture['resourceGroup']->id)
        ->and($data['resources']->first()['id'])->toBe($fixture['resource']->id);
});

test('getIndexData counts bookings per resource', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->count(3)->for($fixture['resource'], 'resource')->create();

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    /** @var Collection<int, array<string, mixed>> $resources */
    $resources = $data['resources'];
    expect($resources->first()['count'])->toBe(3);
});

test('getIndexData returns cancellation status per institution, resource group and resource', function (): void {
    $fixture = buildStatisticsFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();

    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-01-15 10:00:00',
        'end' => '2026-01-15 11:00:00',
    ]);
    $cancelled = Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-02-15 10:00:00',
        'end' => '2026-02-15 11:00:00',
    ]);
    $cancelled->delete();
    $otherCancelled = Happening::factory()->for($otherResource, 'resource')->create([
        'start' => '2026-03-15 10:00:00',
        'end' => '2026-03-15 11:00:00',
    ]);
    $otherCancelled->delete();
    $outsideRange = Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-04-15 10:00:00',
        'end' => '2026-04-15 11:00:00',
    ]);
    $outsideRange->delete();

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'custom', '2026-01-01', '2026-03-31');

    $resourceStat = $data['resources']->firstWhere('id', $fixture['resource']->id);
    $otherResourceStat = $data['resources']->firstWhere('id', $otherResource->id);
    $resourceGroupStat = $data['resourceGroups']->firstWhere('id', $fixture['resourceGroup']->id);
    $institutionStat = $data['institutions']->firstWhere('id', $fixture['institution']->id);

    expect($resourceStat['active'])->toBe(1)
        ->and($resourceStat['cancelled'])->toBe(1)
        ->and($resourceStat['cancellationRate'])->toBe(50.0)
        ->and($otherResourceStat['active'])->toBe(0)
        ->and($otherResourceStat['cancelled'])->toBe(1)
        ->and($otherResourceStat['cancellationRate'])->toBe(100.0)
        ->and($resourceGroupStat['active'])->toBe(1)
        ->and($resourceGroupStat['cancelled'])->toBe(2)
        ->and($resourceGroupStat['cancellationRate'])->toBe(66.7)
        ->and($institutionStat['active'])->toBe(1)
        ->and($institutionStat['cancelled'])->toBe(2)
        ->and($institutionStat['cancellationRate'])->toBe(66.7)
        ->and($data['cancellations']['active'])->toBe(1)
        ->and($data['cancellations']['cancelled'])->toBe(2)
        ->and($data['cancellations']['rate'])->toBe(66.7);
});

test('getIndexData builds a peak-times heatmap for the selected resource scope', function (): void {
    $fixture = buildStatisticsFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();

    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-01-05 10:00:00',
        'end' => '2026-01-05 11:00:00',
    ]);
    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-01-05 10:30:00',
        'end' => '2026-01-05 11:30:00',
    ]);
    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-01-06 15:00:00',
        'end' => '2026-01-06 16:00:00',
    ]);
    Happening::factory()->for($otherResource, 'resource')->create([
        'start' => '2026-01-05 10:00:00',
        'end' => '2026-01-05 11:00:00',
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData(
        $admin,
        'custom',
        '2026-01-01',
        '2026-01-31',
        resourceId: (string) $fixture['resource']->id,
    );

    $mondayAtTen = collect($data['heatmap']['cells'])
        ->first(fn (array $cell): bool => $cell['dayOfWeek'] === 1 && $cell['hour'] === 10);
    $tuesdayAtFifteen = collect($data['heatmap']['cells'])
        ->first(fn (array $cell): bool => $cell['dayOfWeek'] === 2 && $cell['hour'] === 15);

    expect($data['heatmap']['cells'])->toHaveCount(168)
        ->and($data['heatmap']['maxCount'])->toBe(2)
        ->and($data['heatmap']['totalCount'])->toBe(3)
        ->and($mondayAtTen['count'])->toBe(2)
        ->and($mondayAtTen['percentage'])->toBe(66.7)
        ->and($tuesdayAtFifteen['count'])->toBe(1)
        ->and($tuesdayAtFifteen['percentage'])->toBe(33.3);
});

test('getIndexData returns comparison data for an explicit comparison range', function (): void {
    $fixture = buildStatisticsFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();

    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-02-05 10:00:00',
        'end' => '2026-02-05 11:00:00',
    ]);
    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-02-06 10:00:00',
        'end' => '2026-02-06 11:00:00',
    ]);
    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-01-05 10:00:00',
        'end' => '2026-01-05 11:00:00',
    ]);
    Happening::factory()->for($otherResource, 'resource')->create([
        'start' => '2026-02-07 10:00:00',
        'end' => '2026-02-07 11:00:00',
    ]);
    Happening::factory()->for($otherResource, 'resource')->create([
        'start' => '2026-01-07 10:00:00',
        'end' => '2026-01-07 11:00:00',
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData(
        $admin,
        'custom',
        '2026-02-01',
        '2026-02-28',
        resourceId: (string) $fixture['resource']->id,
        compareFrom: '2026-01-01',
        compareTo: '2026-01-31',
    );

    expect($data['resources']->first()['count'])->toBe(2)
        ->and($data['comparison'])->not->toBeNull()
        ->and($data['comparison']['from'])->toBe('2026-01-01')
        ->and($data['comparison']['to'])->toBe('2026-01-31')
        ->and($data['comparison']['currentCount'])->toBe(2)
        ->and($data['comparison']['comparisonCount'])->toBe(1)
        ->and($data['comparison']['deltaPct'])->toBe(100.0)
        ->and($data['comparison']['resources']->first()['count'])->toBe(1)
        ->and($data['comparison']['timeSeries'])->toHaveCount(1)
        ->and($data['comparison']['timeSeries'][0]['label'])->toBe('2026-01')
        ->and($data['comparison']['timeSeries'][0]['count'])->toBe(1);
});

test('getIndexData flags cancellation retention when the selected range is unbounded', function (): void {
    config(['roomz.happenings.cleanup_days' => 1000]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['cancellations']['retentionDays'])->toBe(1000)
        ->and($data['cancellations']['retentionExceeded'])->toBeTrue();
});

test('getIndexData resource with no bookings has a count of zero', function (): void {
    $fixture = buildStatisticsFixture();
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['resources']->first()['count'])->toBe(0);
});

test('getIndexData sums booking counts up from resource to resource group to institution', function (): void {
    $fixture = buildStatisticsFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();

    Happening::factory()->count(2)->for($fixture['resource'], 'resource')->create();
    Happening::factory()->count(5)->for($otherResource, 'resource')->create();

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['resourceGroups']->first()['count'])->toBe(7)
        ->and($data['institutions']->first()['count'])->toBe(7);
});

test('getIndexData does not count bookings from resources in other institutions', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->count(2)->for($fixture['resource'], 'resource')->create();

    $otherInstitution = Institution::factory()->create();
    $otherResourceGroup = ResourceGroup::factory()->for($otherInstitution, 'institution')->create();
    $otherResource = Resource::factory()->for($otherResourceGroup, 'resource_group')->create();
    Happening::factory()->count(4)->for($otherResource, 'resource')->create();

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    $institutionStat = $data['institutions']->firstWhere('id', $fixture['institution']->id);
    expect($institutionStat['count'])->toBe(2);
});

test('getIndexData excludes soft-deleted happenings from booking counts', function (): void {
    $fixture = buildStatisticsFixture();
    $happening = Happening::factory()->for($fixture['resource'], 'resource')->create();
    $happening->delete();

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['resources']->first()['count'])->toBe(0);
});

test('getIndexData defaults to the "all" range and returns no date bounds', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['range'])->toBe('all')
        ->and($data['from'])->toBeNull()
        ->and($data['to'])->toBeNull();
});

test('getIndexData only counts bookings starting within a custom range', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-15 10:00:00', 'end' => '2026-01-15 11:00:00']);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-06-15 10:00:00', 'end' => '2026-06-15 11:00:00']);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2027-01-15 10:00:00', 'end' => '2027-01-15 11:00:00']);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'custom', '2026-01-01', '2026-12-31');

    expect($data['resources']->first()['count'])->toBe(2)
        ->and($data['range'])->toBe('custom')
        ->and($data['from'])->toBe('2026-01-01')
        ->and($data['to'])->toBe('2026-12-31');
});

test('getIndexData last_30_days range excludes bookings outside the window', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subDays(2), 'end' => now()->subDays(2)->addHour()]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subDays(60), 'end' => now()->subDays(60)->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'last_30_days');

    expect($data['resources']->first()['count'])->toBe(1);
});

test('getIndexData last_7_days range excludes bookings outside the window', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subDays(1), 'end' => now()->subDays(1)->addHour()]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subDays(10), 'end' => now()->subDays(10)->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'last_7_days');

    expect($data['resources']->first()['count'])->toBe(1);
});

test('getIndexData this_week range excludes bookings before the start of the week', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->startOfWeek()->addHour(), 'end' => now()->startOfWeek()->addHours(2)]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->startOfWeek()->subDay(), 'end' => now()->startOfWeek()->subDay()->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'this_week');

    expect($data['resources']->first()['count'])->toBe(1);
});

test('getIndexData this_month range excludes bookings before the start of the month', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->startOfMonth()->addHour(), 'end' => now()->startOfMonth()->addHours(2)]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->startOfMonth()->subDay(), 'end' => now()->startOfMonth()->subDay()->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'this_month');

    expect($data['resources']->first()['count'])->toBe(1);
});

test('getIndexData defaults to a monthly time series of 12 buckets', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['granularity'])->toBe('month')
        ->and($data['timeSeries'])->toHaveCount(12)
        ->and($data['timeSeries'][11]['label'])->toBe(now()->format('Y-m'));
});

test('getIndexData week granularity returns 12 buckets labelled by ISO week', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'all', null, null, 'week');

    expect($data['granularity'])->toBe('week')
        ->and($data['timeSeries'])->toHaveCount(12)
        ->and($data['timeSeries'][11]['label'])->toBe(now()->format('o-\WW'));
});

test('getIndexData year granularity returns 5 buckets labelled by year', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'all', null, null, 'year');

    expect($data['granularity'])->toBe('year')
        ->and($data['timeSeries'])->toHaveCount(5)
        ->and($data['timeSeries'][4]['label'])->toBe(now()->format('Y'));
});

test('getIndexData time series counts bookings into the current monthly bucket', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->count(2)->for($fixture['resource'], 'resource')->create(['start' => now(), 'end' => now()->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($data['timeSeries'][11]['count'])->toBe(2);
});

test('getIndexData time series excludes bookings older than the window', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subYears(2), 'end' => now()->subYears(2)->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    /** @var array<int, array{label: string, count: int}> $timeSeries */
    $timeSeries = $data['timeSeries'];
    expect(collect($timeSeries)->sum('count'))->toBe(0);
});

test('getIndexData time series excludes bookings from institutions the user cannot view', function (): void {
    buildStatisticsFixture();
    $user = User::factory()->create(['is_admin' => false]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($user);

    /** @var array<int, array{label: string, count: int}> $timeSeries */
    $timeSeries = $data['timeSeries'];
    expect($timeSeries)->toHaveCount(12)
        ->and(collect($timeSeries)->sum('count'))->toBe(0);
});

test('getIndexData time series window shrinks to match a selected "this_month" range', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'this_month');

    expect($data['timeSeries'])->toHaveCount(1)
        ->and($data['timeSeries'][0]['label'])->toBe(now()->format('Y-m'));
});

test('getIndexData time series window matches a custom range', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'custom', '2026-01-01', '2026-03-31');

    /** @var array<int, array{label: string, count: int}> $timeSeries */
    $timeSeries = $data['timeSeries'];
    expect($timeSeries)->toHaveCount(3)
        ->and(array_column($timeSeries, 'label'))->toBe(['2026-01', '2026-02', '2026-03']);
});

test('getIndexData time series only counts bookings for the selected resource', function (): void {
    $fixture = buildStatisticsFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now(), 'end' => now()->addHour()]);
    Happening::factory()->count(3)->for($otherResource, 'resource')->create(['start' => now(), 'end' => now()->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, resourceId: (string) $fixture['resource']->id);

    expect($data['timeSeries'][11]['count'])->toBe(1)
        ->and($data['timeSeriesResourceId'])->toBe((string) $fixture['resource']->id);
});

test('getIndexData time series only counts bookings for the selected resource group', function (): void {
    $fixture = buildStatisticsFixture();
    $otherGroup = ResourceGroup::factory()->for($fixture['institution'], 'institution')->create();
    $otherResource = Resource::factory()->for($otherGroup, 'resource_group')->create();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now(), 'end' => now()->addHour()]);
    Happening::factory()->count(3)->for($otherResource, 'resource')->create(['start' => now(), 'end' => now()->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, resourceGroupId: (string) $fixture['resourceGroup']->id);

    expect($data['timeSeries'][11]['count'])->toBe(1);
});

test('getIndexData time series only counts bookings for the selected institution', function (): void {
    $fixture = buildStatisticsFixture();

    $otherInstitution = Institution::factory()->create();
    $otherResourceGroup = ResourceGroup::factory()->for($otherInstitution, 'institution')->create();
    $otherResource = Resource::factory()->for($otherResourceGroup, 'resource_group')->create();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now(), 'end' => now()->addHour()]);
    Happening::factory()->count(3)->for($otherResource, 'resource')->create(['start' => now(), 'end' => now()->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, institutionId: (string) $fixture['institution']->id);

    expect($data['timeSeries'][11]['count'])->toBe(1);
});

test('getIndexData this_year range excludes bookings before the start of the year', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->startOfYear()->addHour(), 'end' => now()->startOfYear()->addHours(2)]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->startOfYear()->subDay(), 'end' => now()->startOfYear()->subDay()->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'this_year');

    expect($data['resources']->first()['count'])->toBe(1);
});

test('getIndexData last_3_months range excludes bookings outside the window', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subMonths(1), 'end' => now()->subMonths(1)->addHour()]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subMonths(4), 'end' => now()->subMonths(4)->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'last_3_months');

    expect($data['resources']->first()['count'])->toBe(1);
});

test('getIndexData last_12_months range excludes bookings outside the window', function (): void {
    $fixture = buildStatisticsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subMonths(6), 'end' => now()->subMonths(6)->addHour()]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subMonths(13), 'end' => now()->subMonths(13)->addHour()]);

    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'last_12_months');

    expect($data['resources']->first()['count'])->toBe(1);
});

test('getIndexData clamps the time series window to the maximum number of buckets for a very wide custom range', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'custom', '2000-01-01', now()->toDateString());

    expect($data['timeSeries'])->toHaveCount(104)
        ->and($data['timeSeries'][103]['label'])->toBe(now()->startOfMonth()->format('Y-m'));
});

test('getIndexData comparison delta is zero when neither the current nor the comparison period has bookings', function (): void {
    buildStatisticsFixture();
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData(
        $admin,
        'custom',
        '2026-02-01',
        '2026-02-28',
        compareFrom: '2026-01-01',
        compareTo: '2026-01-31',
    );

    expect($data['comparison']['currentCount'])->toBe(0)
        ->and($data['comparison']['comparisonCount'])->toBe(0)
        ->and($data['comparison']['deltaPct'])->toBe(0.0);
});

test('toCsvRows builds a time series CSV with a label and count column', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    $rows = $service->toCsvRows($data, 'time_series');

    expect($rows[0])->toBe(['Label', 'Count'])
        ->and($rows)->toHaveCount(13)
        ->and($rows[1])->toBe([$data['timeSeries'][0]['label'], (string) $data['timeSeries'][0]['count']]);
});

test('toCsvRows builds a heatmap CSV with one row per cell', function (): void {
    $fixture = buildStatisticsFixture();
    Happening::factory()->for($fixture['resource'], 'resource')->create([
        'start' => '2026-01-05 10:00:00',
        'end' => '2026-01-05 11:00:00',
    ]);
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin, 'custom', '2026-01-01', '2026-01-31');

    $rows = $service->toCsvRows($data, 'heatmap');

    expect($rows[0])->toBe(['Day of Week', 'Hour', 'Count', 'Percentage'])
        ->and($rows)->toHaveCount(169)
        ->and($rows[11])->toBe(['1', '10', '1', '100']);
});

test('toCsvRows uses the active locale title for institutions and falls back to english', function (): void {
    Institution::factory()->create(['title' => ['en' => 'English Title', 'de' => 'Deutscher Titel']]);
    Institution::factory()->create(['title' => ['en' => 'Only English']]);
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    app()->setLocale('de');
    $rows = $service->toCsvRows($data, 'institutions');
    app()->setLocale('en');

    $titles = array_column(array_slice($rows, 1), 0);
    expect($titles)->toContain('Deutscher Titel')
        ->and($titles)->toContain('Only English');
});

test('toCsvRows falls back to the first available translation when neither the active locale nor english exists', function (): void {
    Institution::factory()->create(['title' => ['fr' => 'Titre Francais']]);
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    app()->setLocale('de');
    $rows = $service->toCsvRows($data, 'institutions');
    app()->setLocale('en');

    expect($rows[1][0])->toBe('Titre Francais');
});

test('toCsvRows resolves the parent institution and resource group titles by id', function (): void {
    $fixture = buildStatisticsFixture();
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    $resourceGroupRows = $service->toCsvRows($data, 'resource_groups');
    $resourceRows = $service->toCsvRows($data, 'resources');

    expect($resourceGroupRows[1][1])->toBe($fixture['institution']->getTranslation('title', 'en'))
        ->and($resourceRows[1][1])->toBe($fixture['resourceGroup']->getTranslation('title', 'en'));
});

test('toCsvRows leaves the parent name blank when a resource group or resource has no matching parent', function (): void {
    $service = app(StatisticsAdminService::class);
    $data = [
        'institutions' => collect([buildInstitutionStat('other-institution', ['en' => 'Other Institution'])]),
        'resourceGroups' => collect([buildResourceGroupStat('rg-1', ['en' => 'Group'], 'missing')]),
        'resources' => collect([buildResourceStat('r-1', ['en' => 'Resource'], 'missing')]),
        'timeSeries' => [],
        'heatmap' => ['cells' => [], 'maxCount' => 0, 'totalCount' => 0],
    ];

    expect($service->toCsvRows($data, 'resource_groups')[1])->toBe(['Group', '', '0', '0', '0'])
        ->and($service->toCsvRows($data, 'resources')[1])->toBe(['Resource', '', '0', '0', '0']);
});

test('toCsvRows returns an empty array for an unknown export type', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $service = app(StatisticsAdminService::class);
    $data = $service->getIndexData($admin);

    expect($service->toCsvRows($data, 'not_a_real_type'))->toBe([]);
});
