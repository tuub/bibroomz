<?php

declare(strict_types=1);

use App\Services\Http\LogoutAction;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(LogoutAction::class);

uses(MockeryPHPUnitIntegration::class);

test('execute does not throw when no user is authenticated', function (): void {
    Auth::logout();

    $session = Mockery::mock(Session::class);
    $session->shouldReceive('invalidate');
    $session->shouldReceive('regenerateToken');

    $request = Mockery::mock(Request::class);
    $request->shouldReceive('session')->andReturn($session);

    $action = new LogoutAction;

    expect(fn () => $action->execute($request))->not->toThrow(Throwable::class);
});

test('execute calls session invalidate and regenerateToken (RemoveMethodCall lines 25-26)', function (): void {
    Auth::logout();

    $session = Mockery::mock(Session::class);
    $session->shouldReceive('invalidate')->once();
    $session->shouldReceive('regenerateToken')->once();

    $request = Mockery::mock(Request::class);
    $request->shouldReceive('session')->andReturn($session);

    $action = new LogoutAction;
    $action->execute($request);
});
