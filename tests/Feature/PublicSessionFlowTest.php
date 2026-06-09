<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Http\CurrentUserStatusBuilder;
use App\Services\Http\HomePageDataBuilder;
use App\Services\Http\InstitutionAccessService;
use App\Services\Http\LocalePreferenceManager;
use App\Services\Http\LoginAction;
use App\Services\Http\LogoutAction;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

covers(
    HomeController::class,
    LoginController::class,
    UserController::class,
    HomePageDataBuilder::class,
    LoginAction::class,
    LogoutAction::class,
    CurrentUserStatusBuilder::class,
    LocalePreferenceManager::class,
    InstitutionAccessService::class
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
});

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup|null, resourceGroups: Collection<int, ResourceGroup>}
 */
/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resourceGroups: Collection<int, ResourceGroup>}
 */
function createPublicSessionFixture(int $resourceGroupCount = 1): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroups = ResourceGroup::factory()
        ->count($resourceGroupCount)
        ->for($institution, 'institution')
        ->create();

    /** @var ResourceGroup $resourceGroup */
    $resourceGroup = $resourceGroups->first();

    return [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resourceGroups' => $resourceGroups,
    ];
}

test('start page redirects directly when exactly one allowed resource group exists', function (): void {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();

    $this->get(route('start'))
        ->assertRedirect(route('home', [
            'institution_slug' => $institution->slug,
            'resource_group_slug' => $resourceGroup->slug,
        ]));
});

test('start page renders filtered institutions when multiple allowed resource groups exist', function (): void {
    $allowedInstitution = Institution::factory()->create(['is_active' => true]);
    ResourceGroup::factory()->count(2)->for($allowedInstitution, 'institution')->create();

    $blockedInstitution = Institution::factory()->create(['is_active' => true]);
    ResourceGroup::factory()->for($blockedInstitution, 'institution')->create();
    $blockedInstitution->settings()->firstWhere('key', 'allowed_ips')?->update(['value' => '10.0.0.0/24']);

    $this->get(route('start'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Start')
            ->where('appName', config('app.name'))
            ->has('institutions', 1)
            ->where('institutions.0.id', $allowedInstitution->id));
});

test('institutional home redirects to start when the request ip is not allowed', function (): void {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();
    $institution->settings()->firstWhere('key', 'allowed_ips')?->update(['value' => '10.0.0.0/24']);

    $this->get(route('home', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))->assertRedirect(route('start'));
});

test('terminal view redirects to start when the request ip is not allowed', function (): void {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();
    $institution->settings()->firstWhere('key', 'allowed_ips')?->update(['value' => '10.0.0.0/24']);

    $this->get(route('terminal_view', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))->assertRedirect(route('start'));
});

test('home page preserves the current inertia payload contract', function (): void {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture(2);

    $this->get(route('home', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Home')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.institution.allowed_ips', '0.0.0.0/0')
            ->where('isMultiTenancy', true)
            ->where('hiddenDays', []));
});

test('terminal view preserves the current inertia payload contract', function (): void {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createPublicSessionFixture();

    $this->get(route('terminal_view', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('TerminalView')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.resource_group.time_slot_length', config('roomz.default.timeslot_length'))
            ->where('hiddenDays', []));
});

test('privacy statement and site credits routes render their inertia components', function (): void {
    $this->get(route('privacy_statement'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page->component('PrivacyStatement'));

    $this->get(route('site_credits'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page->component('SiteCredits'));
});

test('switch language queues the locale cookie', function (): void {
    $this->post(route('switch_lang'), ['locale' => 'de'])
        ->assertOk()
        ->assertCookie('locale', 'de');
});

test('check rejects guests with the public auth message', function (): void {
    $this->postJson(route('check'))
        ->assertUnauthorized()
        ->assertJsonPath('message', __('auth.errors.no_auth'));
});

test('check returns the authenticated user payload with allowed resource groups', function (): void {
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

    /** @var array<int, string> $allowedGroups */
    $allowedGroups = $response->json('allowedResourceGroups') ?? [];
    expect($response->json('permissions'))->toBeArray()
        ->and(collect($allowedGroups)->sort()->values()->all())
        ->toBe($resourceGroups->pluck('id')->sort()->values()->all());
});

test('logout returns no content, logs the user out, and clears the login flag', function (): void {
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
