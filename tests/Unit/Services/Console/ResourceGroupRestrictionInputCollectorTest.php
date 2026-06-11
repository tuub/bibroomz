<?php

declare(strict_types=1);

use App\Services\Console\ResourceGroupRestrictionInputCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceGroupRestrictionInputCollector::class);

uses(RefreshDatabase::class);

test('resource group restriction input collector resolves selected key by value', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKey');

    $options = ['uuid-1' => 'Group A', 'uuid-2' => 'Group B'];

    // Direct key match
    expect($method->invoke($collector, 'uuid-1', $options))->toBe('uuid-1');
});

test('resource group restriction input collector resolves selected key by display name', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKey');

    $options = ['uuid-1' => 'Group A', 'uuid-2' => 'Group B'];

    // Display name match
    expect($method->invoke($collector, 'Group A', $options))->toBe('uuid-1');
});

test('resolveSelectedKey returns empty string when selection not found', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKey');

    $options = ['uuid-1' => 'Group A'];

    expect($method->invoke($collector, 'Not Found', $options))->toBe('');
});

test('resolveSelectedKeys maps multiple selections', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKeys');

    $options = ['uuid-1' => 'Group A', 'uuid-2' => 'Group B', 'uuid-3' => 'Group C'];

    $result = $method->invoke($collector, ['uuid-1', 'Group C'], $options);

    expect($result)->toBe(['uuid-1', 'uuid-3']);
});

test('resolveSelectedKeys returns empty array for no selections', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKeys');

    $result = $method->invoke($collector, [], ['uuid-1' => 'Group A']);

    expect($result)->toBe([]);
});

test('stringOptions filters non-string values', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('stringOptions');

    $options = ['key-1' => 'Valid', 'key-2' => 42, 'key-3' => null, 'key-4' => 'Also Valid'];

    $result = $method->invoke($collector, $options);

    expect($result)->toHaveKey('key-1')
        ->and($result)->toHaveKey('key-4')
        ->and($result)->not->toHaveKey('key-2')
        ->and($result)->not->toHaveKey('key-3');
    expect(count((array) $result))->toBe(2);
});

test('normalizeSelection returns string as-is', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('normalizeSelection');

    expect($method->invoke($collector, 'hello'))->toBe('hello');
});

test('normalizeSelection converts scalar to string', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('normalizeSelection');

    expect($method->invoke($collector, 42))->toBe('42')
        ->and($method->invoke($collector, 3.14))->toBe('3.14');
});

test('normalizeSelection returns empty string for non-scalar', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('normalizeSelection');

    expect($method->invoke($collector, []))->toBe('')
        ->and($method->invoke($collector, null))->toBe('');
});

test('normalizeSelections filters non-string items', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('normalizeSelections');

    $result = $method->invoke($collector, ['uuid-1', 42, null, 'uuid-2', []]);

    expect($result)->toBe(['uuid-1', 'uuid-2']);
});

test('resolveSelectedKey resolves by integer key in options', function (): void {
    $collector = new ResourceGroupRestrictionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('resolveSelectedKey');

    $options = [1 => 'Monday', 2 => 'Tuesday'];

    // When the key is numeric, the selection is an integer — normalizeSelection converts it to string
    expect($method->invoke($collector, 1, $options))->toBe('1');
});
