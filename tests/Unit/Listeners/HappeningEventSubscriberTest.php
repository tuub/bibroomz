<?php

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Events\HappeningVerifiedEvent;
use App\Listeners\HappeningEventSubscriber;
use App\Models\Happening;
use App\Models\User;
use App\Services\Happenings\HappeningNotificationService;
use App\Services\Happenings\HappeningNotificationTypeResolver;

covers(
    HappeningEventSubscriber::class,
    HappeningNotificationService::class,
    HappeningNotificationTypeResolver::class
);

afterEach(fn () => Mockery::close());

test('happening event subscriber forwards every event to the notification service', function (): void {
    $service = Mockery::mock(HappeningNotificationService::class);
    $subscriber = new HappeningEventSubscriber($service);
    $happening = Mockery::mock(Happening::class);
    $user = Mockery::mock(User::class);

    $created = new HappeningCreatedEvent($happening, $user);
    $updated = new HappeningUpdatedEvent($happening, $user);
    $deleted = new HappeningDeletedEvent($happening, $user);
    $verified = new HappeningVerifiedEvent($happening, $user);

    $service->shouldReceive('sendForEvent')->once()->with($created);
    $service->shouldReceive('sendForEvent')->once()->with($updated);
    $service->shouldReceive('sendForEvent')->once()->with($deleted);
    $service->shouldReceive('sendForEvent')->once()->with($verified);

    $subscriber->handleHappeningCreatedEvent($created);
    $subscriber->handleHappeningUpdatedEvent($updated);
    $subscriber->handleHappeningDeletedEvent($deleted);
    $subscriber->handleHappeningVerifiedEvent($verified);
});

test('happening event subscriber exposes the expected event map', function (): void {
    $subscriber = new HappeningEventSubscriber(Mockery::mock(HappeningNotificationService::class));

    expect($subscriber->subscribe())->toBe([
        HappeningCreatedEvent::class => 'handleHappeningCreatedEvent',
        HappeningVerifiedEvent::class => 'handleHappeningVerifiedEvent',
        HappeningUpdatedEvent::class => 'handleHappeningUpdatedEvent',
        HappeningDeletedEvent::class => 'handleHappeningDeletedEvent',
    ]);
});
