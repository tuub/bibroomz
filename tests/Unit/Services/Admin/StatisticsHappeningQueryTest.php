<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\StatisticsHappeningQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsHappeningQuery::class);

uses(RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function buildHappeningQueryFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('forResources scopes to the given resources', function (): void {
    $fixture = buildHappeningQueryFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();

    Happening::factory()->for($fixture['resource'], 'resource')->create();
    Happening::factory()->count(2)->for($otherResource, 'resource')->create();

    $query = app(StatisticsHappeningQuery::class);

    expect($query->forResources(collect([$fixture['resource']]), null, null)->count())->toBe(1);
});

test('forResources filters by the given date range', function (): void {
    $fixture = buildHappeningQueryFixture();

    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-01-15 10:00:00', 'end' => '2026-01-15 11:00:00']);
    Happening::factory()->for($fixture['resource'], 'resource')->create(['start' => '2026-06-15 10:00:00', 'end' => '2026-06-15 11:00:00']);

    $query = app(StatisticsHappeningQuery::class);
    $count = $query->forResources(
        collect([$fixture['resource']]),
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-01-31'),
    )->count();

    expect($count)->toBe(1);
});

test('forResources only returns soft-deleted rows when onlyTrashed is true', function (): void {
    $fixture = buildHappeningQueryFixture();
    $active = Happening::factory()->for($fixture['resource'], 'resource')->create();
    $cancelled = Happening::factory()->for($fixture['resource'], 'resource')->create();
    $cancelled->delete();

    $query = app(StatisticsHappeningQuery::class);

    expect($query->forResources(collect([$fixture['resource']]), null, null)->count())->toBe(1)
        ->and($query->forResources(collect([$fixture['resource']]), null, null, onlyTrashed: true)->count())->toBe(1);
});

test('retentionDays reads the configured cleanup window', function (): void {
    config(['roomz.happenings.cleanup_days' => 45]);

    $query = app(StatisticsHappeningQuery::class);

    expect($query->retentionDays())->toBe(45);
});

test('retentionDays defaults to zero for a non-numeric configuration value', function (): void {
    config(['roomz.happenings.cleanup_days' => 'not-a-number']);

    $query = app(StatisticsHappeningQuery::class);

    expect($query->retentionDays())->toBe(0);
});
