<?php

declare(strict_types=1);

use App\Http\Controllers\LoginController;
use App\Services\Http\CurrentUserStatusBuilder;
use App\Services\Http\LoginAction;
use App\Services\Http\LogoutAction;
use Illuminate\Support\Facades\Auth;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(LoginController::class);

uses(MockeryPHPUnitIntegration::class);

test('LoginController can be resolved from container', function (): void {
    $controller = app(LoginController::class);

    expect($controller)->toBeInstanceOf(LoginController::class);
});

test('check aborts with 401 when auth passes but user is not App User', function (): void {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('user')->andReturn(new stdClass);

    $controller = new LoginController(
        Mockery::mock(CurrentUserStatusBuilder::class),
        Mockery::mock(LoginAction::class),
        Mockery::mock(LogoutAction::class),
    );

    try {
        $controller->check();
        test()->fail('Expected HttpException');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(401);
    }
});
