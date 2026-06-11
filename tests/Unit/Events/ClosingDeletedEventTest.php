<?php

declare(strict_types=1);

use App\Events\ClosingDeletedEvent;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

covers(ClosingDeletedEvent::class);

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
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $happenings = Happening::whereKey([$happening->id])->get();

    $event = new ClosingDeletedEvent($user, $happenings, $closing);

    expect($event->user())->toBe($user)
        ->and($event->closing())->toBe($closing)
        ->and($event->happenings())->toBe($happenings);
});

test('event stores public properties correctly', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);

    $event = new ClosingDeletedEvent($user, Happening::whereKey([])->get(), $closing);

    expect($event->user)->toBe($user)
        ->and($event->closing)->toBe($closing)
        ->and($event->happenings)->toBeInstanceOf(Collection::class);
});
