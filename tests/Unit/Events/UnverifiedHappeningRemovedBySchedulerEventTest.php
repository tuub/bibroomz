<?php

declare(strict_types=1);

use App\Events\HappeningBroadcastEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\UnverifiedHappeningRemovedBySchedulerEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(UnverifiedHappeningRemovedBySchedulerEvent::class);

uses(RefreshDatabase::class);

test('extends HappeningDeletedEvent and HappeningBroadcastEvent', function (): void {
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

    $event = new UnverifiedHappeningRemovedBySchedulerEvent($happening, $user);

    expect($event)->toBeInstanceOf(HappeningDeletedEvent::class)
        ->and($event)->toBeInstanceOf(HappeningBroadcastEvent::class)
        ->and($event)->toBeInstanceOf(ShouldBroadcastNow::class);
});

test('broadcast channel is private and scoped to user id', function (): void {
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

    $event = new UnverifiedHappeningRemovedBySchedulerEvent($happening, $user, ['removed_by' => 'scheduler']);

    expect($event->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($event->broadcastWith())->toBe(['removed_by' => 'scheduler'])
        ->and($event->happening)->toBe($happening)
        ->and($event->user)->toBe($user);
});
