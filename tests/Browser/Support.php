<?php

use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Database\Eloquent\Model;

function seedBrowserPrerequisites(): void
{
    Model::unguard();
    (new PermissionSeeder)->run();
    (new WeekDaySeeder)->run();
    Model::reguard();
}

function browserPassword(): string
{
    return 'password';
}

function createBrowserSystemUser(string $name, ?string $password = null): User
{
    $password ??= browserPassword();

    return User::factory()->create([
        'name' => $name,
        'password' => bcrypt($password),
        'is_system_user' => true,
    ]);
}

function createBrowserInstitution(
    string $title = 'Browser Institution',
    string $slug = 'browser-institution',
): Institution {
    return Institution::factory()->create([
        'title' => Utility::getTranslatable($title),
        'short_title' => 'BRW',
        'slug' => $slug,
        'is_active' => true,
    ]);
}

function createBrowserResourceGroup(
    Institution $institution,
    string $title,
    string $slug,
): ResourceGroup {
    return ResourceGroup::factory()
        ->for($institution, 'institution')
        ->create([
            'title' => Utility::getTranslatable($title),
            'slug' => $slug,
            'term_singular' => Utility::getTranslatable('Arbeitsraum'),
            'term_plural' => Utility::getTranslatable('Arbeitsraeume'),
            'description' => Utility::getTranslatable('Beschreibung '.$title),
            'is_active' => true,
        ]);
}

function createBrowserResource(
    ResourceGroup $resourceGroup,
    string $title,
    bool $verificationRequired = true,
): Resource {
    return Resource::factory()
        ->for($resourceGroup, 'resource_group')
        ->create([
            'title' => Utility::getTranslatable($title),
            'location' => Utility::getTranslatable('Standort '.$title),
            'description' => Utility::getTranslatable('Beschreibung '.$title),
            'is_active' => true,
            'is_verification_required' => $verificationRequired,
        ]);
}

function buildBrowserHomeRoute(ResourceGroup $resourceGroup): string
{
    return route('home', [
        'institution_slug' => $resourceGroup->institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ], absolute: false);
}

function buildBrowserInstitutionCatalogFixture(int $resourceGroupCount = 2): array
{
    $institution = createBrowserInstitution();
    $resourceGroups = collect();

    for ($index = 1; $index <= $resourceGroupCount; $index++) {
        $resourceGroup = createBrowserResourceGroup(
            $institution,
            'Arbeitsraeume '.$index,
            'browser-group-'.$index,
        );

        createBrowserResource($resourceGroup, sprintf('Resource %02d', $index));
        $resourceGroups->push($resourceGroup);
    }

    return ['institution' => $institution, 'resourceGroups' => $resourceGroups];
}

function buildBrowserCalendarFixture(
    int $resourceCount = 2,
    bool $verificationRequired = true,
): array {
    $institution = createBrowserInstitution('Calendar Institution', 'calendar-institution');
    $resourceGroup = createBrowserResourceGroup(
        $institution,
        'Calendar Resource Group',
        'calendar-resource-group',
    );

    $resources = collect();

    for ($index = 1; $index <= $resourceCount; $index++) {
        $resources->push(createBrowserResource(
            $resourceGroup,
            sprintf('Resource %02d', $index),
            $verificationRequired,
        ));
    }

    $route = buildBrowserHomeRoute($resourceGroup);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resources' => $resources, 'route' => $route];
}

function buildBrowserBookingFixture(): array
{
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resources' => $resources,
        'route' => $route,
    ] = buildBrowserCalendarFixture(resourceCount: 1, verificationRequired: true);

    $resource = $resources->firstOrFail();
    $today = CarbonImmutable::today(config('roomz.app.timezone'));
    $password = browserPassword();

    $owner = createBrowserSystemUser('browser.owner', $password);
    $verifier = createBrowserSystemUser('browser.verifier', $password);
    $otherUser = createBrowserSystemUser('browser.other', $password);

    $verifiableBooking = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => $today->addDay()->setTime(9, 0),
        'end' => $today->addDay()->setTime(10, 0),
        'reserved_at' => now()->subHour(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Verify'),
    ]);

    $editableBooking = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => $today->addDay()->setTime(11, 0),
        'end' => $today->addDay()->setTime(12, 0),
        'reserved_at' => now()->subHour(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Edit'),
    ]);

    ['start' => $pastStart, 'end' => $pastEnd] = browserPastBookingWindow();

    $pastBooking = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'user_id_02' => $verifier->id,
        'verifier' => null,
        'start' => $pastStart,
        'end' => $pastEnd,
        'reserved_at' => now()->subDays(2),
        'verified_at' => now()->subDays(2),
        'label' => Utility::getTranslatable('Past'),
    ]);

    return ['editableBooking' => $editableBooking, 'institution' => $institution, 'otherUser' => $otherUser, 'owner' => $owner, 'password' => $password, 'pastBooking' => $pastBooking, 'resource' => $resource, 'resourceGroup' => $resourceGroup, 'route' => $route, 'verifiableBooking' => $verifiableBooking, 'verifier' => $verifier];
}

