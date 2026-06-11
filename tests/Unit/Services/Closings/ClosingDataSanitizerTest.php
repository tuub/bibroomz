<?php

declare(strict_types=1);

use App\Services\Closings\ClosingDataSanitizer;

covers(ClosingDataSanitizer::class);

test('sanitize converts date and time fields to start and end ISO strings', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '15.01.2026',
        'start_time' => '09:00',
        'end_date' => '15.01.2026',
        'end_time' => '17:00',
        'description' => ['en' => 'Closed'],
    ]);

    expect($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end')
        ->and($result)->not->toHaveKey('start_date')
        ->and($result)->not->toHaveKey('start_time')
        ->and($result)->not->toHaveKey('end_date')
        ->and($result)->not->toHaveKey('end_time');
});

test('sanitize removes date and time fields from output', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '01.06.2026',
        'start_time' => '08:00',
        'end_date' => '01.06.2026',
        'end_time' => '18:00',
    ]);

    expect(array_keys($result))->not->toContain('start_date')
        ->and(array_keys($result))->not->toContain('end_date');
});

test('sanitize returns start and end as ISO strings', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '01.01.2026',
        'start_time' => '09:00',
        'end_date' => '02.01.2026',
        'end_time' => '17:00',
    ]);

    expect($result['start'])->toBeString()
        ->and($result['end'])->toBeString();
});

test('sanitize uses the actual start_date string value', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    // Both BooleanAndToBooleanOr (converts && to ||) and EmptyStringToNotEmpty tests:
    // When start_date IS a string, it should be used verbatim in createCarbonDateTime.
    // We verify by checking the resulting ISO string contains the correct parsed date.
    $result = $sanitizer->sanitize([
        'start_date' => '05.03.2026',
        'start_time' => '14:00',
        'end_date' => '05.03.2026',
        'end_time' => '16:00',
    ]);

    expect($result['start'])->toContain('2026-03-05');
});

test('sanitize uses the actual start_time string value', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '10.06.2026',
        'start_time' => '11:30',
        'end_date' => '10.06.2026',
        'end_time' => '15:00',
    ]);

    // start ISO string should contain the actual hour 11
    expect($result['start'])->toContain('11:30');
});

test('sanitize uses the actual end_date string value', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '20.07.2026',
        'start_time' => '09:00',
        'end_date' => '22.07.2026',
        'end_time' => '17:00',
    ]);

    expect($result['end'])->toContain('2026-07-22');
});

test('sanitize uses the actual end_time string value', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '10.08.2026',
        'start_time' => '09:00',
        'end_date' => '10.08.2026',
        'end_time' => '20:45',
    ]);

    expect($result['end'])->toContain('20:45');
});

test('sanitize uses actual start_date value when it is a string', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '15.03.2026',
        'start_time' => '10:00',
        'end_date' => '15.03.2026',
        'end_time' => '18:00',
    ]);

    // The ISO string should contain the parsed date from '15.03.2026' not today
    expect($result['start'])->toContain('2026-03-15');
});

test('sanitize uses actual end_date value when it is a string', function (): void {
    $sanitizer = new ClosingDataSanitizer;
    $result = $sanitizer->sanitize([
        'start_date' => '20.04.2026',
        'start_time' => '08:00',
        'end_date' => '20.04.2026',
        'end_time' => '20:00',
    ]);

    expect($result['end'])->toContain('2026-04-20');
});

test('when start_date is a string it is used not empty string', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    $resultWithDate = $sanitizer->sanitize([
        'start_date' => '15.09.2026',
        'start_time' => '09:00',
        'end_date' => '15.09.2026',
        'end_time' => '10:00',
    ]);

    $resultWithDifferentDate = $sanitizer->sanitize([
        'start_date' => '20.09.2026',
        'start_time' => '09:00',
        'end_date' => '20.09.2026',
        'end_time' => '10:00',
    ]);

    expect($resultWithDate['start'])->toContain('2026-09-15')
        ->and($resultWithDifferentDate['start'])->toContain('2026-09-20')
        ->and($resultWithDate['start'])->not->toBe($resultWithDifferentDate['start']);
});

test('when start_time is a string it is used not empty string', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    $resultMorning = $sanitizer->sanitize([
        'start_date' => '15.09.2026',
        'start_time' => '08:00',
        'end_date' => '15.09.2026',
        'end_time' => '10:00',
    ]);

    $resultAfternoon = $sanitizer->sanitize([
        'start_date' => '15.09.2026',
        'start_time' => '14:00',
        'end_date' => '15.09.2026',
        'end_time' => '16:00',
    ]);

    expect($resultMorning['start'])->toContain('08:00')
        ->and($resultAfternoon['start'])->toContain('14:00');
});

test('when end_date is a string it is used not empty string', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    $resultA = $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => '05.09.2026',
        'end_time' => '10:00',
    ]);

    $resultB = $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => '10.09.2026',
        'end_time' => '10:00',
    ]);

    expect($resultA['end'])->toContain('2026-09-05')
        ->and($resultB['end'])->toContain('2026-09-10');
});

test('when end_time is a string it is used not empty string', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    $resultA = $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => '01.09.2026',
        'end_time' => '12:30',
    ]);

    $resultB = $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => '01.09.2026',
        'end_time' => '18:45',
    ]);

    expect($resultA['end'])->toContain('12:30')
        ->and($resultB['end'])->toContain('18:45');
});

test('non-string start_date falls back to empty string not the value', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => 42,
        'start_time' => '09:00',
        'end_date' => '01.09.2026',
        'end_time' => '10:00',
    ]))->toThrow(InvalidArgumentException::class);
});

test('non-string start_time falls back to empty string not the value', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => 42,
        'end_date' => '01.09.2026',
        'end_time' => '10:00',
    ]))->toThrow(InvalidArgumentException::class);
});

test('non-string end_date falls back to empty string not the value', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => 42,
        'end_time' => '10:00',
    ]))->toThrow(InvalidArgumentException::class);
});

test('non-string end_time falls back to empty string not the value', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => '01.09.2026',
        'end_time' => 42,
    ]))->toThrow(InvalidArgumentException::class);
});

test('object start_date with mutation causes TypeError not InvalidArgumentException', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => new stdClass,
        'start_time' => '09:00',
        'end_date' => '01.09.2026',
        'end_time' => '10:00',
    ]))->toThrow(InvalidArgumentException::class);
});

test('object start_time with mutation causes TypeError not InvalidArgumentException', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => new stdClass,
        'end_date' => '01.09.2026',
        'end_time' => '10:00',
    ]))->toThrow(InvalidArgumentException::class);
});

test('object end_date with mutation causes TypeError not InvalidArgumentException', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => new stdClass,
        'end_time' => '10:00',
    ]))->toThrow(InvalidArgumentException::class);
});

test('object end_time with mutation causes TypeError not InvalidArgumentException', function (): void {
    $sanitizer = new ClosingDataSanitizer;

    expect(fn (): array => $sanitizer->sanitize([
        'start_date' => '01.09.2026',
        'start_time' => '09:00',
        'end_date' => '01.09.2026',
        'end_time' => new stdClass,
    ]))->toThrow(InvalidArgumentException::class);
});
