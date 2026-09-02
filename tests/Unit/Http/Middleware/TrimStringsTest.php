<?php

declare(strict_types=1);

use App\Http\Middleware\TrimStrings;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(TrimStrings::class);

test('TrimStrings is a middleware', function (): void {
    $middleware = app(TrimStrings::class);

    expect($middleware)->toBeInstanceOf(TrimStrings::class);
});

test('TrimStrings handle processes request without error', function (): void {
    $middleware = app(TrimStrings::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
