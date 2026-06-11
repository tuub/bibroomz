<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;
use App\Services\Resources\GenerateResourceTimeSlotsAction;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(GenerateResourceTimeSlotsAction::class);

uses(RefreshDatabase::class);

test('execute returns array with start and end time slots', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end');
});

// Lines 37, 41: UnwrapArrayValues — result arrays must be 0-indexed lists
test('execute returns start and end slots as sequential lists', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    // Verify the first key is 0 (list-indexed), not a string or arbitrary int
    expect(array_key_first($result['start']))->toBe(0)
        ->and(array_key_first($result['end']))->toBe(0);
});

// ForeachEmptyIterable — slots collection must not be empty
test('execute returns non-empty start and end slot arrays', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result['start'])->not->toBeEmpty()
        ->and($result['end'])->not->toBeEmpty();
});

// isDisabled: true — slots created without business hours should all be disabled
test('slots are disabled by default when resource has no business hours', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Remove all business hours so no slots get enabled
    $resource->business_hours()->delete();
    $resource->refresh();

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    $allDisabled = collect($result['start'])->every(fn (array $slot): bool => $slot['is_disabled'] === true);

    expect($allDisabled)->toBeTrue();
});

// EqualToIdentical / EqualToNotEqual — selected time slot is correctly marked
test('slot matching the selected time has is_selected true', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Business hours 09:00–23:00 with all weekdays from factory
    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    // Pick a start slot in business hours
    $selectedStart = $today->setTime(10, 0);
    $selectedEnd = $today->setTime(11, 0);

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $selectedStart, $selectedEnd);

    $selectedStartSlots = collect($result['start'])->filter(fn (array $s): bool => $s['is_selected'] === true);
    $selectedEndSlots = collect($result['end'])->filter(fn (array $s): bool => $s['is_selected'] === true);

    expect($selectedStartSlots->count())->toBe(1)
        ->and($selectedEndSlots->count())->toBe(1);

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// initTimePeriod minute > 0 — 30-min interval creates 30-min steps
test('time slots are generated at 30-minute intervals for 30-minute slot length setting', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    // Default time_slot_length is '00:30' — minute=30 takes precedence over hour
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GenerateResourceTimeSlotsAction::class);
    // Start from 09:00 so we get slots within business hours, with no past filtering interference
    $start = CarbonImmutable::parse('2026-06-12 09:00:00', 'UTC');
    $result = $action->execute($resource, null, $start, $start->addDay());

    // With 30-min intervals there should be many slots in the business hour window
    expect(count($result['start']))->toBeGreaterThan(1);

    // Check first two slot times are exactly 30 minutes apart
    $slots = $result['start'];
    if (count($slots) >= 2) {
        $time0 = $slots[0]['time'];
        $time1 = $slots[1]['time'];
        $diffMinutes = (int) $time0->diffInMinutes($time1);
        expect($diffMinutes)->toBe(30);
    }

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// minute=30,hour=0 — minute branch used (not hour branch)
test('30-minute slot length uses minute branch not hour branch', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-11 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-11 10:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Verify the setting is indeed 00:30
    /** @var Setting|null $setting */
    $setting = $rg->settings()->where('key', 'time_slot_length')->first();
    expect($setting?->value)->toBe('00:30');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    $result = $action->execute($resource, null, $today, $today->addDay());

    // With minute=30, a full day should have 48 slots
    // With hour=0 (fallback) it would have just 1 slot (start to end CarbonPeriod with no interval)
    expect(count($result['start']))->toBeGreaterThan(2);

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// PlusToMinus — intervalMinutes = minute + 60*hour
// With '01:30' (hour=1,minute=30): intervalMinutes = 30 + 60*1 = 90
// With minus mutation: intervalMinutes = 30 - 60*1 = -30 (negative → all past slots excluded)
test('slot length with hour and minute combines both correctly for past slot filtering', function (): void {
    $this->seed(WeekDaySeeder::class);

    // getCarbonNow() = UTC_now + Berlin_offset(+2h) = 08:00 + 2 = 10:00
    Carbon::setTestNow(Carbon::parse('2026-06-12 08:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 08:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Set slot length to 01:30 (hour=1, minute=30) → intervalMinutes=90
    // Period uses 90-min steps: 00:00, 01:30, 03:00, 04:30, 06:00, 07:30, 09:00, 10:30, ...
    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '01:30']);

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addDay());

    // getCarbonNow = 10:00. Slot at 09:00 UTC is 60 min before now.
    // intervalMinutes=90: 60 < 90 → slot at 09:00 is KEPT
    // With PlusToMinus mutation: intervalMinutes = 30 - 60 = -30 → 60 < -30 is FALSE → excluded
    $startTimes = collect($result['start'])->map(fn (array $s): string => $s['time']->format('H:i'))->values()->toArray();

    // 09:00 is 60 min before getCarbonNow(10:00) → 60 < 90 → should appear
    expect(in_array('09:00', $startTimes))->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

// IfNegated — isAfter check
// SmallerToGreaterOrEqual — slot within intervalMinutes before now is kept
test('past slots within interval window are kept in result', function (): void {
    $this->seed(WeekDaySeeder::class);

    // getCarbonNow() = UTC_now + Berlin_offset(+2h) = 10:00 + 2 = 12:00
    Carbon::setTestNow(Carbon::parse('2026-06-12 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 10:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Default slot length is 00:30 → intervalMinutes=30
    // getCarbonNow = 12:00 UTC
    // Slot at 12:00 UTC: NOT after 12:00, diffInMinutes(12:00, 12:00)=0 < 30 → KEPT
    // Slot at 11:30 UTC: NOT after 12:00, diffInMinutes(11:30, 12:00)=30 NOT < 30 → excluded
    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addDay());

    $startTimes = collect($result['start'])->map(fn (array $s): string => $s['time']->format('H:i'))->values()->toArray();

    // 12:00 slot: diffInMinutes=0 < 30 → kept
    expect(in_array('12:00', $startTimes))->toBeTrue();

    // 11:30 slot: diffInMinutes=30 NOT < 30 → excluded
    expect(in_array('11:30', $startTimes))->toBeFalse();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

// future slots pass the isAfter check
test('future slots are always kept regardless of interval', function (): void {
    $this->seed(WeekDaySeeder::class);

    // getCarbonNow() = UTC_now + Berlin_offset(+2h) = 10:00 + 2 = 12:00
    Carbon::setTestNow(Carbon::parse('2026-06-12 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 10:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addDay());

    // 12:30 UTC is after getCarbonNow(12:00 UTC) → kept by isAfter branch
    $startTimes = collect($result['start'])->map(fn (array $s): string => $s['time']->format('H:i'))->values()->toArray();
    expect(in_array('12:30', $startTimes))->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

// Lines 85/86: isEnd:true TrueToFalse for enableBusinessHours and disableClosedTimeSlots
// The business hour END time (23:00) is included in end slots but excluded from start slots
test('end slots include the business hour end time while start slots exclude it', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // ResourceFactory creates a business hour 09:00–23:00 with all weekdays
    // isTimeSlotInBusinessHour with isEnd=false: timeSlot >= 09:00 && timeSlot < 23:00
    //   → 23:00 is NOT in start slots (timeSlot < 23:00 fails)
    // isTimeSlotInBusinessHour with isEnd=true: timeSlot > 09:00 && timeSlot <= 23:00
    //   → 23:00 IS in end slots (timeSlot <= 23:00 passes)

    // Use $start = 09:00 (within business hours) to avoid disableNonSequentialTimeSlots
    // disabling the 23:00 end slot due to gaps before business hours
    $start = CarbonImmutable::parse('2026-06-12 09:00:00', 'UTC');
    $end = CarbonImmutable::parse('2026-06-12 23:00:00', 'UTC');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $start, $end);

    $startSlot23 = collect($result['start'])->first(fn (array $s): bool => $s['time']->format('H:i') === '23:00');
    $endSlot23 = collect($result['end'])->first(fn (array $s): bool => $s['time']->format('H:i') === '23:00');

    // In start slots: 23:00 >= 09:00 but NOT < 23:00 → not enabled by business hours → disabled
    expect($startSlot23)->not->toBeNull()
        ->and($startSlot23['is_disabled'])->toBeTrue();

    // In end slots: 23:00 > 09:00 AND <= 23:00 → enabled by business hours
    // disableNonSequentialTimeSlots: $start=09:00, slots > 09:00 all within business hours → no gap → 23:00 enabled
    expect($endSlot23)->not->toBeNull()
        ->and($endSlot23['is_disabled'])->toBeFalse();

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// Lines 95/101: isEnd:true TrueToFalse for disableReservedTimeSlots and disableConcurrentUserHappeningTimeSlots
// A happening from 10:00–12:00: slot at 12:00 is reserved for end (timeSlot <= end) but NOT for start
test('end slots disable the happening end time while start slots do not', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    // Create a happening from 10:00–12:00
    $resource->happenings()->create([
        'start' => $today->setTime(10, 0),
        'end' => $today->setTime(12, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resource->refresh();

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addDay());

    // isTimeSlotReserved with isEnd=false: 12:00 >= 10:00 but NOT < 12:00 → NOT reserved → enabled
    $startSlot12 = collect($result['start'])->first(fn (array $s): bool => $s['time']->format('H:i') === '12:00');
    // isTimeSlotReserved with isEnd=true: 12:00 > 10:00 AND <= 12:00 → reserved → disabled
    $endSlot12 = collect($result['end'])->first(fn (array $s): bool => $s['time']->format('H:i') === '12:00');

    expect($startSlot12)->not->toBeNull()
        ->and($startSlot12['is_disabled'])->toBeFalse();

    expect($endSlot12)->not->toBeNull()
        ->and($endSlot12['is_disabled'])->toBeTrue();

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// Lines 289/290: adjustSelectedTimeSlots — when selected slot is disabled, first enabled is auto-selected
test('when initially selected slot is disabled a different slot gets selected', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    // Provide a start time (00:00) that will be disabled because it's outside business hours (09:00–23:00)
    // The selected slot at 00:00 will be disabled → adjustSelectedTimeSlots triggers → first enabled selected
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addHour());

    $selectedSlots = collect($result['start'])->filter(fn (array $s): bool => $s['is_selected'] === true);

    // Must have exactly one selected slot
    expect($selectedSlots->count())->toBe(1);

    // The selected slot must not be disabled
    $selected = $selectedSlots->first();
    expect($selected['is_disabled'])->toBeFalse();

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// RemoveNot — getFirstEnabledTimeSlot must pick an ENABLED (not disabled) slot
test('auto-selected slot is enabled not disabled when adjusting selection', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    // Selected time at 00:00 is outside business hours → disabled → triggers auto-select
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addHour());

    $selectedStartSlot = collect($result['start'])->first(fn (array $s): bool => $s['is_selected'] === true);

    expect($selectedStartSlot)->not->toBeNull();
    // The RemoveNot mutation would cause a DISABLED slot to be selected — verify it's enabled
    expect($selectedStartSlot['is_disabled'])->toBeFalse();

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// IfNegated — containsSelectedTimeSlot: when a valid slot IS selected, no fallback override happens
test('when selected slot remains enabled it stays selected without auto-override', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    // 10:00 is within business hours 09:00–23:00 → stays enabled → stays selected
    $selectedStart = $today->setTime(10, 0);
    $selectedEnd = $today->setTime(11, 0);

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $selectedStart, $selectedEnd);

    // The slot at 10:00 should be selected
    $slot10 = collect($result['start'])->first(fn (array $s): bool => $s['time']->format('H:i') === '10:00');
    expect($slot10)->not->toBeNull()
        ->and($slot10['is_selected'])->toBeTrue();

    // There should be exactly one selected slot in start (no second override)
    $selectedCount = collect($result['start'])->filter(fn (array $s): bool => $s['is_selected'] === true)->count();
    expect($selectedCount)->toBe(1);

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// Verify hour-only slot length uses hour branch (initTimePeriod: minute=0, hour=1 → hourly intervals)
test('hourly slot length creates slots at one-hour intervals', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Set hour=1, minute=0
    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '01:00']);

    $action = app(GenerateResourceTimeSlotsAction::class);
    // Start from 09:00 to avoid past-slot filtering removing early slots
    $start = CarbonImmutable::parse('2026-06-12 09:00:00', 'UTC');
    $result = $action->execute($resource, null, $start, $start->addDay());

    $slots = $result['start'];
    if (count($slots) >= 2) {
        $time0 = $slots[0]['time'];
        $time1 = $slots[1]['time'];
        $diffMinutes = (int) $time0->diffInMinutes($time1);
        expect($diffMinutes)->toBe(60);
    }

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// Verify isDisabled:true is respected: slots outside business hours remain disabled after enableBusinessHours
test('slots outside business hours remain disabled after business hours processing', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // Business hours 09:00–23:00; slot at 00:00 is outside → disabled
    // Slot at 08:00 is outside → disabled (< 09:00)
    // Slot at 09:00 is inside → enabled
    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addDay());

    $slot08 = collect($result['start'])->first(fn (array $s): bool => $s['time']->format('H:i') === '08:00');
    $slot09 = collect($result['start'])->first(fn (array $s): bool => $s['time']->format('H:i') === '09:00');

    expect($slot08)->not->toBeNull()
        ->and($slot08['is_disabled'])->toBeTrue()
        ->and($slot09)->not->toBeNull()
        ->and($slot09['is_disabled'])->toBeFalse();

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// isEnd:true for disableClosedTimeSlots: a closing boundary check
// isTimeSlotInClosing with isEnd=false: timeSlot >= closing.start && timeSlot < closing.end
// isTimeSlotInClosing with isEnd=true: timeSlot > closing.start && timeSlot < closing.end
// → the closing START time is disabled in start slots (>= start) but NOT in end slots (> start)
test('start of closing range is disabled in start slots but enabled in end slots', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    // Create a closing from 14:00–16:00
    $resource->closings()->create([
        'closable_type' => Resource::class,
        'closable_id' => $resource->id,
        'start' => $today->setTime(14, 0),
        'end' => $today->setTime(16, 0),
    ]);

    $resource->refresh();

    // Use $start = 13:30 (within business hours, before closing) to avoid
    // disableNonSequentialTimeSlots disabling 14:00 due to gaps before business hours
    $start = CarbonImmutable::parse('2026-06-12 13:30:00', 'UTC');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $start, $start->addDay());

    // At 14:00:
    // Start: isTimeSlotInClosing(isEnd=false) → 14:00 >= 14:00 → TRUE → disabled
    $startSlot14 = collect($result['start'])->first(fn (array $s): bool => $s['time']->format('H:i') === '14:00');
    // End: isTimeSlotInClosing(isEnd=true) → 14:00 > 14:00 → FALSE → NOT disabled by closing
    // Also: 14:00 <= $start(13:30) is false, and no disabled gap between 13:30 and 14:00
    $endSlot14 = collect($result['end'])->first(fn (array $s): bool => $s['time']->format('H:i') === '14:00');

    expect($startSlot14)->not->toBeNull()
        ->and($startSlot14['is_disabled'])->toBeTrue();

    expect($endSlot14)->not->toBeNull()
        ->and($endSlot14['is_disabled'])->toBeFalse();

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});

// edge: PlusToMinus when only minutes (minute=30, hour=0) → intervalMinutes=30
// Mutation would give 30 - 0 = 30 (same result!), so also test with hour only
test('interval minutes is calculated correctly for hour-only slot length', function (): void {
    $this->seed(WeekDaySeeder::class);

    // getCarbonNow() = UTC_now + Berlin_offset(+2h) = 08:00 + 2 = 10:00
    Carbon::setTestNow(Carbon::parse('2026-06-12 08:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 08:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // hour=1, minute=0 → intervalMinutes = 0 + 60*1 = 60
    // PlusToMinus → 0 - 60*1 = -60 → no past slots kept at all
    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '01:00']);

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addDay());

    // getCarbonNow = 10:00 UTC. Slot at 09:00 UTC is 60 min ago → diffInMinutes=60, NOT < 60 → excluded
    // Slot at 10:00 UTC: isAfter(10:00) = false, diffInMinutes=0 < 60 → KEPT
    $startTimes = collect($result['start'])->map(fn (array $s): string => $s['time']->format('H:i'))->values()->toArray();

    expect(in_array('10:00', $startTimes))->toBeTrue();
    expect(in_array('09:00', $startTimes))->toBeFalse();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('execute returns start slots as zero-indexed array even after collection operations', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $start = CarbonImmutable::parse('2026-06-12 09:00:00', 'UTC');
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $start, $start->addDay());

    $keys = array_keys($result['start']);
    expect($keys[0])->toBe(0)
        ->and($keys)->toBe(range(0, count($keys) - 1));

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('execute returns end slots as zero-indexed array even after collection operations', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $start = CarbonImmutable::parse('2026-06-12 09:00:00', 'UTC');
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $start, $start->addDay());

    $keys = array_keys($result['end']);
    expect($keys[0])->toBe(0)
        ->and($keys)->toBe(range(0, count($keys) - 1));

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('end slot exactly at start time is disabled', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $start = CarbonImmutable::parse('2026-06-12 10:00:00', 'UTC');
    $end = CarbonImmutable::parse('2026-06-12 11:00:00', 'UTC');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $start, $end);

    $endSlotAtStart = collect($result['end'])->first(fn (array $s): bool => $s['time']->format('H:i') === '10:00');

    expect($endSlotAtStart)->not->toBeNull()
        ->and($endSlotAtStart['is_disabled'])->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('end slots reserve the happening end boundary with isEnd true', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    $resource->happenings()->create([
        'start' => $today->setTime(13, 30),
        'end' => $today->setTime(14, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resource->refresh();

    $start = CarbonImmutable::parse('2026-06-12 13:30:00', 'UTC');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $start, $start->addDay());

    $endSlot14 = collect($result['end'])->first(fn (array $s): bool => $s['time']->format('H:i') === '14:00');

    expect($endSlot14)->not->toBeNull()
        ->and($endSlot14['is_disabled'])->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('end slots concurrent user check uses isEnd true flag', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $user = User::factory()->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    $resource->happenings()->create([
        'start' => $today->setTime(15, 30),
        'end' => $today->setTime(16, 0),
        'is_verified' => false,
        'reserved_at' => now(),
        'user_id_01' => $user->id,
    ]);

    $resource->refresh();

    $start = CarbonImmutable::parse('2026-06-12 15:30:00', 'UTC');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, $user, $start, $start->addDay());

    $endSlot16 = collect($result['end'])->first(fn (array $s): bool => $s['time']->format('H:i') === '16:00');

    expect($endSlot16)->not->toBeNull()
        ->and($endSlot16['is_disabled'])->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('minute interval of exactly 1 creates 1-minute steps', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '00:01']);

    $action = app(GenerateResourceTimeSlotsAction::class);
    $start = CarbonImmutable::parse('2026-06-12 09:00:00', 'UTC');
    $result = $action->execute($resource, null, $start, $start->addDay());

    $slots = $result['start'];

    expect(count($slots))->toBeGreaterThan(2);

    if (count($slots) >= 2) {
        $time0 = $slots[0]['time'];
        $time1 = $slots[1]['time'];
        expect((int) $time0->diffInMinutes($time1))->toBe(1);
    }

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('hour interval greater-than-zero condition prevents hour branch for minute-only slots', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '00:30']);

    $action = app(GenerateResourceTimeSlotsAction::class);
    $start = CarbonImmutable::parse('2026-06-12 09:00:00', 'UTC');
    $result = $action->execute($resource, null, $start, $start->addDay());

    $slots = $result['start'];

    expect(count($slots))->toBeGreaterThan(1);

    if (count($slots) >= 2) {
        $time0 = $slots[0]['time'];
        $time1 = $slots[1]['time'];
        expect((int) $time0->diffInMinutes($time1))->toBe(30);
    }

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('interval minutes multiplies hours by exactly 60 not 59', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 08:30:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 08:30:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '01:00']);

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');
    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $today, $today->addDay());

    $startTimes = collect($result['start'])->map(fn (array $s): string => $s['time']->format('H:i'))->values()->toArray();

    expect(in_array('09:00', $startTimes))->toBeFalse();

    expect(in_array('10:00', $startTimes))->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('reserved time slots within a happening range are disabled in end slots', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    $resource->happenings()->create([
        'start' => $today->setTime(11, 0),
        'end' => $today->setTime(12, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $resource->refresh();

    $start = CarbonImmutable::parse('2026-06-12 10:30:00', 'UTC');

    $action = app(GenerateResourceTimeSlotsAction::class);
    $result = $action->execute($resource, null, $start, $start->addDay());

    $endSlot1130 = collect($result['end'])->first(fn (array $s): bool => $s['time']->format('H:i') === '11:30');

    expect($endSlot1130)->not->toBeNull()
        ->and($endSlot1130['is_disabled'])->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('end slot concurrency is evaluated against the users other resource-group happenings', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $otherResource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    $otherResource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => $today->setTime(15, 30),
        'end' => $today->setTime(16, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $result = app(GenerateResourceTimeSlotsAction::class)->execute(
        $resource,
        $user,
        $today->setTime(15, 30),
        $today->setTime(16, 0),
    );

    $endSlot16 = collect($result['end'])->first(fn (array $slot): bool => $slot['time']->format('H:i') === '16:00');

    expect($endSlot16)->not->toBeNull()
        ->and($endSlot16['is_disabled'])->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('zero time-slot length falls back to the default period branch instead of the hour branch', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-11 00:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-11 00:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '00:00']);

    $result = app(GenerateResourceTimeSlotsAction::class)->execute(
        $resource,
        null,
        CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC'),
        CarbonImmutable::parse('2026-06-13 00:00:00', 'UTC'),
    );

    expect($result['start'])->toHaveCount(2)
        ->and($result['end'])->toHaveCount(2);

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('hourly past-slot filtering keeps a slot that is fifty-nine minutes behind now', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 07:59:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 07:59:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $rg->settings()->where('key', 'time_slot_length')->update(['value' => '01:00']);

    $result = app(GenerateResourceTimeSlotsAction::class)->execute(
        $resource,
        null,
        CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC'),
        CarbonImmutable::parse('2026-06-13 00:00:00', 'UTC'),
    );

    $startTimes = collect($result['start'])->map(fn (array $slot): string => $slot['time']->format('H:i'))->all();

    expect($startTimes)->toContain('09:00');

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('quota-exceeding end slots stay disabled', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '1']);

    $result = app(GenerateResourceTimeSlotsAction::class)->execute(
        $resource,
        $user,
        $today->setTime(9, 0),
        $today->setTime(10, 0),
    );

    $endSlot1030 = collect($result['end'])->first(fn (array $slot): bool => $slot['time']->format('H:i') === '10:30');

    expect($endSlot1030)->not->toBeNull()
        ->and($endSlot1030['is_disabled'])->toBeTrue();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('a disabled selected start slot is deselected so the first enabled slot can be auto-selected', function (): void {
    $this->seed(WeekDaySeeder::class);

    Carbon::setTestNow(Carbon::parse('2026-06-12 06:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-12 06:00:00', 'UTC'));

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $today = CarbonImmutable::parse('2026-06-12 00:00:00', 'UTC');

    $result = app(GenerateResourceTimeSlotsAction::class)->execute(
        $resource,
        null,
        $today->setTime(8, 0),
        $today->setTime(9, 0),
    );

    $selectedSlots = collect($result['start'])->filter(fn (array $slot): bool => $slot['is_selected']);
    $slot08 = collect($result['start'])->first(fn (array $slot): bool => $slot['time']->format('H:i') === '08:00');
    $slot09 = collect($result['start'])->first(fn (array $slot): bool => $slot['time']->format('H:i') === '09:00');

    expect($slot08)->not->toBeNull()
        ->and($slot08['is_disabled'])->toBeTrue()
        ->and($slot08['is_selected'])->toBeFalse()
        ->and($slot09)->not->toBeNull()
        ->and($slot09['is_selected'])->toBeTrue()
        ->and($selectedSlots)->toHaveCount(1);

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});
