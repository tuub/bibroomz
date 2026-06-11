<?php

declare(strict_types=1);

use App\Http\Requests\ResourceTimeSlotsRequest;

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
