<?php

declare(strict_types=1);

use App\Services\Admin\StatisticsFormatter;

covers(StatisticsFormatter::class);

test('toInt converts numeric values and defaults non-numeric values to zero', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->toInt('3'))->toBe(3)
        ->and($formatter->toInt(3.9))->toBe(3)
        ->and($formatter->toInt('not-a-number'))->toBe(0)
        ->and($formatter->toInt(null))->toBe(0);
});

test('toStringValue stringifies strings and numbers and defaults everything else to an empty string', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->toStringValue('abc'))->toBe('abc')
        ->and($formatter->toStringValue(42))->toBe('42')
        ->and($formatter->toStringValue(null))->toBe('')
        ->and($formatter->toStringValue(['x']))->toBe('');
});

test('percentage returns zero when the total is zero', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->percentage(5, 0))->toBe(0.0);
});

test('percentage rounds the part-over-total ratio to one decimal', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->percentage(1, 3))->toBe(33.3);
});

test('deltaPercentage returns zero when both current and comparison are zero', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->deltaPercentage(0, 0))->toBe(0.0);
});

test('deltaPercentage returns one hundred when comparison is zero but current is not', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->deltaPercentage(5, 0))->toBe(100.0);
});

test('deltaPercentage rounds the relative change to one decimal', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->deltaPercentage(15, 10))->toBe(50.0);
});

test('stringTranslations returns an empty array for non-array input', function (): void {
    $formatter = app(StatisticsFormatter::class);

    expect($formatter->stringTranslations('not-an-array'))->toBe([]);
});

test('stringTranslations keeps only string-keyed values, dropping non-string keys', function (): void {
    $formatter = app(StatisticsFormatter::class);

    $result = $formatter->stringTranslations(['en' => 'Hello', 'de' => 'Hallo', 0 => 'skip-me']);

    expect($result)->toBe(['en' => 'Hello', 'de' => 'Hallo']);
});

test('stringTranslations stringifies numeric translation values instead of dropping them', function (): void {
    $formatter = app(StatisticsFormatter::class);

    $result = $formatter->stringTranslations(['en' => 481, 'de' => '481', 'fr' => null]);

    expect($result)->toBe(['en' => '481', 'de' => '481']);
});
