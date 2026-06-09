<?php

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\CalendarEntryPresenter;
use App\Services\Happenings\HappeningStatusCalculator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(
    HappeningStatusCalculator::class,
    CalendarEntryPresenter::class
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    config()->set('roomz.app.timezone', 'UTC');
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00', 'UTC'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

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
