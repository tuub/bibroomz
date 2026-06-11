<?php

declare(strict_types=1);

use App\Http\Controllers\ResourceController;

covers(ResourceController::class);

test('ResourceController can be resolved from container', function (): void {
    $controller = app(ResourceController::class);

    expect($controller)->toBeInstanceOf(ResourceController::class);
});
