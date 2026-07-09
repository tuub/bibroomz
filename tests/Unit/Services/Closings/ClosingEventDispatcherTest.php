<?php

declare(strict_types=1);

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Closings\ClosingEventDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(ClosingEventDispatcher::class);

uses(RefreshDatabase::class);

test('dispatchCreated dispatches no events when no users are affected', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchCreated($closing);

    Event::assertNotDispatched(ClosingCreatedEvent::class);
    Event::assertNotDispatched(ClosingUpdatedEvent::class);
    Event::assertNotDispatched(ClosingDeletedEvent::class);
});

test('dispatchUpdated dispatches no events when no users are affected', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchUpdated($closing);

    Event::assertNotDispatched(ClosingCreatedEvent::class);
    Event::assertNotDispatched(ClosingUpdatedEvent::class);
    Event::assertNotDispatched(ClosingDeletedEvent::class);
});

test('dispatchDeleted dispatches no events when no users are affected', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchDeleted($closing);

    Event::assertNotDispatched(ClosingCreatedEvent::class);
    Event::assertNotDispatched(ClosingUpdatedEvent::class);
    Event::assertNotDispatched(ClosingDeletedEvent::class);
});

test('dispatchCreated dispatches ClosingCreatedEvent for each affected user', function (): void {
    Event::fake();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => now()->subDay(),
        'end' => now()->addDays(2),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchCreated($closing);

    Event::assertDispatched(ClosingCreatedEvent::class);
});

test('dispatchUpdated dispatches ClosingUpdatedEvent for each affected user', function (): void {
    Event::fake();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => now()->subDay(),
        'end' => now()->addDays(2),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchUpdated($closing);

    Event::assertDispatched(ClosingUpdatedEvent::class);
});

test('dispatchDeleted dispatches ClosingDeletedEvent for each affected user', function (): void {
    Event::fake();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => now()->subDay(),
        'end' => now()->addDays(2),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchDeleted($closing);

    Event::assertDispatched(ClosingDeletedEvent::class);
});

test('dispatch loads closable relation even when no users are affected', function (): void {
    // RemoveMethodCall on line 29 removes $closing->loadMissing('closable').
    // When no happenings overlap the closing, getUsersAffected() returns [] and the loop never
    // runs, so $this->closable is never lazy-loaded. Only loadMissing guarantees it is set.
    Event::fake();
    $institution = Institution::factory()->create();

    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => now()->addYears(100),
        'end' => now()->addYears(100)->addDay(),
    ]);

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchCreated($closing);

    expect($closing->relationLoaded('closable'))->toBeTrue();
});

test('dispatch calls loadMissing on closing', function (): void {
    Event::fake();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => now()->subDay(),
        'end' => now()->addDays(2),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchCreated($closing);

    expect($closing->relationLoaded('closable'))->toBeTrue();
});

test('dispatchCreated does not dispatch events when notify_users is false even if users are affected', function (): void {
    Event::fake();

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => now()->subDay(),
        'end' => now()->addDays(2),
        'notify_users' => false,
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $dispatcher = new ClosingEventDispatcher;
    $dispatcher->dispatchCreated($closing);

    Event::assertNotDispatched(ClosingCreatedEvent::class);
});
