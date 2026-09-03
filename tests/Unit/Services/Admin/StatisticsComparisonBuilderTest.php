<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\StatisticsComparisonBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsComparisonBuilder::class);

uses(RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function buildComparisonBuilderFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('build returns null when no comparison range is given', function (): void {
    $fixture = buildComparisonBuilderFixture();

    $builder = app(StatisticsComparisonBuilder::class);
    $result = $builder->build(
        collect([$fixture['institution']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['resource']]),
        collect([$fixture['resource']]),
        'month',
        0,
        'none',
        null,
        null,
    );

    expect($result)->toBeNull();
});

test('build returns booking counts and a delta percentage for the comparison range', function (): void {
    $fixture = buildComparisonBuilderFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-05 10:00:00', 'end' => '2026-01-05 11:00:00']);

    $builder = app(StatisticsComparisonBuilder::class);
    $result = $builder->build(
        collect([$fixture['institution']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['resource']]),
        collect([$fixture['resource']]),
        'month',
        2,
        'none',
        '2026-01-01',
        '2026-01-31',
    );

    expect($result)->not->toBeNull()
        ->and($result['from'])->toBe('2026-01-01')
        ->and($result['to'])->toBe('2026-01-31')
        ->and($result['currentCount'])->toBe(2)
        ->and($result['comparisonCount'])->toBe(1)
        ->and($result['deltaPct'])->toBe(100.0)
        ->and($result['resources']->first()['count'])->toBe(1)
        ->and($result['timeSeries'])->toHaveCount(1)
        ->and($result['timeSeries'][0]['label'])->toBe('2026-01')
        ->and($result['timeSeries'][0]['count'])->toBe(1);
});
