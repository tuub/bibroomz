<?php

use App\Library\Utility;
use App\Models\Happening;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedBrowserPrerequisites();
});

it('shows future bookings and keeps filtered past bookings out of the sidebar', function () {
    [
        'owner' => $owner,
        'password' => $password,
        'pastBooking' => $pastBooking,
        'route' => $route,
        'verifiableBooking' => $verifiableBooking,
    ] = buildBrowserBookingFixture();

    loginThroughBrowserUi($route, $owner, $password, '#auth', true)
        ->assertPresent(browserHappeningSelector($verifiableBooking))
        ->assertNotPresent(browserHappeningSelector($pastBooking));
});

it('lets authenticated users create bookings from the calendar when they provide a verifier', function () {
    ['route' => $route] = buildBrowserCalendarFixture();

    $owner = createBrowserSystemUser('browser.creator');
    $verifier = createBrowserSystemUser('browser.creator.verifier');

    $page = openBrowserCreateModalForNextDay(
        loginThroughBrowserUi($route, $owner, browserPassword(), '#auth', true),
    )
        ->assertPresent('#verifier')
        ->type('#verifier', $verifier->name)
        ->click('[data-testid="modal-action-create"]')
        ->wait(1)
        ->assertNotPresent('#modal');

    $createdHappening = Happening::query()
        ->where('user_id_01', $owner->id)
        ->latest('created_at')
        ->first();

    expect($createdHappening)->not->toBeNull()
        ->and($createdHappening?->verifier)->toBe($verifier->name);

    $page
        ->refresh()
        ->wait(1)
        ->assertPresent(browserHappeningSelector($createdHappening->id))
        ->assertSeeIn(browserHappeningSelector($createdHappening->id), $verifier->name);
});

it('requires verifier input for verifier-required bookings', function () {
    ['route' => $route] = buildBrowserCalendarFixture();

    $owner = createBrowserSystemUser('browser.verifier.required');

    openBrowserCreateModalForNextDay(
        loginThroughBrowserUi($route, $owner, browserPassword(), '#auth', true),
    )
        ->assertPresent('#verifier')
        ->click('[data-testid="modal-action-create"]')
        ->wait(1)
        ->assertPresent('#modal')
        ->assertSee(__('validation.required', [
            'attribute' => __('validation.attributes.verifier'),
        ]));
});

it('lets users with the no verifier permission create bookings without a verifier', function () {
    [
        'institution' => $institution,
        'route' => $route,
    ] = buildBrowserCalendarFixture();

    $owner = createBrowserSystemUser('browser.no.verifier');
    grantAdminPermission($owner, $institution, 'no_verifier');

    $page = openBrowserCreateModalForNextDay(
        loginThroughBrowserUi($route, $owner->fresh(), browserPassword(), '#auth', true),
    )
        ->assertNotPresent('#verifier')
        ->click('[data-testid="modal-action-create"]')
        ->wait(1)
        ->assertNotPresent('#modal');

    $createdHappening = Happening::query()
        ->where('user_id_01', $owner->id)
        ->latest('created_at')
        ->first();

    expect($createdHappening)->not->toBeNull()
        ->and($createdHappening?->verifier)->toBeNull();

    $page
        ->refresh()
        ->wait(1)
        ->assertPresent(browserHappeningSelector($createdHappening->id));
});

it('lets designated verifiers verify a booking from the sidebar', function () {
    [
        'password' => $password,
        'route' => $route,
        'verifiableBooking' => $verifiableBooking,
        'verifier' => $verifier,
    ] = buildBrowserBookingFixture();

    loginThroughBrowserUi($route, $verifier, $password, '#auth', true)
        ->assertSeeIn(browserHappeningSelector($verifiableBooking), __('user_happenings.item.unverified'))
        ->assertPresent(browserHappeningActionSelector($verifiableBooking, 'verify'))
        ->click(browserHappeningActionSelector($verifiableBooking, 'verify'))
        ->wait(1)
        ->assertPresent('#modal')
        ->keys('#end', 'ArrowDown')
        ->wait(0.5)
        ->click('[data-testid="modal-action-verify"]')
        ->wait(1)
        ->assertNotPresent('#modal');

    $verifiableBooking->refresh();

    expect($verifiableBooking->is_verified)->toBeTrue()
        ->and($verifiableBooking->user_id_02)->toBe($verifier->id)
        ->and($verifiableBooking->verifier)->toBeNull()
        ->and($verifiableBooking->end->format('H:i'))->toBe('10:30');

    visit($route)
        ->wait(1)
        ->assertPresent(browserHappeningSelector($verifiableBooking))
        ->assertSeeIn(browserHappeningSelector($verifiableBooking), __('user_happenings.item.verified'))
        ->assertNotPresent(browserHappeningActionSelector($verifiableBooking, 'verify'));
});

