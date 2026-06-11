<?php

declare(strict_types=1);

use App\Services\Http\LocalePreferenceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

covers(LocalePreferenceManager::class);

test('applyFromRequest sets locale from cookie when present', function (): void {
    $request = Request::create('/');
    $request->cookies->set('locale', 'de');

    $manager = new LocalePreferenceManager;
    $manager->applyFromRequest($request);

    expect(app()->getLocale())->toBe('de');
});

test('applyFromRequest does not change locale when no cookie present', function (): void {
    app()->setLocale('en');
    $request = Request::create('/');

    $manager = new LocalePreferenceManager;
    $manager->applyFromRequest($request);

    expect(app()->getLocale())->toBe('en');
});

test('queue method accepts locale string without modifying app locale', function (): void {
    app()->setLocale('en');
    $manager = new LocalePreferenceManager;
    $manager->queue('fr');

    expect(app()->getLocale())->toBe('en');
});

beforeEach(function (): void {
    Cookie::flushQueuedCookies();
});

afterEach(function (): void {
    Cookie::flushQueuedCookies();
});

test('applyFromRequest queues cookie with minutes 600 not 599 or 601', function (): void {
    $request = Request::create('/');

    $manager = new LocalePreferenceManager;
    $manager->applyFromRequest($request);

    $queued = Cookie::queued('locale');
    expect($queued)->not->toBeNull();
    /** @var Symfony\Component\HttpFoundation\Cookie $queued */
    expect($queued->getMaxAge())->toBe(600 * 60);
});

test('queue method queues locale cookie with minutes 600', function (): void {
    $manager = new LocalePreferenceManager;
    $manager->queue('de');

    $queued = Cookie::queued('locale');
    expect($queued)->not->toBeNull();
    /** @var Symfony\Component\HttpFoundation\Cookie $queued */
    expect($queued->getValue())->toBe('de');
    expect($queued->getMaxAge())->toBe(600 * 60);
});

test('queue method calls Cookie::queue to set the locale cookie', function (): void {
    $manager = new LocalePreferenceManager;
    $manager->queue('en');

    expect(Cookie::hasQueued('locale'))->toBeTrue();
});

test('applyFromRequest calls Cookie::queue when no locale cookie present', function (): void {
    $request = Request::create('/');

    $manager = new LocalePreferenceManager;
    $manager->applyFromRequest($request);

    expect(Cookie::hasQueued('locale'))->toBeTrue();
});

test('applyFromRequest does not queue cookie when locale cookie is present', function (): void {
    $request = Request::create('/');
    $request->cookies->set('locale', 'fr');

    $manager = new LocalePreferenceManager;
    $manager->applyFromRequest($request);

    expect(Cookie::hasQueued('locale'))->toBeFalse();
});
