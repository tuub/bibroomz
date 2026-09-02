<?php

declare(strict_types=1);

use App\Http\Middleware\TrustProxies;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(TrustProxies::class);

test('TrustProxies is a middleware', function (): void {
    $middleware = app(TrustProxies::class);

    expect($middleware)->toBeInstanceOf(TrustProxies::class);
});

test('TrustProxies handle processes request without error', function (): void {
    $middleware = app(TrustProxies::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
