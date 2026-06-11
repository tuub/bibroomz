<?php

declare(strict_types=1);

use App\Services\Console\CleanupIntervalResolver;
use Carbon\Carbon;

covers(CleanupIntervalResolver::class);

function invokeCleanupIntervalResolveInt(CleanupIntervalResolver $resolver, int|string|null $value): ?int
{
    $method = new ReflectionMethod($resolver, 'resolveInt');

    /** @var ?int $resolved */
    $resolved = $method->invoke($resolver, $value);

    return $resolved;
}

test('fromValues subtracts minutes from now', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues(30, null, null);

    expect($result)->toBeInstanceOf(Carbon::class)
        ->and($result->lt($before))->toBeTrue();
});

test('fromValues subtracts hours from now', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues(null, 2, null);

    expect($result->lt($before))->toBeTrue();
});

test('fromValues subtracts days from now', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues(null, null, 7);

    expect($result->lt($before))->toBeTrue();
});

test('fromValues with null inputs returns approximately now', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues(null, null, null);

    expect($result->diffInSeconds($before))->toBeLessThan(2);
});

test('resolveInt returns null for null value', function (): void {
    $result = (new CleanupIntervalResolver)->fromValues(null, null, null);

    expect($result)->toBeInstanceOf(Carbon::class);
    expect($result->diffInSeconds(now()))->toBeLessThan(2);
});

test('resolveInt returns null for empty string value', function (): void {
    $result = (new CleanupIntervalResolver)->fromValues('', null, null);

    expect($result)->toBeInstanceOf(Carbon::class);
    expect($result->diffInSeconds(now()))->toBeLessThan(2);
});

test('resolveInt returns null for empty string hours (EmptyStringToNotEmpty triggers no subtraction)', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues(null, '', null);

    expect($result->diffInSeconds($before))->toBeLessThan(2);
});

test('resolveInt returns null for empty string days (EmptyStringToNotEmpty triggers no subtraction)', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues(null, null, '');

    expect($result->diffInSeconds($before))->toBeLessThan(2);
});

test('resolveInt returns the int value unchanged', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

    $result = (new CleanupIntervalResolver)->fromValues(30, null, null);
    $expected = Carbon::parse('2026-06-01 12:00:00')->subMinutes(30);

    expect($result->format('Y-m-d H:i:s'))->toBe($expected->format('Y-m-d H:i:s'));

    Carbon::setTestNow();
});

test('resolveInt casts string to int for string value', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

    $result = (new CleanupIntervalResolver)->fromValues('30', null, null);
    $expected = Carbon::parse('2026-06-01 12:00:00')->subMinutes(30);

    expect($result->format('Y-m-d H:i:s'))->toBe($expected->format('Y-m-d H:i:s'));

    Carbon::setTestNow();
});

test('resolveInt result is an integer type', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

    $result = (new CleanupIntervalResolver)->fromValues('45', null, null);
    $expected = Carbon::parse('2026-06-01 12:00:00')->subMinutes(45);

    expect($result->format('Y-m-d H:i:s'))->toBe($expected->format('Y-m-d H:i:s'));

    Carbon::setTestNow();
});

test('fromValues early return fires when only null is passed', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues(null, null, null);

    expect($result->diffInSeconds($before))->toBeLessThan(2);
});

test('fromValues with string zero does not subtract (boundary: empty vs zero)', function (): void {
    $before = now();
    $result = (new CleanupIntervalResolver)->fromValues('0', null, null);

    expect($result->diffInSeconds($before))->toBeLessThan(2);
});

test('resolveInt returns null exactly for null input', function (): void {
    $resolver = new CleanupIntervalResolver;

    expect(invokeCleanupIntervalResolveInt($resolver, null))->toBeNull();
});

test('resolveInt returns null exactly for empty string input', function (): void {
    $resolver = new CleanupIntervalResolver;

    expect(invokeCleanupIntervalResolveInt($resolver, ''))->toBeNull();
});

test('resolveInt preserves integer inputs', function (): void {
    $resolver = new CleanupIntervalResolver;

    expect(invokeCleanupIntervalResolveInt($resolver, 12))->toBe(12);
});

test('resolveInt casts numeric string inputs to integers', function (): void {
    $resolver = new CleanupIntervalResolver;

    expect(invokeCleanupIntervalResolveInt($resolver, '15'))->toBe(15);
});

test('resolveInt applies an explicit integer cast to loosely numeric strings', function (): void {
    $resolver = new CleanupIntervalResolver;

    expect(invokeCleanupIntervalResolveInt($resolver, '15minutes'))->toBe(15);
});

test('fromValues subtracts minutes from loosely numeric string inputs', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

    $result = (new CleanupIntervalResolver)->fromValues('15minutes', null, null);
    $expected = Carbon::parse('2026-06-01 12:00:00')->subMinutes(15);

    expect($result->format('Y-m-d H:i:s'))->toBe($expected->format('Y-m-d H:i:s'));

    Carbon::setTestNow();
});
