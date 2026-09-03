<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\StatisticsScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsScopeResolver::class);

uses(RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function buildScopeResolverFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('resolve omits institutions the user cannot view', function (): void {
    buildScopeResolverFixture();
    $user = User::factory()->create(['is_admin' => false]);

    $resolver = app(StatisticsScopeResolver::class);
    [$institutions, $resourceGroups, $resources] = $resolver->resolve($user, [], [], []);

    expect($institutions)->toHaveCount(0)
        ->and($resourceGroups)->toHaveCount(0)
        ->and($resources)->toHaveCount(0);
});

test('resolve includes institutions the user can view', function (): void {
    $fixture = buildScopeResolverFixture();
    $admin = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [$institutions, $resourceGroups, $resources] = $resolver->resolve($admin, [], [], []);

    expect($institutions->first()?->id)->toBe($fixture['institution']->id)
        ->and($resourceGroups->first()?->id)->toBe($fixture['resourceGroup']->id)
        ->and($resources->first()?->id)->toBe($fixture['resource']->id);
});

test('resolve narrows the time series resources to the selected resource id', function (): void {
    $fixture = buildScopeResolverFixture();
    $otherResource = Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [, , , $timeSeriesResources] = $resolver->resolve($admin, [], [], [(string) $fixture['resource']->id]);

    expect($timeSeriesResources->pluck('id')->all())->toBe([$fixture['resource']->id])
        ->and($timeSeriesResources->pluck('id'))->not->toContain($otherResource->id);
});

test('resolve narrows the time series resources to the selected resource group id', function (): void {
    $fixture = buildScopeResolverFixture();
    $otherGroup = ResourceGroup::factory()->for($fixture['institution'], 'institution')->create();
    $otherResource = Resource::factory()->for($otherGroup, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [, , , $timeSeriesResources] = $resolver->resolve($admin, [], [(string) $fixture['resourceGroup']->id], []);

    expect($timeSeriesResources->pluck('id')->all())->toBe([$fixture['resource']->id])
        ->and($timeSeriesResources->pluck('id'))->not->toContain($otherResource->id);
});

test('resolve narrows the time series resources to the selected institution id', function (): void {
    $fixture = buildScopeResolverFixture();
    $otherInstitution = Institution::factory()->create();
    $otherGroup = ResourceGroup::factory()->for($otherInstitution, 'institution')->create();
    $otherResource = Resource::factory()->for($otherGroup, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [, , , $timeSeriesResources] = $resolver->resolve($admin, [(string) $fixture['institution']->id], [], []);

    expect($timeSeriesResources->pluck('id')->all())->toBe([$fixture['resource']->id])
        ->and($timeSeriesResources->pluck('id'))->not->toContain($otherResource->id);
});

test('resolve infers "none" split when there are no resources in scope', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [, , , , $split] = $resolver->resolve($user, [], [], []);

    expect($split)->toBe('none');
});

test('resolve infers "institution" split when the time series resources span multiple institutions', function (): void {
    buildScopeResolverFixture();
    $secondInstitution = Institution::factory()->create();
    $secondGroup = ResourceGroup::factory()->for($secondInstitution, 'institution')->create();
    Resource::factory()->for($secondGroup, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [, , , , $split] = $resolver->resolve($admin, [], [], []);

    expect($split)->toBe('institution');
});

test('resolve infers "resource_group" split when scoped to one institution with multiple resource groups', function (): void {
    $fixture = buildScopeResolverFixture();
    $secondGroup = ResourceGroup::factory()->for($fixture['institution'], 'institution')->create();
    Resource::factory()->for($secondGroup, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [, , , , $split] = $resolver->resolve($admin, [(string) $fixture['institution']->id], [], []);

    expect($split)->toBe('resource_group');
});

test('resolve infers "resource" split when scoped to one resource group', function (): void {
    $fixture = buildScopeResolverFixture();
    Resource::factory()->for($fixture['resourceGroup'], 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $resolver = app(StatisticsScopeResolver::class);
    [, , , , $split] = $resolver->resolve($admin, [], [(string) $fixture['resourceGroup']->id], []);

    expect($split)->toBe('resource');
});