function buildBrowserValidationFixture(): array
{
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resources' => $resources,
        'route' => $route,
    ] = buildBrowserCalendarFixture(resourceCount: 1, verificationRequired: true);

    $resource = $resources->firstOrFail();
    $today = CarbonImmutable::today(config('roomz.app.timezone'));
    $password = browserPassword();

    $owner = createBrowserSystemUser('browser.validation.owner', $password);
    $verifier = createBrowserSystemUser('browser.validation.verifier', $password);
    $otherUser = createBrowserSystemUser('browser.validation.other', $password);

    $editableBooking = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => $today->addDay()->setTime(9, 0),
        'end' => $today->addDay()->setTime(10, 0),
        'reserved_at' => now()->subHour(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Conflict'),
    ]);

    ['start' => $pastStart, 'end' => $pastEnd] = browserPastBookingWindow();

    $pastBooking = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'user_id_02' => $verifier->id,
        'verifier' => null,
        'start' => $pastStart,
        'end' => $pastEnd,
        'reserved_at' => now()->subDays(2),
        'verified_at' => now()->subDays(2),
        'label' => Utility::getTranslatable('Locked'),
    ]);

    return ['editableBooking' => $editableBooking, 'institution' => $institution, 'otherUser' => $otherUser, 'owner' => $owner, 'password' => $password, 'pastBooking' => $pastBooking, 'resource' => $resource, 'resourceGroup' => $resourceGroup, 'route' => $route, 'verifier' => $verifier];
}

/**
 * @return array{start: CarbonImmutable, end: CarbonImmutable}
 */
function browserPastBookingWindow(): array
{
    $now = CarbonImmutable::now(config('roomz.app.timezone'));
    $end = $now->subHours(4);
    $currentWeekStart = $now->startOfWeek();

    if (! $end->startOfWeek()->equalTo($currentWeekStart)) {
        $minutesSinceWeekStart = $currentWeekStart->diffInMinutes($now);
        $end = $currentWeekStart->addMinutes(max(1, $minutesSinceWeekStart - 1));
    }

    $start = $end->subHour();

    if (! $start->startOfWeek()->equalTo($currentWeekStart)) {
        $start = $currentWeekStart;
    }

    return [
        'start' => $start,
        'end' => $end,
    ];
}

function browserInstitutionResourceGroupSelector(
    Institution $institution,
    ResourceGroup $resourceGroup,
): string {
    return "[data-testid='institution-{$institution->id}-resource-group-{$resourceGroup->id}']";
}

function browserHappeningSelector(Happening|string $happening): string
{
    $id = $happening instanceof Happening ? $happening->id : $happening;

    return "[data-testid='user-happening-{$id}']";
}

function browserHappeningActionSelector(Happening|string $happening, string $action): string
{
    $id = $happening instanceof Happening ? $happening->id : $happening;

    return "[data-testid='user-happening-{$id}-{$action}']";
}

function openBrowserLoginModal($page, string $triggerSelector = '#auth')
{
    return $page
        ->click($triggerSelector)
        ->wait(0.5)
        ->assertPresent('#modal')
        ->assertVisible('#username');
}

function submitBrowserLoginModal(
    $page,
    User $user,
    ?string $password = null,
    bool $assertSidebar = false,
) {
    $password ??= browserPassword();

    $page = $page
        ->type('#username', $user->name)
        ->type('#password', $password)
        ->click('[data-testid="modal-action-login"]')
        ->wait(1)
        ->assertNotPresent('#modal')
        ->assertSeeIn('#auth', $user->name);

    if ($assertSidebar) {
        $page->assertPresent('[data-testid="toggle-past-happenings-label"]');
    }

    return $page;
}

function loginThroughBrowserUi(
    string $route,
    User $user,
    ?string $password = null,
    string $triggerSelector = '#auth',
    bool $assertSidebar = false,
) {
    $page = visit($route)->wait(1);

    return submitBrowserLoginModal(
        openBrowserLoginModal($page, $triggerSelector),
        $user,
        $password,
        $assertSidebar,
    );
}

function openBrowserCreateModalForNextDay(
    $page,
    string $startTime = '11:00:00',
    string $endTime = '13:00:00',
) {
    $tomorrow = CarbonImmutable::today(config('roomz.app.timezone'))
        ->addDay()
        ->format('d.m.Y');

    return $page
        ->assertPresent("td.fc-timegrid-slot-lane[data-time=\"{$startTime}\"]")
        ->click('#calendar-date-next')
        ->wait(0.5)
        ->assertSeeIn('#calendar-date-display', $tomorrow)
        ->wait(0.5)
        ->drag(
            "td.fc-timegrid-slot-lane[data-time=\"{$startTime}\"]",
            "td.fc-timegrid-slot-lane[data-time=\"{$endTime}\"]",
        )
        ->wait(1)
        ->assertPresent('#modal');
}
