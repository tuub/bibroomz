<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seedBrowserPrerequisites();
});

it('shows public entry content, institutions, and locale switching', function (): void {
    [
        'institution' => $institution,
        'resourceGroups' => $resourceGroups,
    ] = buildBrowserInstitutionCatalogFixture();

    visit('/')
        ->wait(0.5)
        ->assertSee(config('app.name'))
        ->assertSee('Buchung von Arbeitsräumen')
        ->assertPresent('[data-testid="start-login-link"]')
        ->assertSee($institution->getTranslation('title', 'de'))
        ->assertSee($resourceGroups->first()->getTranslation('title', 'de'))
        ->click('#i18n a[title="EN"]')
        ->wait(0.5)
        ->assertSeeIn('.i18n-active', 'EN')
        ->assertSee('Booking of study rooms')
        ->click('#i18n a[title="DE"]')
        ->wait(0.5)
        ->assertSeeIn('.i18n-active', 'DE')
        ->assertSee('Buchung von Arbeitsräumen');
});

it('navigates from a start page institution card into a resource group calendar', function (): void {
    [
        'institution' => $institution,
        'resourceGroups' => $resourceGroups,
    ] = buildBrowserInstitutionCatalogFixture();

    $targetGroup = $resourceGroups->firstOrFail();
    $route = buildBrowserHomeRoute($targetGroup);

    visit('/')
        ->wait(0.5)
        ->click(browserInstitutionResourceGroupSelector($institution, $targetGroup))
        ->wait(1)
        ->assertPathIs($route)
        ->assertSee($institution->getTranslation('title', 'de').': '.$targetGroup->getTranslation('title', 'de'));
});

it('lets users authenticate from the start page and log out again', function (): void {
    buildBrowserInstitutionCatalogFixture();

    $user = createBrowserSystemUser('browser.entry.user');

    openBrowserLoginModal(visit('/')->wait(0.5), '[data-testid="start-login-link"]')
        ->type('#username', $user->name)
        ->type('#password', browserPassword())
        ->click('[data-testid="modal-action-login"]')
        ->wait(1)
        ->assertNotPresent('#modal')
        ->assertSeeIn('#auth', $user->name)
        ->click('#auth')
        ->wait(1)
        ->assertPathIs('/')
        ->assertPresent('[data-testid="start-login-link"]')
        ->assertSeeIn('#auth', __('navigation.login'));

    expect($user->fresh()->is_logged_in)->toBeFalse();
});
