<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\StatisticsCancellationCalculator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

covers(StatisticsCancellationCalculator::class);

uses(LazilyRefreshDatabase::class);

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
