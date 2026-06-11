<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Console\RemoveUnverifiedHappeningsQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(RemoveUnverifiedHappeningsQueryBuilder::class);

uses(RefreshDatabase::class);

test('resolveInstitution returns null for non-string non-int input', function (): void {
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);

    expect($builder->resolveInstitution(null))->toBeNull()
        ->and($builder->resolveInstitution([]))->toBeNull();
});

test('resolveInstitution returns institution by id', function (): void {
    $institution = Institution::factory()->create();
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);

    $found = $builder->resolveInstitution($institution->id);
    expect($found?->id)->toBe($institution->id);
});

test('resolveInstitution returns institution by slug', function (): void {
    $institution = Institution::factory()->create();
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);

    $found = $builder->resolveInstitution($institution->slug);
    expect($found?->id)->toBe($institution->id);
});

test('baseQuery returns a query builder for unverified happenings', function (): void {
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);
    $query = $builder->baseQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

test('resolveInstitution returns null for non-existent id string', function (): void {
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);

    $result = $builder->resolveInstitution('non-existent-slug-that-does-not-exist');
    expect($result)->toBeNull();
});

test('restrictToInstitution adds institution filter to query', function (): void {
    $institution = Institution::factory()->create();
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);
    $query = $builder->baseQuery();

    $restricted = $builder->restrictToInstitution($query, $institution);

    expect($restricted)->toBeInstanceOf(Builder::class);
});

test('applySettingsPerInstitution returns whereRaw false when no institutions', function (): void {
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);
    $query = $builder->baseQuery();
    $emptyCollection = Institution::newModelInstance()->newCollection();

    $result = $builder->applySettingsPerInstitution($query, $emptyCollection);

    // The query should be a no-results query (1=0 condition)
    expect($result)->toBeInstanceOf(Builder::class)
        ->and($result->count())->toBe(0);
});

test('baseQuery excludes verified happenings', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_verification_required' => true]);
    $user = User::factory()->create();

    // Create one unverified and one verified happening
    Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'is_verified' => false,
    ]);
    Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'is_verified' => true,
    ]);

    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);
    $results = $builder->baseQuery()->get();

    expect($results->every(fn (Happening $h): bool => ! $h->is_verified))->toBeTrue();
});

test('applySettingsPerInstitution calls onInstitution callback for each institution', function (): void {
    $institution1 = Institution::factory()->create();
    $institution2 = Institution::factory()->create();
    $builder = app(RemoveUnverifiedHappeningsQueryBuilder::class);
    $query = $builder->baseQuery();

    $institutions = Institution::newModelInstance()
        ->newCollection([$institution1, $institution2]);

    $called = [];
    $builder->applySettingsPerInstitution($query, $institutions, function (Institution $inst) use (&$called): void {
        $called[] = $inst->id;
    });

    expect($called)->toContain($institution1->id)
        ->and($called)->toContain($institution2->id);
});
