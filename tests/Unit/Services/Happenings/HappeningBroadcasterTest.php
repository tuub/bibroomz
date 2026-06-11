<?php

declare(strict_types=1);

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningsChangedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(HappeningBroadcaster::class);

uses(RefreshDatabase::class);

function makeBroadcastHappening(): Happening
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
        'verified_at' => now(),
    ]);
}

test('dispatches HappeningCreatedEvent for each audience user', function (): void {
    Event::fake();

    $happening = makeBroadcastHappening();
    $broadcaster = app(HappeningBroadcaster::class);

    $broadcaster->broadcast($happening, HappeningCreatedEvent::class);

    Event::assertDispatched(HappeningCreatedEvent::class);
});

test('always dispatches HappeningsChangedEvent after broadcast', function (): void {
    Event::fake();

    $happening = makeBroadcastHappening();
    $broadcaster = app(HappeningBroadcaster::class);

    $broadcaster->broadcast($happening, HappeningCreatedEvent::class);

    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('dispatches HappeningDeletedEvent when deleting', function (): void {
    Event::fake();

    $happening = makeBroadcastHappening();
    $broadcaster = app(HappeningBroadcaster::class);

    $broadcaster->broadcast($happening, HappeningDeletedEvent::class);

    Event::assertDispatched(HappeningDeletedEvent::class);
});

test('dispatches event for each audience member when happening has two users', function (): void {
    Event::fake();

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
        'verified_at' => now(),
    ]);

    $broadcaster = app(HappeningBroadcaster::class);
    $broadcaster->broadcast($happening, HappeningUpdatedEvent::class);

    Event::assertDispatched(HappeningUpdatedEvent::class, 2);
    Event::assertDispatched(HappeningsChangedEvent::class);
});
