<?php

declare(strict_types=1);

use App\Http\Middleware\Localization;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(Localization::class);

test('Localization is a middleware', function (): void {
    $middleware = app(Localization::class);

    expect($middleware)->toBeInstanceOf(Localization::class);
});

test('Localization handle processes request without error', function (): void {
    $middleware = app(Localization::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
