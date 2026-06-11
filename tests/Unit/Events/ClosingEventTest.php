<?php

declare(strict_types=1);

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingEvent;
use App\Events\ClosingUpdatedEvent;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ClosingCreatedEvent implements ClosingEvent interface', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);

    $event = new ClosingCreatedEvent($user, Happening::whereKey([])->get(), $closing);

    expect($event)->toBeInstanceOf(ClosingEvent::class);
});

test('ClosingDeletedEvent implements ClosingEvent interface', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);

    $event = new ClosingDeletedEvent($user, Happening::whereKey([])->get(), $closing);

    expect($event)->toBeInstanceOf(ClosingEvent::class);
});

test('ClosingUpdatedEvent implements ClosingEvent interface', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);

    $event = new ClosingUpdatedEvent($user, Happening::whereKey([])->get(), $closing);

    expect($event)->toBeInstanceOf(ClosingEvent::class);
});
