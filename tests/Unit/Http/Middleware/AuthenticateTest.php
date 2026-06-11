<?php

declare(strict_types=1);

use App\Http\Middleware\Authenticate;
use Illuminate\Http\Request;

covers(Authenticate::class);

test('redirectTo returns the start route for non-JSON requests', function (): void {
    $middleware = app(Authenticate::class);
    $request = Request::create('/some-page', 'GET');

    $method = new ReflectionMethod($middleware, 'redirectTo');

    expect($method->invoke($middleware, $request))->toBe(route('start'));
});

test('redirectTo returns null for JSON requests', function (): void {
    $middleware = app(Authenticate::class);
    $request = Request::create('/api/something', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $method = new ReflectionMethod($middleware, 'redirectTo');

    expect($method->invoke($middleware, $request))->toBeNull();
});
