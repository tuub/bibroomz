<?php

declare(strict_types=1);

use App\Services\Admin\StatisticsRetentionResolver;
use Carbon\CarbonImmutable;

covers(StatisticsRetentionResolver::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('resolve reports the configured retention window and its cut-off date', function (): void {
    config(['roomz.happenings.cleanup_days' => 1000]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-06 12:00:00'));

    $retention = app(StatisticsRetentionResolver::class)->resolve(null, null);

    expect($retention['days'])->toBe(1000)
        ->and($retention['cutoff'])->toBe('2024-01-10');
});

test('resolve flags a period without a lower bound as exceeded but names no missing span', function (): void {
    config(['roomz.happenings.cleanup_days' => 30]);

    $retention = app(StatisticsRetentionResolver::class)->resolve(null, null);

    expect($retention['exceeded'])->toBeTrue()
        ->and($retention['missingFrom'])->toBeNull()
        ->and($retention['missingTo'])->toBeNull();
});

test('resolve names the span a period is missing when it reaches past the cut-off', function (): void {
    config(['roomz.happenings.cleanup_days' => 30]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-06 12:00:00'));

    $retention = app(StatisticsRetentionResolver::class)->resolve(
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-10-06'),
    );

    expect($retention['exceeded'])->toBeTrue()
        ->and($retention['missingFrom'])->toBe('2026-01-01')
        ->and($retention['missingTo'])->toBe('2026-09-06');
});

test('resolve reports nothing missing for a period inside the retention window', function (): void {
    config(['roomz.happenings.cleanup_days' => 30]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-06 12:00:00'));

    $retention = app(StatisticsRetentionResolver::class)->resolve(
        CarbonImmutable::parse('2026-09-20'),
        CarbonImmutable::parse('2026-10-06'),
    );

    expect($retention['exceeded'])->toBeFalse()
        ->and($retention['missingFrom'])->toBeNull()
        ->and($retention['missingTo'])->toBeNull();
});

/**
 * Everything the period covers ended before the cut-off, so the missing span
 * is the period itself - not the stretch up to the cut-off, which reaches
 * beyond what was asked for.
 */
test('resolve stops the missing span at the end of a period that lies entirely before the cut-off', function (): void {
    config(['roomz.happenings.cleanup_days' => 30]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-06 12:00:00'));

    $retention = app(StatisticsRetentionResolver::class)->resolve(
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-01-31'),
    );

    expect($retention['missingFrom'])->toBe('2026-01-01')
        ->and($retention['missingTo'])->toBe('2026-01-31');
});

/**
 * The period bounds are wall-clock bounds on `happenings.start`, so the
 * retention window they are measured against has to be read on the same clock.
 */
test('resolve measures the retention window from the app timezone', function (): void {
    useAppTimezone('Europe/Berlin');
    config(['roomz.happenings.cleanup_days' => 1]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 22:30:00', 'UTC'));

    // A day back from 11 June 00:30 in Berlin is 10 June 00:30, so a period
    // reaching to 9 June 23:00 asks for data the retention window no longer
    // covers. The UTC clock would put its own cut-off at 9 June 22:30 and call
    // the same period safe.
    $retention = app(StatisticsRetentionResolver::class)->resolve(CarbonImmutable::parse('2026-06-09 23:00:00'), null);

    expect($retention['exceeded'])->toBeTrue()
        ->and($retention['cutoff'])->toBe('2026-06-10');
});
