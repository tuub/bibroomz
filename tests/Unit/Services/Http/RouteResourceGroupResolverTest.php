<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Services\Http\RouteResourceGroupResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(RouteResourceGroupResolver::class);

uses(RefreshDatabase::class);

test('resolve returns the correct resource group by slug pair', function (): void {
    $institution = Institution::factory()->create(['slug' => 'my-institution']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'my-group']);

    $resolver = app(RouteResourceGroupResolver::class);
    $resolved = $resolver->resolve('my-institution', 'my-group');

    expect($resolved)->toBeInstanceOf(ResourceGroup::class)
        ->and($resolved->id)->toBe($resourceGroup->id);
});

test('resolve throws ModelNotFoundException when slugs do not match', function (): void {
    $institution = Institution::factory()->create(['slug' => 'real-institution']);
    ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'real-group']);

    $resolver = app(RouteResourceGroupResolver::class);

    expect(fn () => $resolver->resolve('real-institution', 'nonexistent-group'))
        ->toThrow(ModelNotFoundException::class);
});

test('resolve eager loads given relations', function (): void {
    $institution = Institution::factory()->create(['slug' => 'inst-slug']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'rg-slug']);

    $resolver = app(RouteResourceGroupResolver::class);
    $resolved = $resolver->resolve('inst-slug', 'rg-slug', ['institution']);

    expect($resolved->relationLoaded('institution'))->toBeTrue();
});
