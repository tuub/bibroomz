<?php

declare(strict_types=1);

use App\Events\HappeningBroadcastEvent;
use App\Events\HappeningCreatedEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HappeningBroadcastEvent::class);

uses(RefreshDatabase::class);

test('broadcastOn returns a private channel keyed by user id', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);

    // HappeningBroadcastEvent is abstract; use a concrete subclass
    $event = new HappeningCreatedEvent($happening, $user);

    $channel = $event->broadcastOn();

    expect($channel)->toBeInstanceOf(PrivateChannel::class)
        ->and($channel->name)->toBe('private-happenings.'.$user->id);
});

test('broadcastWith returns the payload passed at construction', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $payload = ['action' => 'created', 'id' => $happening->id];

    $event = new HappeningCreatedEvent($happening, $user, $payload);

    expect($event->broadcastWith())->toBe($payload);
});

test('broadcastWith returns empty array when no payload provided', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);

    $event = new HappeningCreatedEvent($happening, $user);

    expect($event->broadcastWith())->toBe([]);
});

test('event stores happening and user as public properties', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);

    $event = new HappeningCreatedEvent($happening, $user);

    expect($event->happening)->toBe($happening)
        ->and($event->user)->toBe($user);
});
