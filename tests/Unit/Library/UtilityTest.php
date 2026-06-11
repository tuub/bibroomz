<?php

use App\Library\Utility;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(Utility::class);

uses(MockeryPHPUnitIntegration::class);

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('get carbon now applies configured timezone offset', function (): void {
    config()->set('roomz.app.timezone', 'Europe/Berlin');
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-15 10:00:00', 'UTC'));

    expect(Utility::getCarbonNow()->toDateTimeString())->toBe('2026-01-15 11:00:00');
});

test('create carbon date time combines date and time strings', function (): void {
    expect(Utility::createCarbonDateTime('03.06.2026', '14:30')->format('Y-m-d H:i:s'))
        ->toBe('2026-06-03 14:30:00');
});

test('time string helpers parse date and time values', function (): void {
    expect(Utility::getTimeValuesFromEnvTimeString('02:30'))->toBe([
        'hour' => 2,
        'minute' => 30,
    ]);
});

test('camel case conversion and login normalization are configurable', function (): void {
    config()->set('roomz.user.login_name_normalization_method', 1);

    expect(Utility::convertCamelCaseToSnakeCase('UserGroupPolicy'))->toBe('user_group_policy')
        ->and(Utility::normalizeLoginName('MiXeD.User'))->toBe('mixed.user')
        ->and(Utility::normalizeLoginName(null))->toBeNull();

    config()->set('roomz.user.login_name_normalization_method', 0);

    expect(Utility::normalizeLoginName('MiXeD.User'))->toBe('MiXeD.User');
});

test('get translatable returns all locales and restores the original locale', function (): void {
    app()->setLocale('en');
    config()->set('app.supported_locales', ['en', 'de']);

    expect(Utility::getTranslatable('Rooms'))->toBe([
        'en' => 'Rooms',
        'de' => 'Rooms',
    ])->and(app()->getLocale())->toBe('en');
});

test('createCarbonDateTime throws InvalidArgumentException for invalid date time combination', function (): void {
    expect(fn (): Carbon => Utility::createCarbonDateTime('not-a-date', 'not-a-time'))
        ->toThrow(InvalidArgumentException::class, 'Invalid date/time combination.');
});

test('getTranslatable skips non-string locales in the supported_locales array', function (): void {
    app()->setLocale('en');
    // Mix of valid string locales and non-string values
    config()->set('app.supported_locales', ['en', 42, null, 'de']);

    $result = Utility::getTranslatable('Test');

    // Non-string locales should be skipped; only 'en' and 'de' should appear
    expect($result)->toBe(['en' => 'Test', 'de' => 'Test'])
        ->and(app()->getLocale())->toBe('en');
});

test('createCarbonDateTime includes the time portion in the result', function (): void {
    $result = Utility::createCarbonDateTime('01.01.2026', '15:45');

    expect($result->format('H:i'))->toBe('15:45')
        ->and($result->format('d.m.Y'))->toBe('01.01.2026');
});

test('getTimeValuesFromEnvTimeString with only hours returns zero for minute', function (): void {
    $result = Utility::getTimeValuesFromEnvTimeString('5');

    expect($result['hour'])->toBe(5)
        ->and($result['minute'])->toBe(0);
});

test('getTimeValuesFromEnvTimeString minute is distinct from hour', function (): void {
    $result = Utility::getTimeValuesFromEnvTimeString('10:45');

    expect($result['hour'])->toBe(10)
        ->and($result['minute'])->toBe(45)
        ->and($result['minute'])->not->toBe($result['hour']);
});

test('getTranslatable with non-array supported_locales returns empty array', function (): void {
    app()->setLocale('en');
    config()->set('app.supported_locales', null);

    expect(Utility::getTranslatable('Test'))->toBe([]);
});
