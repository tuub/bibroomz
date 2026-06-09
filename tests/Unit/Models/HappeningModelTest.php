<?php

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningsChangedEvent;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningStatusCalculator;
use App\Services\Resources\ResourceAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPermissions;

covers(
    Happening::class,
    HappeningStatusCalculator::class
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seedPermissions();
    config()->set('roomz.app.timezone', 'UTC');
    config()->set('roomz.happenings.cleanup_days', 5);
    Carbon::setTestNow(Carbon::parse('2026-06-03 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 10:00:00', 'UTC'));
});

afterEach(function (): void {
    Auth::logout();
    app()->forgetInstance(ResourceAvailabilityService::class);
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

/**
 * @param  array<string, mixed>  $attributes
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: App\Models\Resource, owner: User, second: User, verifier: User, happening: Happening}
 */
function createHappeningFixture(array $attributes = []): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create([
        'resource_group_id' => $resourceGroup->id,
        'is_active' => true,
    ]);

    $owner = User::factory()->create(['name' => 'owner-'.uniqid()]);
    $second = User::factory()->create(['name' => 'second-'.uniqid()]);
    $verifier = User::factory()->create(['name' => 'verifier-'.uniqid()]);

    /** @var Happening $happening */
    $happening = Happening::create(array_merge([
        'user_id_01' => $owner->id,
        'user_id_02' => $second->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => Utility::normalizeLoginName($verifier->name),
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Study'),
    ], $attributes));

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'second' => $second, 'verifier' => $verifier, 'happening' => $happening];
}

test('happening scopes and state helpers work on persisted data', function (): void {
    $fixture = createHappeningFixture();
    $future = $fixture['happening'];

    $otherFixture = createHappeningFixture([
        'resource_id' => $fixture['resource']->id,
        'user_id_01' => $fixture['owner']->id,
        'start' => CarbonImmutable::now()->subDays(10),
        'end' => CarbonImmutable::now()->subDays(10)->addHour(),
        'reserved_at' => CarbonImmutable::now()->subDays(10),
    ]);
    $otherFixture['resource']->update(['is_active' => false]);

    expect(Happening::weekly()->pluck('id')->all())->toContain($future->id)
        ->and(Happening::user($fixture['owner'])->pluck('id')->all())
        ->toContain($future->id, $otherFixture['happening']->id)
        ->and(Happening::resourceGroup($fixture['resourceGroup'])->pluck('id')->all())->toContain($future->id)
        ->and(Happening::active()->pluck('id')->all())->toContain($future->id)
        ->and($future->isVerified())->toBeFalse()
        ->and($future->isBelongingTo($fixture['owner']))->toBeTrue()
        ->and($future->isBelongingTo($fixture['verifier']))->toBeTrue()
        ->and($future->isPast())->toBeFalse()
        ->and($future->isPresent())->toBeFalse()
        ->and($otherFixture['happening']->isPast())->toBeTrue();
});

test('happening permissions users and status reflect the current viewer', function (): void {
    $fixture = createHappeningFixture();
    /** @var Happening $happening */
    $happening = $fixture['happening']->fresh(['user1', 'user2', 'resource.resource_group.institution']);

    $admin = User::factory()->create(['name' => 'admin-'.uniqid()]);
    $this->grantPermission($admin, $fixture['institution'], 'edit_happenings');
    $this->grantPermission($admin, $fixture['institution'], 'delete_happenings');
    $this->grantPermission($admin, $fixture['institution'], 'view_happenings');

    expect($happening->getPermissions(null))->toBe([
        'verify' => false,
        'edit' => false,
        'delete' => false,
    ]);

    Auth::login($fixture['owner']);
    expect($happening->getStatus()['type'])->toBe('user-reservation');

    $verified = $happening->replicate()->fill([
        'is_verified' => true,
        'verified_at' => CarbonImmutable::now(),
        'start' => CarbonImmutable::now()->subMinutes(30),
        'end' => CarbonImmutable::now()->addMinutes(30),
    ]);
    $verified->id = (string) Str::uuid();
    $verified->save();
    /** @var Happening $verified */
    $verified = $verified->fresh(['user1', 'user2']);

    expect($verified->isPresent())->toBeTrue()
        ->and($verified->getStatus()['type'])->toBe('user-booking');

    Auth::logout();
    expect($verified->getStatus()['type'])->toBe('booking')
        ->and($happening->getStatus()['type'])->toBe('reservation');

    Auth::login($fixture['verifier']);
    expect($happening->getStatus()['type'])->toBe('user-to-verify');

    $permissions = $happening->getPermissions($admin);
    expect($permissions)->toBe([
        'verify' => false,
        'edit' => false,
        'delete' => false,
    ]);

    expect($happening->users()->pluck('name')->all())->toContain(
        $fixture['owner']->name,
        $fixture['second']->name,
        $fixture['verifier']->name,
    );
});

