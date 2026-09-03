<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\StatisticsCancellationCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsCancellationCalculator::class);

uses(RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function buildCancellationFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('calculate counts active and cancelled happenings and their rate', function (): void {
    $fixture = buildCancellationFixture();
    Happening::factory()->for($fixture['resource'], 'resource')->create();
    $cancelledOne = Happening::factory()->for($fixture['resource'], 'resource')->create();
    $cancelledOne->delete();
    $cancelledTwo = Happening::factory()->for($fixture['resource'], 'resource')->create();
    $cancelledTwo->delete();

    $calculator = app(StatisticsCancellationCalculator::class);
    $result = $calculator->calculate(collect([$fixture['resource']]), null, null);

    expect($result['active'])->toBe(1)
        ->and($result['cancelled'])->toBe(2)
        ->and($result['rate'])->toBe(66.7);
});

test('calculate reports the configured retention window', function (): void {
    config(['roomz.happenings.cleanup_days' => 1000]);
    $fixture = buildCancellationFixture();

    $calculator = app(StatisticsCancellationCalculator::class);
    $result = $calculator->calculate(collect([$fixture['resource']]), null, null);

    expect($result['retentionDays'])->toBe(1000);
});

test('calculate flags retention as exceeded when there is no lower date bound', function (): void {
    $fixture = buildCancellationFixture();

    $calculator = app(StatisticsCancellationCalculator::class);
    $result = $calculator->calculate(collect([$fixture['resource']]), null, null);

    expect($result['retentionExceeded'])->toBeTrue();
});

test('calculate flags retention as exceeded only when the range start predates the retention window', function (): void {
    config(['roomz.happenings.cleanup_days' => 30]);
    $fixture = buildCancellationFixture();

    $calculator = app(StatisticsCancellationCalculator::class);

    $withinWindow = $calculator->calculate($resources = collect([$fixture['resource']]), CarbonImmutable::now()->subDays(5), null);
    $beforeWindow = $calculator->calculate($resources, CarbonImmutable::now()->subDays(60), null);

    expect($withinWindow['retentionExceeded'])->toBeFalse()
        ->and($beforeWindow['retentionExceeded'])->toBeTrue();
});
