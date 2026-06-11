<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Resources\ResourceSettingsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceSettingsResolver::class);

uses(RefreshDatabase::class);

test('resourceGroupString returns fallback when key not found', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $resource->load('resource_group.settings');

    $resolver = new ResourceSettingsResolver;
    $result = $resolver->resourceGroupString($resource, 'nonexistent_key', 'fallback');

    expect($result)->toBe('fallback');
});

test('timeSlotLength returns array with hour and minute', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $resource->load('resource_group.settings');

    $resolver = new ResourceSettingsResolver;
    $result = $resolver->timeSlotLength($resource);

    expect($result)->toHaveKey('hour')
        ->and($result)->toHaveKey('minute');
});
