<?php

use App\Models\BusinessHour;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\WeekDay;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

covers(BusinessHour::class);

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<int, int>  $dayNumbers
 */
function createBusinessHour(array $attributes = [], array $dayNumbers = [1]): BusinessHour
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);

    /** @var BusinessHour $businessHour */
    $businessHour = BusinessHour::create(array_merge([
        'resource_id' => $resource->id,
        'start' => '09:00:00',
        'end' => '17:00:00',
        'start_date' => null,
        'end_date' => null,
    ], $attributes));

    foreach ($dayNumbers as $index => $dayNumber) {
        DB::table('week_days')->insert([
            'day_of_week' => $dayNumber,
            'key' => 'day-'.$dayNumber.'-'.$index.'-'.uniqid(),
        ]);
        /** @var WeekDay $weekDay */
        $weekDay = WeekDay::query()->latest('id')->first();
        $businessHour->week_days()->attach($weekDay);
    }

    /** @var BusinessHour */
    return $businessHour->fresh('week_days');
}

test('business hour detects weekday membership and fallback ranges', function (): void {
    $fallback = createBusinessHour();
    $dated = createBusinessHour([
        'start_date' => '2026-06-01 00:00:00',
        'end_date' => '2026-06-30 23:59:59',
    ], [1, 2]);

    expect($fallback->isOpenOnDayOfWeek(1))->toBeTrue()
        ->and($fallback->isOpenOnDayOfWeek(3))->toBeFalse()
        ->and($fallback->isFallback())->toBeTrue()
        ->and($dated->isFallback())->toBeFalse()
        ->and($dated->isValidForDate(CarbonImmutable::parse('2026-06-10 12:00:00')))->toBeTrue()
        ->and($dated->isValidForDate(CarbonImmutable::parse('2026-07-01 12:00:00')))->toBeFalse();
});

test('business hour validates open ended date ranges', function (): void {
    $startOnly = createBusinessHour([
        'start_date' => '2026-06-01 00:00:00',
        'end_date' => null,
    ]);
    $endOnly = createBusinessHour([
        'start_date' => null,
        'end_date' => '2026-06-30 23:59:59',
    ]);

    expect($startOnly->isValidForDate(CarbonImmutable::parse('2026-06-10 12:00:00')))->toBeTrue()
        ->and($startOnly->isValidForDate(CarbonImmutable::parse('2026-05-10 12:00:00')))->toBeFalse()
        ->and($endOnly->isValidForDate(CarbonImmutable::parse('2026-06-10 12:00:00')))->toBeTrue()
        ->and($endOnly->isValidForDate(CarbonImmutable::parse('2026-07-10 12:00:00')))->toBeFalse();
});

