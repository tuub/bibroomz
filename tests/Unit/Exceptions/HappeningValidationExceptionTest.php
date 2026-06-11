<?php

use App\Exceptions\HappeningValidationException;

covers(HappeningValidationException::class);

test('happening validation exception can be created', function (): void {
    $exception = new HappeningValidationException('Invalid happening data');

    expect($exception)->toBeInstanceOf(HappeningValidationException::class)
        ->and($exception->getMessage())->toBe('Invalid happening data');
});

test('happening validation exception has correct status code', function (): void {
    $exception = new HappeningValidationException('Test error');

    expect($exception->getCode())->toBeInt();
});

test('happening validation exception can be thrown and caught', function (): void {
    expect(function (): void {
        throw new HappeningValidationException('Test');
    })->toThrow(HappeningValidationException::class);
});

test('happening validation exception extends exception', function (): void {
    $exception = new HappeningValidationException('Test');

    expect($exception)->toBeInstanceOf(Exception::class);
});
