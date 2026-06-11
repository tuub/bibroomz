<?php

declare(strict_types=1);

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningsChangedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Events\HappeningVerifiedEvent;
use App\Http\Controllers\HappeningController;
use App\Http\Requests\UpdateHappeningRequest as PublicUpdateHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(
    HappeningController::class,
    PublicUpdateHappeningRequest::class,
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    config()->set('roomz.app.timezone', 'UTC');
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00', 'UTC'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

// ---------------------------------------------------------------------------
// Helpers (from PublicHappeningAuthorizationTest)
// ---------------------------------------------------------------------------

/**
 * @param  array<string, mixed>  $resourceOverrides
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, verifier: User, otherUser: User}
 */
function buildHappeningAuthFixture(array $resourceOverrides = []): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    /** @var array<string, mixed> $resourceAttrs */
    $resourceAttrs = array_merge([
        'is_active' => true,
        'is_verification_required' => true,
    ], $resourceOverrides);
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create($resourceAttrs);
    $owner = User::factory()->create(['name' => 'owner.user']);
    $verifier = User::factory()->create(['name' => 'verifier.user']);
    $otherUser = User::factory()->create(['name' => 'other.user']);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'verifier' => $verifier, 'otherUser' => $otherUser];
}

function createFixtureHappening(User $owner, Resource $resource, User $verifier): Happening
{
    return Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Fixture'],
    ]);
}

// ---------------------------------------------------------------------------
// From PublicHappeningFlowTest
// ---------------------------------------------------------------------------

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, verifier: User, otherUser: User}
 */
function createPublicHappeningFixture(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => true,
    ]);
    $owner = User::factory()->create(['name' => 'owner.user']);
    $verifier = User::factory()->create(['name' => 'verifier.user']);
    $otherUser = User::factory()->create(['name' => 'other.user']);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'verifier' => $verifier, 'otherUser' => $otherUser];
}

test('guest requests are rejected for sanctum protected public happening routes', function (): void {
    ['resourceGroup' => $resourceGroup] = createPublicHappeningFixture();

    $this->getJson(route('user.happenings.get', ['resource_group_id' => $resourceGroup->id]))
        ->assertUnauthorized();

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => (string) fake()->uuid()],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
    ])->assertUnauthorized();
});

test('my happenings route returns adjusted open entries and filters out fully closed ones', function (): void {
    [
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
    ] = createPublicHappeningFixture();

    $resource->closings()->create([
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'description' => ['en' => 'Midday close'],
    ]);

    $visible = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 08:00:00',
        'end' => '2026-06-10 12:00:00',
        'reserved_at' => now()->subHour(),
        'verified_at' => null,
        'label' => ['en' => 'Visible'],
    ]);

    $filtered = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 11:05:00',
        'end' => '2026-06-10 11:30:00',
        'reserved_at' => now()->subHour(),
        'verified_at' => null,
        'label' => ['en' => 'Filtered'],
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson(route('user.happenings.get', ['resource_group_id' => $resourceGroup->id]));

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $visible->id)
        ->assertJsonPath('0.start', '2026-06-10 09:00')
        ->assertJsonPath('0.end', '2026-06-10 11:00')
        ->assertJsonPath('0.user_02', $verifier->name);

    /** @var array<int, mixed> $jsonData */
    $jsonData = $response->json() ?? [];
    expect(collect($jsonData)->pluck('id')->all())->not->toContain($filtered->id);
});

test('authenticated users can create a public reservation and dispatch events', function (): void {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Study slot'],
    ])->assertNoContent();

    $happening = Happening::firstWhere('resource_id', $resource->id);

    expect($happening)->not->toBeNull()
        ->and($happening?->user_id_01)->toBe($owner->id)
        ->and($happening?->is_verified)->toBeFalse()
        ->and($happening?->verifier)->toBe($verifier->name);

    Event::assertDispatched(HappeningCreatedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('overlapping reservations are rejected with the translated public error message', function (): void {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    Happening::create([
        'user_id_01' => User::factory()->create()->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Existing'],
    ]);

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Conflicting'],
    ])->assertStatus(400)
        ->assertJsonPath('message', __('happening.errors.reserved', [
            'resource_type' => (string) $resource->resource_group->term_singular,
            'resource_title' => (string) $resource->title,
        ]));
});

test('verifier required reservations reject missing verifier input', function (): void {
    ['resource' => $resource, 'owner' => $owner] = createPublicHappeningFixture();

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['verifier']);
});

