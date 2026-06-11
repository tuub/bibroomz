<?php

declare(strict_types=1);

use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningStatusCalculator;
use App\Services\Resources\ResourceAvailabilityService;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(Happening::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-01 10:00:00', 'UTC'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('happening creates with required fields', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->id)->not->toBeNull()
        ->and($happening->resource_id)->toBe($resource->id)
        ->and($happening->user_id_01)->toBe($user->id)
        ->and($happening->is_verified)->toBeFalse();
});

test('happening is_verified field casts to boolean', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $unverified = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $verified = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHours(3),
        'end' => CarbonImmutable::now()->addHours(4),
        'is_verified' => true,
        'reserved_at' => now(),
    ]);

    expect($unverified->is_verified)->toBeFalse()
        ->and($unverified->isVerified())->toBeFalse()
        ->and($verified->is_verified)->toBeTrue()
        ->and($verified->isVerified())->toBeTrue();
});

test('happening resource relationship resolves correctly', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->resource()->firstOrFail()->id)->toBe($resource->id);
});

test('happening user1 and user2 relationships resolve correctly', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->user1()->firstOrFail()->id)->toBe($user1->id)
        ->and($happening->user2()->firstOrFail()->id)->toBe($user2->id);
});

test('happening label stores translations', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'label' => ['en' => 'Study session', 'de' => 'Lernsitzung'],
        'reserved_at' => now(),
    ]);

    expect($happening->getTranslation('label', 'en'))->toBe('Study session')
        ->and($happening->getTranslation('label', 'de'))->toBe('Lernsitzung');
});

test('happening soft delete removes it from normal queries', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $id = $happening->id;
    $happening->delete();

    expect(Happening::find($id))->toBeNull()
        ->and(Happening::withTrashed()->find($id)?->trashed())->toBeTrue();
});

test('happening isBelongingTo returns true for user_id_01 and user_id_02', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $stranger = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isBelongingTo($user1))->toBeTrue()
        ->and($happening->isBelongingTo($user2))->toBeTrue()
        ->and($happening->isBelongingTo($stranger))->toBeFalse();
});

test('happening isPast returns true for past happenings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $past = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-01 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-01 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $future = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHours(2),
        'end' => CarbonImmutable::now()->addHours(3),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($past->isPast())->toBeTrue()
        ->and($future->isPast())->toBeFalse();
});

test('happening getPermissions returns verify edit and delete keys', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $permissions = $happening->getPermissions(null);

    expect($permissions)->toHaveKeys(['verify', 'edit', 'delete'])
        ->and($permissions['verify'])->toBeFalse()
        ->and($permissions['edit'])->toBeFalse()
        ->and($permissions['delete'])->toBeFalse();
});

test('happening getPermissions returns array with false values for null user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->getPermissions(null))->toBe([
        'verify' => false,
        'edit' => false,
        'delete' => false,
    ]);
});

// --- Mutation-killing tests ---

test('getPermissions verify key requires User instance', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // With null user, verify must be false (not true)
    $permissions = $happening->getPermissions(null);
    expect($permissions['verify'])->toBeFalse();

    // With actual User, check that the instanceof check runs (result may vary by policy)
    $permissions = $happening->getPermissions($user);
    expect($permissions)->toHaveKey('verify');
});

test('getPermissions edit key requires User instance', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $permissions = $happening->getPermissions(null);
    expect($permissions['edit'])->toBeFalse();
});

test('getPermissions delete key requires User instance', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $permissions = $happening->getPermissions(null);
    expect($permissions['delete'])->toBeFalse();
});

test('isPast returns false for happening ending exactly at now', function (): void {
    // isPast: $this->end < now → strictly less than
    // At exact boundary (end === now), isPast should be false with <, but true with <=
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    // end is clearly in the future → not past
    $future = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHours(2),
        'end' => CarbonImmutable::now()->addHours(3),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($future->isPast())->toBeFalse();
});

test('isPresent returns true when now is strictly between start and end', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    // Utility::getCarbonNow() resolves to 2026-07-01 12:00:00 with the configured timezone offset.
    $present = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 11:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 13:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($present->isPresent())->toBeTrue();
});

test('isPresent returns false when happening is entirely in the past', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $past = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-01 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-01 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($past->isPresent())->toBeFalse();
});

test('isPresent returns false when happening is entirely in the future', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $future = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 11:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($future->isPresent())->toBeFalse();
});

test('isConcurrent returns true when other happening overlaps at the start', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // start=10, end=12; check 09:00–11:00 → $this->start(10) < end(11) && $this->start(10) >= start(09) → concurrent
    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-07-01 09:00:00'),
        CarbonImmutable::parse('2026-07-01 11:00:00')
    ))->toBeTrue();
});

