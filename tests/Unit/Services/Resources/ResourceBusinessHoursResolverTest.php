<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Resources\ResourceBusinessHoursResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

covers(ResourceBusinessHoursResolver::class);

uses(RefreshDatabase::class);

test('forDate returns collection of business hours', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $resolver = app(ResourceBusinessHoursResolver::class);
    $result = $resolver->forDate($resource, CarbonImmutable::today());

    expect($result)->toBeInstanceOf(Collection::class);
});

test('forDate prefers date-valid business hours over fallback business hours', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $fallbackBusinessHourId = $resource->business_hours()->value('id');

    $datedBusinessHour = $resource->business_hours()->create([
        'start' => '08:00:00',
        'end' => '18:00:00',
        'start_date' => CarbonImmutable::parse('2026-06-01 00:00:00'),
        'end_date' => CarbonImmutable::parse('2026-06-30 23:59:59'),
    ]);

    $freshResource = $resource->fresh('business_hours');

    if (! $freshResource instanceof Resource) {
        throw new RuntimeException('Resource not found after refresh.');
    }

    $resolver = app(ResourceBusinessHoursResolver::class);
    $result = $resolver->forDate($freshResource, CarbonImmutable::parse('2026-06-12 12:00:00'));

    expect($result)->toHaveCount(1)
        ->and($result->first()?->id)->toBe($datedBusinessHour->id)
        ->and($result->contains('id', $fallbackBusinessHourId))->toBeFalse();
});
