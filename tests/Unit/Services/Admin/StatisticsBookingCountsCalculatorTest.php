<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\StatisticsBookingCountsCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsBookingCountsCalculator::class);

uses(RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function buildBookingCountsFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('calculate counts active bookings per resource', function (): void {
    $fixture = buildBookingCountsFixture();
    Happening::factory()->count(3)->for($fixture['resource'], 'resource')->create();

    $calculator = app(StatisticsBookingCountsCalculator::class);
    $result = $calculator->calculate(
        collect([$fixture['institution']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['resource']]),
        null,
        null,
    );

    expect($result['resources']->first()['count'])->toBe(3)
        ->and($result['total'])->toBe(3);
});

test('calculate sums booking counts up from resource to resource group to institution', function (): void {
    $fixture = buildBookingCountsFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();

    Happening::factory()->count(2)->for($fixture['resource'], 'resource')->create();
    Happening::factory()->count(5)->for($otherResource, 'resource')->create();

    $calculator = app(StatisticsBookingCountsCalculator::class);
    $result = $calculator->calculate(
        collect([$fixture['institution']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['resource'], $otherResource]),
        null,
        null,
    );

    expect($result['resourceGroups']->first()['count'])->toBe(7)
        ->and($result['institutions']->first()['count'])->toBe(7);
});

test('calculate reports cancellation counts and rate per resource', function (): void {
    $fixture = buildBookingCountsFixture();
    Happening::factory()->for($fixture['resource'], 'resource')->create();
    $cancelled = Happening::factory()->for($fixture['resource'], 'resource')->create();
    $cancelled->delete();

    $calculator = app(StatisticsBookingCountsCalculator::class);
    $result = $calculator->calculate(
        collect([$fixture['institution']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['resource']]),
        null,
        null,
    );

    $resourceStat = $result['resources']->first();

    expect($resourceStat['active'])->toBe(1)
        ->and($resourceStat['cancelled'])->toBe(1)
        ->and($resourceStat['cancellationRate'])->toBe(50.0);
});

test('calculate returns a count of zero for a resource with no bookings', function (): void {
    $fixture = buildBookingCountsFixture();

    $calculator = app(StatisticsBookingCountsCalculator::class);
    $result = $calculator->calculate(
        collect([$fixture['institution']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['resource']]),
        null,
        null,
    );

    expect($result['resources']->first()['count'])->toBe(0);
});

test('calculate only counts bookings starting within the given date range', function (): void {
    $fixture = buildBookingCountsFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-15 10:00:00', 'end' => '2026-01-15 11:00:00']);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-06-15 10:00:00', 'end' => '2026-06-15 11:00:00']);

    $calculator = app(StatisticsBookingCountsCalculator::class);
    $result = $calculator->calculate(
        collect([$fixture['institution']]),
        collect([$fixture['resourceGroup']]),
        collect([$fixture['resource']]),
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-01-31'),
    );

    expect($result['resources']->first()['count'])->toBe(1);
});
