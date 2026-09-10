<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\StatisticsTimeSeriesBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsTimeSeriesBuilder::class);

uses(RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function buildTimeSeriesFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('build defaults to a monthly time series of 12 buckets', function (): void {
    $fixture = buildTimeSeriesFixture();

    $builder = app(StatisticsTimeSeriesBuilder::class);
    $result = $builder->build(collect([$fixture['resource']]), collect([$fixture['resourceGroup']]), collect([$fixture['institution']]), 'month', null, null, 'none');

    expect($result)->toHaveCount(12)
        ->and($result[11]['label'])->toBe(now()->format('Y-m'));
});

test('build week granularity returns 12 buckets labelled by ISO week', function (): void {
    $fixture = buildTimeSeriesFixture();

    $builder = app(StatisticsTimeSeriesBuilder::class);
    $result = $builder->build(collect([$fixture['resource']]), collect([$fixture['resourceGroup']]), collect([$fixture['institution']]), 'week', null, null, 'none');

    expect($result)->toHaveCount(12)
        ->and($result[11]['label'])->toBe(now()->format('o-\WW'));
});

test('build year granularity returns 5 buckets labelled by year', function (): void {
    $fixture = buildTimeSeriesFixture();

    $builder = app(StatisticsTimeSeriesBuilder::class);
    $result = $builder->build(collect([$fixture['resource']]), collect([$fixture['resourceGroup']]), collect([$fixture['institution']]), 'year', null, null, 'none');

    expect($result)->toHaveCount(5)
        ->and($result[4]['label'])->toBe(now()->format('Y'));
});

test('build counts bookings into the current monthly bucket and excludes bookings older than the window', function (): void {
    $fixture = buildTimeSeriesFixture();
    Happening::factory()->count(2)->for($fixture['resource'], 'resource')->create(['start' => now(), 'end' => now()->addHour()]);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => now()->subYears(2), 'end' => now()->subYears(2)->addHour()]);

    $builder = app(StatisticsTimeSeriesBuilder::class);
    $result = $builder->build(collect([$fixture['resource']]), collect([$fixture['resourceGroup']]), collect([$fixture['institution']]), 'month', null, null, 'none');

    expect($result[11]['count'])->toBe(2)
        ->and(array_sum(array_column($result, 'count')))->toBe(2);
});

test('build window shrinks to match a given custom range', function (): void {
    $fixture = buildTimeSeriesFixture();

    $builder = app(StatisticsTimeSeriesBuilder::class);
    $result = $builder->build(
        collect([$fixture['resource']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['institution']]),
        'month',
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-03-31'),
        'none',
    );

    expect($result)->toHaveCount(3)
        ->and(array_column($result, 'label'))->toBe(['2026-01', '2026-02', '2026-03']);
});

test('build clamps the window to the maximum number of buckets for a very wide range', function (): void {
    $fixture = buildTimeSeriesFixture();

    $builder = app(StatisticsTimeSeriesBuilder::class);
    $result = $builder->build(
        collect([$fixture['resource']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['institution']]),
        'month',
        CarbonImmutable::parse('2000-01-01'),
        CarbonImmutable::now(),
        'none',
    );

    expect($result)->toHaveCount(104)
        ->and($result[103]['label'])->toBe(now()->startOfMonth()->format('Y-m'));
});

test('build with split "none" omits segments', function (): void {
    $fixture = buildTimeSeriesFixture();

    $builder = app(StatisticsTimeSeriesBuilder::class);
    $result = $builder->build(collect([$fixture['resource']]), collect([$fixture['resourceGroup']]), collect([$fixture['institution']]), 'month', null, null, 'none');

    expect($result[0])->not->toHaveKey('segments');
});

test('build splits counts by institution, resource group or resource', function (): void {
    $firstInstitution = Institution::factory()->create(['title' => ['en' => 'Institution A'], 'order' => 1]);
    $firstResourceGroup = ResourceGroup::factory()->for($firstInstitution, 'institution')->create(['title' => ['en' => 'Group A1']]);
    $firstResource = Resource::factory()->for($firstResourceGroup, 'resource_group')->create(['title' => ['en' => 'Resource A1a']]);
    $secondResourceGroup = ResourceGroup::factory()->for($firstInstitution, 'institution')->create(['title' => ['en' => 'Group A2']]);
    $secondResource = Resource::factory()->for($secondResourceGroup, 'resource_group')->create(['title' => ['en' => 'Resource A2a']]);

    $secondInstitution = Institution::factory()->create(['title' => ['en' => 'Institution B'], 'order' => 2]);
    $thirdResourceGroup = ResourceGroup::factory()->for($secondInstitution, 'institution')->create(['title' => ['en' => 'Group B1']]);
    $thirdResource = Resource::factory()->for($thirdResourceGroup, 'resource_group')->create(['title' => ['en' => 'Resource B1a']]);

    Happening::factory()->for($firstResource, 'resource')->create(['start' => '2026-01-05 10:00:00', 'end' => '2026-01-05 11:00:00']);
    Happening::factory()->count(2)->for($secondResource, 'resource')->create(['start' => '2026-01-06 10:00:00', 'end' => '2026-01-06 11:00:00']);
    Happening::factory()->count(3)->for($thirdResource, 'resource')->create(['start' => '2026-01-07 10:00:00', 'end' => '2026-01-07 11:00:00']);

    $resources = collect([$firstResource, $secondResource, $thirdResource]);
    $resourceGroups = collect([$firstResourceGroup, $secondResourceGroup, $thirdResourceGroup]);
    $institutions = collect([$firstInstitution, $secondInstitution]);
    $range = [CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-01-31')];

    $builder = app(StatisticsTimeSeriesBuilder::class);

    $byInstitution = $builder->build($resources, $resourceGroups, $institutions, 'month', ...$range, split: 'institution');
    $institutionSegments = $byInstitution[0]['segments'] ?? [];

    expect(array_column($institutionSegments, 'id'))->toBe([(string) $firstInstitution->id, (string) $secondInstitution->id])
        ->and(array_map(fn (array $segment): string => $segment['title']['en'], $institutionSegments))->toBe(['Institution A', 'Institution B'])
        ->and(array_column($institutionSegments, 'count'))->toBe([3, 3]);

    $byResourceGroup = $builder->build(collect([$firstResource, $secondResource]), $resourceGroups, $institutions, 'month', ...$range, split: 'resource_group');
    $resourceGroupSegments = $byResourceGroup[0]['segments'] ?? [];

    expect(array_column($resourceGroupSegments, 'id'))->toBe([(string) $firstResourceGroup->id, (string) $secondResourceGroup->id])
        ->and(array_column($resourceGroupSegments, 'count'))->toBe([1, 2]);

    $byResource = $builder->build(collect([$firstResource]), $resourceGroups, $institutions, 'month', ...$range, split: 'resource');
    $resourceSegments = $byResource[0]['segments'] ?? [];

    expect(array_column($resourceSegments, 'id'))->toBe([(string) $firstResource->id])
        ->and(array_column($resourceSegments, 'count'))->toBe([1]);
});
