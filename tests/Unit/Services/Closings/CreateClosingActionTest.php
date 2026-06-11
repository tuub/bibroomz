<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Services\Closings\ClosingEventDispatcher;
use App\Services\Closings\CreateClosingAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(CreateClosingAction::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

/** @return array<string, mixed> */
function closingAttributes(Institution $institution): array
{
    return [
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start_date' => '01.07.2026',
        'start_time' => '00:00',
        'end_date' => '01.07.2026',
        'end_time' => '23:59',
        'description' => ['en' => 'Closed'],
    ];
}

test('execute creates a closing for the closable', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();

    $action = app(CreateClosingAction::class);
    $closing = $action->execute($institution, closingAttributes($institution));

    expect($closing)->toBeInstanceOf(Closing::class)
        ->and($closing->id)->not->toBeNull();
});

test('execute associates the closing with the closable via the relation', function (): void {
    // RemoveMethodCall on line 24 removes $closable->closings()->save($closing),
    // so the closing would not be persisted under the closable.
    Event::fake();
    $institution = Institution::factory()->create();

    $action = app(CreateClosingAction::class);
    $closing = $action->execute($institution, closingAttributes($institution));

    expect($institution->closings()->whereKey($closing->id)->exists())->toBeTrue();
});

test('execute sets closable relation on the returned closing', function (): void {
    // RemoveMethodCall on line 25 removes $closing->setRelation('closable', $closable).
    // The relation would not be in-memory loaded and callers would need a DB round-trip.
    Event::fake();
    $institution = Institution::factory()->create();

    $action = app(CreateClosingAction::class);
    $closing = $action->execute($institution, closingAttributes($institution));

    expect($closing->relationLoaded('closable'))->toBeTrue()
        ->and($closing->closable?->id)->toBe($institution->id);
});

test('execute dispatches closing created event via dispatcher', function (): void {
    // RemoveMethodCall on line 26 removes dispatchCreated(), so no event is fired.
    $institution = Institution::factory()->create();

    $dispatcher = Mockery::mock(ClosingEventDispatcher::class);
    $dispatcher->shouldReceive('dispatchCreated')->once()->with(Mockery::type(Closing::class));
    app()->instance(ClosingEventDispatcher::class, $dispatcher);

    $action = app(CreateClosingAction::class);
    $action->execute($institution, closingAttributes($institution));
});
