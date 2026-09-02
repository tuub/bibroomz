<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CacheUserActivity;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\Localization;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use App\Services\Http\InertiaSharedDataBuilder;
use App\Services\Http\LocalePreferenceManager;
use App\Services\Http\UserActivityRecorder;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(
    Localization::class,
    CacheUserActivity::class,
    HandleInertiaRequests::class,
    InertiaSharedDataBuilder::class,
    LocalePreferenceManager::class,
    UserActivityRecorder::class
);

uses(RefreshDatabase::class);

test('localization middleware applies the locale from the cookie and queues the default locale otherwise', function (): void {
    app('cookie')->unqueue('locale');
    app()->setLocale('en');

    $request = Request::create('/', 'GET', [], ['locale' => 'de']);
    $response = (new Localization)->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and(app()->getLocale())->toBe('de');

    app('cookie')->unqueue('locale');
    app()->setLocale('en');

    $request = Request::create('/', 'GET');
    (new Localization)->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect(app('cookie')->queued('locale')?->getValue())->toBe('en');
});

test('cache user activity middleware stores the current user activity timestamp', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);
    cache()->forget('user_activity_'.$user->id);

    $response = (new CacheUserActivity)->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getStatusCode())->toBe(200)
        ->and(cache()->has('user_activity_'.$user->id))->toBeTrue();
});

test('handle inertia requests shares the current route and authenticated user name', function (): void {
    $user = User::factory()->create(['name' => 'Shared User']);
    $this->actingAs($user);

    $request = Request::create(route('start'), 'GET');
    $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

    $shared = (new HandleInertiaRequests)->share($request);

    expect($shared['route'])->toBe('start')
        ->and($shared['auth']['user']['name'])->toBe('Shared User');
});

test('redirect if authenticated sends signed in users to the home path', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = Request::create('/', 'GET');
    $response = (new RedirectIfAuthenticated)->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getTargetUrl())->toContain('/home');
});

test('redirect if authenticated passes guests through unchanged', function (): void {
    $request = Request::create('/', 'GET');
    $response = (new RedirectIfAuthenticated)->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('cache user activity middleware passes guest requests unchanged', function (): void {
    $request = Request::create('/', 'GET');

    $response = (new CacheUserActivity)->handle($request, fn (): ResponseFactory|Response => response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('handle inertia requests version is callable and returns a value', function (): void {
    $request = Request::create('/', 'GET');

    $version = (new HandleInertiaRequests)->version($request);

    expect(gettype($version))->toBeIn(['NULL', 'string']);
});

test('authenticate middleware redirects html guests to the start page', function (): void {
    $middleware = new class(app('auth')) extends Authenticate
    {
        public function publicRedirectTo(Request $request): ?string
        {
            return $this->redirectTo($request);
        }
    };

    $request = Request::create('/', 'GET');

    expect($middleware->publicRedirectTo($request))->toBe(route('start'));
});