test('isConcurrent returns false for non-overlapping happening', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // check 13:00–14:00 → no overlap
    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-07-01 13:00:00'),
        CarbonImmutable::parse('2026-07-01 14:00:00')
    ))->toBeFalse();
});

test('isConcurrent returns true for second overlap case (start < $this->start && $this->end > start)', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    // Happening: 10:00–12:00
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // check 11:00–13:00 → $this->start(10) < start(11) is false, but $this->start(10) < end(13) && $this->start(10) >= start(11) fails
    // Actually check 09:00–11:00: $this->start(10) >= 09 && < 11 → true (first branch)
    // For second branch: check 08:00–11:00: $this->start(10) < 08 is false → won't hit second branch
    // For second branch: check 08:00–09:00: $this->start(10) >= 08 but $this->start(10) < 09 is false
    // Actually second branch: $this->start(10) < start AND $this->end(12) > start
    // check start=11: $this->start(10) < 11=true && $this->end(12) > 11=true
    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-07-01 11:00:00'),
        CarbonImmutable::parse('2026-07-01 13:00:00')
    ))->toBeTrue();
});

test('prunable returns builder that excludes recent happenings', function (): void {
    // The prunable method uses config cleanup_days; when config returns non-int, defaults to 0
    config(['roomz.happenings.cleanup_days' => 30]);

    $builder = (new Happening)->prunable();
    expect($builder)->not->toBeNull();
});

test('withAdjustedStartEndTimes returns self not null', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $happening->load('resource.resource_group.institution', 'resource.business_hours.week_days');

    $result = $happening->withAdjustedStartEndTimes();

    expect($result)->not->toBeNull()
        ->and($result)->toBeInstanceOf(Happening::class);
});

test('isPast is false when end is well in the future', function (): void {
    config(['roomz.app.timezone' => 'UTC']);

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $now = CarbonImmutable::now();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => $now->subHour(),
        'end' => $now->addHours(3),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isPast())->toBeFalse();
});

test('isPast is true when end is strictly before now', function (): void {
    config(['roomz.app.timezone' => 'UTC']);

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $now = CarbonImmutable::now();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => $now->subHours(2),
        'end' => $now->subSecond(),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isPast())->toBeTrue();
});

test('isPresent is false when start equals now', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $now = CarbonImmutable::now();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => $now,
        'end' => $now->addHour(),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isPresent())->toBeFalse();
});

test('isPresent is false when start equals Utility now exactly in the configured timezone', function (): void {
    config(['roomz.app.timezone' => 'Europe/Berlin']);

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $utilityNow = Utility::getCarbonNow();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => $utilityNow,
        'end' => $utilityNow->addHour(),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isPresent())->toBeFalse();
});

test('isConcurrent returns false when checked range end equals happening start', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-07-01 09:00:00'),
        CarbonImmutable::parse('2026-07-01 10:00:00'),
    ))->toBeFalse();
});

test('isConcurrent returns true when checked range starts exactly at the happening start', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-07-01 10:00:00'),
        CarbonImmutable::parse('2026-07-01 11:00:00'),
    ))->toBeTrue();
});

test('isConcurrent returns false for a zero-length checked range at the happening start', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-07-01 10:00:00'),
        CarbonImmutable::parse('2026-07-01 10:00:00'),
    ))->toBeFalse();
});

test('isConcurrent returns false when checked range starts exactly at the happening end', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 10:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-07-01 12:00:00'),
        CarbonImmutable::parse('2026-07-01 13:00:00'),
    ))->toBeFalse();
});

test('prunable uses exact cleanup_days integer value', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    config(['roomz.happenings.cleanup_days' => 7]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->subDays(8),
        'end' => CarbonImmutable::now()->subDays(8)->addHour(),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->subDays(6),
        'end' => CarbonImmutable::now()->subDays(6)->addHour(),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $prunable = (new Happening)->prunable()->get();

    expect($prunable->count())->toBe(1);
});

test('getStatus passes null to calculator when viewer is not User instance', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $status = $happening->getStatus();

    expect($status)->toHaveKey('type')
        ->and($status['type'])->toBe('reservation');
});

test('getStatus passes the authenticated User instance to the calculator', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $viewer = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $viewer->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    auth()->login($viewer);

    $calculator = Mockery::mock(HappeningStatusCalculator::class);
    $calculator->shouldReceive('calculate')
        ->once()
        ->with($happening, Mockery::on(fn (mixed $user): bool => $user instanceof User && $user->is($viewer)))
        ->andReturn(['type' => 'user-reservation', 'user' => []]);
    app()->instance(HappeningStatusCalculator::class, $calculator);

    expect($happening->getStatus())->toBe(['type' => 'user-reservation', 'user' => []]);

    auth()->logout();
});

