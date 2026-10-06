<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;

/**
 * Guards the environment that every wall-clock assertion in this suite relies on.
 *
 * Every datetime this app handles is a zone-less wall clock in
 * `config('app.timezone')`. A test can only notice a stray conversion to UTC
 * while the app timezone actually differs from UTC: in UTC, a converted value
 * and the wall clock it was entered as carry the same digits, and a bug like
 * the one that stored admin happenings two hours early has nothing to show.
 *
 * `phpunit.xml` sets APP_TIMEZONE=Europe/Berlin for that reason, and it stayed
 * UTC long enough for that bug to sit in `HappeningRequest` unnoticed. These
 * tests make a change back to UTC - the kind reached for to make a suite
 * "deterministic" - fail here, loudly and in one place, instead of silently
 * disarming every datetime assertion in the suite at once.
 */
test('the suite runs in an app timezone that is offset from UTC', function (): void {
    $timezone = config('app.timezone');

    expect($timezone)->toBeString()
        ->and($timezone)->not->toBe('UTC')
        // PHP's own default has to agree with it, or a datetime parsed without
        // an explicit zone lands somewhere other than the configured clock.
        ->and(date_default_timezone_get())->toBe($timezone);
});

test('a zone conversion is observable in both halves of the year', function (): void {
    // Asserted on a summer and a winter date so that neither DST half can
    // quietly become the one where a conversion is a no-op.
    foreach (['2026-06-10 09:00:00', '2026-01-10 09:00:00'] as $wallClock) {
        $datetime = CarbonImmutable::parse($wallClock);

        expect($datetime->getOffset())->not->toBe(0)
            ->and($datetime->toDateTimeString())->toBe($wallClock);
    }
});
