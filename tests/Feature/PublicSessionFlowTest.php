<?php

covers(
    App\Http\Controllers\HomeController::class,
    App\Http\Controllers\LoginController::class,
    App\Http\Controllers\UserController::class,
    App\Services\Http\HomePageDataBuilder::class,
    App\Services\Http\LoginAction::class,
    App\Services\Http\LogoutAction::class,
    App\Services\Http\CurrentUserStatusBuilder::class,
    App\Services\Http\LocalePreferenceManager::class,
    App\Services\Http\InstitutionAccessService::class
);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
});

function createPublicSessionFixture(int $resourceGroupCount = 1): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroups = ResourceGroup::factory()
        ->count($resourceGroupCount)
        ->for($institution, 'institution')
        ->create();

    return [
        'institution' => $institution,
        'resourceGroup' => $resourceGroups->first(),
        'resourceGroups' => $resourceGroups,
    ];
}

test('start page redirects directly when exactly one allowed resource group exists', function () {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();

    $this->get(route('start'))
        ->assertRedirect(route('home', [
            'institution_slug' => $institution->slug,
            'resource_group_slug' => $resourceGroup->slug,
        ]));
});

test('start page renders filtered institutions when multiple allowed resource groups exist', function () {
    $allowedInstitution = Institution::factory()->create(['is_active' => true]);
    ResourceGroup::factory()->count(2)->for($allowedInstitution, 'institution')->create();

    $blockedInstitution = Institution::factory()->create(['is_active' => true]);
    ResourceGroup::factory()->for($blockedInstitution, 'institution')->create();
    $blockedInstitution->settings()->firstWhere('key', 'allowed_ips')?->update(['value' => '10.0.0.0/24']);

    $this->get(route('start'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Start')
            ->where('appName', config('app.name'))
            ->has('institutions', 1)
            ->where('institutions.0.id', $allowedInstitution->id));
});

test('institutional home redirects to start when the request ip is not allowed', function () {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();
    $institution->settings()->firstWhere('key', 'allowed_ips')?->update(['value' => '10.0.0.0/24']);

    $this->get(route('home', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))->assertRedirect(route('start'));
});

test('terminal view redirects to start when the request ip is not allowed', function () {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();
    $institution->settings()->firstWhere('key', 'allowed_ips')?->update(['value' => '10.0.0.0/24']);

    $this->get(route('terminal_view', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))->assertRedirect(route('start'));
});

test('home page preserves the current inertia payload contract', function () {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture(2);

    $this->get(route('home', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.institution.allowed_ips', '0.0.0.0/0')
            ->where('isMultiTenancy', true)
            ->where('hiddenDays', []));
});

test('terminal view preserves the current inertia payload contract', function () {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();

    $this->get(route('terminal_view', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TerminalView')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.resource_group.time_slot_length', config('roomz.default.timeslot_length'))
            ->where('hiddenDays', []));
});

test('privacy statement and site credits routes render their inertia components', function () {
    $this->get(route('privacy_statement'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('PrivacyStatement'));

    $this->get(route('site_credits'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('SiteCredits'));
});

test('switch language queues the locale cookie', function () {
    $this->post(route('switch_lang'), ['locale' => 'de'])
        ->assertOk()
        ->assertCookie('locale', 'de');
});

test('check rejects guests with the public auth message', function () {
    $this->postJson(route('check'))
        ->assertUnauthorized()
        ->assertJsonPath('message', __('auth.errors.no_auth'));
});

test('check returns the authenticated user payload with allowed resource groups', function () {
    ['resourceGroups' => $resourceGroups] = createPublicSessionFixture(2);
    $user = User::factory()->create([
        'is_logged_in' => true,
        'is_system_user' => true,
    ]);

    $response = $this->actingAs($user)->postJson(route('check'));

    $response->assertOk()
        ->assertJsonPath('isAdmin', false)
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.name', $user->name);

    expect($response->json('permissions'))->toBeArray()
        ->and(collect($response->json('allowedResourceGroups'))->sort()->values()->all())
        ->toBe($resourceGroups->pluck('id')->sort()->values()->all());
});

test('logout returns no content, logs the user out, and clears the login flag', function () {
    $user = User::factory()->create([
        'is_logged_in' => true,
        'is_system_user' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('logout'))
        ->assertNoContent();

    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_logged_in' => false,
    ]);
});
