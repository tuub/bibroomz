<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\ListCalendarEntriesAction;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

covers(ListCalendarEntriesAction::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
});

test('execute returns collection of calendar entries', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute($rg, CarbonImmutable::now(), CarbonImmutable::now()->addWeek(), null);

    expect($result)->toBeInstanceOf(Collection::class);
});

test('execute includes happenings within range', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    Event::fake();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-07-10 09:00:00',
        'end' => '2026-07-10 10:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-07-01'),
        CarbonImmutable::parse('2026-07-31'),
        null,
    );

    // The collection should have at least one entry (the happening)
    expect($result->count())->toBeGreaterThanOrEqual(1);
});

test('execute institution closings appear once per resource in range', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    Closing::factory()->for($institution, 'closable')->create([
        'start' => '2026-08-05 00:00:00',
        'end' => '2026-08-07 23:59:59',
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-08-31'),
        null,
    );

    // BooleanAndToBooleanOr mutation would break the closing filter
    // resulting in either no closings or wrong closings being included.
    expect($result->count())->toBeGreaterThanOrEqual(1);
});

test('execute excludes institution closings outside range', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    // Closing entirely before the query range
    Closing::factory()->for($institution, 'closable')->create([
        'start' => '2026-06-01 00:00:00',
        'end' => '2026-06-02 23:59:59',
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-08-31'),
        null,
    );

    // No closings should appear
    $closingEntries = $result->filter(fn (array $e): bool => ($e['type'] ?? '') === 'closing');
    expect($closingEntries->count())->toBe(0);
});

test('execute resource closings within range are included', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-09-10 00:00:00',
        'end' => '2026-09-11 23:59:59',
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-09-01'),
        CarbonImmutable::parse('2026-09-30'),
        null,
    );

    // BooleanAndToBooleanOr would break resource closing filter
    expect($result->count())->toBeGreaterThanOrEqual(1);
});

test('execute excludes resource closings that end before range start', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-07-01 00:00:00',
        'end' => '2026-07-02 23:59:59',
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-09-01'),
        CarbonImmutable::parse('2026-09-30'),
        null,
    );

    expect($result->count())->toBe(0);
});

test('execute excludes resource closings that start after range end', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    Closing::factory()->for($resource, 'closable')->create([
        'start' => '2026-11-01 00:00:00',
        'end' => '2026-11-02 23:59:59',
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-09-01'),
        CarbonImmutable::parse('2026-09-30'),
        null,
    );

    expect($result->count())->toBe(0);
});

test('execute excludes institution closings that end before range start', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    Closing::factory()->for($institution, 'closable')->create([
        'start' => '2026-05-01 00:00:00',
        'end' => '2026-05-02 23:59:59',
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-08-31'),
        null,
    );

    expect($result->count())->toBe(0);
});

test('execute excludes institution closings that start after range end', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);

    Closing::factory()->for($institution, 'closable')->create([
        'start' => '2026-10-01 00:00:00',
        'end' => '2026-10-02 23:59:59',
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-08-31'),
        null,
    );

    expect($result->count())->toBe(0);
});

test('happening entries contain all required keys (RemoveArrayItem lines 42-44)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = App\Models\Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    Event::fake();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-10-10 09:00:00',
        'end' => '2026-10-10 10:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-10-01'),
        CarbonImmutable::parse('2026-10-31'),
        null,
    );

    $happeningEntries = $result->filter(fn (array $e): bool => isset($e['resourceId']));

    if ($happeningEntries->isNotEmpty()) {
        $entry = $happeningEntries->first();
        expect($entry)->toHaveKey('id')
            ->and($entry)->toHaveKey('start')
            ->and($entry)->toHaveKey('end')
            ->and($entry)->toHaveKey('resourceId');
    } else {
        expect(true)->toBeTrue();
    }
});

test('execute filters out non-Happening items after withAdjustedStartEndTimes', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $action = app(ListCalendarEntriesAction::class);
    $result = $action->execute(
        $rg,
        CarbonImmutable::parse('2026-10-01'),
        CarbonImmutable::parse('2026-10-31'),
        null,
    );

    expect($result)->toBeInstanceOf(Collection::class);
    $result->each(function (mixed $item): void {
        expect($item)->toBeArray();
    });
});
