<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Http\LogoutAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

covers(LogoutAction::class);

uses(RefreshDatabase::class);

test('execute logs out the authenticated user and clears is_logged_in', function (): void {
    $user = User::factory()->create(['is_logged_in' => true]);
    Auth::login($user);

    $request = Request::create('/logout', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LogoutAction::class);
    $action->execute($request);

    expect(Auth::check())->toBeFalse()
        ->and(User::findOrFail($user->id)->is_logged_in)->toBeFalse();
});

test('execute runs without error when no user is authenticated', function (): void {
    $request = Request::create('/logout', 'POST');
    $request->setLaravelSession(app('session.store'));

    $action = app(LogoutAction::class);

    expect(fn () => $action->execute($request))->not->toThrow(Throwable::class);
    expect(Auth::check())->toBeFalse();
});
