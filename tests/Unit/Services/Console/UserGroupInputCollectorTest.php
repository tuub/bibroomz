<?php

declare(strict_types=1);

use App\Services\Console\UserGroupInputCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(UserGroupInputCollector::class);

uses(RefreshDatabase::class);

test('user group input collector normalizes string selections', function (): void {
    $collector = new UserGroupInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('normalizeSelection');

    expect($method->invoke($collector, 'my-uuid'))->toBe('my-uuid')
        ->and($method->invoke($collector, 42))->toBe('42')
        ->and($method->invoke($collector, null))->toBe('')
        ->and($method->invoke($collector, []))->toBe('');
});

test('user group input collector string options returns only string values', function (): void {
    $collector = new UserGroupInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('stringOptions');

    $result = $method->invoke($collector, ['key1' => 'Value 1', 'key2' => 42, 'key3' => 'Value 3']);

    expect($result)->toHaveKey('key1')
        ->and($result)->not->toHaveKey('key2')
        ->and($result)->toHaveKey('key3');
});

test('resolveSelectedKey returns empty string when selection is empty string', function (): void {
    $collector = new UserGroupInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKey');

    $options = ['uuid-1' => 'Institution A', 'uuid-2' => 'Institution B'];

    $result = $method->invoke($collector, '', $options);

    expect($result)->toBe('');
});

test('resolveSelectedKey result is a string not integer when key is found by value', function (): void {
    $collector = new UserGroupInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKey');

    $options = ['uuid-abc' => 'My Institution'];

    $result = $method->invoke($collector, 'My Institution', $options);

    expect($result)->toBeString()
        ->and($result)->toBe('uuid-abc');
});

test('resolveSelectedKey result is a string when key is numeric', function (): void {
    $collector = new UserGroupInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKey');

    $options = [42 => 'Some Institution'];

    $result = $method->invoke($collector, 'Some Institution', $options);

    expect($result)->toBeString()
        ->and($result)->toBe('42');
});

test('normalizeSelection returns a string when input is integer', function (): void {
    $collector = new UserGroupInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('normalizeSelection');

    $result = $method->invoke($collector, 123);

    expect($result)->toBeString()
        ->and($result)->toBe('123');
});

test('normalizeSelection returns empty string not null for non-scalar non-string', function (): void {
    $collector = new UserGroupInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('normalizeSelection');

    $result = $method->invoke($collector, new stdClass);

    expect($result)->toBeString()
        ->and($result)->toBe('');
});
