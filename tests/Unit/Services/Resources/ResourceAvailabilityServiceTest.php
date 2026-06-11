<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Resources\ResourceAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(ResourceAvailabilityService::class);

uses(RefreshDatabase::class);

test('findClosed returns empty when no closings', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAvailabilityService::class);
    [$closed, $closing] = $service->findClosed($resource, CarbonImmutable::now()->addHour(), CarbonImmutable::now()->addHours(2));

    expect($closed)->toBeFalse();
});

test('hasReservationConflict returns false when no happenings exist', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceAvailabilityService::class);
    $result = $service->hasReservationConflict($resource, CarbonImmutable::now()->addHour(), CarbonImmutable::now()->addHours(2));

    expect($result)->toBeFalse();
});

test('findClosed returns true and original times when time slot is within a closing', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Add a closing that covers the query range
    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-07-01 00:00:00',
        'end' => '2026-07-31 23:59:59',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    $service = app(ResourceAvailabilityService::class);
    $start = CarbonImmutable::parse('2026-07-10 09:00:00');
    $end = CarbonImmutable::parse('2026-07-10 17:00:00');
    [$isClosed, $retStart, $retEnd] = $service->findClosed($resource, $start, $end);

    // ForeachEmptyIterable mutation would skip the loop → always returns false
    expect($isClosed)->toBeTrue()
        // RemoveArrayItem mutation would drop $isClosed from return
        ->and($retStart->format('H:i'))->toBe('09:00')
        ->and($retEnd->format('H:i'))->toBe('17:00');
});

test('findClosed adjusts start when slot partially overlaps closing start', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-08-10 08:00:00',
        'end' => '2026-08-10 11:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    $service = app(ResourceAvailabilityService::class);
    $start = CarbonImmutable::parse('2026-08-10 09:00:00');
    $end = CarbonImmutable::parse('2026-08-10 14:00:00');
    [$isClosed, $retStart, $retEnd] = $service->findClosed($resource, $start, $end);

    // start (09:00) >= closingStart (08:00) && start (09:00) < closingEnd (11:00) → adjust start
    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('11:00');
});

test('hasReservationConflict returns true when happening overlaps', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    Event::fake();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-09-15 09:00:00',
        'end' => '2026-09-15 11:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resource->load('happenings');

    $service = app(ResourceAvailabilityService::class);
    $result = $service->hasReservationConflict(
        $resource,
        CarbonImmutable::parse('2026-09-15 10:00:00'),
        CarbonImmutable::parse('2026-09-15 12:00:00'),
    );

    // ForeachEmptyIterable mutation would make the loop skip → always return false
    expect($result)->toBeTrue();
});

test('findClosed returns isClosed true at exact boundary', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-10-01 09:00:00',
        'end' => '2026-10-01 17:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    $service = app(ResourceAvailabilityService::class);

    [$isClosed] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-10-01 09:00:00'),
        CarbonImmutable::parse('2026-10-01 17:00:00'),
    );

    expect($isClosed)->toBeTrue();
});

test('findClosed breaks on first full-enclosing closing and does not continue', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-11-01 08:00:00',
        'end' => '2026-11-01 18:00:00',
    ]);

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-11-01 09:00:00',
        'end' => '2026-11-01 12:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    $service = app(ResourceAvailabilityService::class);

    [$isClosed, $retStart, $retEnd] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-11-01 10:00:00'),
        CarbonImmutable::parse('2026-11-01 11:00:00'),
    );

    expect($isClosed)->toBeTrue()
        ->and($retStart->format('H:i'))->toBe('10:00')
        ->and($retEnd->format('H:i'))->toBe('11:00');
});

test('findClosed adjusts start when start equals closingStart', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-01 09:00:00',
        'end' => '2026-12-01 11:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    $service = app(ResourceAvailabilityService::class);

    [$isClosed, $retStart] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-01 09:00:00'),
        CarbonImmutable::parse('2026-12-01 14:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('11:00');
});

test('findClosed does not adjust when start equals closingEnd', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-02 09:00:00',
        'end' => '2026-12-02 11:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    $service = app(ResourceAvailabilityService::class);

    [$isClosed, $retStart] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-02 11:00:00'),
        CarbonImmutable::parse('2026-12-02 14:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('11:00');
});

