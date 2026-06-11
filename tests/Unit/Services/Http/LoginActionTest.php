<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Http\LoginAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(LoginAction::class);

uses(RefreshDatabase::class);

test('execute returns null when credentials are invalid', function (): void {
    $request = Request::create('/login', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LoginAction::class);
    $result = $action->execute($request, ['username' => 'nobody', 'password' => 'wrong']);

    expect($result)->toBeNull();
});

test('execute uses auth attempt with provided credentials', function (): void {
    Auth::shouldReceive('attempt')->once()->with(['username' => 'testuser', 'password' => 'secret123'])->andReturn(false);

    $request = Request::create('/login', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LoginAction::class);
    $result = $action->execute($request, ['username' => 'testuser', 'password' => 'secret123']);

    expect($result)->toBeNull();
});

test('execute returns a User model when credentials are valid', function (): void {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('correct-password'),
        'is_logged_in' => false,
    ]);

    Auth::shouldReceive('attempt')
        ->once()
        ->with(['email' => 'login@example.com', 'password' => 'correct-password'])
        ->andReturn(true);
    Auth::shouldReceive('user')->once()->andReturn($user);

    $request = Request::create('/login', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LoginAction::class);
    $result = $action->execute($request, ['email' => 'login@example.com', 'password' => 'correct-password']);

    // InstanceOfToTrue would skip the abort_unless and still return the user
    // but DecrementInteger/IncrementInteger/RemoveFunctionCall would change abort_unless(…, 500)
    // which would cause an abort when user IS a User.
    // This test verifies the happy path returns a User instance.
    expect($result)->toBeInstanceOf(User::class);
    /** @var User $result */
    expect($result->id)->toBe($user->id);
});

test('execute sets is_logged_in to true after successful login', function (): void {
    $user = User::factory()->create([
        'email' => 'login2@example.com',
        'password' => bcrypt('secret'),
        'is_logged_in' => false,
    ]);

    Auth::shouldReceive('attempt')
        ->once()
        ->with(['email' => 'login2@example.com', 'password' => 'secret'])
        ->andReturn(true);
    Auth::shouldReceive('user')->once()->andReturn($user);

    $request = Request::create('/login', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LoginAction::class);
    $action->execute($request, ['email' => 'login2@example.com', 'password' => 'secret']);

    // The user should be marked as logged in
    expect(User::findOrFail($user->id)->is_logged_in)->toBeTrue();
});

test('execute regenerates session on successful login', function (): void {
    $user = User::factory()->create([
        'email' => 'session@example.com',
        'password' => bcrypt('sessionpass'),
        'is_logged_in' => false,
    ]);

    Auth::shouldReceive('attempt')
        ->once()
        ->andReturn(true);
    Auth::shouldReceive('user')->once()->andReturn($user);

    $session = app('session.store');
    $tokenBefore = $session->token();

    $request = Request::create('/login', 'POST');
    $request->setLaravelSession($session);

    $action = app(LoginAction::class);
    $action->execute($request, ['email' => 'session@example.com', 'password' => 'sessionpass']);

    expect($session->token())->not->toBe($tokenBefore);
});

test('execute aborts with 500 when Auth::user() is not a User instance', function (): void {
    Auth::shouldReceive('attempt')->once()->andReturn(true);
    Auth::shouldReceive('user')->once()->andReturn(null);

    $request = Request::create('/login', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LoginAction::class);

    expect(fn () => $action->execute($request, ['email' => 'x@x.com', 'password' => 'pass']))
        ->toThrow(HttpException::class);
});

test('abort_unless uses status code 500 not 499 or 501', function (): void {
    Auth::shouldReceive('attempt')->once()->andReturn(true);
    Auth::shouldReceive('user')->once()->andReturn(null);

    $request = Request::create('/login', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LoginAction::class);

    try {
        $action->execute($request, ['email' => 'x@x.com', 'password' => 'pass']);
        expect(false)->toBeTrue();
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(500);
    }
});
