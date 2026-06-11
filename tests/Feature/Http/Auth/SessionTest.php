<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Http\HomePageDataBuilder;
use App\Services\Http\LocalePreferenceManager;
use App\Services\Http\LoginAction;
use App\Services\Http\LogoutAction;
use App\Services\Http\UserActivityRecorder;
use Carbon\Carbon;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

covers(
    HomeController::class,
    LoginController::class,
    UserController::class,
    LoginAction::class,
    LogoutAction::class,
    HomePageDataBuilder::class,
    LocalePreferenceManager::class,
    UserActivityRecorder::class,
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// From LoginSecurityTest
// ---------------------------------------------------------------------------

test('local system user login does not fall back to alma', function (): void {
    $user = User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'secret-pass'])
        ->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('local system user with wrong password is rejected without remote lookup', function (): void {
    User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
        ->assertUnauthorized();

    $this->assertGuest();
});

test('remote user cannot authenticate with stored local password', function (): void {
    User::factory()->create([
        'name' => 'remote-user',
        'password' => Hash::make('stored-local-password'),
        'is_system_user' => false,
    ]);

    mockLoginAlmaResponse('<result><code>1</code></result>');

    $this->postJson(route('login'), ['username' => 'remote-user', 'password' => 'stored-local-password'])
        ->assertUnauthorized();

    $this->assertGuest();
});

test('new alma users are created as directory accounts with non static passwords', function (): void {
    mockLoginAlmaResponse('<result><code>0</code><email_address>remote@example.org</email_address></result>');

    $this->postJson(route('login'), ['username' => 'RemoteUser', 'password' => 'remote-secret'])
        ->assertOk();

    $user = User::where('name', 'remoteuser')->firstOrFail();

    expect($user)->not->toBeNull();
    expect($user->isSystemUser())->toBeFalse();
    expect(Hash::check('Test123!', (string) $user->password))->toBeFalse();
});

test('login is rate limited after five failed attempts', function (): void {
    User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
            ->assertUnauthorized();
    }

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
        ->assertStatus(429);
});

// ---------------------------------------------------------------------------
// From PublicSessionFlowTest
// ---------------------------------------------------------------------------

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

test('unauthenticated non-json request to auth route redirects to start page', function (): void {
    // Triggers Authenticate::redirectTo() which returns route('start') for non-JSON requests
    $this->post(route('logout'))
        ->assertRedirect(route('start'));
});

// ---------------------------------------------------------------------------
// From Http/PublicSessionControllersTest (controller-level tests)
// ---------------------------------------------------------------------------

test('institutional home and terminal view preserve the current inertia props', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    ResourceGroup::factory()->for($institution, 'institution')->create();

    $routeParams = [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ];

    $this->get(route('home', $routeParams))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Home')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.institution.allowed_ips', '0.0.0.0/0')
            ->where('isMultiTenancy', true)
            ->where('hiddenDays', []));

    $this->get(route('terminal_view', $routeParams))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('TerminalView')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->where('settings.resource_group.time_slot_length', config('roomz.default.timeslot_length'))
            ->where('hiddenDays', []));
});

test('login and check return the current user status payload', function (): void {
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

test('login rejects invalid credentials with the public auth error message', function (): void {
    User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
        ->assertUnauthorized()
        ->assertJsonPath('message', __('auth.errors.user_not_found'));
});

test('check rejects guests and logout clears the login flag', function (): void {
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

test('public resources endpoint preserves resource and pagination payload shapes', function (): void {
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

test('resource time slots endpoint returns the expected top level shape', function (): void {
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

test('user happenings endpoint preserves the existing payload shape', function (): void {
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
