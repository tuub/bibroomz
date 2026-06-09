<?php

use App\Models\Institution;
use App\Services\Console\CleanupIntervalResolver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(CleanupIntervalResolver::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 12:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('fromInstitution parses D:H:M format from institution setting', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->firstWhere('key', 'cleanup_interval')
        ?->update(['value' => '1:2:30']); // 1 day, 2 hours, 30 minutes

    $resolver = app(CleanupIntervalResolver::class);
    $result = $resolver->fromInstitution($institution);

    // 12:00 - 1d 2h 30m = 2026-06-09 09:30:00
    expect($result->format('Y-m-d H:i:s'))->toBe('2026-06-09 09:30:00');
});

test('fromInstitution falls back to config when no cleanup_interval setting exists', function (): void {
    $institution = Institution::factory()->create();
    // Delete the setting entirely so $settingModel is null → config fallback
    $institution->settings()->where('key', 'cleanup_interval')->delete();
    $institution->load('settings');

    config()->set('roomz.default.cleanup_interval', '0:1:0'); // 1 hour via config

    $resolver = app(CleanupIntervalResolver::class);
    $result = $resolver->fromInstitution($institution);

    expect($result->format('Y-m-d H:i:s'))->toBe('2026-06-10 11:00:00');
});

test('fromInstitution handles days-only interval', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->firstWhere('key', 'cleanup_interval')
        ?->update(['value' => '3:0:0']); // 3 days only

    $resolver = app(CleanupIntervalResolver::class);
    $result = $resolver->fromInstitution($institution);

    expect($result->format('Y-m-d H:i:s'))->toBe('2026-06-07 12:00:00');
});

test('fromValues subtracts each provided component', function (): void {
    $resolver = app(CleanupIntervalResolver::class);

    $result = $resolver->fromValues('45', '3', '1'); // 45 min, 3 hours, 1 day
    expect($result->format('Y-m-d H:i:s'))->toBe('2026-06-09 08:15:00');
});

test('fromValues handles null arguments by ignoring them', function (): void {
    $resolver = app(CleanupIntervalResolver::class);

    $result = $resolver->fromValues(null, '2', null); // only 2 hours
    expect($result->format('Y-m-d H:i:s'))->toBe('2026-06-10 10:00:00');
});

test('fromValues handles empty string arguments as null', function (): void {
    $resolver = app(CleanupIntervalResolver::class);

    $result = $resolver->fromValues('', null, '1'); // only 1 day
    expect($result->format('Y-m-d H:i:s'))->toBe('2026-06-09 12:00:00');
});

test('fromValues handles integer arguments', function (): void {
    $resolver = app(CleanupIntervalResolver::class);

    $result = $resolver->fromValues(30, null, null); // 30 minutes as int
    expect($result->format('Y-m-d H:i:s'))->toBe('2026-06-10 11:30:00');
});
