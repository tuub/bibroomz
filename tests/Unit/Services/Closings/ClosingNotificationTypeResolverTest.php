<?php

declare(strict_types=1);

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\User;
use App\Services\Closings\ClosingNotificationTypeResolver;

covers(ClosingNotificationTypeResolver::class);

test('resolves closing_created for ClosingCreatedEvent', function (): void {
    $event = new ClosingCreatedEvent(new User, (new Happening)->newCollection(), new Closing);

    $resolver = new ClosingNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('closing_created');
});

test('resolves closing_deleted for ClosingDeletedEvent', function (): void {
    $event = new ClosingDeletedEvent(new User, (new Happening)->newCollection(), new Closing);

    $resolver = new ClosingNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('closing_deleted');
});

test('resolves closing_updated for ClosingUpdatedEvent', function (): void {
    $event = new ClosingUpdatedEvent(new User, (new Happening)->newCollection(), new Closing);

    $resolver = new ClosingNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('closing_updated');
});
