<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seedBrowserPrerequisites();
});

it('shows the calendar title and lets users browse dates forward and backward', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'route' => $route,
    ] = buildBrowserCalendarFixture();

    $page = visit($route)
        ->wait(1)
        ->assertPathIs($route)
        ->assertSee($institution->getTranslation('title', 'de').': '.$resourceGroup->getTranslation('title', 'de'));

    $initialDate = trim((string) $page->script("document.querySelector('#calendar-date-display').textContent"));

    $page
        ->click('#calendar-date-next')
        ->wait(0.5)
        ->assertSeeIn(
            '#calendar-date-display',
            CarbonImmutable::today(config('roomz.app.timezone'))->addDay()->format('d.m.Y'),
        )
        ->click('#calendar-date-previous')
        ->wait(0.5)
        ->assertSeeIn('#calendar-date-display', $initialDate);
});

it('lets users browse resource pages when more resources exist than fit on one calendar page', function (): void {
    ['route' => $route] = buildBrowserCalendarFixture(resourceCount: 9);

    visit($route)
        ->wait(1)
        ->assertSee('Resource 01')
        ->click('#calendar-resources-next')
        ->wait(1)
        ->assertSee('Resource 09')
        ->click('#calendar-resources-previous')
        ->wait(1)
        ->assertSee('Resource 01');
});

it('asks guests to log in before they can create a booking from the calendar', function (): void {
    ['route' => $route] = buildBrowserCalendarFixture();

    openBrowserCreateModalForNextDay(visit($route)->wait(1))
        ->assertPresent('#username')
        ->assertPresent('[data-testid="modal-action-login"]')
        ->assertNotPresent('#verifier');
});
