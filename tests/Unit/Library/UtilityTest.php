<?php

use App\Library\Utility;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(Utility::class);

uses(MockeryPHPUnitIntegration::class);

/**
 * @param  array<string, mixed>  $data
 */
function callUtilitySendToLog(array $data, ?string $level = null): void
{
    Utility::sendToLog('audit', $data, $level);
}

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('carbonize parses strings and preserves carbon instances', function (): void {
    $carbon = Carbon::parse('2026-06-03 11:45:00');

    expect(Utility::carbonize('2026-06-03 11:45:00')->toDateTimeString())->toBe('2026-06-03 11:45:00')
        ->and(Utility::carbonize($carbon))->toBe($carbon);
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

test('send to log uses default log level and adds calling action', function (): void {
    config()->set('roomz.log.level', 'warning');

    $logger = Mockery::mock();
    $logger->shouldReceive('warning')
        ->once()
        ->with(Mockery::on(fn (string $message): bool => str_contains($message, 'ACTION=callUtilitySendToLog')
            && str_contains($message, 'user=alice')
            && str_contains($message, 'state=ready')));

    Log::shouldReceive('channel')->once()->with('audit')->andReturn($logger);

    callUtilitySendToLog([
        'user' => 'alice',
        'state' => 'ready',
    ]);
});

test('time string helpers parse date and time values', function (): void {
    expect(Utility::getTimeValuesFromEnvTimeString('02:30'))->toBe([
        'hour' => 2,
        'minute' => 30,
    ]);

    expect(Utility::getDateTimeFromStrings('2026-06-03', '14:45')->format('Y-m-d H:i:s'))
        ->toBe('2026-06-03 14:45:00');
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