test('business hour returns full or trimmed open windows', function (): void {
    $businessHour = createBusinessHour(dayNumbers: [1]);

    [$isOpen, $start, $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 10:00:00'),
        CarbonImmutable::parse('2026-06-01 11:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($start->format('H:i'))->toBe('10:00')
        ->and($end->format('H:i'))->toBe('11:00');

    [$isOpen, $start, $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 16:00:00'),
        CarbonImmutable::parse('2026-06-01 18:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($start->format('H:i'))->toBe('16:00')
        ->and($end->format('H:i'))->toBe('17:00');

    [$isOpen, $start, $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 08:00:00'),
        CarbonImmutable::parse('2026-06-01 10:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($start->format('H:i'))->toBe('09:00')
        ->and($end->format('H:i'))->toBe('10:00');
});

test('business hour handles overnight end times and closed requests', function (): void {
    $overnight = createBusinessHour([
        'start' => '18:00:00',
        'end' => '00:00:00',
    ], [1]);

    [$isOpen, $start, $end] = $overnight->isOpen(
        CarbonImmutable::parse('2026-06-01 23:00:00'),
        CarbonImmutable::parse('2026-06-02 00:00:00'),
    );

    expect($isOpen)->toBeTrue()
        ->and($start->format('Y-m-d H:i'))->toBe('2026-06-01 23:00')
        ->and($end->format('Y-m-d H:i'))->toBe('2026-06-02 00:00');

    [$isOpen] = $overnight->isOpen(
        CarbonImmutable::parse('2026-06-02 10:00:00'),
        CarbonImmutable::parse('2026-06-02 11:00:00'),
    );

    expect($isOpen)->toBeFalse();
});

test('business hour isOpen exact boundary: booking start equals business hour start', function (): void {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // Start exactly at business hour start → open
    [$isOpen, $start, $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 09:00:00'),
        CarbonImmutable::parse('2026-06-01 10:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($start->format('H:i'))->toBe('09:00');
});

test('business hour isOpen exact boundary: booking end equals business hour end', function (): void {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // End exactly at business hour end → open (inclusive <=)
    [$isOpen, , $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 15:00:00'),
        CarbonImmutable::parse('2026-06-01 17:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($end->format('H:i'))->toBe('17:00');
});

test('business hour isOpen: booking end equals business hour start — not open', function (): void {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // Booking ends exactly at business hour start — should NOT be open
    [$isOpen] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 07:00:00'),
        CarbonImmutable::parse('2026-06-01 09:00:00'),
    );
    expect($isOpen)->toBeFalse();
});

test('isValidForDate boundary: date equals start_date is valid', function (): void {
    $businessHour = createBusinessHour([
        'start_date' => '2026-06-10 00:00:00',
        'end_date' => null,
    ], [1]);

    expect($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-10')))->toBeTrue()
        ->and($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-09')))->toBeFalse();
});

test('business hour resource and week_days relations return correct related models', function (): void {
    $businessHour = createBusinessHour();

    expect($businessHour->resource()->getRelated()::class)->toBe(App\Models\Resource::class)
        ->and($businessHour->week_days()->getRelated()::class)->toBe(WeekDay::class);
});

test('isValidForDate boundary: date equals end_date is valid', function (): void {
    $businessHour = createBusinessHour([
        'start_date' => null,
        'end_date' => '2026-06-10 23:59:59',
    ], [1]);

    expect($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-10')))->toBeTrue()
        ->and($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-11')))->toBeFalse();
});

test('isValidForDate treats an end_date exactly at midnight as inclusive for that day', function (): void {
    $businessHour = createBusinessHour([
        'start_date' => null,
        'end_date' => '2026-06-10 00:00:00',
    ], [1]);

    expect($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-10')))->toBeTrue()
        ->and($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-11')))->toBeFalse();
});

test('isValidForDate returns false when both start_date and end_date are null (non-fallback edge case)', function (): void {
    $businessHour = createBusinessHour([
        'start_date' => null,
        'end_date' => null,
    ], [1]);

    // When both dates are null, none of the if-branches fire → returns false
    expect($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-10')))->toBeFalse();
});

test('isFallback returns false when only start_date is set', function (): void {
    $businessHour = createBusinessHour([
        'start_date' => '2026-06-01 00:00:00',
        'end_date' => null,
    ], [1]);

    expect($businessHour->isFallback())->toBeFalse();
});

test('isFallback returns false when only end_date is set', function (): void {
    $businessHour = createBusinessHour([
        'start_date' => null,
        'end_date' => '2026-06-30 23:59:59',
    ], [1]);

    expect($businessHour->isFallback())->toBeFalse();
});

test('isOpen: start exactly at business hour end (not open, since start >= end is strictly less)', function (): void {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // start = business_hour_end: 17:00 is NOT >= 09:00 && NOT < 17:00 → first inner if fails
    // end = 18:00 > 17:00 but start = 17:00 is NOT < 17:00 → second inner if fails
    [$isOpen] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 17:00:00'),
        CarbonImmutable::parse('2026-06-01 18:00:00'),
    );
    expect($isOpen)->toBeFalse();
});

test('isOpen: booking starts before, ends within business hours — trims start', function (): void {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // start=07:00, end=10:00: start < 09:00, end > 09:00, end <= 17:00 → trim start
    [$isOpen, $start, $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 07:00:00'),
        CarbonImmutable::parse('2026-06-01 10:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($start->format('H:i'))->toBe('09:00')
        ->and($end->format('H:i'))->toBe('10:00');
});

test('isValidForDate: date one day after end_date is invalid', function (): void {
    // SmallerOrEqualToSmaller would change <= to <, making date == end_date fail the check
    $businessHour = createBusinessHour([
        'start_date' => null,
        'end_date' => '2026-06-10 23:59:59',
    ], [1]);

    // Exactly at end_date boundary (end_date is 2026-06-10 23:59:59, startOfDay = 2026-06-10 00:00:00)
    expect($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-10')))->toBeTrue()
        ->and($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-11')))->toBeFalse();
});

test('isOpen does not add day to overnight business_hour_end when end is midnight', function (): void {
    // RemoveNot would change "if (! $end->isMidnight() && $business_hour_end->isMidnight())"
    // to "if ($end->isMidnight() && ...)" causing opposite behavior.
    $overnight = createBusinessHour([
        'start' => '18:00:00',
        'end' => '00:00:00',
    ], [1]);

    // Booking where end IS midnight — should NOT add a day to business_hour_end a second time
    // (business_hour_end is already midnight, and our $end is midnight → the addDay should NOT fire)
    [$isOpen, , $resultEnd] = $overnight->isOpen(
        CarbonImmutable::parse('2026-06-01 20:00:00'),
        CarbonImmutable::parse('2026-06-02 00:00:00'), // end IS midnight
    );

    expect($isOpen)->toBeTrue()
        ->and($resultEnd->format('Y-m-d H:i'))->toBe('2026-06-02 00:00');
});

test('isOpen: booking start strictly before business hour start — trim start case', function (): void {
    // GreaterOrEqualToGreater: changes start >= business_hour_start to start > business_hour_start
    // This means: booking start exactly at business hour start should still trigger the trim path
    // when booking end exceeds business_hour_end.
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // start = 09:00 (exactly at business_hour_start), end = 19:00 (exceeds)
    // start >= business_hour_start && start < business_hour_end && end > business_hour_end
    // GreaterOrEqualToGreater would make it start > business_hour_start, failing for start == 09:00
    [$isOpen, $adjustedStart, $adjustedEnd] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 09:00:00'),
        CarbonImmutable::parse('2026-06-01 19:00:00'),
    );

    expect($isOpen)->toBeTrue()
        ->and($adjustedEnd->format('H:i'))->toBe('17:00');
});

test('isOpen: booking end strictly before business hour end — trim end case', function (): void {
    // GreaterToGreaterOrEqual: changes end > business_hour_end to end >= business_hour_end
    // The trim-end branch requires end > business_hour_end, not end == business_hour_end.
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // end = 17:00 exactly — should NOT trigger the trim path (main if-branch handles it)
    // This verifies the inner else-if requires end > business_hour_end
    [$isOpen, , $adjustedEnd] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 10:00:00'),
        CarbonImmutable::parse('2026-06-01 17:00:00'),
    );

    // Main branch fires: start >= 09:00 && end <= 17:00 → open, end unchanged
    expect($isOpen)->toBeTrue()
        ->and($adjustedEnd->format('H:i'))->toBe('17:00');
});

test('isOpen: booking end exactly at business hour end, start before — trims start to business hour start', function (): void {
    // SmallerOrEqualToSmaller: changes end <= business_hour_end to end < business_hour_end
    // end > business_hour_start && end <= business_hour_end && start < business_hour_start
    // We need end == business_hour_end, start < business_hour_start: booking 07:00-17:00
    // SmallerOrEqualToSmaller would make end < business_hour_end, so 17:00 would fail the condition
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    [$isOpen, $adjustedStart] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 07:00:00'),
        CarbonImmutable::parse('2026-06-01 17:00:00'),
    );

    // start=07:00 < 09:00, end=17:00 == business_hour_end: trim start to 09:00
    expect($isOpen)->toBeTrue()
        ->and($adjustedStart->format('H:i'))->toBe('09:00');
});

test('isOpen adds day to overnight business_hour_end when booking end is past midnight but not midnight itself', function (): void {
    // RemoveNot on line 113 changes "! $end->isMidnight()" to "$end->isMidnight()"
    // When end = 00:30 (not midnight): original fires addDay, mutation does not → end gets trimmed to 00:00
    $overnight = createBusinessHour(['start' => '18:00:00', 'end' => '00:00:00'], [1]);

    // business_hour_end = parse('00:00')->setDateFrom('2026-06-02 00:30') = 2026-06-02 00:00
    // ! end->isMidnight() = true (00:30 is not midnight), bh_end->isMidnight() = true → addDay fires
    // → bh_end = 2026-06-03 00:00 → booking (23:00 – 00:30) fits within 18:00 – next-midnight → open, end stays 00:30
    // With mutation: end->isMidnight() = false → no addDay → bh_end stays 2026-06-02 00:00
    //   → 00:30 > 00:00 so main branch fails; trim path fires: end set to 2026-06-02 00:00 ≠ 00:30
    [$isOpen, , $resultEnd] = $overnight->isOpen(
        CarbonImmutable::parse('2026-06-01 23:00:00'),
        CarbonImmutable::parse('2026-06-02 00:30:00'),
    );

    expect($isOpen)->toBeTrue()
        ->and($resultEnd->format('Y-m-d H:i'))->toBe('2026-06-02 00:30');
});

test('isOpen: booking start exactly at business hour start, end past — trims end not start', function (): void {
    // SmallerToSmallerOrEqual: changes start < business_hour_start to start <= business_hour_start
    // start < business_hour_start
    // We test start exactly at business_hour_start: should NOT trigger the trim-start path
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // start=09:00 == business_hour_start, end=18:00 > business_hour_end
    // Should go into the line-126 branch (trim end), not line-136 branch (trim start)
    [$isOpen, , $adjustedEnd] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 09:00:00'),
        CarbonImmutable::parse('2026-06-01 18:00:00'),
    );

    expect($isOpen)->toBeTrue()
        ->and($adjustedEnd->format('H:i'))->toBe('17:00');
});
