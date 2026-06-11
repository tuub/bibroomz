<?php

declare(strict_types=1);

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

covers(RedirectIfAuthenticated::class);

uses(RefreshDatabase::class);

test('handle redirects to HOME when user is authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $middleware = new RedirectIfAuthenticated;
    $request = Request::create('/login', 'GET');

    $response = $middleware->handle($request, fn () => response('next'));

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toEndWith('/home');
});

test('handle calls next when user is not authenticated', function (): void {
    Auth::logout();

    $middleware = new RedirectIfAuthenticated;
    $request = Request::create('/login', 'GET');

    $nextCalled = false;
    $middleware->handle($request, function () use (&$nextCalled) {
        $nextCalled = true;

        return response('next');
    });

    expect($nextCalled)->toBeTrue();
});
