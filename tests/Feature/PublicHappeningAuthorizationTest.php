<?php

covers(
    App\Http\Controllers\HappeningController::class,
    App\Services\Happenings\ValidateHappeningReservation::class,
    App\Policies\HappeningPolicy::class
);

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningsChangedEvent;
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
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    config()->set('roomz.app.timezone', 'UTC');
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function buildHappeningAuthFixture(array $resourceOverrides = []): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(array_merge([
        'is_active' => true,
        'is_verification_required' => true,
    ], $resourceOverrides));
    $owner = User::factory()->create(['name' => 'owner.user']);
    $verifier = User::factory()->create(['name' => 'verifier.user']);
    $otherUser = User::factory()->create(['name' => 'other.user']);

    return compact('institution', 'resourceGroup', 'resource', 'owner', 'verifier', 'otherUser');
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

test('non-owner cannot update another users reservation', function () {
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

    expect($happening->fresh()->getTranslations('label')['en'])->toBe('Fixture');
});

test('non-owner cannot delete another users reservation', function () {
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

test('banned user is rejected when creating a reservation', function () {
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

test('banned user is rejected when updating a reservation', function () {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = buildHappeningAuthFixture();

    $happening = createFixtureHappening($owner, $resource, $verifier);

    $owner->update(['banned_at' => now()]);

    Sanctum::actingAs($owner->fresh());

    $this->postJson(route('happening.update', ['id' => $happening->id]), [
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'label' => ['en' => 'Banned update'],
    ])->assertForbidden();

    expect($happening->fresh()->getTranslations('label')['en'])->toBe('Fixture');
});

test('resource group with user group rejects non-members', function () {
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

test('resource group with user group accepts current members', function () {
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

test('expired user group membership blocks new reservations', function () {
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

test('users with no_verifier permission can skip verifier field', function () {
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

test('weekly happening quota is enforced when limit is reached', function () {
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

test('users with unlimited_quotas permission can exceed the weekly happening quota', function () {
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

test('single booking exceeding block hours quota is rejected', function () {
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

test('weekly hours quota is enforced when accumulated bookings exceed the limit', function () {
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

test('daily hours quota is enforced when the days bookings exceed the limit', function () {
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
