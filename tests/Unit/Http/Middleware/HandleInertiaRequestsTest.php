<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(HandleInertiaRequests::class);

uses(RefreshDatabase::class);

test('HandleInertiaRequests is a middleware', function (): void {
    $middleware = app(HandleInertiaRequests::class);

    expect($middleware)->toBeInstanceOf(HandleInertiaRequests::class);
});

test('HandleInertiaRequests handle processes request without error', function (): void {
    $middleware = app(HandleInertiaRequests::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
