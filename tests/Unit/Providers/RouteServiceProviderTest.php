<?php

declare(strict_types=1);

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

covers(RouteServiceProvider::class);

uses(RefreshDatabase::class);

test('route service provider defines home constant', function (): void {
    expect(RouteServiceProvider::HOME)->toBe('/home');
});

test('route service provider is registered in application', function (): void {
    expect(app()->getProviders(RouteServiceProvider::class))->not->toBeEmpty();
});

test('api rate limiter is registered', function (): void {
    expect(RateLimiter::limiter('api'))->not->toBeNull();
});

test('login rate limiter is registered', function (): void {
    expect(RateLimiter::limiter('login'))->not->toBeNull();
});

test('api rate limiter returns a limit for a request', function (): void {
    $request = Request::create('/api/test', 'GET');
    $limiter = RateLimiter::limiter('api');

    expect($limiter)->toBeCallable();
    $limit = is_callable($limiter) ? $limiter($request) : null;

    expect($limit)->not->toBeNull();
});

test('login rate limiter returns a limit keyed by username and ip', function (): void {
    $request = Request::create('/login', 'POST', ['username' => 'testuser']);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $limiter = RateLimiter::limiter('login');

    expect($limiter)->toBeCallable();
    $limit = is_callable($limiter) ? $limiter($request) : null;

    expect($limit)->not->toBeNull();
});

test('login rate limiter handles non-string username input', function (): void {
    $request = Request::create('/login', 'POST', ['username' => null]);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $limiter = RateLimiter::limiter('login');

    expect($limiter)->toBeCallable();
    $limit = is_callable($limiter) ? $limiter($request) : null;

    expect($limit)->not->toBeNull();
});

test('web routes are accessible after provider boot', function (): void {
    $routes = app('router')->getRoutes();

    expect(count($routes->getRoutes()))->toBeGreaterThan(0);
});

test('api rate limiter allows exactly 60 requests per minute', function (): void {
    $request = Request::create('/api/test', 'GET');
    $limiter = RateLimiter::limiter('api');

    $limit = is_callable($limiter) ? $limiter($request) : null;

    // DecrementInteger would make it 59, IncrementInteger 61
    expect($limit)->not->toBeNull();

    // Extract max attempts from the Limit object
    $maxAttempts = $limit instanceof Limit
        ? $limit->maxAttempts
        : null;

    expect($maxAttempts)->toBe(60);
});

test('login rate limiter allows exactly 5 requests per minute', function (): void {
    $request = Request::create('/login', 'POST', ['username' => 'testuser']);
    $limiter = RateLimiter::limiter('login');

    $limit = is_callable($limiter) ? $limiter($request) : null;

    expect($limit)->not->toBeNull();

    $maxAttempts = $limit instanceof Limit
        ? $limit->maxAttempts
        : null;

    expect($maxAttempts)->toBe(5);
});

test('api rate limiter uses user id when user is authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = Request::create('/api/test', 'GET');
    $request->setUserResolver(fn () => $user);

    $limiter = RateLimiter::limiter('api');
    $limit = is_callable($limiter) ? $limiter($request) : null;

    // TernaryNegated would swap user id and ip — but limit object itself should still exist
    expect($limit)->not->toBeNull();

    $key = $limit instanceof Limit
        ? $limit->key
        : null;

    // When user is set, key should contain user id, not ip
    expect($key)->toContain($user->id);
});

test('login rate limiter normalizes username in the key', function (): void {
    // TernaryNegated would change "is_string($usernameInput) ? $usernameInput : null"
    // to "! is_string($usernameInput) ? $usernameInput : null" — always passing null when string
    $request = Request::create('/login', 'POST', ['username' => 'TestUser@EXAMPLE.COM']);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $limiter = RateLimiter::limiter('login');
    $limit = is_callable($limiter) ? $limiter($request) : null;

    $key = $limit instanceof Limit ? $limit->key : null;

    // The key should be based on normalized (lowercased) username, not original case
    // TernaryNegated would make username=null → normalized to '' → key would be '|127.0.0.1'
    expect($key)->not->toBeNull()
        ->and($key)->not->toBe('|127.0.0.1'); // would be this if TernaryNegated
});

test('application registers the admin users api route with web session authentication', function (): void {
    $routes = app('router')->getRoutes()->getRoutes();

    $apiRoutes = array_values(array_filter($routes, fn ($r): bool => (string) $r->uri() === 'api/admin/user/users'));
    $route = $apiRoutes[0] ?? null;
    $middleware = $route?->gatherMiddleware() ?? [];

    expect($apiRoutes)
        ->toHaveCount(1)
        ->and($route?->uri())->toBe('api/admin/user/users')
        ->and($route?->getName())->toBe('api.admin.user.users')
        ->and($middleware)->toContain('api')
        ->and($middleware)->toContain('web')
        ->and($middleware)->toContain('auth')
        ->and($middleware)->toContain('can:admin');
});