it('lets owners edit and delete their bookings from the sidebar', function () {
    [
        'editableBooking' => $editableBooking,
        'owner' => $owner,
        'password' => $password,
        'route' => $route,
    ] = buildBrowserBookingFixture();

    loginThroughBrowserUi($route, $owner, $password, '#auth', true)
        ->assertSeeIn(browserHappeningSelector($editableBooking), '11:00 - 12:00')
        ->click(browserHappeningActionSelector($editableBooking, 'edit'))
        ->wait(1)
        ->assertPresent('#modal')
        ->keys('#end', 'ArrowDown')
        ->wait(0.5)
        ->click('[data-testid="modal-action-update"]')
        ->wait(1)
        ->assertNotPresent('#modal');

    $editableBooking->refresh();

    expect($editableBooking->end->format('H:i'))->toBe('12:30');

    visit($route)
        ->wait(1)
        ->assertPresent(browserHappeningActionSelector($editableBooking, 'edit'))
        ->click(browserHappeningActionSelector($editableBooking, 'delete'))
        ->wait(0.5)
        ->assertPresent('#modal')
        ->click('[data-testid="modal-action-delete"]')
        ->wait(1)
        ->assertNotPresent('#modal');

    $this->assertSoftDeleted('happenings', ['id' => $editableBooking->id]);
});

it('keeps the edit modal open and shows the reservation conflict error', function () {
    [
        'editableBooking' => $editableBooking,
        'otherUser' => $otherUser,
        'owner' => $owner,
        'password' => $password,
        'resource' => $resource,
        'route' => $route,
        'verifier' => $verifier,
    ] = buildBrowserValidationFixture();

    $page = loginThroughBrowserUi($route, $owner, $password, '#auth', true)
        ->assertPresent(browserHappeningActionSelector($editableBooking, 'edit'))
        ->click(browserHappeningActionSelector($editableBooking, 'edit'))
        ->wait(1)
        ->assertPresent('#modal');

    Happening::create([
        'user_id_01' => $otherUser->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'user_id_02' => $verifier->id,
        'verifier' => null,
        'start' => $editableBooking->start,
        'end' => $editableBooking->end,
        'reserved_at' => now()->subMinutes(10),
        'verified_at' => now()->subMinutes(5),
        'label' => Utility::getTranslatable('Conflict booking'),
    ]);

    $page
        ->click('[data-testid="modal-action-update"]')
        ->wait(1)
        ->assertPresent('#modal')
        ->assertSee(__('happening.errors.reserved', [
            'resource_type' => $resource->resource_group->getTranslation('term_singular', 'de'),
            'resource_title' => $resource->getTranslation('title', 'de'),
        ]));
});

it('keeps edit and delete actions available for verified second users but hides verify', function () {
    [
        'owner' => $owner,
        'password' => $password,
        'resource' => $resource,
        'route' => $route,
        'verifier' => $verifier,
    ] = buildBrowserBookingFixture();

    $verifiedSecondUserBooking = Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => $verifier->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => CarbonImmutable::today(config('roomz.app.timezone'))->addDay()->setTime(13, 0),
        'end' => CarbonImmutable::today(config('roomz.app.timezone'))->addDay()->setTime(14, 0),
        'reserved_at' => now()->subHour(),
        'verified_at' => now()->subMinutes(30),
        'label' => Utility::getTranslatable('Locked'),
    ]);

    loginThroughBrowserUi($route, $verifier, $password, '#auth', true)
        ->assertPresent(browserHappeningSelector($verifiedSecondUserBooking))
        ->assertPresent(browserHappeningActionSelector($verifiedSecondUserBooking, 'edit'))
        ->assertPresent(browserHappeningActionSelector($verifiedSecondUserBooking, 'delete'))
        ->assertNotPresent(browserHappeningActionSelector($verifiedSecondUserBooking, 'verify'));
});
