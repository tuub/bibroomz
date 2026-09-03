<?php

declare(strict_types=1);

use App\Services\Admin\StatisticsDateRangeResolver;
use Carbon\CarbonInterface;

covers(StatisticsDateRangeResolver::class);

test('resolve returns null bounds for an unrecognized range', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    expect($resolver->resolve('all', null, null))->toBe([null, null]);
});

test('resolve returns start-of-week through now for this_week', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    [$from, $to] = $resolver->resolve('this_week', null, null);
    assert($from instanceof CarbonInterface && $to instanceof CarbonInterface);

    expect($from->toDateString())->toBe(now()->startOfWeek()->toDateString())
        ->and($to->toDateString())->toBe(now()->toDateString());
});

test('resolve returns start-of-month through now for this_month', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    [$from] = $resolver->resolve('this_month', null, null);
    assert($from instanceof CarbonInterface);

    expect($from->toDateString())->toBe(now()->startOfMonth()->toDateString());
});

test('resolve returns start-of-year through now for this_year', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    [$from] = $resolver->resolve('this_year', null, null);
    assert($from instanceof CarbonInterface);

    expect($from->toDateString())->toBe(now()->startOfYear()->toDateString());
});

test('resolve returns a window of days back from now for last_7_days, last_30_days, last_3_months and last_12_months', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    [$sevenDaysFrom] = $resolver->resolve('last_7_days', null, null);
    [$thirtyDaysFrom] = $resolver->resolve('last_30_days', null, null);
    [$threeMonthsFrom] = $resolver->resolve('last_3_months', null, null);
    [$twelveMonthsFrom] = $resolver->resolve('last_12_months', null, null);
    assert($sevenDaysFrom instanceof CarbonInterface && $thirtyDaysFrom instanceof CarbonInterface && $threeMonthsFrom instanceof CarbonInterface && $twelveMonthsFrom instanceof CarbonInterface);

    expect($sevenDaysFrom->toDateString())->toBe(now()->subDays(7)->toDateString())
        ->and($thirtyDaysFrom->toDateString())->toBe(now()->subDays(30)->toDateString())
        ->and($threeMonthsFrom->toDateString())->toBe(now()->subMonths(3)->toDateString())
        ->and($twelveMonthsFrom->toDateString())->toBe(now()->subMonths(12)->toDateString());
});

test('resolve parses custom range bounds to start and end of day', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    [$from, $to] = $resolver->resolve('custom', '2026-01-01', '2026-01-31');
    assert($from instanceof CarbonInterface && $to instanceof CarbonInterface);

    expect($from->toDateTimeString())->toBe('2026-01-01 00:00:00')
        ->and($to->toDateTimeString())->toBe('2026-01-31 23:59:59');
});

test('resolve treats missing custom bounds as null', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    expect($resolver->resolve('custom', null, null))->toBe([null, null]);
});

test('resolveComparison returns null bounds when either date is missing', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    expect($resolver->resolveComparison(null, '2026-01-31'))->toBe([null, null])
        ->and($resolver->resolveComparison('2026-01-01', null))->toBe([null, null]);
});

test('resolveComparison parses both bounds to start and end of day', function (): void {
    $resolver = app(StatisticsDateRangeResolver::class);

    [$from, $to] = $resolver->resolveComparison('2026-01-01', '2026-01-31');
    assert($from instanceof CarbonInterface && $to instanceof CarbonInterface);

    expect($from->toDateTimeString())->toBe('2026-01-01 00:00:00')
        ->and($to->toDateTimeString())->toBe('2026-01-31 23:59:59');
});
