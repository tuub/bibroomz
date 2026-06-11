<?php

declare(strict_types=1);

use App\Http\Middleware\CacheUserActivity;
use App\Models\User;
use App\Services\Http\UserActivityRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

covers(CacheUserActivity::class);

uses(RefreshDatabase::class);

test('CacheUserActivity is a middleware', function (): void {
    $middleware = app(CacheUserActivity::class);

    expect($middleware)->toBeInstanceOf(CacheUserActivity::class);
});

test('CacheUserActivity handle processes request without error', function (): void {
    $middleware = app(CacheUserActivity::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(200);
});

test('CacheUserActivity passes request through when not authenticated', function (): void {
    $middleware = app(CacheUserActivity::class);
    $request = Request::create('/');

    $passed = false;
    $middleware->handle($request, function ($req) use (&$passed) {
        $passed = true;

        return response('ok');
    });

    expect($passed)->toBeTrue();
});

test('CacheUserActivity passes request through when authenticated user is null on request', function (): void {
    $middleware = app(CacheUserActivity::class);
    $request = Request::create('/');

    $response = $middleware->handle($request, fn ($req) => response('pass'));

    expect($response->getContent())->toBe('pass');
});

test('CacheUserActivity records user activity when authenticated', function (): void {
    // InstanceOfToTrue: $user instanceof User becomes true.
    // Without this test, mutation may survive if activity recording is never called.
    $user = User::factory()->create();
    $recorded = false;

    $this->mock(UserActivityRecorder::class, function ($mock) use (&$recorded): void {
        $mock->shouldReceive('record')->once()->andReturnUsing(function () use (&$recorded): void {
            $recorded = true;
        });
    });

    $this->actingAs($user);
    $middleware = app(CacheUserActivity::class);
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    $middleware->handle($request, fn () => response('ok'));

    expect($recorded)->toBeTrue();
});

test('CacheUserActivity does not record activity and returns early when not authenticated', function (): void {
    // RemoveEarlyReturn: the return $next($request) after auth check removed.
    // Without it, the code would continue and try to record activity for null user.
    $this->mock(UserActivityRecorder::class, function ($mock): void {
        $mock->shouldNotReceive('record');
    });

    $middleware = app(CacheUserActivity::class);
    $request = Request::create('/');
    // No user authenticated → auth()->check() = false → should return early

    $response = $middleware->handle($request, fn () => response('early_return'));

    expect($response->getContent())->toBe('early_return');
});