test('happening broadcast concurrency and resource status helpers work', function (): void {
    Event::fake([
        HappeningCreatedEvent::class,
        HappeningsChangedEvent::class,
    ]);

    $fixture = createHappeningFixture();
    /** @var Happening $happening */
    $happening = $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']);

    expect($happening->isConcurrent(
        CarbonImmutable::parse('2026-06-03 10:30:00'),
        CarbonImmutable::parse('2026-06-03 11:30:00'),
    ))->toBeTrue()
        ->and($happening->isConcurrent(
            CarbonImmutable::parse('2026-06-03 13:30:00'),
            CarbonImmutable::parse('2026-06-03 14:30:00'),
        ))->toBeFalse();

    $availabilityService = Mockery::mock(ResourceAvailabilityService::class);
    $adjustedResource = Mockery::mock(Resource::class);
    $availabilityService->shouldReceive('findOpen')
        ->once()
        ->with($adjustedResource, Mockery::type(CarbonImmutable::class), Mockery::type(CarbonImmutable::class))
        ->andReturn([
            true,
            CarbonImmutable::parse('2026-06-03 11:00:00'),
            CarbonImmutable::parse('2026-06-03 12:00:00'),
        ]);
    $availabilityService->shouldReceive('findClosed')
        ->once()
        ->with($adjustedResource, Mockery::type(CarbonImmutable::class), Mockery::type(CarbonImmutable::class))
        ->andReturn([
            false,
            CarbonImmutable::parse('2026-06-03 11:30:00'),
            CarbonImmutable::parse('2026-06-03 12:30:00'),
        ]);
    app()->instance(ResourceAvailabilityService::class, $availabilityService);

    $happening->setRelation('resource', $adjustedResource);
    $happening->withAdjustedStartEndTimes();

    expect(CarbonImmutable::parse($happening->start)->format('H:i'))->toBe('11:30')
        ->and(CarbonImmutable::parse($happening->end)->format('H:i'))->toBe('12:30');

    $resourceStatusService = Mockery::mock(ResourceAvailabilityService::class);
    $resourceStatus = Mockery::mock(Resource::class);
    $resourceStatusService->shouldReceive('findOpen')
        ->once()
        ->with($resourceStatus, Mockery::type(CarbonImmutable::class), Mockery::type(CarbonImmutable::class))
        ->andReturn([true]);
    $resourceStatusService->shouldReceive('findClosed')
        ->once()
        ->with($resourceStatus, Mockery::type(CarbonImmutable::class), Mockery::type(CarbonImmutable::class))
        ->andReturn([false]);
    app()->instance(ResourceAvailabilityService::class, $resourceStatusService);
    $happening->setRelation('resource', $resourceStatus);
    expect($happening->isResourceOpen())->toBeTrue();

    $resourceStatusService = Mockery::mock(ResourceAvailabilityService::class);
    $resourceStatus = Mockery::mock(Resource::class);
    $resourceStatusService->shouldReceive('findOpen')
        ->once()
        ->with($resourceStatus, Mockery::type(CarbonImmutable::class), Mockery::type(CarbonImmutable::class))
        ->andReturn([true]);
    $resourceStatusService->shouldReceive('findClosed')
        ->once()
        ->with($resourceStatus, Mockery::type(CarbonImmutable::class), Mockery::type(CarbonImmutable::class))
        ->andReturn([true]);
    app()->instance(ResourceAvailabilityService::class, $resourceStatusService);
    $happening->setRelation('resource', $resourceStatus);
    expect($happening->isResourceOpen())->toBeFalse();

    $happening->setRelation('resource', $fixture['resource']);
    $happening->broadcast(HappeningCreatedEvent::class);

    Event::assertDispatched(HappeningCreatedEvent::class, 3);
    Event::assertDispatched(HappeningsChangedEvent::class);
    expect($happening->prunable()->toSql())->toContain('"end" <=')
        ->and($happening->isEditableByUser($fixture['owner']))->toBeFalse()
        ->and($happening->isViewableByUser($fixture['owner']))->toBeFalse();
});

