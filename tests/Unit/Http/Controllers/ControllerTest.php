<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;

covers(Controller::class);

test('Controller can be resolved from container', function (): void {
    $controller = app(Controller::class);

    expect($controller)->toBeInstanceOf(Controller::class);
});
