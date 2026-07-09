<?php

declare(strict_types=1);

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

covers(AppSetting::class);

uses(RefreshDatabase::class);

test('getDefinitionKeys returns all known global setting keys', function (): void {
    expect(AppSetting::getDefinitionKeys())->toContain('system_notification');
});

test('hasDefinition returns true for known keys and false otherwise', function (): void {
    expect(AppSetting::hasDefinition('system_notification'))->toBeTrue()
        ->and(AppSetting::hasDefinition('missing_key'))->toBeFalse()
        ->and(AppSetting::hasDefinition(null))->toBeFalse()
        ->and(AppSetting::hasDefinition(''))->toBeFalse();
});

test('getDefaultValue and getValidationRules and getInputType return the configured definition', function (): void {
    expect(AppSetting::getDefaultValue('system_notification'))->toBe('')
        ->and(AppSetting::getValidationRules('system_notification'))->toBe(['nullable', 'string'])
        ->and(AppSetting::getInputType('system_notification'))->toBe('textarea');
});

test('get returns the default value when no row exists', function (): void {
    expect(AppSetting::get('system_notification'))->toBe('');
});

test('get returns the stored value once set', function (): void {
    AppSetting::set('system_notification', 'Hello');

    expect(AppSetting::get('system_notification'))->toBe('Hello');
});

test('get falls back to the default when the stored value is null', function (): void {
    AppSetting::set('system_notification', null);

    expect(AppSetting::get('system_notification'))->toBe('');
});

test('get aborts with 404 for an undefined key', function (): void {
    expect(fn (): mixed => AppSetting::get('missing_key'))->toThrow(NotFoundHttpException::class);
});

test('set aborts with 404 for an undefined key', function (): void {
    expect(fn (): AppSetting => AppSetting::set('missing_key', 'value'))->toThrow(NotFoundHttpException::class);
});

test('set creates a row when none exists and updates it on subsequent calls', function (): void {
    AppSetting::set('system_notification', 'First');
    expect(AppSetting::query()->count())->toBe(1)
        ->and(AppSetting::get('system_notification'))->toBe('First');

    AppSetting::set('system_notification', 'Second');
    expect(AppSetting::query()->count())->toBe(1)
        ->and(AppSetting::get('system_notification'))->toBe('Second');
});

test('getCurrentValues returns every definition key with its current or default value', function (): void {
    expect(AppSetting::getCurrentValues())->toBe(['system_notification' => '']);

    AppSetting::set('system_notification', 'Notice');

    expect(AppSetting::getCurrentValues())->toBe(['system_notification' => 'Notice']);
});

test('app setting does not auto-increment and uses key as its primary key', function (): void {
    $appSetting = new AppSetting;

    expect($appSetting->incrementing)->toBeFalse()
        ->and($appSetting->getKeyName())->toBe('key');
});
