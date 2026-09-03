<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\StatisticsHeatmapBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsHeatmapBuilder::class);

uses(RefreshDatabase::class);

test('build returns 168 cells covering every hour of every day', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $builder = app(StatisticsHeatmapBuilder::class);
    $result = $builder->build(collect([$resource]), null, null);

    expect($result['cells'])->toHaveCount(168)
        ->and($result['maxCount'])->toBe(0)
        ->and($result['totalCount'])->toBe(0);
});

test('build counts happenings into their day-of-week and hour cell', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $otherResource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Happening::factory()->for($resource, 'resource')->create(['start' => '2026-01-05 10:00:00', 'end' => '2026-01-05 11:00:00']);
    Happening::factory()->for($resource, 'resource')->create(['start' => '2026-01-05 10:30:00', 'end' => '2026-01-05 11:30:00']);
    Happening::factory()->for($resource, 'resource')->create(['start' => '2026-01-06 15:00:00', 'end' => '2026-01-06 16:00:00']);
    Happening::factory()->for($otherResource, 'resource')->create(['start' => '2026-01-05 10:00:00', 'end' => '2026-01-05 11:00:00']);

    $builder = app(StatisticsHeatmapBuilder::class);
    $result = $builder->build(
        collect([$resource]),
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-01-31'),
    );

    $mondayAtTen = collect($result['cells'])->first(fn (array $cell): bool => $cell['dayOfWeek'] === 1 && $cell['hour'] === 10);
    $tuesdayAtFifteen = collect($result['cells'])->first(fn (array $cell): bool => $cell['dayOfWeek'] === 2 && $cell['hour'] === 15);

    expect($result['maxCount'])->toBe(2)
        ->and($result['totalCount'])->toBe(3)
        ->and($mondayAtTen['count'])->toBe(2)
        ->and($mondayAtTen['percentage'])->toBe(66.7)
        ->and($tuesdayAtFifteen['count'])->toBe(1)
        ->and($tuesdayAtFifteen['percentage'])->toBe(33.3);
});
