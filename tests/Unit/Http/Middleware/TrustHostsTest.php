<?php

declare(strict_types=1);

use App\Http\Middleware\TrustHosts;

covers(TrustHosts::class);

test('hosts returns a non-empty array', function (): void {
    $middleware = app(TrustHosts::class);

    expect($middleware->hosts())->toBeArray()->not->toBeEmpty();
});
