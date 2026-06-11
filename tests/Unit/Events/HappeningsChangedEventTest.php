<?php

declare(strict_types=1);

use App\Events\HappeningsChangedEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

covers(HappeningsChangedEvent::class);

test('implements ShouldBroadcastNow', function (): void {
    $event = new HappeningsChangedEvent;

    expect($event)->toBeInstanceOf(ShouldBroadcastNow::class);
});

test('broadcastOn returns public happenings channel', function (): void {
    $event = new HappeningsChangedEvent;

    $channel = $event->broadcastOn();

    expect($channel)->toBeInstanceOf(Channel::class)
        ->and($channel->name)->toBe('happenings');
});
