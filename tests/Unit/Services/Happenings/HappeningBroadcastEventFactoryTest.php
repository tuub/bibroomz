<?php

declare(strict_types=1);

use App\Events\HappeningBroadcastEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Events\HappeningVerifiedEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningBroadcastEventFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HappeningBroadcastEventFactory::class);

uses(RefreshDatabase::class);

function makeBasicHappening(): Happening
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

test('creates HappeningCreatedEvent instance', function (): void {
    $happening = makeBasicHappening();
    $user = User::factory()->create();
    $factory = new HappeningBroadcastEventFactory;

    $event = $factory->make(HappeningCreatedEvent::class, $happening, $user, ['key' => 'value']);

    expect($event)->toBeInstanceOf(HappeningCreatedEvent::class)
        ->and($event->happening->id)->toBe($happening->id)
        ->and($event->user->id)->toBe($user->id);
});

test('creates HappeningUpdatedEvent instance', function (): void {
    $happening = makeBasicHappening();
    $user = User::factory()->create();
    $factory = new HappeningBroadcastEventFactory;

    $event = $factory->make(HappeningUpdatedEvent::class, $happening, $user, []);

    expect($event)->toBeInstanceOf(HappeningUpdatedEvent::class);
});

test('creates HappeningDeletedEvent instance', function (): void {
    $happening = makeBasicHappening();
    $user = User::factory()->create();
    $factory = new HappeningBroadcastEventFactory;

    $event = $factory->make(HappeningDeletedEvent::class, $happening, $user, []);

    expect($event)->toBeInstanceOf(HappeningDeletedEvent::class);
});

test('creates HappeningVerifiedEvent instance', function (): void {
    $happening = makeBasicHappening();
    $user = User::factory()->create();
    $factory = new HappeningBroadcastEventFactory;

    $event = $factory->make(HappeningVerifiedEvent::class, $happening, $user, []);

    expect($event)->toBeInstanceOf(HappeningVerifiedEvent::class);
});

test('returned event stores broadcastWith payload', function (): void {
    $happening = makeBasicHappening();
    $user = User::factory()->create();
    $factory = new HappeningBroadcastEventFactory;
    $payload = ['test_key' => 'test_value'];

    $event = $factory->make(HappeningCreatedEvent::class, $happening, $user, $payload);

    expect($event->broadcastWith())->toBe($payload);
});

test('throws InvalidArgumentException for non-HappeningBroadcastEvent class', function (): void {
    $happening = makeBasicHappening();
    $user = User::factory()->create();
    $factory = new HappeningBroadcastEventFactory;

    expect(fn (): HappeningBroadcastEvent => $factory->make(User::class, $happening, $user, []))
        ->toThrow(InvalidArgumentException::class);
});

test('all returned events are HappeningBroadcastEvent instances', function (): void {
    $happening = makeBasicHappening();
    $user = User::factory()->create();
    $factory = new HappeningBroadcastEventFactory;

    $event = $factory->make(HappeningCreatedEvent::class, $happening, $user, []);

    expect($event)->toBeInstanceOf(HappeningBroadcastEvent::class);
});
