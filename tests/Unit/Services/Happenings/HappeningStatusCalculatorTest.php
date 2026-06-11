<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningStatusCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HappeningStatusCalculator::class);

uses(RefreshDatabase::class);

function makeHappening(): Happening
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    return Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);
}

test('returns reservation type when no viewer is provided', function (): void {
    $happening = makeHappening();
    $calculator = new HappeningStatusCalculator;

    $result = $calculator->calculate($happening, null);

    expect($result['type'])->toBe('reservation')
        ->and($result['user'])->toBeEmpty();
});

test('returns booking type when verified and no viewer', function (): void {
    $happening = makeHappening();
    $happening->update(['is_verified' => true]);
    $happening->refresh();

    $calculator = new HappeningStatusCalculator;

    $result = $calculator->calculate($happening, null);

    expect($result['type'])->toBe('booking')
        ->and($result['user'])->toBeEmpty();
});

test('returns user-reservation when viewer is user_id_01 and not verified', function (): void {
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

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $user);

    expect($result['type'])->toBe('user-reservation')
        ->and($result['user'])->toHaveKey('reservation')
        ->and($result['user'])->toHaveKey('verification');
});

test('returns user-booking when viewer is user_id_01 and verified', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $verifier = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'user_id_02' => $verifier->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => true,
        'reserved_at' => now(),
    ]);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $user);

    expect($result['type'])->toBe('user-booking')
        ->and($result['user']['reservation'])->toBe($user->name)
        ->and($result['user']['verification'])->toBe($verifier->name);
});

test('returns booking when viewer is not owner and happening is verified', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => true,
        'reserved_at' => now(),
    ]);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $otherUser);

    expect($result['type'])->toBe('booking')
        ->and($result['user'])->toBeEmpty();
});

test('returns user-to-verify when viewer name matches verifier field', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $verifier = User::factory()->create(['name' => 'verifier.user']);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'verifier' => 'verifier.user',
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $verifier);

    expect($result['type'])->toBe('user-to-verify')
        ->and($result['user'])->toHaveKey('reservation')
        ->and($result['user'])->toHaveKey('verification');
});

test('returns reservation when viewer is not involved and happening is unverified', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $stranger);

    expect($result['type'])->toBe('reservation')
        ->and($result['user'])->toBeEmpty();
});

test('reservation name is empty string when user1 relation is null but viewer still owns the happening', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // Force user1 relation to null to simulate InstanceOfToTrue mutation impact
    $happening->setRelation('user1', null);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $owner);

    expect($result['type'])->toBe('user-reservation')
        ->and($result['user']['reservation'])->toBe('');
});

test('verifier name falls back to empty string when verifier field is not a string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'verifier' => null, // not a string
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $owner);

    // user-reservation: verification should be '' not null
    expect($result['type'])->toBe('user-reservation')
        ->and($result['user']['verification'])->toBe('');
});

test('verifier name is the actual verifier string when set', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'verifier' => 'verifier.name',
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $owner);

    // EmptyStringToNotEmpty would return the non-empty branch even when verifier is null
    expect($result['user']['verification'])->toBe('verifier.name');
});

test('user-booking verification is empty string when user2 is null', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'user_id_02' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => true,
        'reserved_at' => now(),
    ]);

    $calculator = new HappeningStatusCalculator;
    $result = $calculator->calculate($happening, $owner);

    // user-booking with no user2: verification should be '' not throw
    expect($result['type'])->toBe('user-booking')
        ->and($result['user']['verification'])->toBe('');
});