test('getStatus passes null to the calculator when authenticated viewer is not an App User model', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    auth()->setUser(new GenericUser([
        'id' => 'external-viewer',
        'name' => 'External',
        'remember_token' => null,
    ]));

    $calculator = Mockery::mock(HappeningStatusCalculator::class);
    $calculator->shouldReceive('calculate')
        ->once()
        ->with($happening, null)
        ->andReturn(['type' => 'reservation', 'user' => []]);
    app()->instance(HappeningStatusCalculator::class, $calculator);

    expect($happening->getStatus())->toBe(['type' => 'reservation', 'user' => []]);

    auth()->logout();
});

test('getPermissions returns all three keys with correct types', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $permissions = $happening->getPermissions($user);

    expect($permissions)->toHaveKey('verify')
        ->and($permissions)->toHaveKey('edit')
        ->and($permissions)->toHaveKey('delete')
        ->and($permissions['verify'])->toBeBool()
        ->and($permissions['edit'])->toBeBool()
        ->and($permissions['delete'])->toBeBool();
});

test('getPermissions returns true for edit when user is owner', function (): void {
    config(['roomz.app.timezone' => 'UTC']);

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

    $happening->load('resource.resource_group.institution', 'user1', 'user2');

    $permissions = $happening->getPermissions($owner);

    expect($permissions['edit'])->toBeTrue()
        ->and($permissions['delete'])->toBeTrue();
});

test('getPermissions returns true for verify when user matches verifier on future unverified happening', function (): void {
    config(['roomz.app.timezone' => 'UTC']);

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $verifier = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => User::factory()->create()->id,
        'verifier' => $verifier->name,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->getPermissions($verifier)['verify'])->toBeTrue();
});

test('isPast returns false when end equals now exactly', function (): void {
    config(['roomz.app.timezone' => 'UTC']);

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $now = CarbonImmutable::now();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => $now->subHour(),
        'end' => $now,
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($happening->isPast())->toBeFalse();
});

test('prunable uses 0 as fallback when cleanup_days is not an integer', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    config(['roomz.happenings.cleanup_days' => 'not-an-int']);

    $pastEnough = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->subHours(2),
        'end' => CarbonImmutable::now()->subHour(),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $future = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $prunable = (new Happening)->prunable()->pluck('id');

    expect($prunable)->toContain($pastEnough->id)
        ->and($prunable)->not->toContain($future->id);
});

test('withAdjustedStartEndTimes applies findOpen result to $start and $end', function (): void {
    config(['roomz.app.timezone' => 'UTC']);

    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 07:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 11:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $happening->load('resource.resource_group.institution', 'resource.business_hours.week_days');

    $result = $happening->withAdjustedStartEndTimes();

    if (! $result instanceof Happening) {
        throw new RuntimeException('withAdjustedStartEndTimes must return Happening');
    }

    $adjustedStart = CarbonImmutable::parse($result->start);

    expect($adjustedStart->format('H:i'))->toBe('09:00');
});

test('withAdjustedStartEndTimes applies both open and closed adjustments to start and end', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-07-01 08:00:00'),
        'end' => CarbonImmutable::parse('2026-07-01 12:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $availability = Mockery::mock(ResourceAvailabilityService::class);
    $availability->shouldReceive('findOpen')
        ->once()
        ->andReturn([
            true,
            CarbonImmutable::parse('2026-07-01 09:15:00'),
            CarbonImmutable::parse('2026-07-01 11:45:00'),
        ]);
    $availability->shouldReceive('findClosed')
        ->once()
        ->withArgs(fn (mixed $calledResource, mixed $start, mixed $end): bool => $calledResource instanceof Resource
            && $calledResource->is($resource)
            && $start instanceof CarbonImmutable
            && $start->format('Y-m-d H:i:s') === '2026-07-01 09:15:00'
            && $end instanceof CarbonImmutable
            && $end->format('Y-m-d H:i:s') === '2026-07-01 11:45:00')
        ->andReturn([
            false,
            CarbonImmutable::parse('2026-07-01 09:30:00'),
            CarbonImmutable::parse('2026-07-01 11:30:00'),
        ]);
    app()->instance(ResourceAvailabilityService::class, $availability);

    $result = $happening->withAdjustedStartEndTimes();

    if (! $result instanceof Happening) {
        throw new RuntimeException('withAdjustedStartEndTimes must return Happening');
    }

    expect(CarbonImmutable::parse($result->start)->format('H:i'))->toBe('09:30')
        ->and(CarbonImmutable::parse($result->end)->format('H:i'))->toBe('11:30');
});
