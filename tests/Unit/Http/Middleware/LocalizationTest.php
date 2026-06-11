<?php

declare(strict_types=1);

use App\Http\Middleware\Localization;
use Illuminate\Http\Request;

covers(Localization::class);

test('Localization is a middleware', function (): void {
    $middleware = app(Localization::class);

    expect($middleware)->toBeInstanceOf(Localization::class);
});

test('Localization handle processes request without error', function (): void {
    $middleware = app(Localization::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