test('owners can update reservations and dispatch update events', function (): void {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Original'],
    ]);

    Event::fake([HappeningUpdatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.update', ['id' => $happening->id]), [
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'label' => ['en' => 'Updated'],
    ])->assertNoContent();

    $happening->refresh();

    expect($happening->start->format('Y-m-d H:i:s'))->toBe('2026-06-10 10:00:00')
        ->and($happening->end->format('Y-m-d H:i:s'))->toBe('2026-06-10 11:00:00')
        ->and($happening->getTranslations('label')['en'])->toBe('Updated');

    Event::assertDispatched(HappeningUpdatedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('designated verifiers can verify reservations and dispatch verification events', function (): void {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Needs verification'],
    ]);

    Event::fake([HappeningVerifiedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($verifier);

    $this->postJson(route('happening.verify', ['id' => $happening->id]), [
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
    ])->assertNoContent();

    $happening->refresh();

    expect($happening->is_verified)->toBeTrue()
        ->and($happening->user_id_02)->toBe($verifier->id)
        ->and($happening->verifier)->toBeNull()
        ->and($happening->verified_at)->not->toBeNull()
        ->and($happening->start->format('Y-m-d H:i:s'))->toBe('2026-06-10 09:30:00')
        ->and($happening->end->format('Y-m-d H:i:s'))->toBe('2026-06-10 10:30:00');

    Event::assertDispatched(HappeningVerifiedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('authorized users can delete reservations and dispatch deletion events', function (): void {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Delete me'],
    ]);

    Event::fake([HappeningDeletedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->deleteJson(route('happening.delete', ['id' => $happening->id]))
        ->assertOk();

    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();

    Event::assertDispatched(HappeningDeletedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('happening creation without event faking exercises the full broadcast and notification chain', function (): void {
    config()->set('broadcasting.default', 'log');
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();
    Sanctum::actingAs($owner);

    // Do NOT use Event::fake() — let the full broadcast/notification chain execute
    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-11 09:00:00',
        'end' => '2026-06-11 10:00:00',
        'verifier' => $verifier->name,
        'label' => null,
    ])->assertSuccessful();
});

// ---------------------------------------------------------------------------
// From PublicHappeningAuthorizationTest
// ---------------------------------------------------------------------------

test('non-owner cannot update another users reservation', function (): void {
    [
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
        'otherUser' => $otherUser,
    ] = buildHappeningAuthFixture();

    $happening = createFixtureHappening($owner, $resource, $verifier);

    Sanctum::actingAs($otherUser);

    $this->postJson(route('happening.update', ['id' => $happening->id]), [
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'label' => ['en' => 'Unauthorized update'],
    ])->assertForbidden();

    expect($happening->fresh()?->getTranslations('label')['en'])->toBe('Fixture');
});

test('non-owner cannot delete another users reservation', function (): void {
    [
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
        'otherUser' => $otherUser,
    ] = buildHappeningAuthFixture();

    $happening = createFixtureHappening($owner, $resource, $verifier);

    Sanctum::actingAs($otherUser);

    $this->deleteJson(route('happening.delete', ['id' => $happening->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('happenings', ['id' => $happening->id]);
});

test('banned user is rejected when creating a reservation', function (): void {
    ['resource' => $resource, 'verifier' => $verifier] = buildHappeningAuthFixture();

    $bannedUser = User::factory()->create(['banned_at' => now()]);

    Sanctum::actingAs($bannedUser);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Banned attempt'],
    ])->assertForbidden();

    $this->assertDatabaseMissing('happenings', ['resource_id' => $resource->id]);
});

test('banned user is rejected when updating a reservation', function (): void {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = buildHappeningAuthFixture();

    $happening = createFixtureHappening($owner, $resource, $verifier);

    $owner->update(['banned_at' => now()]);

    $freshOwner = $owner->fresh();
    if ($freshOwner !== null) {
        Sanctum::actingAs($freshOwner);
    }

    $this->postJson(route('happening.update', ['id' => $happening->id]), [
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'label' => ['en' => 'Banned update'],
    ])->assertForbidden();

    expect($happening->fresh()?->getTranslations('label')['en'])->toBe('Fixture');
});

test('resource group with user group rejects non-members', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Members Only'),
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($userGroup->id);
    // $owner is NOT added to the user group

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Non-member attempt'],
    ])->assertStatus(400);

    $this->assertDatabaseMissing('happenings', ['resource_id' => $resource->id]);
});

test('resource group with user group accepts current members', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Members Only'),
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($userGroup->id);
    $userGroup->users()->attach($owner->id, ['valid_from' => null, 'valid_until' => null]);

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Member booking'],
    ])->assertNoContent();

    $this->assertDatabaseHas('happenings', ['resource_id' => $resource->id, 'user_id_01' => $owner->id]);
});

