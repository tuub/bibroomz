<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningAudienceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HappeningAudienceResolver::class);

uses(RefreshDatabase::class);

test('resolves user1 only when no user2 and no verifier match', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resolver = new HappeningAudienceResolver;
    $audience = $resolver->resolve($happening);

    expect($audience)->toHaveCount(1)
        ->and($audience->first()?->id)->toBe($user1->id);
});

test('resolves both user1 and user2 when both are set', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => true,
        'reserved_at' => now(),
    ]);

    $resolver = new HappeningAudienceResolver;
    $audience = $resolver->resolve($happening);

    $ids = $audience->pluck('id')->all();
    expect($ids)->toContain($user1->id)
        ->and($ids)->toContain($user2->id)
        ->and($audience)->toHaveCount(2);
});

test('includes verifier user found by name when verifier field is set', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();
    $verifier = User::factory()->create(['name' => 'verifier.name']);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'verifier' => 'verifier.name',
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resolver = new HappeningAudienceResolver;
    $audience = $resolver->resolve($happening);

    $ids = $audience->pluck('id')->all();
    expect($ids)->toContain($user1->id)
        ->and($ids)->toContain($verifier->id)
        ->and($audience)->toHaveCount(2);
});

test('does not include null entries when verifier name has no matching user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'verifier' => 'nonexistent.verifier',
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resolver = new HappeningAudienceResolver;
    $audience = $resolver->resolve($happening);

    expect($audience)->toHaveCount(1)
        ->and($audience->first())->toBeInstanceOf(User::class);
});
