<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Http\ListPublicResourcesAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ListPublicResourcesAction::class);

uses(RefreshDatabase::class);

test('execute returns array with resources and pagination keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        10,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('resources')
        ->and($result)->toHaveKey('pagination');
});

test('execute pagination sub-array contains previousPage and nextPage keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        10,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    expect($result['pagination'])->toHaveKeys(['previousPage', 'nextPage']);
});

test('execute returns resources as array of presented resource data', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 1]);
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 2]);

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        10,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    expect($result['resources'])->toBeArray()
        ->and(count($result['resources']))->toBe(2);
});

test('execute inactive resources are excluded from results', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 1]);
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => false, 'order' => 2]);

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        10,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    expect(count($result['resources']))->toBe(1);
});

test('execute pagination nextPage is set when more resources than page size', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    for ($i = 1; $i <= 3; $i++) {
        Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => $i]);
    }

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        2,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    expect($result['pagination']['nextPage'])->not->toBeNull();
});

test('execute pagination previousPage is set on second page', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    for ($i = 1; $i <= 3; $i++) {
        Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => $i]);
    }

    $action = app(ListPublicResourcesAction::class);

    // Simulate being on page 2 by manually overriding the request page parameter
    request()->merge(['page' => 2]);

    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        2,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    expect($result['pagination']['previousPage'])->not->toBeNull();
});

test('execute each resource entry contains expected presenter keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 1]);

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        10,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    expect($result['resources'][0])->toHaveKeys([
        'id',
        'title',
        'businessHours',
        'isVerificationRequired',
        'capacity',
        'location_uri',
        'resourceGroup',
        'order',
        'translations',
    ]);
});

// --- Mutation-killing tests ---

test('execute pagination nextPage url contains count and date parameters', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    for ($i = 1; $i <= 3; $i++) {
        Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => $i]);
    }

    $date = CarbonImmutable::parse('2026-06-12');
    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        2,
        $date,
        'http://localhost/resources'
    );

    // The nextPage URL must contain count and date params from the path built with concat
    $nextPage = $result['pagination']['nextPage'];
    expect($nextPage)->toContain('count=2')
        ->and($nextPage)->toContain('date=2026-06-12');
});

test('execute resources is a list', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 1]);
    Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 2]);

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        10,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    // array_values() ensures keys are 0, 1, 2, ...
    expect(array_keys($result['resources']))->toBe([0, 1]);
});

test('execute resources are ordered by order column', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $r1 = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 2]);
    $r2 = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true, 'order' => 1]);

    $action = app(ListPublicResourcesAction::class);
    $result = $action->execute(
        $institution->slug,
        $rg->slug,
        10,
        CarbonImmutable::today(),
        'http://localhost/resources'
    );

    // r2 has order=1, should come first
    expect($result['resources'][0]['id'])->toBe($r2->id)
        ->and($result['resources'][1]['id'])->toBe($r1->id);
});
