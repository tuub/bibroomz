<?php

declare(strict_types=1);

use App\Http\Middleware\ValidateSignature;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

covers(ValidateSignature::class);

test('ValidateSignature is a middleware', function (): void {
    $middleware = app(ValidateSignature::class);

    expect($middleware)->toBeInstanceOf(ValidateSignature::class);
});

test('ValidateSignature rejects unsigned requests with InvalidSignatureException', function (): void {
    $middleware = app(ValidateSignature::class);
    $request = Request::create('/');

    expect(fn () => $middleware->handle($request, fn () => response('ok')))
        ->toThrow(InvalidSignatureException::class);
});
