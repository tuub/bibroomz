<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

covers(RouteServiceProvider::class);

test('login rate limiter uses normalized username and ip', function (): void {
    /** @var Closure $limiter */
    $limiter = app(RateLimiter::class)->limiter('login');

    $request = Request::create('/login', 'POST', ['username' => 'MiXeD.User']);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $limit = $limiter($request);

    expect($limit->maxAttempts)->toBe(5)
        ->and($limit->decaySeconds)->toBe(60)
        ->and($limit->key)->toBe('mixed.user|127.0.0.1');
});
