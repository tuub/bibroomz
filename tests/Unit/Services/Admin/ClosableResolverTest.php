<?php

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\ClosableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ClosableResolver::class);

uses(RefreshDatabase::class);

test('closable resolver resolves institution by id', function (): void {
    $institution = Institution::factory()->create();

    $resolver = app(ClosableResolver::class);
    $resolved = $resolver->resolve('institution', $institution->id);

    expect($resolved)->toBeInstanceOf(Institution::class)
        ->and($resolved->id)->toBe($institution->id);
});

test('closable resolver resolves resource by id', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $resolver = app(ClosableResolver::class);
    $resolved = $resolver->resolve('resource', $resource->id);

    expect($resolved)->toBeInstanceOf(Resource::class)
        ->and($resolved->id)->toBe($resource->id);
});

test('closable resolver returns type for model', function (): void {
    $institution = Institution::factory()->create();

    $resolver = app(ClosableResolver::class);
    $type = $resolver->typeForModel($institution);

    expect($type)->toBe('institution');
});

test('closable resolver throws for invalid type', function (): void {
    $resolver = app(ClosableResolver::class);

    expect(fn () => $resolver->resolve('invalid_type', 'some-id'))
        ->toThrow(Exception::class);
});
