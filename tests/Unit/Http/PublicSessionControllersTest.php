<?php

covers(
    App\Http\Controllers\HomeController::class,
    App\Http\Controllers\LoginController::class,
    App\Http\Controllers\UserController::class,
    App\Services\Http\LoginAction::class,
    App\Services\Http\LogoutAction::class,
    App\Services\Http\HomePageDataBuilder::class,
    App\Services\Http\LocalePreferenceManager::class,
    App\Services\Http\UserActivityRecorder::class
);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
});

test('start page redirects directly when exactly one resource group is allowed', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $this->get(route('start'))
        ->assertRedirect(route('home', [
            'institution_slug' => $institution->slug,
            'resource_group_slug' => $resourceGroup->slug,
        ]));
});

test('start page filters institutions by allowed ip before rendering', function () {
    $allowedInstitution = Institution::factory()->create(['is_active' => true]);
    ResourceGroup::factory()->count(2)->for($allowedInstitution, 'institution')->create();

    $blockedInstitution = Institution::factory()->create(['is_active' => true]);
    ResourceGroup::factory()->for($blockedInstitution, 'institution')->create();
    $blockedInstitution->settings()->firstWhere('key', 'allowed_ips')->update(['value' => '10.0.0.0/24']);

    $this->get(route('start'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Start')
            ->where('appName', config('app.name'))
            ->has('institutions', 1)
            ->where('institutions.0.id', $allowedInstitution->id));
});

test('institutional home redirects to start when the request ip is not allowed', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $institution->settings()->firstWhere('key', 'allowed_ips')->update(['value' => '10.0.0.0/24']);

    $this->get(route('home', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]))->assertRedirect(route('start'));
});

test('institutional home and terminal view preserve the current inertia props', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    ResourceGroup::factory()->for($institution, 'institution')->create();

    $routeParams = [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ];

    $this->get(route('home', $routeParams))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.institution.allowed_ips', '0.0.0.0/0')
            ->where('isMultiTenancy', true)
            ->where('hiddenDays', []));

    $this->get(route('terminal_view', $routeParams))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TerminalView')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.resource_group.time_slot_length', config('roomz.default.timeslot_length'))
            ->where('hiddenDays', []));
});

test('switch language queues the locale cookie', function () {
    $this->post(route('switch_lang'), ['locale' => 'de'])
        ->assertOk()
        ->assertCookie('locale', 'de');
});

test('privacy statement and site credits pages render their inertia components', function () {
    $this->get(route('privacy_statement'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('PrivacyStatement'));

    $this->get(route('site_credits'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('SiteCredits'));
});

test('login and check return the current user status payload', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $firstGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $secondGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'secret-pass'])
        ->assertOk()
        ->assertJsonPath('isAdmin', false)
        ->assertJsonPath('user.name', 'LocalUser')
        ->assertJsonCount(2, 'allowedResourceGroups');

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'is_logged_in' => true]);

    $this->postJson(route('check'))
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('allowedResourceGroups.0', $firstGroup->id)
        ->assertJsonPath('allowedResourceGroups.1', $secondGroup->id);
});

test('login rejects invalid credentials with the public auth error message', function () {
    User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
        ->assertUnauthorized()
        ->assertJsonPath('message', __('auth.errors.user_not_found'));
});

test('check rejects guests and logout clears the login flag', function () {
    $this->postJson(route('check'))
        ->assertUnauthorized()
        ->assertJsonPath('message', __('auth.errors.no_auth'));

    $user = User::factory()->create([
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
        'is_logged_in' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('logout'))
        ->assertNoContent();

    $this->assertGuest();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'is_logged_in' => false]);
});

test('public resources endpoint preserves resource and pagination payload shapes', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $firstResource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'order' => 1,
        'is_verification_required' => true,
    ]);
    Resource::factory()->for($resourceGroup, 'resource_group')->create(['order' => 2]);

    $response = $this->getJson(route('resources.get', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'count' => 1,
        'date' => now()->toDateString(),
    ]));

    $response->assertOk()
        ->assertJsonPath('resources.0.id', $firstResource->id)
        ->assertJsonPath('resources.0.resourceGroup', $resourceGroup->id)
        ->assertJsonPath('resources.0.isVerificationRequired', true)
        ->assertJsonPath(
            'resources.0.translations.resourceGroup.en',
            $resourceGroup->getTranslations('term_singular')['en'],
        );

    expect($response->json('pagination.previousPage'))->toBeNull()
        ->and($response->json('pagination.nextPage'))->not->toBeNull();
});

test('resource time slots endpoint returns the expected top level shape', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson(route('resource.time_slots', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'id' => $resource->id,
    ]), [
        'start' => now()->setTime(10, 0)->format('Y-m-d H:i:s'),
        'end' => now()->setTime(11, 0)->format('Y-m-d H:i:s'),
    ]);

    $response->assertOk()
        ->assertJsonStructure(['start', 'end']);

    expect($response->json('start'))->toBeArray()
        ->and($response->json('end'))->toBeArray();
});

test('user happenings endpoint preserves the existing payload shape', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();
    $verifier = User::factory()->create();

    $happening = Happening::create([
        'user_id_01' => $user->id,
        'user_id_02' => $verifier->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => $verifier->name,
        'start' => Carbon::now()->startOfWeek()->addDay()->setTime(10, 0),
        'end' => Carbon::now()->startOfWeek()->addDay()->setTime(12, 0),
        'reserved_at' => now()->subHour(),
        'verified_at' => now(),
        'label' => ['en' => 'Focus'],
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('user.happenings.get', [
        'resource_group_id' => $resourceGroup->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('0.id', $happening->id)
        ->assertJsonPath('0.user_01', $user->name)
        ->assertJsonPath('0.user_02', $verifier->name)
        ->assertJsonPath('0.resource.id', $resource->id)
        ->assertJsonPath('0.resource.institutionId', $institution->id)
        ->assertJsonPath('0.isVerificationRequired', true)
        ->assertJsonPath('0.label.en', 'Focus');
});
