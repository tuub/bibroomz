<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Services\Closings\ClosingEventDispatcher;
use App\Services\Closings\DeleteClosingAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(DeleteClosingAction::class);

uses(RefreshDatabase::class);

test('execute deletes the closing', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $id = $closing->id;

    $action = app(DeleteClosingAction::class);
    $action->execute($closing);

    expect(Closing::find($id))->toBeNull();
});

test('execute returns true when closing is deleted', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $action = app(DeleteClosingAction::class);
    $result = $action->execute($closing);

    expect($result)->toBeTrue();
});

test('execute calls dispatchDeleted on closing event dispatcher', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $eventDispatcher = Mockery::mock(ClosingEventDispatcher::class);
    $eventDispatcher->shouldReceive('dispatchDeleted')->once()->with(Mockery::type(Closing::class));
    app()->instance(ClosingEventDispatcher::class, $eventDispatcher);

    $action = app(DeleteClosingAction::class);
    $action->execute($closing);
});

test('execute returns false and does not dispatch when delete returns false', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $eventDispatcher = Mockery::mock(ClosingEventDispatcher::class);
    $eventDispatcher->shouldNotReceive('dispatchDeleted');
    app()->instance(ClosingEventDispatcher::class, $eventDispatcher);

    $closingMock = Mockery::mock($closing)->makePartial();
    $closingMock->shouldReceive('delete')->once()->andReturn(false);

    $action = app(DeleteClosingAction::class);
    $result = $action->execute($closingMock);

    expect($result)->toBeFalse();
});
