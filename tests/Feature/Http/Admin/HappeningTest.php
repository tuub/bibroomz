<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\HappeningController;
use App\Http\Requests\Admin\DeleteHappeningRequest;
use App\Http\Requests\Admin\HappeningRequest;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\HappeningAdminService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;

covers(
    HappeningController::class,
    HappeningAdminService::class,
    StoreHappeningRequest::class,
    DeleteHappeningRequest::class,
    HappeningRequest::class,
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00'));
    config()->set('broadcasting.default', 'log');
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * @return array<string, string>
 */
function adminHappeningFeatureTranslatable(string $value): array
{
    return Utility::getTranslatable($value);
}

/**
 * @param  list<string>  $permissions
 */
function actingHappeningFeatureAdmin(Institution $institution, array $permissions): User
{
    $user = User::factory()->create([
        'is_system_user' => true,
        'is_admin' => false,
    ]);

    foreach ($permissions as $permission) {
        grantAdminPermission($user, $institution, $permission);
    }

    test()->actingAs($user);

    return $user;
}

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForHappenings(Institution $institution): User
{
    $actor = User::factory()->create();
    // grant 'view_users' so the actor passes the 'view-admin-panel' gate
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, verifier: User, happening: Happening, admin: User}
 */
function buildHappeningIndexFixture(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => false,
    ]);
    $owner = User::factory()->create(['name' => 'happening.index.owner', 'is_system_user' => true]);
    $verifier = User::factory()->create(['name' => 'happening.index.verifier', 'is_system_user' => true]);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => $verifier->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => '2026-06-10 08:05:00',
        'label' => Utility::getTranslatable('Index Test Booking'),
    ]);

    $admin = User::factory()->create(['is_system_user' => true, 'is_admin' => false]);
    foreach (['view_happenings', 'create_happenings', 'edit_happenings', 'delete_happenings'] as $perm) {
        grantAdminPermission($admin, $institution, $perm);
    }
    test()->actingAs($admin);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'verifier' => $verifier, 'happening' => $happening, 'admin' => $admin];
}

// ---------------------------------------------------------------------------
// From AdminHappeningFlowTest
// ---------------------------------------------------------------------------

test('scoped admins without happening create permission cannot store admin happenings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $bookedUser = User::factory()->create(['is_system_user' => true]);
    $verifier = User::factory()->create(['is_system_user' => true]);

    actingHappeningFeatureAdmin($institution, ['view_happenings']);

    $this->post(route('admin.happening.store'), [
        'start_date' => '04.06.2026',
        'start_time' => '10:00',
        'end_date' => '04.06.2026',
        'end_time' => '12:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => adminHappeningFeatureTranslatable('Blocked session'),
    ])->assertForbidden();

    $this->assertDatabaseMissing('happenings', ['resource_id' => $resource->id]);
});

test('admin happening routes render and mutate happenings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $bookedUser = User::factory()->create(['is_system_user' => true]);
    $verifier = User::factory()->create(['is_system_user' => true]);

    actingHappeningFeatureAdmin($institution, [
        'view_happenings',
        'create_happenings',
        'edit_happenings',
        'delete_happenings',
    ]);

    $this->get(route('admin.happening.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Happenings/Index')
            ->has('happenings'));

    $this->get(route('admin.happening.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Happenings/Form')
            ->has('resources')
            ->has('users'));

    $this->post(route('admin.happening.store'), [
        'start_date' => '04.06.2026',
        'start_time' => '10:00',
        'end_date' => '04.06.2026',
        'end_time' => '12:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => adminHappeningFeatureTranslatable('Focus session'),
    ])->assertRedirect(route('admin.happening.index'));

    $happening = Happening::query()->where('resource_id', $resource->id)->firstOrFail();

    $this->get(route('admin.happening.edit', ['id' => $happening->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Happenings/Form')
            ->where('happening.id', $happening->id)
            ->has('resources')
            ->has('users'));

    $this->post(route('admin.happening.update'), [
        'id' => $happening->id,
        'start_date' => '04.06.2026',
        'start_time' => '11:00',
        'end_date' => '04.06.2026',
        'end_time' => '13:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => adminHappeningFeatureTranslatable('Updated focus session'),
    ])->assertRedirect(route('admin.happening.index'));

    expect($happening->fresh()?->getTranslation('label', 'en'))->toBe('Updated focus session');

    $this->post(route('admin.happening.delete'), ['id' => $happening->id])
        ->assertRedirect(route('admin.happening.index'));

    $this->assertSoftDeleted('happenings', ['id' => $happening->id]);
});

// ---------------------------------------------------------------------------
// From AdminHappeningIndexAndVisibilityTest — happening index test
// ---------------------------------------------------------------------------

test('admin happenings index presents happening data including institution and resource group', function (): void {
    $fixture = buildHappeningIndexFixture();

    $this->get(route('admin.happening.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Happenings/Index')
            ->has('happenings', 1, fn (Assert $h): AssertableJson => $h
                ->where('id', $fixture['happening']->id)
                ->where('is_verified', true)
                ->where('user1', $fixture['owner']->name)
                ->where('user2', $fixture['verifier']->name)
                ->has('institution')
                ->has('resource_group')
                ->has('resource')
                ->etc()));
});

// ---------------------------------------------------------------------------
// From Http/Controllers/Admin/HappeningControllerTest
// ---------------------------------------------------------------------------

test('editHappening returns 403 for user without adminUpdate permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('adminUpdate', $happening)
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.happening.edit', ['id' => $happening->id]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editHappening returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForHappenings($institution);

    $this->actingAs($actor)
        ->get(route('admin.happening.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('storeHappening returns redirect when required date fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $bookedUser = User::factory()->create(['is_system_user' => true]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_happenings');

    // Provide resource_id and user_id_01 to pass authorize(), but omit date fields
    $this->actingAs($actor)
        ->post(route('admin.happening.store'), [
            'resource_id' => $resource->id,
            'user_id_01' => $bookedUser->id,
            'is_verified' => false,
        ])
        ->assertRedirect();
});

test('updateHappening returns redirect when required date fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $owner = User::factory()->create(['is_system_user' => true]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_happenings');

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Existing'],
    ]);

    // Provide id (valid happening) and resource_id/user_id_01 to pass authorize(), but omit date fields
    $this->actingAs($actor)
        ->post(route('admin.happening.update'), [
            'id' => $happening->id,
            'resource_id' => $resource->id,
            'user_id_01' => $owner->id,
            'is_verified' => false,
        ])
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — happening tests
// ---------------------------------------------------------------------------

test('scoped admin without edit_happenings cannot update admin happening', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $actor = buildScopedActorForHappenings($institution);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Original'],
    ]);

    $this->actingAs($actor)
        ->post(route('admin.happening.update'), [
            'id' => $happening->id,
            'resource_id' => $resource->id,
            'user_id_01' => $owner->id,
            'start' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'end' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'label' => ['en' => 'Unauthorized update'],
        ])
        ->assertForbidden();

    expect($happening->fresh()?->getTranslations('label')['en'])->toBe('Original');
});

test('scoped admin without delete_happenings cannot delete admin happening', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $actor = buildScopedActorForHappenings($institution);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Original'],
    ]);

    $this->actingAs($actor)
        ->post(route('admin.happening.delete'), ['id' => $happening->id])
        ->assertForbidden();

    $this->assertDatabaseHas('happenings', ['id' => $happening->id]);
});
