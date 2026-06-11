<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\AdminHappeningPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(AdminHappeningPresenter::class);

uses(RefreshDatabase::class);

test('present returns array with all expected keys', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);
    $happening->load(['resource.resource_group.institution', 'user1', 'user2']);

    $presenter = new AdminHappeningPresenter;
    $result = $presenter->present($happening);

    expect($result)->toHaveKeys([
        'id',
        'start',
        'end',
        'institution_id',
        'institution',
        'resource_group',
        'resource',
        'user1',
        'user2',
        'label',
        'is_verified',
    ]);
});

test('present id matches the happening id', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);
    $happening->load(['resource.resource_group.institution', 'user1', 'user2']);

    $presenter = new AdminHappeningPresenter;
    $result = $presenter->present($happening);

    expect($result['id'])->toBe($happening->id)
        ->and($result['institution_id'])->toBe($institution->id)
        ->and($result['is_verified'])->toBeFalse()
        ->and($result['user1'])->toBe($user->name);
});

test('present shows verifier field as user2 when unverified', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'verifier' => 'pending.verifier',
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);
    $happening->load(['resource.resource_group.institution', 'user1', 'user2']);

    $presenter = new AdminHappeningPresenter;
    $result = $presenter->present($happening);

    expect($result['user2'])->toBe('pending.verifier');
});

test('present shows user2 name when verified', function (): void {
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
    $happening->load(['resource.resource_group.institution', 'user1', 'user2']);

    $presenter = new AdminHappeningPresenter;
    $result = $presenter->present($happening);

    expect($result['user2'])->toBe($user2->name);
});

test('present returns null for user1 when no user1 is set', function (): void {
    // RemoveNullSafeOperator: $happening->user1?->name becomes $happening->user1->name
    // Without null-safe: when user1 is null, ->name would throw.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    // Create happening without a proper user to simulate null user1 relation
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);
    $happening->load(['resource.resource_group.institution']);
    // Force user1 relation to null to simulate edge case
    $happening->setRelation('user1', null);
    $happening->setRelation('user2', null);

    $presenter = new AdminHappeningPresenter;
    $result = $presenter->present($happening);

    // Should return null, not throw
    expect($result['user1'])->toBeNull();
});

test('present returns null for user2 when user is null and happening is unverified', function (): void {
    // $happening->user2?->name — RemoveNullSafeOperator would throw if user2 is null and is_verified=true
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();

    // verified=true but no user_id_02 → user2 should be null
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => true,
        'reserved_at' => now(),
    ]);
    $happening->load(['resource.resource_group.institution', 'user1', 'user2']);

    $presenter = new AdminHappeningPresenter;
    $result = $presenter->present($happening);

    // user2 has no id set → null, without null-safe it would throw
    expect($result['user2'])->toBeNull();
});