test('isBelongingTo is true for user_id_02 even when not user_id_01 or named verifier', function (): void {
    $owner = User::factory()->create(['name' => 'belong.owner.'.uniqid()]);
    $second = User::factory()->create(['name' => 'belong.second.'.uniqid()]);
    $thirdParty = User::factory()->create(['name' => 'belong.third.'.uniqid()]);
    $namedVerifier = User::factory()->create(['name' => 'belong.verifier.'.uniqid()]);

    $fixture = createHappeningFixture();
    $institution = $fixture['institution'];
    $resource = $fixture['resource'];

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => $second->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $namedVerifier->name,
        'start' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 11:00:00'),
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Test'],
    ]);

    expect($happening->isBelongingTo($owner))->toBeTrue()       // user_id_01 path
        ->and($happening->isBelongingTo($second))->toBeTrue()   // user_id_02 path
        ->and($happening->isBelongingTo($namedVerifier))->toBeTrue() // verifier name path
        ->and($happening->isBelongingTo($thirdParty))->toBeFalse();  // none
});

test('isConcurrent boundary: existing starts at exactly new start, or ends at exactly new start', function (): void {
    $fixture = createHappeningFixture();
    $resource = $fixture['resource'];

    $existing = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 11:00:00'),
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Existing'],
    ]);
    /** @var Happening $existing */
    $existing = $existing->fresh();

    // Existing starts exactly at new start → concurrent
    expect($existing->isConcurrent(
        CarbonImmutable::parse('2026-06-10 10:00:00'),
        CarbonImmutable::parse('2026-06-10 11:00:00'),
    ))->toBeTrue();

    // Existing starts exactly at new end → NOT concurrent ($this->start < $end is strict)
    expect($existing->isConcurrent(
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 10:00:00'),
    ))->toBeFalse();

    // Existing ends exactly at new start → NOT concurrent ($this->end > $start is strict)
    expect($existing->isConcurrent(
        CarbonImmutable::parse('2026-06-10 11:00:00'),
        CarbonImmutable::parse('2026-06-10 12:00:00'),
    ))->toBeFalse();

    // Existing start < new start but existing end > new start → concurrent
    expect($existing->isConcurrent(
        CarbonImmutable::parse('2026-06-10 10:30:00'),
        CarbonImmutable::parse('2026-06-10 11:30:00'),
    ))->toBeTrue();
});

test('isPresent is false for past happenings and happenings not yet started', function (): void {
    $fixture = createHappeningFixture();
    $resource = $fixture['resource'];

    $future = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => CarbonImmutable::parse('2026-06-10 15:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 16:00:00'),
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Future'],
    ]);

    $past = Happening::create([
        'user_id_01' => $fixture['owner']->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => CarbonImmutable::parse('2026-06-03 07:00:00'),
        'end' => CarbonImmutable::parse('2026-06-03 08:00:00'),
        'reserved_at' => now()->subDay(),
        'verified_at' => now()->subDay(),
        'label' => ['en' => 'Past'],
    ]);

    expect($future->isPresent())->toBeFalse() // hasn't started
        ->and($past->isPresent())->toBeFalse(); // already ended
});
