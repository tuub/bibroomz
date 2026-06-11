<?php

declare(strict_types=1);

use App\Services\Resources\ResourceTimeSlot;
use Carbon\CarbonImmutable;

covers(ResourceTimeSlot::class);

test('constructor stores all properties', function (): void {
    $time = CarbonImmutable::parse('2024-01-15 09:00:00');
    $slot = new ResourceTimeSlot($time, '09:00', false, true);

    expect($slot->time)->toBe($time)
        ->and($slot->label)->toBe('09:00')
        ->and($slot->isDisabled)->toBeFalse()
        ->and($slot->isSelected)->toBeTrue();
});

test('defaults to disabled and not selected', function (): void {
    $time = CarbonImmutable::parse('2024-01-15 10:00:00');
    $slot = new ResourceTimeSlot($time, '10:00');

    expect($slot->isDisabled)->toBeTrue()
        ->and($slot->isSelected)->toBeFalse();
});

test('withDisabled returns new instance with updated disabled flag', function (): void {
    $time = CarbonImmutable::parse('2024-01-15 09:00:00');
    $original = new ResourceTimeSlot($time, '09:00', true, false);
    $updated = $original->withDisabled(false);

    expect($updated)->not->toBe($original)
        ->and($updated->isDisabled)->toBeFalse()
        ->and($updated->time)->toBe($time)
        ->and($updated->label)->toBe('09:00')
        ->and($updated->isSelected)->toBeFalse();
});

test('withSelected returns new instance with updated selected flag', function (): void {
    $time = CarbonImmutable::parse('2024-01-15 09:00:00');
    $original = new ResourceTimeSlot($time, '09:00', false, false);
    $updated = $original->withSelected(true);

    expect($updated)->not->toBe($original)
        ->and($updated->isSelected)->toBeTrue()
        ->and($updated->isDisabled)->toBeFalse()
        ->and($updated->time)->toBe($time);
});

test('toArray returns correct structure', function (): void {
    $time = CarbonImmutable::parse('2024-01-15 09:00:00');
    $slot = new ResourceTimeSlot($time, '09:00', false, true);

    $array = $slot->toArray();

    expect($array)->toBeArray()
        ->and($array['time'])->toBe($time)
        ->and($array['label'])->toBe('09:00')
        ->and($array['is_disabled'])->toBeFalse()
        ->and($array['is_selected'])->toBeTrue();
});

test('original slot is immutable when creating copy with withDisabled', function (): void {
    $time = CarbonImmutable::parse('2024-01-15 09:00:00');
    $original = new ResourceTimeSlot($time, '09:00', true, false);
    $original->withDisabled(false);

    expect($original->isDisabled)->toBeTrue();
});
