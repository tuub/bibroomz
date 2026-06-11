<?php

declare(strict_types=1);

use App\Http\Requests\LoginRequest;

covers(LoginRequest::class);

test('LoginRequest defines validation rules', function (): void {
    $request = new LoginRequest;
    $rules = $request->rules();

    expect($rules)->toBeArray();
});

test('LoginRequest authorize returns true', function (): void {
    $request = new LoginRequest;

    expect($request->authorize())->toBeTrue();
});
