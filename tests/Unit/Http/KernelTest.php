<?php

declare(strict_types=1);

use App\Http\Kernel;

covers(Kernel::class);

test('http kernel defines web middleware group', function (): void {
    $kernel = app(Kernel::class);

    expect($kernel->getMiddlewareGroups())->toHaveKey('web');
});

test('http kernel defines api middleware group', function (): void {
    $kernel = app(Kernel::class);

    expect($kernel->getMiddlewareGroups())->toHaveKey('api');
});
