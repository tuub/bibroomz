<?php

declare(strict_types=1);

use App\Models\WeekDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

covers(WeekDay::class);

uses(RefreshDatabase::class);

test('week days can be created and queried', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    DB::table('week_days')->insert(['day_of_week' => 2, 'key' => 'tuesday']);

    expect(WeekDay::count())->toBe(2);
});

test('week day has day_of_week field', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $day = WeekDay::firstOrFail();

    expect($day->day_of_week)->toBeInt();
});

test('week day has business_hours relationship', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $day = WeekDay::firstOrFail();

    expect($day->business_hours())->not->toBeNull();
});

test('week day has institutions relationship', function (): void {
    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    $day = WeekDay::firstOrFail();

    expect($day->institutions())->not->toBeNull();
});