test('expired user group membership blocks new reservations', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Expired Members'),
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($userGroup->id);
    $userGroup->users()->attach($owner->id, ['valid_from' => null, 'valid_until' => now()->subDay()->toDateString()]);

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Expired attempt'],
    ])->assertStatus(400);

    $this->assertDatabaseMissing('happenings', ['resource_id' => $resource->id]);
});

test('users with no_verifier permission can skip verifier field', function (): void {
    ['institution' => $institution, 'resource' => $resource, 'owner' => $owner] = buildHappeningAuthFixture();

    grantAdminPermission($owner, $institution, 'no_verifier');

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'label' => ['en' => 'No verifier needed'],
    ])->assertNoContent();

    $this->assertDatabaseHas('happenings', [
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'verifier' => null,
    ]);
});

test('weekly happening quota is enforced when limit is reached', function (): void {
    [
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $resourceGroup->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '1']);

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'First booking'],
    ])->assertNoContent();

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Over quota'],
    ])->assertStatus(400);
});

test('users with unlimited_quotas permission can exceed the weekly happening quota', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $resourceGroup->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '1']);
    grantAdminPermission($owner, $institution, 'unlimited_quotas');

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'First booking'],
    ])->assertNoContent();

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Over quota but allowed'],
    ])->assertNoContent();

    $this->assertDatabaseCount('happenings', 2);
});

test('single booking exceeding block hours quota is rejected', function (): void {
    [
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $resourceGroup->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '1']);

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 11:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Two hour booking'],
    ])->assertStatus(400);

    $this->assertDatabaseMissing('happenings', ['resource_id' => $resource->id]);
});

test('weekly hours quota is enforced when accumulated bookings exceed the limit', function (): void {
    [
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $resourceGroup->settings()->where('key', 'quota_weekly_hours')->update(['value' => '1']);

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    // 30-min booking (0.5 h) — under the 1-hour weekly limit
    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 09:30:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'First booking'],
    ])->assertNoContent();

    // 1-hour booking — pushes weekly total to 1.5 h, over the limit
    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Over weekly hours quota'],
    ])->assertStatus(400);
});

test('daily hours quota is enforced when the days bookings exceed the limit', function (): void {
    [
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'verifier' => $verifier,
        'owner' => $owner,
    ] = buildHappeningAuthFixture();

    $resourceGroup->settings()->where('key', 'quota_daily_hours')->update(['value' => '1']);

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    // 30-min booking (0.5 h) — under the 1-hour daily limit
    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 09:30:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'First booking'],
    ])->assertNoContent();

    // 45-min booking — pushes daily total to 1.25 h, over the limit
    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 10:45:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Over daily hours quota'],
    ])->assertStatus(400);
});

test('non-verifier cannot verify another users reservation', function (): void {
    [
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
        'otherUser' => $otherUser,
    ] = buildHappeningAuthFixture();

    $happening = createFixtureHappening($owner, $resource, $verifier);

    Sanctum::actingAs($otherUser);

    $this->postJson(route('happening.verify', ['id' => $happening->id]), [])
        ->assertForbidden();

    expect($happening->fresh()?->is_verified)->toBeFalse();
});

// ---------------------------------------------------------------------------
// 422 on validation failure (JSON POST) — public happening routes
// ---------------------------------------------------------------------------

test('happeningAdd returns 422 when start and end are missing', function (): void {
    // AddHappeningRequest::resource() calls findOrFail() so a valid resource.id is required.
    // With a valid resource but missing start/end, validation fails with 422.
    ['resource' => $resource, 'owner' => $owner] = buildHappeningAuthFixture(['is_verification_required' => false]);

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        // start and end omitted — triggers 422
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['start', 'end']);
});

test('happeningUpdate returns 422 when required fields are missing with valid id', function (): void {
    [
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
    ] = buildHappeningAuthFixture();

    $happening = createFixtureHappening($owner, $resource, $verifier);

    Sanctum::actingAs($owner);

    // Valid happening id but missing start/end fields
    $this->postJson(route('happening.update', ['id' => $happening->id]), [])
        ->assertUnprocessable();
});

