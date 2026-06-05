<?php

covers(
    App\Events\ClosingCreatedEvent::class,
    App\Events\ClosingUpdatedEvent::class,
    App\Events\ClosingDeletedEvent::class,
    App\Events\HappeningCreatedEvent::class,
    App\Events\HappeningUpdatedEvent::class,
    App\Events\HappeningDeletedEvent::class,
    App\Events\HappeningVerifiedEvent::class,
    App\Events\HappeningBroadcastEvent::class,
    App\Events\HappeningsChangedEvent::class,
    App\Events\UnverifiedHappeningRemovedBySchedulerEvent::class
);

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningsChangedEvent;
use App\Events\UnverifiedHappeningRemovedBySchedulerEvent;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\User;
use App\Services\Happenings\HappeningBroadcastEventFactory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Collection;

test('happening broadcast events expose precomputed payload and channel names', function () {
    $happening = new Happening();
    $user = new User();
    $user->id = 'user-1';
    $payload = ['happening' => ['id' => 'event-1']];

    $event = new HappeningCreatedEvent($happening, $user, $payload);

    expect($event->broadcastWith())->toBe($payload)
        ->and($event->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($event->broadcastOn()->name)->toContain('happenings.user-1');
});

test('happening broadcast event factory builds concrete broadcast event instances', function () {
    $factory = new HappeningBroadcastEventFactory();
    $happening = new Happening();
    $user = new User();
    $payload = ['happening' => ['id' => 'event-1']];

    $event = $factory->make(HappeningDeletedEvent::class, $happening, $user, $payload);
    $schedulerEvent = $factory->make(UnverifiedHappeningRemovedBySchedulerEvent::class, $happening, $user, $payload);

    expect($event)->toBeInstanceOf(HappeningDeletedEvent::class)
        ->and($event->broadcastWith())->toBe($payload)
        ->and($schedulerEvent)->toBeInstanceOf(UnverifiedHappeningRemovedBySchedulerEvent::class);
});

test('happenings changed event stays on the public happenings channel', function () {
    $event = new HappeningsChangedEvent();

    expect($event->broadcastOn())->toBeInstanceOf(Channel::class)
        ->and($event->broadcastOn()->name)->toBe('happenings');
});

test('closing events remain simple transport objects', function () {
    $user = new User();
    $closing = new Closing();
    $happenings = Collection::make([new Happening()]);

    $created = new ClosingCreatedEvent($user, $happenings, $closing);
    $updated = new ClosingUpdatedEvent($user, $happenings, $closing);
    $deleted = new ClosingDeletedEvent($user, $happenings, $closing);

    expect($created->user)->toBe($user)
        ->and($created->user())->toBe($user)
        ->and($created->happenings)->toBe($happenings)
        ->and($created->happenings())->toBe($happenings)
        ->and($created->closing)->toBe($closing)
        ->and($created->closing())->toBe($closing)
        ->and($updated->user)->toBe($user)
        ->and($deleted->closing)->toBe($closing);
});
