<?php

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Listeners\ClosingEventSubscriber;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\User;
use App\Services\Closings\ClosingInstitutionResolver;
use App\Services\Closings\ClosingNotificationService;
use App\Services\Closings\ClosingNotificationTypeResolver;
use Illuminate\Support\Collection;

covers(
    ClosingEventSubscriber::class,
    ClosingNotificationService::class,
    ClosingInstitutionResolver::class,
    ClosingNotificationTypeResolver::class
);

afterEach(fn () => Mockery::close());

test('closing event subscriber forwards every event to the notification service', function (): void {
    $service = Mockery::mock(ClosingNotificationService::class);
    $subscriber = new ClosingEventSubscriber($service);
    $closing = Mockery::mock(Closing::class);
    $user = Mockery::mock(User::class);
    /** @var Collection<int, Happening> $happenings */
    $happenings = collect();

    $created = new ClosingCreatedEvent($user, $happenings, $closing);
    $updated = new ClosingUpdatedEvent($user, $happenings, $closing);
    $deleted = new ClosingDeletedEvent($user, $happenings, $closing);

    $service->shouldReceive('sendForEvent')->once()->with($created);
    $service->shouldReceive('sendForEvent')->once()->with($updated);
    $service->shouldReceive('sendForEvent')->once()->with($deleted);

    $subscriber->handleClosingCreatedEvent($created);
    $subscriber->handleClosingUpdatedEvent($updated);
    $subscriber->handleClosingDeletedEvent($deleted);
});

test('closing event subscriber exposes the expected event map', function (): void {
    $subscriber = new ClosingEventSubscriber(Mockery::mock(ClosingNotificationService::class));

    expect($subscriber->subscribe())->toBe([
        ClosingCreatedEvent::class => 'handleClosingCreatedEvent',
        ClosingUpdatedEvent::class => 'handleClosingUpdatedEvent',
        ClosingDeletedEvent::class => 'handleClosingDeletedEvent',
    ]);
});
