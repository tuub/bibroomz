<?php

declare(strict_types=1);

use App\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Request;

covers(PreventRequestsDuringMaintenance::class);

test('PreventRequestsDuringMaintenance is a middleware', function (): void {
    $middleware = app(PreventRequestsDuringMaintenance::class);

    expect($middleware)->toBeInstanceOf(PreventRequestsDuringMaintenance::class);
});

test('PreventRequestsDuringMaintenance handle processes request without error', function (): void {
    $middleware = app(PreventRequestsDuringMaintenance::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
