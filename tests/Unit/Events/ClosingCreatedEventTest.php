<?php

declare(strict_types=1);

use App\Events\ClosingCreatedEvent;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

covers(ClosingCreatedEvent::class);

uses(RefreshDatabase::class);

test('event exposes user, happenings, and closing via accessor methods', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);
    $happenings = Happening::whereKey([$happening->id])->get();

    $event = new ClosingCreatedEvent($user, $happenings, $closing);

    expect($event->user())->toBe($user)
        ->and($event->closing())->toBe($closing)
        ->and($event->happenings())->toBe($happenings)
        ->and($event->happenings()->first()?->id)->toBe($happening->id);
});

test('event stores user, happenings, and closing as public properties', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);

    $event = new ClosingCreatedEvent($user, Happening::whereKey([])->get(), $closing);

    expect($event->user)->toBe($user)
        ->and($event->closing)->toBe($closing)
        ->and($event->happenings)->toBeInstanceOf(Collection::class);
});