test('happeningVerify returns 422 when required fields are missing with valid id', function (): void {
    [
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
    ] = buildHappeningAuthFixture();

    $happening = createFixtureHappening($owner, $resource, $verifier);

    Sanctum::actingAs($verifier);

    // Valid happening id but missing start/end fields
    $this->postJson(route('happening.verify', ['id' => $happening->id]), [])
        ->assertUnprocessable();
});

test('update non-existent happening returns 404', function (): void {
    ['owner' => $owner] = buildHappeningAuthFixture();

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.update', ['id' => (string) Str::uuid()]), [])
        ->assertNotFound();
});

test('delete non-existent happening returns 404', function (): void {
    ['owner' => $owner] = buildHappeningAuthFixture();

    Sanctum::actingAs($owner);

    $this->deleteJson(route('happening.delete', ['id' => (string) Str::uuid()]))
        ->assertNotFound();
});

test('verify non-existent happening returns 404', function (): void {
    ['verifier' => $verifier] = buildHappeningAuthFixture();

    Sanctum::actingAs($verifier);

    $this->postJson(route('happening.verify', ['id' => (string) Str::uuid()]), [])
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// From HappeningStatusViewerTest
// ---------------------------------------------------------------------------

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, verifier: User, bystander: User}
 */
function buildStatusViewerFixture(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => true,
    ]);
    $owner = User::factory()->create(['name' => 'status.owner.user']);
    $verifier = User::factory()->create(['name' => 'status.verifier.user']);
    $bystander = User::factory()->create(['name' => 'status.bystander.user']);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'verifier' => $verifier, 'bystander' => $bystander];
}

/**
 * @return array<int, mixed>
 */
function fetchStatusEntries(mixed $test, Institution $institution, ResourceGroup $resourceGroup): array
{
    $response = $test->getJson(route('happenings.get', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'start' => '2026-06-10 00:00:00',
        'end' => '2026-06-10 23:59:59',
    ]));

    $response->assertOk();

    /** @var array<int, mixed> $data */
    $data = $response->json() ?? [];

    return collect($data)->where('status', '!==', null)->values()->all();
}

test('calendar entries show user-booking type for the booking owner of a verified happening', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
    ] = buildStatusViewerFixture();

    Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => $verifier->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'My booking'],
    ]);

    $this->actingAs($owner);
    $entries = fetchStatusEntries($this, $institution, $resourceGroup);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['status']['type'])->toBe('user-booking')
        ->and($entries[0]['status']['user']['reservation'])->toBe($owner->name)
        ->and($entries[0]['status']['user']['verification'])->toBe($verifier->name);
});

test('calendar entries show booking type for a verified happening viewed by a bystander', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
        'bystander' => $bystander,
    ] = buildStatusViewerFixture();

    Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => $verifier->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Their booking'],
    ]);

    $this->actingAs($bystander);
    $entries = fetchStatusEntries($this, $institution, $resourceGroup);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['status']['type'])->toBe('booking');
});

test('calendar entries show user-reservation type for the owner of an unverified happening', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
    ] = buildStatusViewerFixture();

    Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Pending'],
    ]);

    $this->actingAs($owner);
    $entries = fetchStatusEntries($this, $institution, $resourceGroup);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['status']['type'])->toBe('user-reservation')
        ->and($entries[0]['status']['user']['reservation'])->toBe($owner->name)
        ->and($entries[0]['status']['user']['verification'])->toBe($verifier->name);
});

test('calendar entries show user-to-verify type for the named verifier of an unverified happening', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
    ] = buildStatusViewerFixture();

    Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Awaiting verification'],
    ]);

    $this->actingAs($verifier);
    $entries = fetchStatusEntries($this, $institution, $resourceGroup);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['status']['type'])->toBe('user-to-verify')
        ->and($entries[0]['status']['user']['reservation'])->toBe($owner->name);
});

test('calendar entries show reservation type for an unverified happening viewed by a bystander', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
        'bystander' => $bystander,
    ] = buildStatusViewerFixture();

    Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Unrelated reservation'],
    ]);

    $this->actingAs($bystander);
    $entries = fetchStatusEntries($this, $institution, $resourceGroup);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['status']['type'])->toBe('reservation');
});