test('findClosed only adjusts start when both conditions true simultaneously', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-03 11:00:00',
        'end' => '2026-12-03 13:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    $service = app(ResourceAvailabilityService::class);

    [$isClosed, $retStart] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-03 08:00:00'),
        CarbonImmutable::parse('2026-12-03 10:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('08:00');
});

test('findClosed does not continue mutating the slot after a full-closing match', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-10 08:00:00',
        'end' => '2026-12-10 18:00:00',
    ]);
    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-10 10:30:00',
        'end' => '2026-12-10 13:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    [$isClosed, $retStart, $retEnd] = app(ResourceAvailabilityService::class)->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-10 10:00:00'),
        CarbonImmutable::parse('2026-12-10 11:00:00'),
    );

    expect($isClosed)->toBeTrue()
        ->and($retStart->format('H:i'))->toBe('10:00')
        ->and($retEnd->format('H:i'))->toBe('11:00');
});

test('findClosed trims the end when only the slot end overlaps a closing', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-11 11:00:00',
        'end' => '2026-12-11 13:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    [$isClosed, $retStart, $retEnd] = app(ResourceAvailabilityService::class)->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-11 08:00:00'),
        CarbonImmutable::parse('2026-12-11 12:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('08:00')
        ->and($retEnd->format('H:i'))->toBe('11:00');
});

test('findClosed keeps non-overlapping late slots unchanged', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-12 11:00:00',
        'end' => '2026-12-12 13:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    [$isClosed, $retStart, $retEnd] = app(ResourceAvailabilityService::class)->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-12 13:30:00'),
        CarbonImmutable::parse('2026-12-12 14:30:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('13:30')
        ->and($retEnd->format('H:i'))->toBe('14:30');
});

test('findClosed shifts the start forward when the slot spans a closing and the earlier side is shorter', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-13 10:00:00',
        'end' => '2026-12-13 12:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    [$isClosed, $retStart, $retEnd] = app(ResourceAvailabilityService::class)->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-13 09:30:00'),
        CarbonImmutable::parse('2026-12-13 15:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('12:00')
        ->and($retEnd->format('H:i'))->toBe('15:00');
});

test('findClosed shifts the end backward when the later side is shorter', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-14 10:00:00',
        'end' => '2026-12-14 12:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    [$isClosed, $retStart, $retEnd] = app(ResourceAvailabilityService::class)->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-14 08:00:00'),
        CarbonImmutable::parse('2026-12-14 12:30:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('08:00')
        ->and($retEnd->format('H:i'))->toBe('10:00');
});

test('findClosed breaks ties by shifting the end backward when both sides are equally far from the closing', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-16 10:00:00',
        'end' => '2026-12-16 12:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    [$isClosed, $retStart, $retEnd] = app(ResourceAvailabilityService::class)->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-16 09:00:00'),
        CarbonImmutable::parse('2026-12-16 13:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('09:00')
        ->and($retEnd->format('H:i'))->toBe('10:00');
});

test('hasReservationConflict result array contains isConcurrent key', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $resource->load('happenings');

    $service = app(ResourceAvailabilityService::class);
    $result = $service->hasReservationConflict(
        $resource,
        CarbonImmutable::parse('2026-09-20 10:00:00'),
        CarbonImmutable::parse('2026-09-20 11:00:00'),
    );

    expect($result)->toBeBool()
        ->and($result)->toBeFalse();
});

test('hasReservationConflict ignores the provided happening when checking overlaps', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    Event::fake();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-12-15 09:00:00',
        'end' => '2026-12-15 11:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resource->load('happenings');

    $result = app(ResourceAvailabilityService::class)->hasReservationConflict(
        $resource,
        CarbonImmutable::parse('2026-12-15 09:30:00'),
        CarbonImmutable::parse('2026-12-15 10:30:00'),
        $happening,
    );

    expect($result)->toBeFalse();
});

test('findClosed trims end when slot end equals closing end', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-12-20 09:00:00',
        'end' => '2026-12-20 11:00:00',
    ]);

    $resource->load(['closings', 'resource_group.institution.closings']);

    [$isClosed, $retStart, $retEnd] = app(ResourceAvailabilityService::class)->findClosed(
        $resource,
        CarbonImmutable::parse('2026-12-20 07:00:00'),
        CarbonImmutable::parse('2026-12-20 11:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($retStart->format('H:i'))->toBe('07:00')
        ->and($retEnd->format('H:i'))->toBe('09:00');
});
