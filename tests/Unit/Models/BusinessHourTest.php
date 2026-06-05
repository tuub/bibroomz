<?php

covers(App\Models\BusinessHour::class);

use App\Models\BusinessHour;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\WeekDay;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createBusinessHour(array $attributes = [], array $dayNumbers = [1]): BusinessHour
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);

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
            'key' => 'day-' . $dayNumber . '-' . $index . '-' . uniqid(),
        ]);
        $weekDay = WeekDay::query()->latest('id')->first();
        $businessHour->week_days()->attach($weekDay);
    }

    return $businessHour->fresh('week_days');
}

test('business hour detects weekday membership and fallback ranges', function () {
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

test('business hour validates open ended date ranges', function () {
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

test('business hour returns full or trimmed open windows', function () {
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

test('business hour handles overnight end times and closed requests', function () {
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

test('business hour isOpen exact boundary: booking start equals business hour start', function () {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // Start exactly at business hour start → open
    [$isOpen, $start, $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 09:00:00'),
        CarbonImmutable::parse('2026-06-01 10:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($start->format('H:i'))->toBe('09:00');
});

test('business hour isOpen exact boundary: booking end equals business hour end', function () {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // End exactly at business hour end → open (inclusive <=)
    [$isOpen, , $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 15:00:00'),
        CarbonImmutable::parse('2026-06-01 17:00:00'),
    );
    expect($isOpen)->toBeTrue()
        ->and($end->format('H:i'))->toBe('17:00');
});

test('business hour isOpen does not trim end when booking end equals business hour end', function () {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // End exactly at business hour end — must not trigger the "trim" path
    [$isOpen, , $end] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 15:00:00'),
        CarbonImmutable::parse('2026-06-01 17:00:00'),
    );
    // The main if-branch fires (start >= 09:00 && end <= 17:00), so end stays 17:00
    expect($isOpen)->toBeTrue()
        ->and($end->format('H:i'))->toBe('17:00');
});

test('business hour isOpen: booking end equals business hour start — not open', function () {
    $businessHour = createBusinessHour(dayNumbers: [1]); // 09:00-17:00

    // Booking ends exactly at business hour start — should NOT be open
    [$isOpen] = $businessHour->isOpen(
        CarbonImmutable::parse('2026-06-01 07:00:00'),
        CarbonImmutable::parse('2026-06-01 09:00:00'),
    );
    expect($isOpen)->toBeFalse();
});

test('isValidForDate boundary: date equals start_date is valid', function () {
    $businessHour = createBusinessHour([
        'start_date' => '2026-06-10 00:00:00',
        'end_date' => null,
    ], [1]);

    expect($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-10')))->toBeTrue()
        ->and($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-09')))->toBeFalse();
});

test('isValidForDate boundary: date equals end_date is valid', function () {
    $businessHour = createBusinessHour([
        'start_date' => null,
        'end_date' => '2026-06-10 23:59:59',
    ], [1]);

    expect($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-10')))->toBeTrue()
        ->and($businessHour->isValidForDate(CarbonImmutable::parse('2026-06-11')))->toBeFalse();
});
