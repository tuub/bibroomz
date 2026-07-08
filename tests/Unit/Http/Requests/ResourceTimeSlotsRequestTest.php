<?php

declare(strict_types=1);

use App\Http\Requests\ResourceTimeSlotsRequest;
use Carbon\CarbonImmutable;

covers(ResourceTimeSlotsRequest::class);

test('resource time slots request defines validation rules', function (): void {
    $request = new ResourceTimeSlotsRequest;
    $rules = $request->rules();

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('id');
});

test('resource time slots request validates id as required uuid', function (): void {
    $request = new ResourceTimeSlotsRequest;
    $rules = $request->rules();

    expect($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:resources,id')
        ->and($rules)->toHaveKey('happening_id')
        ->and($rules['happening_id'])->toContain('nullable')
        ->and($rules['happening_id'])->toContain('uuid')
        ->and($rules['happening_id'])->toContain('exists:happenings,id')
        ->and($rules)->toHaveKey('start')
        ->and($rules['start'])->toContain('required')
        ->and($rules)->toHaveKey('end')
        ->and($rules['end'])->toContain('required');
});

test('start() parses a valid date string', function (): void {
    $request = buildFormRequest(ResourceTimeSlotsRequest::class, ['start' => '2026-01-15 10:00:00']);

    expect($request->start()->toDateTimeString())->toBe('2026-01-15 10:00:00');
});

test('start() does not throw when input is an array', function (): void {
    // Regression for #490: CarbonImmutable::parse() was receiving an array
    $request = buildFormRequest(ResourceTimeSlotsRequest::class, ['start' => ['2026-01-15', '10:00:00']]);

    expect($request->start())->toBeInstanceOf(CarbonImmutable::class);
});

test('end() parses a valid date string', function (): void {
    $request = buildFormRequest(ResourceTimeSlotsRequest::class, ['end' => '2026-01-15 12:00:00']);

    expect($request->end()->toDateTimeString())->toBe('2026-01-15 12:00:00');
});

test('end() does not throw when input is an array', function (): void {
    // Regression for #490: CarbonImmutable::parse() was receiving an array
    $request = buildFormRequest(ResourceTimeSlotsRequest::class, ['end' => ['2026-01-15', '12:00:00']]);

    expect($request->end())->toBeInstanceOf(CarbonImmutable::class);
});
