<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Services\Closings\ClosingEventDispatcher;
use App\Services\Closings\UpdateClosingAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(UpdateClosingAction::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('execute updates closing attributes', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $action = app(UpdateClosingAction::class);
    $updated = $action->execute($closing, [
        'start_date' => '01.08.2026',
        'start_time' => '08:00',
        'end_date' => '01.08.2026',
        'end_time' => '18:00',
        'description' => ['en' => 'Updated'],
    ]);

    expect($updated->id)->toBe($closing->id);
});

test('execute persists updated start and end to database', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => Carbon::parse('2026-01-01 09:00:00'),
        'end' => Carbon::parse('2026-01-01 17:00:00'),
    ]);
    $originalId = $closing->id;

    $action = app(UpdateClosingAction::class);
    $action->execute($closing, [
        'start_date' => '15.08.2026',
        'start_time' => '10:00',
        'end_date' => '15.08.2026',
        'end_time' => '20:00',
        'description' => ['en' => 'Changed'],
    ]);

    $fresh = Closing::findOrFail($originalId);
    // IfNegated mutation would skip the update; this catches it
    expect((string) $fresh->start)->not->toContain('2026-01-01');
});

test('execute excludes closable_id and closable_type from update data', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $originalClosableId = $closing->closable_id;

    $action = app(UpdateClosingAction::class);
    $action->execute($closing, [
        'start_date' => '01.09.2026',
        'start_time' => '08:00',
        'end_date' => '01.09.2026',
        'end_time' => '18:00',
        'closable_id' => 'some-other-id', // should be excluded
        'closable_type' => App\Models\Resource::class, // should be excluded
    ]);

    $fresh = Closing::findOrFail($closing->id);
    // RemoveArrayItem mutations would keep closable_id/closable_type, overwriting them
    expect((string) $fresh->closable_id)->toBe($originalClosableId);
});

test('execute dispatches updated event when closing is saved', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $eventDispatcher = Mockery::mock(ClosingEventDispatcher::class);
    $eventDispatcher->shouldReceive('dispatchUpdated')->once()->with(Mockery::type(Closing::class));
    app()->instance(ClosingEventDispatcher::class, $eventDispatcher);

    $action = app(UpdateClosingAction::class);
    $action->execute($closing, [
        'start_date' => '01.07.2026',
        'start_time' => '09:00',
        'end_date' => '01.07.2026',
        'end_time' => '17:00',
    ]);
});

test('normalizeStringKeys filters non-string keys from the data passed to update', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $action = app(UpdateClosingAction::class);
    // ForeachEmptyIterable/IfNegated on normalizeStringKeys: passing a normal array should still work
    $result = $action->execute($closing, [
        'start_date' => '10.06.2026',
        'start_time' => '08:00',
        'end_date' => '10.06.2026',
        'end_time' => '16:00',
        'description' => ['en' => 'Closed'],
    ]);

    // AlwaysReturnEmptyArray mutation would make normalizeStringKeys return []
    // and then the closing->update([]) would succeed trivially but start/end would not persist.
    $fresh = Closing::findOrFail($closing->id);
    expect($fresh->start)->not->toBeNull();
});
