<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use App\Http\Requests\UserHappeningsRequest;
use App\Services\Http\ListUserHappeningsAction;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(UserController::class);

uses(MockeryPHPUnitIntegration::class);

test('UserController can be resolved from container', function (): void {
    $controller = app(UserController::class);

    expect($controller)->toBeInstanceOf(UserController::class);
});

test('getUserHappenings aborts with 401 when request user is null', function (): void {
    $request = Mockery::mock(UserHappeningsRequest::class);
    $request->shouldReceive('user')->andReturn(null);

    $controller = new UserController(Mockery::mock(ListUserHappeningsAction::class));

    try {
        $controller->getUserHappenings($request);
        test()->fail('Expected HttpException');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(401);
    }
});
